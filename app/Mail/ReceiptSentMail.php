<?php

namespace App\Mail;

use App\Models\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceiptSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Receipt $receipt;
    public $showWatermark;

    public function __construct(Receipt $receipt)
    {
        $this->receipt = $receipt;
        
        // Check watermark status
        $showWatermark = true;
        if ($receipt->user) {
            $showWatermark = !app(\App\Services\PlanGateService::class)->canRemoveWatermark($receipt->user);
        }
        $this->showWatermark = $showWatermark;
    }

    public function build()
    {
        return $this->view('emails.receipt.sent', [
                'receipt' => $this->receipt,
                'showWatermark' => $this->showWatermark,
            ])
            ->subject('Payment Receipt - ' . ($this->receipt->receipt_number ?? 'RCP-' . $this->receipt->id));
    }
}