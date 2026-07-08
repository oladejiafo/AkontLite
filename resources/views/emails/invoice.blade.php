@component('mail::message')
# Invoice {{ $invoice->invoice_number }}

Dear {{ $invoice->client_name ?? 'Customer' }},

Please find attached your invoice from **{{ $invoice->sender_company_name ?? config('app.name') }}**.

**Total Amount:** {{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}
**Due Date:** {{ $invoice->due_date }}

@component('mail::button', ['url' => route('invoices.show', $invoice->id)])
View Invoice
@endcomponent

@if($showWatermark)
---
*This invoice was created with AkɔntLite*
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent