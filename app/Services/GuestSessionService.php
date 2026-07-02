<?php

namespace App\Services;

use App\Models\GuestSession;
use App\Models\User;
use Illuminate\Http\Request;

class GuestSessionService
{
    public function resolve(Request $request): ?GuestSession
    {
        $token = $request->header('X-Guest-Token')
                ?? $request->input('guest_token');

        if (!$token) return null;

        $session = GuestSession::where('token', $token)
                               ->whereNull('migrated_at')
                               ->first();

        if (!$session || $session->isExpired()) return null;

        $session->touch();
        return $session;
    }

    public function create(Request $request): GuestSession
    {
        return GuestSession::createNew(
            deviceId: $request->header('X-Device-ID'),
            ip:       $request->ip()
        );
    }

    public function migrateToUser(string $token, User $user): array
    {
        $session = GuestSession::where('token', $token)
                               ->whereNull('migrated_at')
                               ->first();

        if (!$session) {
            return ['migrated' => false, 'reason' => 'Session not found or already migrated'];
        }

        if ($session->isExpired()) {
            return ['migrated' => false, 'reason' => 'Session expired'];
        }

        // get the user's active company
        $company = $user->activeCompany();

        // migrate invoices
        $invoiceCount = \App\Models\Invoice::where('guest_token', $token)
            ->whereNull('user_id')
            ->update([
                'user_id'     => $user->id,
                'company_id'  => $company?->id,
                'guest_token' => null,
            ]);

        // migrate receipts
        $receiptCount = \App\Models\Receipt::where('guest_token', $token)
            ->whereNull('user_id')
            ->update([
                'user_id'     => $user->id,
                'company_id'  => $company?->id,
                'guest_token' => null,
            ]);

        // mark session as migrated
        $session->update([
            'migrated_to_user_id' => $user->id,
            'migrated_at'         => now(),
        ]);

        return [
            'migrated'       => true,
            'invoices_moved' => $invoiceCount,
            'receipts_moved' => $receiptCount,
        ];
    }

    public function getOrCreate(Request $request): GuestSession
    {
        $existing = $this->resolve($request);
        return $existing ?? $this->create($request);
    }
}