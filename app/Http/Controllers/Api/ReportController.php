<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;


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
    public function vatxx(Request $request)
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
    
    public function vat(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);
    
        $user    = $request->user();
        $company = $user->activeCompany();
    
        // ==========================================
        // OUTPUT VAT - Calculate from invoice items
        // ==========================================
        $invoiceQuery = Invoice::whereBetween('created_at', [
            $request->from, $request->to
        ])->where('status', 'paid');
    
        if ($company) {
            $invoiceQuery->where('company_id', $company->id);
        } else {
            $invoiceQuery->where('user_id', $user->id);
        }
    
        // Get all invoices and sum their item totals
        $invoices = $invoiceQuery->with('items')->get();
        $outputVat = 0;
        foreach ($invoices as $invoice) {
            foreach ($invoice->items as $item) {
                // If you have a tax rate on invoice_items, calculate tax
                // If not, assume total includes tax or calculate from invoice total
                $outputVat += $item->total; // Or calculate tax from total
            }
        }
    
        // ==========================================
        // INPUT VAT - From receipts table (has tax_amount)
        // ==========================================
        $receiptQuery = Receipt::whereBetween('receipt_date', [
            $request->from, $request->to
        ])->where('type', 'incoming')
          ->where('status', 'confirmed');
    
        if ($company) {
            $receiptQuery->where('company_id', $company->id);
        } else {
            $receiptQuery->where('user_id', $user->id);
        }
    
        $inputVat = $receiptQuery->sum('tax_amount');
    
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
    public function exportXX(Request $request)
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
            // Calculate output VAT from invoices
            $invoiceQuery = Invoice::whereBetween('created_at', [$from, $to])
                ->where('status', 'paid')
                ->when($company, fn($q) => $q->where('company_id', $company->id),
                                fn($q) => $q->where('user_id', $user->id))
                ->with('items');

            $invoices = $invoiceQuery->get();
            $outputVat = 0;
            foreach ($invoices as $invoice) {
                foreach ($invoice->items as $item) {
                    $taxRate = 0.05; // Adjust based on your tax rate
                    $outputVat += $item->total * $taxRate;
                }
            }

            // Input VAT from receipts
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
            $data = Invoice::whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
                ->where('user_id', $user->id)
                ->with('items')
                ->get();

            return $format === 'csv'
                ? $this->invoicesCsv($data)
                : $this->invoicesPdf($data, $from, $to);
        }

        if ($type === 'receipts') {
            $data = Receipt::whereBetween('receipt_date', [$from, $to])
                ->where('type', 'incoming')
                ->where('user_id', $user->id)
                ->get();

            return $format === 'csv'
                ? $this->receiptsCsv($data)
                : $this->receiptsPdf($data, $from, $to);
        }

        if ($type === 'vat') {
            $invoiceQuery = Invoice::whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"])
                ->where('status', 'paid')
                ->where('user_id', $user->id)
                ->with('items');

            $invoices  = $invoiceQuery->get();
            $outputVat = 0;
            foreach ($invoices as $invoice) {
                foreach ($invoice->items as $item) {
                    $outputVat += $item->total * 0.05;
                }
            }

            $inputVat = Receipt::whereBetween('receipt_date', [$from, $to])
                ->where('type', 'incoming')
                ->where('user_id', $user->id)
                ->sum('tax_amount');

            return $format === 'csv'
                ? $this->vatCsv($from, $to, $outputVat, $inputVat, 'USD')
                : $this->vatPdf($from, $to, $outputVat, $inputVat, 'USD');
        }

        return response()->json(['message' => 'Export type not supported'], 422);
    }

    private function invoicesPdf($invoices, $from, $to)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.invoices', [
            'invoices' => $invoices,
            'from'     => $from,
            'to'       => $to,
        ])->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoices-report.pdf"',
        ]);
    }

    private function receiptsPdf($receipts, $from, $to)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.receipts', [
            'receipts' => $receipts,
            'from'     => $from,
            'to'       => $to,
        ])->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="expenses-report.pdf"',
        ]);
    }

    private function vatPdf($from, $to, $outputVat, $inputVat, $currency)
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.reports.vat', [
            'from'       => $from,
            'to'         => $to,
            'outputVat'  => $outputVat,
            'inputVat'   => $inputVat,
            'vatPayable' => $outputVat - $inputVat,
            'currency'   => $currency,
        ])->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="vat-report.pdf"',
        ]);
    }

    private function invoicesCsv($invoices): \Illuminate\Http\Response
    {
        $rows = ["Invoice Number,Customer,Date,Due Date,Currency,Subtotal,Tax,Discount,Total,Status"];

        foreach ($invoices as $inv) {
            // Calculate subtotal from items if needed
            $subtotal = $inv->items->sum('total') ?? $inv->total_amount;
            $tax = 0;
            $discount = 0;
            
            // If you have tax and discount columns on the invoice, use them
            // Otherwise calculate from items
            foreach ($inv->items as $item) {
                $taxRate = 0.05; // Adjust based on your tax rate
                $tax += $item->total * $taxRate;
            }

            $rows[] = implode(',', [
                $inv->invoice_number,
                '"' . ($inv->customer_name ?? $inv->sender_company_name ?? '') . '"',
                $inv->issue_date ?? $inv->created_at->format('Y-m-d'),
                $inv->due_date ?? '',
                $inv->currency,
                number_format($subtotal, 2),
                number_format($tax, 2),
                number_format($discount, 2),
                number_format($inv->total_amount, 2),
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
                '"' . ($r->vendor_name ?? $r->customer_name ?? '') . '"',
                $r->receipt_date->format('Y-m-d'),
                $r->currency,
                number_format($r->subtotal ?? 0, 2),
                number_format($r->tax_amount ?? 0, 2),
                number_format($r->total_amount ?? 0, 2),
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