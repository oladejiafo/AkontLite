<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\CompanyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    // GET /api/company
    public function show(Request $request)
    {
        $company = $request->user()->activeCompany();

        if (!$company) {
            return response()->json(['message' => 'No company found'], 404);
        }

        return response()->json(['data' => $company]);
    }

    // POST /api/company
    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'nullable|email',
            'currency'         => 'required|string|size:3',
            'country_standard' => 'required|in:UAE,Nigeria,Other',
            'vat_number'       => 'nullable|string',
            'tax_number'       => 'nullable|string',
        ]);

        $company = Company::create($request->all());

        // attach user as owner
        $company->companyUsers()->create([
            'user_id'     => $request->user()->id,
            'role'        => 'owner',
            'accepted_at' => now(),
        ]);

        return response()->json(['data' => $company], 201);
    }

    // PUT /api/company
    public function update(Request $request)
    {
        $company = $request->user()->activeCompany();

        if (!$company) {
            // create company if none exists
            return $this->store($request);
        }

        $company->update($request->only([
            'name', 'email', 'phone', 'address',
            'city', 'country', 'vat_number', 'tax_number',
            'registration_number', 'currency', 'timezone',
            'country_standard', 'settings',
        ]));

        return response()->json(['data' => $company]);
    }

    // GET /api/company/members
    public function members(Request $request)
    {
        $company = $request->user()->activeCompany();

        if (!$company) {
            return response()->json(['message' => 'No company found'], 404);
        }

        $members = $company->companyUsers()
                           ->with('user:id,name,email')
                           ->get();

        return response()->json(['data' => $members]);
    }

    // POST /api/company/invite
    public function invite(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'role'  => 'required|in:accountant,staff,viewer',
        ]);

        $company = $request->user()->activeCompany();
        $this->authorizeRole($request, $company, ['owner', 'accountant']);

        // check if already a member
        $exists = $company->users()
                          ->where('email', $request->email)
                          ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'User is already a member of this company',
            ], 422);
        }

        $invitation = Invitation::create([
            'company_id'  => $company->id,
            'invited_by'  => $request->user()->id,
            'email'       => $request->email,
            'role'        => $request->role,
            'token'       => Str::random(64),
            'expires_at'  => now()->addDays(7),
        ]);

        // TODO: send invitation email (queue this)

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent to ' . $request->email,
        ]);
    }

    // POST /api/company/invitations/accept
    public function acceptInvitation(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $invitation = Invitation::where('token', $request->token)
                                ->whereNull('accepted_at')
                                ->first();

        if (!$invitation || $invitation->isExpired()) {
            return response()->json([
                'message' => 'Invalid or expired invitation',
            ], 422);
        }

        $user = $request->user();

        if ($user->email !== $invitation->email) {
            return response()->json([
                'message' => 'This invitation is for a different email address',
            ], 403);
        }

        CompanyUser::create([
            'company_id'  => $invitation->company_id,
            'user_id'     => $user->id,
            'role'        => $invitation->role,
            'invited_at'  => $invitation->created_at,
            'accepted_at' => now(),
        ]);

        $invitation->update(['accepted_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'You have joined the company',
        ]);
    }

    // PUT /api/company/members/{id}
    public function updateMember(Request $request, int $id)
    {
        $request->validate([
            'role' => 'required|in:accountant,staff,viewer',
        ]);

        $company = $request->user()->activeCompany();
        $this->authorizeRole($request, $company, ['owner']);

        $member = CompanyUser::where('company_id', $company->id)
                             ->where('user_id', $id)
                             ->firstOrFail();

        if ($member->role === 'owner') {
            return response()->json([
                'message' => 'Cannot change the owner role',
            ], 403);
        }

        $member->update(['role' => $request->role]);

        return response()->json(['success' => true]);
    }

    // DELETE /api/company/members/{id}
    public function removeMember(Request $request, int $id)
    {
        $company = $request->user()->activeCompany();
        $this->authorizeRole($request, $company, ['owner']);

        $member = CompanyUser::where('company_id', $company->id)
                             ->where('user_id', $id)
                             ->firstOrFail();

        if ($member->role === 'owner') {
            return response()->json([
                'message' => 'Cannot remove the company owner',
            ], 403);
        }

        $member->delete();

        return response()->json(['success' => true]);
    }

    // POST /api/company/logo
    public function uploadLogox(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $company = $request->user()->activeCompany();

        if (!$company) {
            return response()->json(['message' => 'No company found'], 404);
        }

        $this->authorizeRole($request, $company, ['owner']);

        // delete old logo
        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');

        $company->update(['logo_path' => $path]);

        return response()->json([
            'success'   => true,
            'logo_path' => $path,
            'logo_url'  => Storage::url($path),
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $company = $request->user()->activeCompany();

        if (!$company) {
            return response()->json(['message' => 'No company found'], 404);
        }

        if (!app(\App\Services\PlanGateService::class)->canUploadLogo($request->user())) {
            return response()->json([
                'message' => 'Logo upload is a paid feature. Upgrade to add your branding.',
            ], 403);
        }

        $this->authorizeRole($request, $company, ['owner']);

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');

        $company->update(['logo_path' => $path]);

        return response()->json([
            'success'   => true,
            'logo_path' => $path,
            'logo_url'  => Storage::url($path),
        ]);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function authorizeRole(
        Request $request,
        ?Company $company,
        array $allowedRoles
    ): void {
        if (!$company) abort(404, 'No company found');

        $role = $request->user()->roleInCompany($company->id);

        if (!in_array($role, $allowedRoles)) {
            abort(403, 'Insufficient permissions');
        }
    }
}