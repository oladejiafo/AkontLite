@component('mail::message')
# Payment Receipt

Dear {{ $receipt->customer_name ?? 'Customer' }},

Thank you for your payment. Please find attached your receipt.

**Amount:** {{ $receipt->currency }} {{ number_format($receipt->total_amount, 2) }}
**Date:** {{ $receipt->receipt_date }}

@if($showWatermark)
---
*This receipt was created with AkɔntLite*
@endif

Thanks,<br>
{{ config('app.name') }}
@endcomponent