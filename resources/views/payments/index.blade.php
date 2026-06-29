<x-app-layout>
    <x-header />
    <div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Payments</h2>
            <a href="{{ route('payments.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> Create Payment
            </a>
        </div>
    <div class="card p-3">
        <!-- 
        <form method="GET" action="{{ route('payments.index') }}">
            <div class="input-group search">
                <input type="text" name="search" class="form-control textinput" placeholder="Search for payments">
                <div class="input-group-append">
                <button type="submit" class="btn btn-info" style="border-radius:0 .5rem .5rem 0 !important">Search</button>
                </div>
            </div>
        </form>
        <br> -->

        <div class="table-responsive shadow-sm">
                <table class="table table-striped">
                    <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Paid Amount</th>
                        <th>Payment Date</th>
                        <th>Bank</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                    <tr>
                        <td class="align-middle">{{ $payment->customer ? $payment->customer->name : '' }}</td>
                        <td class="align-middle">{{ $payment->paid_amount }}</td>
                        <td class="align-middle">{{ $payment->payment_date }}</td>
                        <td class="align-middle">{{ $payment->bank ? $payment->bank->name : 'N/A' }}</td>
                        <td class="align-middle">
                            <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-info btn" title="View this record"><i class="fa fa-eye"></i></a>
                            <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-secondary btn" title="Modify this record"><i class="far fa-edit"></i></a>
                            <form id="deleteForm" action="{{ route('payments.destroy', $payment->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                {{-- <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this payment?')"  title="Delete this record"><i class="fa fa-trash"></i></button> --}}
                                <button type="button" class="btn btn-danger" onclick="showConfirmation('Are you sure you want to delete this payment record?', function() { document.getElementById('deleteForm').submit(); })"  title="Delete this record"><i class="fa fa-trash"></i></button>
                            </form>
                            @if(!$payment->invoice_id)
                                <a href="{{ route('payments.generate-invoice', $payment->id) }}" class="btn btn-info" title="Generate Invoice">
                                <i class="fa fa-download"></i>
                                </a>
                            @else
                                @php
                                    $invoiceExists = \App\Models\Invoice::find($payment->invoice_id);
                                @endphp

                                @if($invoiceExists)
                                    <a href="{{ route('invoices.show', $payment->invoice_id) }}" class="btn btn-primary" title="View Invoice">
                                        <i class="fa fa-file-invoice"></i>
                                    </a>
                                @else
                                    <span class="text-danger">No invoice</span>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if ($payments->isNotEmpty())
    <div class="row mb-3">
        <div class="col-md-3  d-flex align-items-center">
            <form id="perPageForm" method="GET" action="{{ route('payments.index') }}" class="form-inline">
                <label for="per_page" class="mr-2" style="font-size: 13px">Records per page:</label>
                <select name="per_page" id="per_page" class="form-control" style="width: 65px" onchange="document.getElementById('perPageForm').submit();">
                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                    <option value="75" {{ request('per_page') == 75 ? 'selected' : '' }}>75</option>
                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                </select>
            </form>
        </div>
        <div class="col-md-9 d-flex justify-content-end">
            <div class="pagination">
                {{ $payments->appends(['per_page' => request('per_page')])->links('vendor.pagination.bootstrap-4') }}
            </div>
        </div>
    </div>
    @endif
</div>
<x-footer />
</x-app-layout>
