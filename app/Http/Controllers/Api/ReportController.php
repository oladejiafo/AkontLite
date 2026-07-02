<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Http\Request;


class ReportController extends Controller
{
    // GET /api/reports/summary
    public function summary(Request $request)
    {
        $user    = $request->user();
        $company = $user->activeCompany();

        $baseInvoice = Invoice::where('user_id', $user->id);
        $baseReceipt = Receipt::where('user_id', $user->id);

        if ($company) {
            $baseInvoice->orWhere('company_id', $company->id);
            $baseReceipt->orWhere('company_id', $company->id);
        }

        return response()->json([
            'invoices' => [
                'total'       => (clone $baseInvoice)->count(),
                'draft'       => (clone $baseInvoice)->where('status', 'draft')->count(),
                'sent'        => (clone $baseInvoice)->where('status', 'sent')->count(),
                'paid'        => (clone $baseInvoice)->where('status', 'paid')->count(),
                'overdue'     => (clone $baseInvoice)->where('status', 'overdue')->count(),
                'total_value' => (clone $baseInvoice)->sum('total_amount'),
                'paid_value'  => (clone $baseInvoice)->where('status', 'paid')->sum('total_amount'),
            ],
            'receipts' => [
                'incoming_count' => (clone $baseReceipt)->where('type', 'incoming')->count(),
                'outgoing_count' => (clone $baseReceipt)->where('type', 'outgoing')->count(),
                'total_expenses' => (clone $baseReceipt)->where('type', 'incoming')->sum('total_amount'),
            ],
        ]);
    }

    // GET /api/reports/vat
    public function vat(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $user    = $request->user();
        $company = $user->activeCompany();

        $invoiceQuery = Invoice::whereBetween('created_at', [
            $request->from, $request->to
        ])->where('status', 'paid');

        $receiptQuery = Receipt::whereBetween('receipt_date', [
            $request->from, $request->to
        ])->where('type', 'incoming')
          ->where('status', 'confirmed');

        if ($company) {
            $invoiceQuery->where('company_id', $company->id);
            $receiptQuery->where('company_id', $company->id);
        } else {
            $invoiceQuery->where('user_id', $user->id);
            $receiptQuery->where('user_id', $user->id);
        }

        $outputVat = $invoiceQuery->sum('tax_amount');
        $inputVat  = $receiptQuery->sum('tax_amount');
        $vatPayable = $outputVat - $inputVat;

        return response()->json([
            'period' => [
                'from' => $request->from,
                'to'   => $request->to,
            ],
            'output_vat'  => round($outputVat, 2),
            'input_vat'   => round($inputVat, 2),
            'vat_payable' => round($vatPayable, 2),
            'currency'    => $company?->currency ?? 'USD',
        ]);
    }

    // GET /api/reports/export
public function export(Request $request)
{
    $request->validate([
        'type'   => 'required|in:invoices,receipts,vat',
        'format' => 'required|in:csv,pdf',
        'from'   => 'required|date',
        'to'     => 'required|date|after_or_equal:from',
    ]);

    $user    = $request->user();
    $company = $user->activeCompany();
    $from    = $request->from;
    $to      = $request->to;
    $type    = $request->type;
    $format  = $request->format;

    if ($type === 'invoices') {
        $data = Invoice::whereBetween('created_at', [$from, $to])
            ->when($company, fn($q) => $q->where('company_id', $company->id),
                             fn($q) => $q->where('user_id', $user->id))
            ->with('items')
            ->get();

        if ($format === 'csv') {
            return $this->invoicesCsv($data);
        }
    }

    if ($type === 'receipts') {
        $data = Receipt::whereBetween('receipt_date', [$from, $to])
            ->where('type', 'incoming')
            ->when($company, fn($q) => $q->where('company_id', $company->id),
                             fn($q) => $q->where('user_id', $user->id))
            ->get();

        if ($format === 'csv') {
            return $this->receiptsCsv($data);
        }
    }

    if ($type === 'vat') {
        $outputVat = Invoice::whereBetween('created_at', [$from, $to])
            ->where('status', 'paid')
            ->when($company, fn($q) => $q->where('company_id', $company->id),
                             fn($q) => $q->where('user_id', $user->id))
            ->sum('tax_amount');

        $inputVat = Receipt::whereBetween('receipt_date', [$from, $to])
            ->where('type', 'incoming')
            ->when($company, fn($q) => $q->where('company_id', $company->id),
                             fn($q) => $q->where('user_id', $user->id))
            ->sum('tax_amount');

        if ($format === 'csv') {
            return $this->vatCsv($from, $to, $outputVat, $inputVat,
                                 $company?->currency ?? 'USD');
        }
    }

    return response()->json(['message' => 'Export format not supported yet'], 422);
}

private function invoicesCsv($invoices): \Illuminate\Http\Response
{
    $rows   = ["Invoice Number,Client,Date,Due Date,Currency,Subtotal,Tax,Discount,Total,Status"];

    foreach ($invoices as $inv) {
        $rows[] = implode(',', [
            $inv->invoice_number,
            '"' . ($inv->client_name ?? '') . '"',
            $inv->created_at->format('Y-m-d'),
            $inv->due_date ?? '',
            $inv->currency,
            $inv->subtotal,
            $inv->tax_amount,
            $inv->discount_amount,
            $inv->total_amount,
            $inv->status,
        ]);
    }

    $csv = implode("\n", $rows);

    return response($csv, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="invoices.csv"',
    ]);
}

private function receiptsCsv($receipts): \Illuminate\Http\Response
{
    $rows = ["Receipt Number,Vendor,Date,Currency,Subtotal,Tax,Total,Category,Status"];

    foreach ($receipts as $r) {
        $rows[] = implode(',', [
            $r->receipt_number ?? '',
            '"' . ($r->vendor_name ?? '') . '"',
            $r->receipt_date->format('Y-m-d'),
            $r->currency,
            $r->subtotal,
            $r->tax_amount,
            $r->total_amount,
            $r->category ?? '',
            $r->status,
        ]);
    }

    $csv = implode("\n", $rows);

    return response($csv, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="expenses.csv"',
    ]);
}

private function vatCsv(
    string $from,
    string $to,
    float $outputVat,
    float $inputVat,
    string $currency
): \Illuminate\Http\Response {
    $vatPayable = $outputVat - $inputVat;

    $rows = [
        "Period From,Period To,Output VAT,Input VAT,VAT Payable,Currency",
        implode(',', [
            $from, $to,
            number_format($outputVat, 2),
            number_format($inputVat, 2),
            number_format($vatPayable, 2),
            $currency,
        ]),
    ];

    $csv = implode("\n", $rows);

    return response($csv, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="vat-report.csv"',
    ]);
}

}