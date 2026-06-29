<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->json(Auth::user()->settings);
    }
    
    public function update(Request $request)
    {
        $settings = Auth::user()->settings()->updateOrCreate(
            ['user_id' => Auth::id()],
            $request->only(['company_name', 'logo_path', 'branding_color', 'footer_note'])
        );
    
        return response()->json($settings);
    }
    
    public function uploadLogo(Request $request)
    {
        $request->validate(['logo' => 'image|max:2048']);
    
        $path = $request->file('logo')->store('logos', 'public');
    
        $settings = Auth::user()->settings()->updateOrCreate(
            ['user_id' => Auth::id()],
            ['logo_path' => $path]
        );
    
        return response()->json(['path' => $path]);
    }
    
}
