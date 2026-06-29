<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header, .footer { text-align: center; margin-bottom: 20px; }
        .items table { width: 100%; border-collapse: collapse; }
        .items th, .items td { border: 1px solid #000; padding: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $invoice->sender_company_name }}</h2>
        <p>{{ $invoice->sender_company_email }} | {{ $invoice->sender_company_address }}</p>
    </div>
    <div>
        <strong>Invoice #{{ $invoice->invoice_number }}</strong><br>
        Issue Date: {{ $invoice->issue_date }}<br>
        Due Date: {{ $invoice->due_date }}<br>
    </div>
    <hr>
    <div>
        <strong>Billed To:</strong><br>
        @if($invoice->customer)
            {{ $invoice->customer->name }}<br>
            {{ $invoice->customer->email }}<br>
            {!! nl2br(e($invoice->customer->address)) !!}
        @else
            {{ $invoice->customer_name }}<br>
            {{ $invoice->customer_email }}<br>
            {!! nl2br(e($invoice->customer_address)) !!}
        @endif
    </div>
    <hr>
    <div class="items">
        <table>
            <thead>
                <tr>
                    <th>Description</th><th>Qty</th><th>Unit Price</th><th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Total: {{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</strong></p>
    </div>
</body>
</html>