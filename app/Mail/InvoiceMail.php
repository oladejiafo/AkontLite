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
    public $showWatermark;

    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
        
        // Check watermark status
        $showWatermark = true;
        if ($invoice->user) {
            $showWatermark = !app(\App\Services\PlanGateService::class)->canRemoveWatermark($invoice->user);
        }
        $this->showWatermark = $showWatermark;
    }

    public function build()
    {
        // Generate PDF from your Blade view
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $this->invoice,
            'showWatermark' => $this->showWatermark,
        ]);

        return $this->subject('Your Invoice from ' . config('app.name'))
            ->markdown('emails.invoice', [
                'invoice' => $this->invoice,
                'showWatermark' => $this->showWatermark,
            ])
            ->attachData($pdf->output(), 'invoice_' . $this->invoice->invoice_number . '.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}