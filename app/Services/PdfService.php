<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load('items', 'company');

        $data = [
            'invoice'  => $invoice,
            'company'  => $invoice->company,
            'items'    => $invoice->items,
            'qrCode'   => $invoice->einvoice_qr,
            'standard' => $invoice->einvoice_standard,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data)
                  ->setPaper('a4', 'portrait');

        return $pdf->output();
    }

    public function generateReceiptPdf(Receipt $receipt): string
    {
        $receipt->load('items', 'company');

        $data = [
            'receipt' => $receipt,
            'company' => $receipt->company,
            'items'   => $receipt->items,
            'qrCode'  => $receipt->einvoice_qr,
        ];

        $pdf = Pdf::loadView('pdf.receipt', $data)
                  ->setPaper('a4', 'portrait');

        return $pdf->output();
    }
}