<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function handleWebhook(Request $request)
    {
        // Implement signature check for each provider
        
        $data = $request->all();
    
        $invoice = Invoice::where('invoice_number', $data['invoice_number'])->firstOrFail();
    
        Payment::create([
            'invoice_id' => $invoice->id,
            'user_id' => $invoice->user_id,
            'amount' => $data['amount'],
            'payment_method' => $data['provider'],
            'transaction_id' => $data['transaction_id'],
            'status' => $data['status'],
            'paid_at' => now(),
        ]);
    
        if ($data['status'] === 'successful') {
            $invoice->update(['status' => 'paid']);
        }
    
        return response('ok', 200);
    }
    
    public function generateLink($invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);
    
        // Call Flutterwave/Paystack API with invoice amount
    
        $link = 'https://paylink.example.com/txn123'; // mock response
    
        return response()->json(['link' => $link]);
    }
    
}
