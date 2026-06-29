<x-app-layout>
    <x-header />
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Payment</h2>
            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Payments
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('payments.update', $payment->id) }}">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">

                    <div class="row mb-3">
                        <label for="invoice_id" class="col-md-3 col-form-label">Invoice *</label>
                        <div class="col-md-9">
                            <select id="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror" name="invoice_id" required disabled>
                                <option value="{{ $payment->invoice_id }}" selected>
                                    {{ $payment->invoice->invoice_number }} ({{ $payment->invoice->customer_name }}) - 
                                    {{ $payment->invoice->currency }} {{ number_format($payment->invoice->total_amount, 2) }}
                                </option>
                            </select>
                            <input type="hidden" name="invoice_id" value="{{ $payment->invoice_id }}">
                            @error('invoice_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="customer_name" class="col-md-3 col-form-label">Customer</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control bg-light" value="{{ $payment->invoice->customer_name }}" readonly>
                            <input type="hidden" name="customer_id" value="{{ $payment->invoice->customer_id }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-md-3 col-form-label">Invoice Amount</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control bg-light" 
                                   value="{{ $payment->invoice->currency }} {{ number_format($payment->invoice->total_amount, 2) }}" readonly>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="amount" class="col-md-3 col-form-label">Payment Amount *</label>
                        <div class="col-md-9">
                            <div class="input-group">
                                <span class="input-group-text">{{ $payment->invoice->currency }}</span>
                                <input id="amount" type="number" class="form-control @error('amount') is-invalid @enderror" 
                                    name="amount" value="{{ old('amount', $payment->amount) }}" required step="0.01" min="0.01">
                                @error('amount')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="payment_method" class="col-md-3 col-form-label">Payment Method *</label>
                        <div class="col-md-9">
                            <select id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="Cash" {{ old('payment_method', $payment->payment_method) === 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Credit Card" {{ old('payment_method', $payment->payment_method) === 'Credit Card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="Bank Transfer" {{ old('payment_method', $payment->payment_method) === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Check" {{ old('payment_method', $payment->payment_method) === 'Check' ? 'selected' : '' }}>Check</option>
                                <option value="Online Payment" {{ old('payment_method', $payment->payment_method) === 'Online Payment' ? 'selected' : '' }}>Online Payment</option>
                            </select>
                            @error('payment_method')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="transaction_id" class="col-md-3 col-form-label">Transaction ID</label>
                        <div class="col-md-9">
                            <input id="transaction_id" type="text" class="form-control @error('transaction_id') is-invalid @enderror" 
                                name="transaction_id" value="{{ old('transaction_id', $payment->transaction_id) }}" placeholder="e.g. TRX-123456">
                            @error('transaction_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="payment_date" class="col-md-3 col-form-label">Payment Date *</label>
                        <div class="col-md-9">
                            <input id="payment_date" type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                name="payment_date" value="{{ old('payment_date', $payment->paid_at ? date('Y-m-d', strtotime($payment->paid_at)) : date('Y-m-d') }}" required>
                            @error('payment_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="status" class="col-md-3 col-form-label">Status *</label>
                        <div class="col-md-9">
                            <select id="status" class="form-select @error('status') is-invalid @enderror" name="status" required>
                                <option value="completed" {{ old('status', $payment->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="pending" {{ old('status', $payment->status) === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="failed" {{ old('status', $payment->status) === 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="notes" class="col-md-3 col-form-label">Notes</label>
                        <div class="col-md-9">
                            <textarea id="notes" class="form-control @error('notes') is-invalid @enderror" 
                                name="notes" rows="3">{{ old('notes', $payment->meta['notes'] ?? '') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-9 offset-md-3">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fa fa-save"></i> Update Payment
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="fa fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </form>

                <form id="delete-form" method="POST" action="{{ route('payments.destroy', $payment->id) }}" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        </div>
    </div>

    <x-footer />
</x-app-layout>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>