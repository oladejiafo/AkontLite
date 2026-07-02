<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\PdfService;
use App\Services\GuestSessionService;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function __construct(
        private PdfService          $pdfService,
        private GuestSessionService $guestService
    ) {}

    // GET /api/invoices/{id}/pdf
    public function invoicePdf(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);

        if (!$invoice) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $pdf = $this->pdfService->generateInvoicePdf($invoice);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$invoice->invoice_number}.pdf\"",
        ]);
    }

    // GET /api/receipts/{id}/pdf
    public function receiptPdf(Request $request, int $id)
    {
        $receipt = $this->resolveReceipt($request, $id);

        if (!$receipt) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $pdf = $this->pdfService->generateReceiptPdf($receipt);

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