<x-app-layout>
    <x-header />
    
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Create Payment</h2>
            <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                <i class="fa fa-arrow-left"></i> Back to Payments
            </a>
        </div>

        <form method="POST" action="{{ route('payments.store') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">

            <!-- Customer Section (Updated to match invoice style) -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Client Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Select Existing Client *</label>
                        <select class="form-select @error('customer_id') is-invalid @enderror" 
                                id="customer_id" name="customer_id" required>
                            <option value="">-- Select Client --</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    data-name="{{ $customer->name }}"
                                    data-email="{{ $customer->email }}"
                                    data-address="{{ $customer->address }}"
                                    {{ old('customer_id', $invoice?->customer_id) == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="client_name" class="form-label">Client Name *</label>
                        <input type="text" class="form-control @error('client_name') is-invalid @enderror" 
                               id="client_name" name="client_name" value="{{ old('client_name', $invoice?->customer_name) }}" 
                               required>
                        @error('client_name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="client_email" class="form-label">Client Email</label>
                        <input type="email" class="form-control @error('client_email') is-invalid @enderror" 
                               id="client_email" name="client_email" value="{{ old('client_email', $invoice?->customer_email) }}">
                        @error('client_email')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="client_address" class="form-label">Client Address</label>
                        <textarea class="form-control @error('client_address') is-invalid @enderror" 
                                  id="client_address" name="client_address">{{ old('client_address', $invoice?->customer_address) }}</textarea>
                        @error('client_address')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Invoice Selection Section -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Invoice Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="invoice_id" class="form-label">Select Invoice (Optional)</label>
                        <select id="invoice_id" class="form-select @error('invoice_id') is-invalid @enderror" 
                                name="invoice_id">
                            <option value="">-- Create New Invoice --</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}" 
                                    data-total-amount="{{ $invoice->total_amount }}"
                                    data-paid-amount="{{ $invoice->paid_amount ?? 0 }}"
                                    {{ old('invoice_id', $invoiceId) == $invoice->id ? 'selected' : '' }}>
                                    {{ $invoice->invoice_number }} - {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('invoice_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>

                    <div class="row mb-3">
                        <label class="col-md-4 col-form-label">Invoice Amount</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control bg-light" id="invoice_amount_display" 
                                   value="{{ old('total_amount', $invoice?->total_amount ? $invoice->currency . ' ' . number_format($invoice->total_amount, 2) : '') }}" readonly>
                            <input type="hidden" id="invoice_amount" value="{{ old('total_amount', $invoice?->total_amount) }}">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label class="col-md-4 col-form-label">Already Paid</label>
                        <div class="col-md-8">
                            <input type="text" class="form-control bg-light" id="paid_amount_display" 
                                   value="{{ old('paid_amount', $invoice?->paid_amount ? $invoice->currency . ' ' . number_format($invoice->paid_amount ?? 0, 2) : '') }}" readonly>
                            <input type="hidden" id="paid_amount" value="{{ old('paid_amount', $invoice?->paid_amount ?? 0) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <label for="amount" class="col-md-4 col-form-label">Payment Amount *</label>
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">{{ $invoice?->currency ?? 'USD' }}</span>
                                <input id="amount" type="number" class="form-control @error('amount') is-invalid @enderror" 
                                    name="amount" value="{{ old('amount') }}" required step="0.01" min="0.01">
                                @error('amount')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="payment_method" class="col-md-4 col-form-label">Payment Method *</label>
                        <div class="col-md-8">
                            <select id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" name="payment_method" required>
                                <option value="">Select Payment Method</option>
                                <option value="Cash" {{ old('payment_method') === 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Credit Card" {{ old('payment_method') === 'Credit Card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="Bank Transfer" {{ old('payment_method') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Check" {{ old('payment_method') === 'Check' ? 'selected' : '' }}>Check</option>
                                <option value="Online Payment" {{ old('payment_method') === 'Online Payment' ? 'selected' : '' }}>Online Payment</option>
                            </select>
                            @error('payment_method')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="transaction_id" class="col-md-4 col-form-label">Transaction ID</label>
                        <div class="col-md-8">
                            <input id="transaction_id" type="text" class="form-control @error('transaction_id') is-invalid @enderror" 
                                name="transaction_id" value="{{ old('transaction_id') }}" placeholder="e.g. TRX-123456">
                            @error('transaction_id')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="payment_date" class="col-md-4 col-form-label">Payment Date *</label>
                        <div class="col-md-8">
                            <input id="payment_date" type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                            @error('payment_date')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="status" class="col-md-4 col-form-label">Status *</label>
                        <div class="col-md-8">
                            <select id="status" class="form-select @error('status') is-invalid @enderror" name="status" required>
                                <option value="completed" {{ old('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="failed" {{ old('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            </select>
                            @error('status')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-3">
                        <label for="notes" class="col-md-4 col-form-label">Notes</label>
                        <div class="col-md-8">
                            <textarea id="notes" class="form-control @error('notes') is-invalid @enderror" name="notes" rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fa fa-save"></i> Save Payment
                </button>
                <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

<!-- 
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update customer and amount fields when invoice changes
            const invoiceSelect = document.getElementById('invoice_id');
            if (invoiceSelect) {
                invoiceSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    if (selectedOption.value) {
                        document.getElementById('customer_id').value = selectedOption.dataset.customerId;
                        document.getElementById('customer_name').value = selectedOption.text.split('(')[1].split(')')[0];
                        document.getElementById('total_amount').value = selectedOption.text.split('-')[1].trim();
                        document.getElementById('paid_amount').value = selectedOption.dataset.paidAmount;
                    }
                });
            }

            // Initialize with selected invoice data if present
            if (invoiceSelect && invoiceSelect.value) {
                invoiceSelect.dispatchEvent(new Event('change'));
            }
        });
    </script> -->


<x-footer />
<script>
    // Function to reset form fields
    function resetForm() {
    document.getElementById("account_id").value = "";
    document.getElementById("date").value = "";
    document.getElementById("type").value = "";
    document.getElementById("transaction_name").value = "";
    document.getElementById("amount").value = "";
    document.getElementById("description").value = "";
    document.getElementById("source").value = "";
    document.getElementById("status").value = "";
    document.getElementById("to_account_id").value = "";
    }
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Customer selection handler
        document.getElementById('customer_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            document.getElementById('client_name').value = selectedOption.getAttribute('data-name') || '';
            document.getElementById('client_email').value = selectedOption.getAttribute('data-email') || '';
            document.getElementById('client_address').value = selectedOption.getAttribute('data-address') || '';
        });

        // Invoice selection handler
        document.getElementById('invoice_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currency = selectedOption.text.split('-')[1].trim().split(' ')[0] || 'USD';
            
            if (selectedOption.value) {
                document.getElementById('invoice_amount_display').value = 
                    currency + ' ' + selectedOption.getAttribute('data-total-amount');
                document.getElementById('paid_amount_display').value = 
                    currency + ' ' + selectedOption.getAttribute('data-paid-amount');
                
                document.getElementById('invoice_amount').value = 
                    selectedOption.getAttribute('data-total-amount');
                document.getElementById('paid_amount').value = 
                    selectedOption.getAttribute('data-paid-amount');
            } else {
                document.getElementById('invoice_amount_display').value = '';
                document.getElementById('paid_amount_display').value = '';
                document.getElementById('invoice_amount').value = '';
                document.getElementById('paid_amount').value = '';
            }
        });

        // Initialize fields if values are preselected
        if (document.getElementById('customer_id').value) {
            document.getElementById('customer_id').dispatchEvent(new Event('change'));
        }
        if (document.getElementById('invoice_id').value) {
            document.getElementById('invoice_id').dispatchEvent(new Event('change'));
        }
    });
</script>

</x-app-layout>
