<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\PdfService;
use App\Services\GuestSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PdfController extends Controller
{
    public function __construct(
        private PdfService          $pdfService,
        private GuestSessionService $guestService
    ) {}

    // GET /api/invoices/{id}/pdf
    public function invoicePdf(Request $request, int $id)
    {
        $invoice = $this->resolveByToken($request, $id, 'invoice');

        if (!$invoice) {
            return response()->json(['message' => 'Not found or unauthorized'], 404);
        }

        try {
            $pdf = $this->pdfService->generateInvoicePdf($invoice);
        } catch (\Exception $e) {
            Log::error('PDF generation failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'PDF generation failed'], 500);
        }

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$invoice->invoice_number}.pdf\"",
        ]);
    }

    public function receiptPdf(Request $request, int $id)
    {
        $receipt = $this->resolveByToken($request, $id, 'receipt');

        if (!$receipt) {
            return response()->json(['message' => 'Not found or unauthorized'], 404);
        }

        try {
            $pdf = $this->pdfService->generateReceiptPdf($receipt);
        } catch (\Exception $e) {
            Log::error('Receipt PDF failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'PDF generation failed'], 500);
        }

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"receipt-{$receipt->id}.pdf\"",
        ]);
    }

    private function resolveByToken(Request $request, int $id, string $type): mixed
    {
        // resolve user directly from bearer token
        $user = null;
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($bearerToken);
            if ($pat) {
                $user = $pat->tokenable;
            }
        }

        if ($type === 'invoice') {
            $model = Invoice::with(['items', 'company'])->find($id);
            if (!$model) return null;
            if ($user && $model->user_id === $user->id) return $model;

            $guest = $this->guestService->resolve($request);
            if ($guest && $model->guest_token === $guest->token) return $model;
            return null;
        }

        $model = Receipt::with(['items', 'company'])->find($id);
        if (!$model) return null;
        if ($user && $model->user_id === $user->id) return $model;

        $guest = $this->guestService->resolve($request);
        if ($guest && $model->guest_token === $guest->token) return $model;
        return null;
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