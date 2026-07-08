<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class PlanController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Plan::orderBy('price')->get(),
        ]);
    }
}