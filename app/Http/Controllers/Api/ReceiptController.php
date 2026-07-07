<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\GuestSessionService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function __construct(
        private GuestSessionService $guestService,
        private ReceiptService      $receiptService
    ) {}

    // GET /api/receipts
    public function index(Request $request)
    {
        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if (!$user && !$guest) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Receipt::with('items')->orderBy('created_at', 'desc');

        if ($user) {
            $company = $user->activeCompany();
            $query->where(function ($q) use ($user, $company) {
                $q->where('user_id', $user->id);
                if ($company) {
                    $q->orWhere('company_id', $company->id);
                }
            });
        } else {
            $query->where('guest_token', $guest->token);
        }

        // filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'data' => $query->paginate(20),
        ]);
    }

    // POST /api/receipts
    public function store(Request $request)
    {
        $request->validate([
            'type'           => 'required|in:incoming,outgoing',
            'vendor_name'    => 'nullable|string|max:255',
            'customer_name'  => 'nullable|string|max:255',
            'receipt_date'   => 'required|date',
            'total_amount'   => 'required|numeric|min:0',
            'currency'       => 'required|string|size:3',
            'items'          => 'nullable|array',
        ]);

        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if (!$user && !$guest) {
            $guest = $this->guestService->create($request);
        }

        $receipt = $this->receiptService->createFromData(
            data:  $request->all(),
            user:  $user,
            guest: $guest
        );

        return response()->json([
            'success'     => true,
            'data'        => $receipt,
            'guest_token' => $guest?->token,
        ], 201);
    }

    // GET /api/receipts/{id}
    public function show(Request $request, int $id)
    {
        $receipt = $this->resolveReceipt($request, $id);

        if (!$receipt) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $receipt->load('items')]);
    }

    // PUT /api/receipts/{id}
    public function update(Request $request, int $id)
    {
        $receipt = $this->resolveReceipt($request, $id);

        if (!$receipt) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $receipt->update($request->only([
            'vendor_name', 'customer_name', 'receipt_date',
            'subtotal', 'tax_amount', 'discount_amount',
            'total_amount', 'currency', 'category',
            'notes', 'status',
        ]));

        return response()->json(['data' => $receipt->load('items')]);
    }

    // DELETE /api/receipts/{id}
    public function destroy(Request $request, int $id)
    {
        $receipt = $this->resolveReceipt($request, $id);

        if (!$receipt) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $receipt->delete();

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function resolveReceipt(Request $request, int $id): ?Receipt
    {
        $user = $request->user();
        $guest = $this->guestService->resolve($request);
        $receipt = Receipt::find($id);

        if (!$receipt) return null;

        // If user is logged in
        if ($user) {
            // Allow if receipt belongs to user
            if ($receipt->user_id === $user->id) {
                return $receipt;
            }
            
            // Allow if receipt belongs to user's company
            $company = $user->activeCompany();
            if ($company && $receipt->company_id === $company->id) {
                return $receipt;
            }
            
            // ALLOW if receipt has no user_id (guest receipt) - THIS IS THE KEY FIX
            if ($receipt->user_id === null) {
                return $receipt;
            }
            
            return null;
        }

        // Guest mode
        if ($guest) {
            return $receipt->guest_token === $guest->token ? $receipt : null;
        }

        return null;
    }
}