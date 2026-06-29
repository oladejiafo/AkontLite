@component('mail::message')
# Hello {{ $invoice->customer->name }}

Here is your invoice **#{{ $invoice->invoice_number }}**  
Amount Due: **{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}**

@component('mail::button', ['url' => route('invoices.show', $invoice->id)])
View Invoice
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
