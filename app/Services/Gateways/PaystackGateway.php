<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGatewayInterface
{
    private string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret');
    }

    public function createCheckout(Invoice $invoice): string
    {
        $response = Http::withToken($this->secretKey)
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $invoice->client_email ?? $invoice->customer_email ?? 'noemail@akontlite.com',
                'amount' => (int) round($invoice->total_amount * 100), // Paystack uses kobo/cents
                'currency' => strtoupper($invoice->currency),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                ],
                'callback_url' => config('app.frontend_url') . '/payment/success?invoice=' . $invoice->id,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Paystack checkout initialization failed: ' . $response->body());
        }

        return $response->json('data.authorization_url');
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('x-paystack-signature');

        if (!$signature) {
            return false;
        }

        $computedSignature = hash_hmac('sha512', $request->getContent(), $this->secretKey);

        return hash_equals($computedSignature, $signature);
    }

    public function handleWebhookEvent(Request $request): array
    {
        $event = json_decode($request->getContent(), true);

        if ($event['event'] === 'charge.success') {
            $data = $event['data'];

            return [
                'invoice_id'     => $data['metadata']['invoice_id'] ?? null,
                'amount'         => $data['amount'] / 100,
                'transaction_id' => $data['reference'],
                'status'         => 'successful',
            ];
        }

        return ['status' => 'ignored'];
    }
}