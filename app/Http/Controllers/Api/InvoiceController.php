<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\GuestSessionService;
use App\Services\EInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function __construct(
        private GuestSessionService $guestService,
        private EInvoiceService     $einvoice
    ) {}

    // GET /api/invoices
    public function index(Request $request)
    {
        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if (!$user && !$guest) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $query = Invoice::with('items')->orderBy('created_at', 'desc');

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

        return response()->json([
            'data' => $query->paginate(20),
        ]);
    }

    // POST /api/invoices
    public function store(Request $request)
    {
        $request->validate([
            'client_name'          => 'nullable|string|max:255',
            'client_email'         => 'nullable|email',
            'currency'             => 'required|string|size:3',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.tax_rate'     => 'nullable|numeric|min:0',
            'due_date'             => 'nullable|date',
            'notes'                => 'nullable|string',
            'discount_amount'      => 'nullable|numeric|min:0',
        ]);

        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        if (!$user && !$guest) {
            $guest = $this->guestService->create($request);
        }

        $company = $user?->activeCompany();

        // calculate totals
        $subtotal  = 0;
        $taxAmount = 0;

        $items = collect($request->items)->map(function ($item) use (&$subtotal, &$taxAmount) {
            $lineTotal  = $item['quantity'] * $item['unit_price'];
            $lineTax    = $lineTotal * (($item['tax_rate'] ?? 0) / 100);
            $subtotal  += $lineTotal;
            $taxAmount += $lineTax;

            return [
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'tax_rate'    => $item['tax_rate'] ?? 0,
                'tax_amount'  => $lineTax,
                'total'       => $lineTotal + $lineTax,
            ];
        });

        $discount   = $request->discount_amount ?? 0;
        $total      = $subtotal + $taxAmount - $discount;

        $invoice = Invoice::create([
            'company_id'      => $company?->id,
            'user_id'         => $user?->id,
            'guest_token'     => $guest?->token,
            'invoice_number'  => $this->generateInvoiceNumber($company?->id),
            'client_name'     => $request->client_name,
            'client_email'    => $request->client_email,
            'currency'        => $request->currency,
            'subtotal'        => $subtotal,
            'tax_amount'      => $taxAmount,
            'discount_amount' => $discount,
            'total_amount'    => $total,
            'due_date'        => $request->due_date,
            'notes'           => $request->notes,
            'status'          => 'draft',
        ]);

        // create line items
        foreach ($items as $item) {
            $invoice->items()->create($item);
        }

        // apply e-invoice standard
        if ($company) {
            $this->einvoice->applyToInvoice($invoice);
        }

        return response()->json([
            'success' => true,
            'data'    => $invoice->load('items'),
            'guest_token' => $guest?->token,
        ], 201);
    }

    // GET /api/invoices/{id}
    public function show(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);

        if (!$invoice) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json(['data' => $invoice->load('items')]);
    }

    // PUT /api/invoices/{id}
    public function update(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);

        if (!$invoice) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $request->validate([
            'client_name'  => 'nullable|string|max:255',
            'client_email' => 'nullable|email',
            'status'       => 'nullable|in:draft,sent,paid,overdue,cancelled',
            'notes'        => 'nullable|string',
            'due_date'     => 'nullable|date',
        ]);

        $invoice->update($request->only([
            'client_name', 'client_email',
            'status', 'notes', 'due_date',
        ]));

        return response()->json(['data' => $invoice->load('items')]);
    }

    // DELETE /api/invoices/{id}
    public function destroy(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);

        if (!$invoice) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $invoice->delete();

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────

    private function resolveInvoice(Request $request, int $id): ?Invoice
    {
        $user  = $request->user();
        $guest = $this->guestService->resolve($request);

        $invoice = Invoice::find($id);
        if (!$invoice) return null;

        if ($user) {
            $company = $user->activeCompany();
            $owned   = $invoice->user_id === $user->id
                    || ($company && $invoice->company_id === $company->id);
            return $owned ? $invoice : null;
        }

        if ($guest) {
            return $invoice->guest_token === $guest->token ? $invoice : null;
        }

        return null;
    }

    private function generateInvoiceNumber(?int $companyId): string
    {
        $prefix = 'INV-' . date('Ym') . '-';
        $last   = Invoice::where('invoice_number', 'like', $prefix . '%')
                         ->orderBy('id', 'desc')
                         ->first();

        $next = $last
            ? (int) substr($last->invoice_number, strlen($prefix)) + 1
            : 1;

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    // POST /api/invoices/{id}/mark-sent
    public function markSent(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);
        if (!$invoice) return response()->json(['message' => 'Not found'], 404);

        $invoice->update(['status' => 'sent']);

        return response()->json(['data' => $invoice->load('items')]);
    }

    // POST /api/invoices/{id}/mark-paid
    public function markPaid(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);
        if (!$invoice) return response()->json(['message' => 'Not found'], 404);

        $invoice->load('items');

        $invoice->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);

        $receipt = $this->generatePaymentReceipt($invoice);

        return response()->json([
            'data'    => $invoice->load('items'),
            'receipt' => $receipt,
        ]);
    }

    // POST /api/invoices/{id}/cancel
    public function cancel(Request $request, int $id)
    {
        $invoice = $this->resolveInvoice($request, $id);
        if (!$invoice) return response()->json(['message' => 'Not found'], 404);

        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'Cannot cancel a paid invoice'], 422);
        }

        $invoice->update(['status' => 'cancelled']);

        return response()->json(['data' => $invoice->load('items')]);
    }

    private function generatePaymentReceipt(Invoice $invoice): \App\Models\Receipt
    {
        $existing = \App\Models\Receipt::where('invoice_id', $invoice->id)->first();
        if ($existing) return $existing;

        $receipt = \App\Models\Receipt::create([
            'invoice_id'      => $invoice->id,
            'company_id'      => $invoice->company_id,
            'user_id'         => $invoice->user_id,
            'guest_token'     => $invoice->guest_token,
            'type'            => 'outgoing',
            'receipt_number'  => 'RCP-' . strtoupper(substr($invoice->invoice_number, -6)),
            'customer_name'   => $invoice->client_name,
            'receipt_date'    => now()->format('Y-m-d'),
            'subtotal'        => $invoice->subtotal,
            'tax_amount'      => $invoice->tax_amount,
            'discount_amount' => $invoice->discount_amount,
            'total_amount'    => $invoice->total_amount,
            'currency'        => $invoice->currency,
            'notes'           => 'Payment receipt for invoice ' . $invoice->invoice_number,
            'status'          => 'confirmed',
        ]);

        foreach ($invoice->items as $item) {
            $receipt->items()->create([
                'description' => $item->description,
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'tax_rate'    => $item->tax_rate,
                'tax_amount'  => $item->tax_amount,
                'total'       => $item->total,
                'sort_order'  => $item->sort_order ?? 0,
            ]);
        }

        if ($receipt->company) {
            app(\App\Services\EInvoiceService::class)->applyZatcaToReceipt($receipt);
        }

        return $receipt->load('items');
    }

    // POST /api/invoices/{id}/send
    public function send(Request $request, int $id)
    {
        $request->validate([
            'channel' => 'required|in:email,whatsapp',
        ]);

        $invoice = $this->resolveInvoice($request, $id);
        if (!$invoice) return response()->json(['message' => 'Not found'], 404);

        if ($request->channel === 'email' && $invoice->client_email) {
            // queue email notification
            Mail::raw(
                "Please find attached your invoice {$invoice->invoice_number} for " .
                number_format($invoice->total_amount, 2) . " {$invoice->currency}.",
                function ($message) use ($invoice) {
                    $message->to($invoice->client_email)
                            ->subject("Invoice {$invoice->invoice_number}");
                }
            );

            $invoice->update(['status' => 'sent']);

            return response()->json([
                'success' => true,
                'message' => "Invoice sent to {$invoice->client_email}",
            ]);
        }

        if ($request->channel === 'whatsapp') {
            // return a WhatsApp deep link
            $text = urlencode(
                "Hi, please find your invoice {$invoice->invoice_number} " .
                "for " . number_format($invoice->total_amount, 2) . " {$invoice->currency}."
            );
            return response()->json([
                'success'      => true,
                'whatsapp_url' => "https://wa.me/?text={$text}",
            ]);
        }

        return response()->json(['message' => 'No email on file for this client'], 422);
    }

}