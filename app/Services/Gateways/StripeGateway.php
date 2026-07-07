<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayInterface
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckout(Invoice $invoice): string
    {
        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($invoice->currency),
                    'unit_amount' => (int) round($invoice->total_amount * 100),
                    'product_data' => [
                        'name' => "Invoice {$invoice->invoice_number}",
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'invoice_id' => $invoice->id,
            ],
            'success_url' => config('app.frontend_url') . '/payment/success?invoice=' . $invoice->id,
            'cancel_url'  => config('app.frontend_url') . '/payment/cancel?invoice=' . $invoice->id,
        ]);

        return $session->url;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        try {
            Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('services.stripe.webhook_secret')
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function handleWebhookEvent(Request $request): array
    {
        $event = json_decode($request->getContent(), true);

        if ($event['type'] === 'checkout.session.completed') {
            $session = $event['data']['object'];

            return [
                'invoice_id'     => $session['metadata']['invoice_id'] ?? null,
                'amount'         => $session['amount_total'] / 100,
                'transaction_id' => $session['payment_intent'],
                'status'         => 'successful',
            ];
        }

        return ['status' => 'ignored'];
    }
}