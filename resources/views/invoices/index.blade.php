<x-app-layout>
<x-header />

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Invoices</h2>
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">
                <i class="fa fa-plus-circle"></i> New Invoice
            </a>
        </div>

        @if ($invoices->count()) 
            <div class="table-responsive shadow-sm">
                <table class="table table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Invoice Number</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th class="text-end"> </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoices as $invoice)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer->name }}</td>
                                <td>
                                    <span class="badge p-2 bg-{{ 
                                        $invoice->status === 'paid' ? 'success' : 
                                        ($invoice->status === 'overdue' ? 'danger' : 'secondary') 
                                    }}">
                                        {{ ucfirst($invoice->status) }}
                                    </span>
                                </td>
                                <td>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</td>
                                <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                                <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                                <td>  
                                    <div class="btn-group dropstart">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" title="See actions like View, Download Edit and more" data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions <i class="fas fa-caret-down ms-1"></i>
                                        </button>

                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.show', $invoice->id) }}">View</a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.download', $invoice->id) }}">Download PDF</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('invoices.email', $invoice->id) }}" method="POST">
                                                    @csrf
                                                    <button class="dropdown-item" type="submit">Email Invoice</button>
                                                </form>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="{{ route('invoices.edit', $invoice->id) }}">Edit</a>
                                            </li>
                                            <li>
                                                <form action="{{ route('invoices.destroy', $invoice->id) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="dropdown-item text-danger" type="submit">Delete</button>
                                                </form>
                                            </li>
                                            @if ($invoice->status !== 'paid' && $invoice->payment)
                                                <li>
                                                    <form action="{{ route('invoices.mark-as-paid', $invoice->id) }}" method="POST" onsubmit="return confirm('Are you sure, this has been paid?')">
                                                        @csrf
                                                        <button class="dropdown-item text-success" type="submit">Mark as Paid</button>
                                                    </form>
                                                </li>
                                            @elseif ($invoice->status !== 'paid' && !$invoice->payment)
                                                <li>
                                                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]) }}" class="dropdown-item text-warning">
                                                        Record Payment
                                                    </a>
                                                </li>
                                            @endif

                                        </ul>
                                    </div>

                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $invoices->links() }} {{-- If using pagination --}}
            </div>

        @else
            <div class="alert alert-info">
                You have no invoices yet. Start by creating your first invoice.
            </div>
        @endif

    </div>
    <x-footer />
</x-app-layout>
