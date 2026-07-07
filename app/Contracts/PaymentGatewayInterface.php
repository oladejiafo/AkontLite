<?php

namespace App\Contracts;

use App\Models\Invoice;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function createCheckout(Invoice $invoice): string;

    public function verifyWebhookSignature(Request $request): bool;

    public function handleWebhookEvent(Request $request): array;
}