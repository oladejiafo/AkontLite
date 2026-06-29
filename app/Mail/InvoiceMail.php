<?php
namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        // Generate PDF from your Blade view
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $this->invoice]);

        return $this->subject('Your Invoice from ' . config('app.name'))
            ->markdown('emails.invoice')
            ->attachData($pdf->output(), 'invoice_' . $this->invoice->invoice_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
