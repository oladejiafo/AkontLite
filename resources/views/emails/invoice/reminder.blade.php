@component('mail::message')
# Reminder: Invoice #{{ $invoice->invoice_number }}

This is a reminder that your invoice is still unpaid.

- **Amount**: {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}
- **Due Date**: {{ $invoice->due_date->format('M d, Y') }}

@component('mail::button', ['url' => route('invoices.show', $invoice->id)])
Pay Invoice
@endcomponent

Thank you,<br>
{{ $invoice->sender_company_name ?? config('app.name') }}
@endcomponent
