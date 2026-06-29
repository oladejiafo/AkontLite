<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        // Pass through any redirect parameter
        if (request()->has('redirect')) {
            session()->put('url.intended', request('redirect'));
        }
        
        return Socialite::driver('google')->redirect();
        // $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        // dd($url);
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'password' => bcrypt(Str::random(12)),
                    'email_verified_at' => now(),
                ]
            );

            Auth::login($user);


            // Check for intended URL before redirecting
            if (session()->has('url.intended')) {
                return redirect()->intended();
            }

            return redirect('/'); // or dashboard or previous URL

        } catch (\Exception $e) {
            return redirect('/')->withErrors(['msg' => 'Google login failed.']);
        }
    }
}
