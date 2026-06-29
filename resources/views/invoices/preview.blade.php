@extends('layouts.app')
@section('content')
<div class="container">
    <h1 class="mb-4">Invoice Preview</h1>
    @include('invoices._invoice_details', ['invoice' => $invoice])
    <div class="mt-3">
        <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary">Download PDF</a>
        <a href="{{ route('invoices.edit', $invoice->id) }}" class="btn btn-secondary">Edit Invoice</a>
    </div>
</div>
@endsection