<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FlutterwaveGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $webhookHash;

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret');
        $this->webhookHash = config('services.flutterwave.webhook_hash');
    }

    public function createCheckout(Invoice $invoice): string
    {
        $txRef = 'AKONT-' . $invoice->id . '-' . Str::random(8);

        $response = Http::withToken($this->secretKey)
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $invoice->total_amount,
                'currency' => strtoupper($invoice->currency),
                'redirect_url' => config('app.frontend_url') . '/payment/success?invoice=' . $invoice->id,
                'customer' => [
                    'email' => $invoice->client_email ?? $invoice->customer_email ?? 'noemail@akontlite.com',
                    'name' => $invoice->client_name ?? $invoice->customer_name ?? 'Customer',
                ],
                'meta' => [
                    'invoice_id' => $invoice->id,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('Flutterwave checkout initialization failed: ' . $response->body());
        }

        return $response->json('data.link');
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('verif-hash');

        if (!$signature) {
            return false;
        }

        return hash_equals($this->webhookHash, $signature);
    }

    public function handleWebhookEvent(Request $request): array
    {
        $event = json_decode($request->getContent(), true);

        if (($event['event'] ?? null) === 'charge.completed'
            && ($event['data']['status'] ?? null) === 'successful') {

            $data = $event['data'];

            return [
                'invoice_id'     => $data['meta']['invoice_id'] ?? null,
                'amount'         => $data['amount'],
                'transaction_id' => $data['tx_ref'],
                'status'         => 'successful',
            ];
        }

        return ['status' => 'ignored'];
    }
}