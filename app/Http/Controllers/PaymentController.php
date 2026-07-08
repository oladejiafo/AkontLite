<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function generateCheckout(Request $request, int $invoiceId)
    {
        $request->validate([
            'provider' => 'required|in:stripe,paystack,flutterwave',
        ]);

        if (!app(\App\Services\PlanGateService::class)->canUsePaymentGateways($request->user())) {
            return response()->json([
                'message' => 'Online payment collection is a paid feature. Upgrade to accept card payments.',
            ], 403);
        }

        $invoice = Invoice::where('id', $invoiceId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        try {
            $gateway = PaymentGatewayFactory::make($request->provider);
            $checkoutUrl = $gateway->createCheckout($invoice);

            return response()->json(['checkout_url' => $checkoutUrl]);

        } catch (\Exception $e) {
            Log::error('Checkout generation failed', [
                'provider' => $request->provider,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Could not start checkout. Please try again.',
            ], 500);
        }
    }

    public function handleWebhook(Request $request, string $provider)
    {
        try {
            $gateway = PaymentGatewayFactory::make($provider);
        } catch (\InvalidArgumentException $e) {
            return response('Unknown provider', 400);
        }

        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning('Webhook signature failed', ['provider' => $provider]);
            return response('Invalid signature', 401);
        }

        $result = $gateway->handleWebhookEvent($request);

        if ($result['status'] !== 'successful' || !$result['invoice_id']) {
            return response('ok', 200);
        }

        DB::beginTransaction();

        try {
            $invoice = Invoice::findOrFail($result['invoice_id']);

            $existing = Payment::where('transaction_id', $result['transaction_id'])->first();
            if ($existing) {
                DB::commit();
                return response('ok', 200);
            }

            Payment::create([
                'invoice_id'     => $invoice->id,
                'user_id'        => $invoice->user_id,
                'amount'         => $result['amount'],
                'payment_method' => $provider,
                'transaction_id' => $result['transaction_id'],
                'status'         => 'successful',
                'paid_at'        => now(),
            ]);

            $invoice->update(['status' => 'paid']);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
        }

        return response('ok', 200);
    }
}