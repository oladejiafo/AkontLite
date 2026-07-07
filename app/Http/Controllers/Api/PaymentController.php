<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $payments = Payment::with('invoice')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json(['data' => $payments]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id'      => 'required|exists:invoices,id',
            'amount'          => 'required|numeric|min:0.01',
            'payment_method'  => 'required|string|max:255',
            'transaction_id'  => 'nullable|string|max:255',
            'paid_at'         => 'required|date',
            'notes'           => 'nullable|string',
        ]);

        $user = $request->user();
        $invoice = Invoice::where('id', $request->invoice_id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $payment = Payment::create([
                'invoice_id'     => $invoice->id,
                'user_id'        => $user->id,
                'amount'         => $request->amount,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'status'         => 'successful',
                'paid_at'        => $request->paid_at,
                'meta'           => ['notes' => $request->notes],
            ]);

            $totalPaid = Payment::where('invoice_id', $invoice->id)
                ->where('status', 'successful')
                ->sum('amount');

            $invoice->update([
                'status' => $totalPaid >= $invoice->total_amount ? 'paid' : $invoice->status,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data'    => $payment->load('invoice'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
            ], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        $payment = Payment::with('invoice')
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $payment]);
    }
}