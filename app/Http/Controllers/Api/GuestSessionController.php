<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GuestSessionService;
use Illuminate\Http\Request;

class GuestSessionController extends Controller
{
    public function __construct(
        private GuestSessionService $guestService
    ) {}

    // POST /api/guest/session
    // Create or return a guest session token
    public function create(Request $request)
    {
        $session = $this->guestService->create($request);

        return response()->json([
            'token'      => $session->token,
            'expires_at' => $session->expires_at->toIso8601String(),
        ], 201);
    }

    // POST /api/guest/migrate
    // Migrate guest data to authenticated user after signup
    public function migrate(Request $request)
    {
        $request->validate([
            'guest_token' => 'required|string|uuid',
        ]);

        $result = $this->guestService->migrateToUser(
            token: $request->input('guest_token'),
            user:  $request->user()
        );

        if (!$result['migrated']) {
            return response()->json([
                'success' => false,
                'message' => $result['reason'],
            ], 422);
        }

        return response()->json([
            'success'        => true,
            'invoices_moved' => $result['invoices_moved'],
            'receipts_moved' => $result['receipts_moved'],
        ]);
    }
}