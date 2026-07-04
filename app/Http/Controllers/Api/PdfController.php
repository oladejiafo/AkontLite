<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\PdfService;
use App\Services\GuestSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PdfController extends Controller
{
    public function __construct(
        private PdfService          $pdfService,
        private GuestSessionService $guestService
    ) {}

    // GET /api/invoices/{id}/pdf
public function invoicePdf(Request $request, int $id)
{
    // try token auth manually
    $token = $request->bearerToken();
    if ($token) {
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($accessToken) {
            $user = $accessToken->tokenable;
            Auth::setUser($user);
        }
    }

    $invoice = $this->resolveInvoice($request, $id);

    if (!$invoice) {
        return response()->json(['message' => 'Not found'], 404);
    }

    try {
        $pdf = $this->pdfService->generateInvoicePdf($invoice);
    } catch (\Exception $e) {
        return response()->json(['message' => 'PDF generation failed: ' . $e->getMessage()], 500);
    }

    return response($pdf, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"{$invoice->invoice_number}.pdf\"",
    ]);
}

public function receiptPdf(Request $request, int $id)
{
    $token = $request->bearerToken();
    if ($token) {
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
        if ($accessToken) {
            $user = $accessToken->tokenable;
            Auth::setUser($user);
        }
    }

    $receipt = $this->resolveReceipt($request, $id);

    if (!$receipt) {
        return response()->json(['message' => 'Not found'], 404);
    }

    try {
        $pdf = $this->pdfService->generateReceiptPdf($receipt);
    } catch (\Exception $e) {
        return response()->json(['message' => 'PDF generation failed: ' . $e->getMessage()], 500);
    }

    return response($pdf, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"receipt-{$receipt->id}.pdf\"",
    ]);
}

    // ─── Helpers ─────────────────────────────

    private function resolveInvoice(Request $request, int $id): ?Invoice
    {
        $invoice = Invoice::find($id);
        if (!$invoice) return null;

        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if ($user) {
            $company = $user->activeCompany();
            return ($invoice->user_id === $user->id ||
                   ($company && $invoice->company_id === $company->id))
                   ? $invoice : null;
        }

        if ($guest) {
            return $invoice->guest_token === $guest->token ? $invoice : null;
        }

        return null;
    }

    private function resolveReceipt(Request $request, int $id): ?Receipt
    {
        $receipt = Receipt::find($id);
        if (!$receipt) return null;

        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if ($user) {
            $company = $user->activeCompany();
            return ($receipt->user_id === $user->id ||
                   ($company && $receipt->company_id === $company->id))
                   ? $receipt : null;
        }

        if ($guest) {
            return $receipt->guest_token === $guest->token ? $receipt : null;
        }

        return null;
    }
}