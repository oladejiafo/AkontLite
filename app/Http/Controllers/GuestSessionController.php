<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\GuestToken;

use Illuminate\Support\Str;

class GuestSessionController extends Controller
{
    public function createSession()
    {
        $token = Str::uuid()->toString();
    
        GuestToken::create([
            'token' => $token,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    
        return response()->json(['token' => $token]);
    }

}
