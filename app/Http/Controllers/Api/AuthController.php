<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // POST /api/login
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // revoke old tokens to keep it clean
        $user->tokens()->where('name', 'mobile')->delete();

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'company_id' => $user->activeCompany()?->id,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    // POST /api/register
    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
            'company_name'          => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // auto-create a company for the user
        $companyName = $request->company_name
            ?? $request->name . "'s Business";

        $company = Company::create([
            'name'             => $companyName,
            'email'            => $request->email,
            'currency'         => 'USD',
            'country_standard' => 'Other',
        ]);

        $freePlan = \App\Models\Plan::where('slug', 'free')->first();

        if ($freePlan) {
            $company->subscription()->create([
                'plan_id' => $freePlan->id,
                'status' => 'active',
            ]);
        }

        $company->companyUsers()->create([
            'user_id'     => $user->id,
            'role'        => 'owner',
            'accepted_at' => now(),
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'company_id' => $company->id,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    // POST /api/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['success' => true]);
    }

    // GET /api/user
    public function me(Request $request)
    {
        $user = $request->user();

        try {
            $company = $user->activeCompany();
        } catch (\Exception $e) {
            $company = null;
        }

        return response()->json([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'company_id' => $company?->id,
            'created_at' => $user->created_at,
            'company'    => $company ? [
                'id'               => $company->id,
                'name'             => $company->name,
                'currency'         => $company->currency,
                'country_standard' => $company->country_standard,
                'vat_number'       => $company->vat_number,
                'tax_number'       => $company->tax_number,
                'logo_path'        => $company->logo_path,
            ] : null,
        ]);
    }

    // PUT /api/profile
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'email']));

        return response()->json([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'created_at' => $user->created_at,
        ]);
    }

    // PUT /api/profile/password  
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['success' => true]);
    }

    // DELETE /api/profile
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();
        return response()->json(['success' => true]);
    }

}