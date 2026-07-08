<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class GoogleAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        $googleResponse = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->id_token,
        ]);

        if (!$googleResponse->successful()) {
            return response()->json(['message' => 'Invalid Google token'], 401);
        }

        $googleData = $googleResponse->json();

        if (empty($googleData['email']) || empty($googleData['email_verified']) || $googleData['email_verified'] !== 'true') {
            return response()->json(['message' => 'Google account not verified'], 401);
        }

        $user = User::where('email', $googleData['email'])->first();
        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $user = User::create([
                'name' => $googleData['name'] ?? $googleData['email'],
                'email' => $googleData['email'],
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
            ]);
        }

        if ($isNewUser) {
            $company = Company::create([
                'name' => $user->name . "'s Business",
                'email' => $user->email,
                'currency' => 'USD',
                'country_standard' => 'Other',
            ]);

            $company->companyUsers()->create([
                'user_id' => $user->id,
                'role' => 'owner',
                'accepted_at' => now(),
            ]);

            $freePlan = \App\Models\Plan::where('slug', 'free')->first();
            if ($freePlan) {
                $company->subscription()->create([
                    'plan_id' => $freePlan->id,
                    'status' => 'active',
                ]);
            }
        }

        $user->tokens()->where('name', 'mobile')->delete();
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->activeCompany()?->id,
                'created_at' => $user->created_at,
            ],
            'is_new_user' => $isNewUser,
        ]);
    }
}