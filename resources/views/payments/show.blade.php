<x-app-layout>
    <x-header />
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Payment Details</h2>
            <div>
                <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-primary me-2">
                    <i class="fa fa-edit"></i> Edit
                </a>
                <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> Back to Payments
                </a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Invoice Number:</div>
                    <div class="col-md-8">
                        <a href="{{ route('invoices.show', $payment->invoice_id) }}">
                            {{ $payment->invoice->invoice_number }}
                        </a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Customer:</div>
                    <div class="col-md-8">{{ $payment->invoice->customer_name }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Invoice Amount:</div>
                    <div class="col-md-8">
                        {{ $payment->invoice->currency }} {{ number_format($payment->invoice->total_amount, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Payment Details</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Payment Amount:</div>
                    <div class="col-md-8">
                        {{ $payment->invoice->currency }} {{ number_format($payment->amount, 2) }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Payment Method:</div>
                    <div class="col-md-8">{{ $payment->payment_method }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Transaction ID:</div>
                    <div class="col-md-8">{{ $payment->transaction_id ?? 'N/A' }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Payment Date:</div>
                    <div class="col-md-8">
                        {{ $payment->paid_at ? \Carbon\Carbon::parse($payment->paid_at)->format('M d, Y') : 'N/A' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Status:</div>
                    <div class="col-md-8">
                        <span class="badge bg-{{ $payment->status === 'completed' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Recorded By:</div>
                    <div class="col-md-8">{{ $payment->user->name }}</div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Created At:</div>
                    <div class="col-md-8">
                        {{ $payment->created_at->format('M d, Y h:i A') }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4 fw-bold">Last Updated:</div>
                    <div class="col-md-8">
                        {{ $payment->updated_at->format('M d, Y h:i A') }}
                    </div>
                </div>

                @if(!empty($payment->meta['notes']))
                <div class="row">
                    <div class="col-md-4 fw-bold">Notes:</div>
                    <div class="col-md-8">
                        <div class="border p-3 bg-light rounded">
                            {!! nl2br(e($payment->meta['notes'])) !!}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <form method="POST" action="{{ route('payments.destroy', $payment->id) }}" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fa fa-trash"></i> Delete Payment
                </button>
            </form>
        </div>
    </div>

    <x-footer />
</x-app-layout>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
        event.preventDefault();
        document.querySelector('form[action="{{ route(\'payments.destroy\', $payment->id) }}"]').submit();
    }
}
</script>