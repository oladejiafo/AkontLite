@component('mail::message')
# Invoice Sent: {{ $invoice->title ?? 'Invoice' }}

Dear {{ $invoice->customer->name ?? 'Customer' }},

You've received a new invoice from **{{ $invoice->sender_company_name }}**.

@component('mail::button', ['url' => route('invoices.show', $invoice->id)])
View Invoice
@endcomponent

Thank you,<br>
{{ config('app.name') }}
@endcomponent
