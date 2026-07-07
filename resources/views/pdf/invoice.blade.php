<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #222; background: #fff; }

  .page { padding: 40px; }

  /* Header */
  .header { display: flex; justify-content: space-between; margin-bottom: 32px; }
  .company-name { font-size: 22px; font-weight: bold; color: #2dc4b6; }
  .company-details { font-size: 11px; color: #666; margin-top: 4px; line-height: 1.6; }
  .invoice-meta { text-align: right; }
  .invoice-title { font-size: 28px; font-weight: bold; color: #111; letter-spacing: 2px; }
  .invoice-number { font-size: 13px; color: #555; margin-top: 6px; }
  .invoice-dates { font-size: 11px; color: #888; margin-top: 4px; line-height: 1.6; }

  /* Bill to */
  .bill-section { margin-bottom: 28px; }
  .bill-label { font-size: 10px; font-weight: bold; color: #888;
                text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
  .bill-name { font-size: 15px; font-weight: bold; color: #111; }
  .bill-email { font-size: 11px; color: #666; margin-top: 2px; }

  /* Items table */
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  thead th {
    background: #2dc4b6; color: #fff;
    padding: 10px 12px; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.5px;
    text-align: left;
  }
  thead th:last-child { text-align: right; }
  tbody td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; font-size: 12px; }
  tbody td:last-child { text-align: right; }
  tbody tr:nth-child(even) td { background: #f8fafc; }

  /* Totals */
  .totals { width: 260px; margin-left: auto; margin-bottom: 28px; }
  .totals-row {
    display: flex; justify-content: space-between;
    padding: 6px 0; font-size: 12px; border-bottom: 1px solid #f0f0f0;
  }
  .totals-row.grand {
    padding-top: 10px; border-bottom: none; border-top: 2px solid #2dc4b6;
  }
  .totals-row.grand .label { font-size: 15px; font-weight: bold; color: #111; }
  .totals-row.grand .value { font-size: 16px; font-weight: bold; color: #2dc4b6; }

  /* Notes */
  .notes { background: #f8fafc; border-left: 3px solid #2dc4b6;
           padding: 12px; border-radius: 4px; margin-bottom: 24px; }
  .notes-label { font-size: 10px; font-weight: bold; color: #888;
                 text-transform: uppercase; margin-bottom: 4px; }
  .notes-text { font-size: 11px; color: #555; line-height: 1.6; }

  /* QR section */
  .qr-section { display: flex; align-items: center; margin-bottom: 24px; }
  .qr-box { margin-right: 14px; }
  .qr-label { font-size: 11px; font-weight: bold; color: #333; }
  .qr-desc { font-size: 10px; color: #888; margin-top: 2px; }

  /* Footer */
  .footer { border-top: 1px solid #f0f0f0; padding-top: 16px;
            font-size: 10px; color: #aaa; text-align: center; }
  .watermark { color: #ddd; font-size: 10px; margin-top: 4px; }
</style>
</head>
<body>

<div class="page">

  <!-- Header -->
  <div class="header">
    <div>
      <div class="company-name">
        {{ $company?->name ?? config('app.name') }}
      </div>
      <div class="company-details">
        @if($company?->address) {{ $company->address }}<br> @endif
        @if($company?->city) {{ $company->city }}, {{ $company->country }}<br> @endif
        @if($company?->vat_number) VAT: {{ $company->vat_number }}<br> @endif
        @if($company?->email) {{ $company->email }} @endif
      </div>
    </div>
    <div class="invoice-meta">
      <div class="invoice-title">INVOICE</div>
      <div class="invoice-number">{{ $invoice->invoice_number }}</div>
      <div class="invoice-dates">
        Issued: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}<br>
        @if($invoice->due_date)
        Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
        @endif
      </div>
    </div>
  </div>

  <!-- Bill To -->
  @if($invoice->client_name || $invoice->client_email)
  <div class="bill-section">
    <div class="bill-label">Bill To</div>
    @if($invoice->client_name)
    <div class="bill-name">{{ $invoice->client_name }}</div>
    @endif
    @if($invoice->client_email)
    <div class="bill-email">{{ $invoice->client_email }}</div>
    @endif
  </div>
  @endif

  <!-- Items -->
  <table>
    <thead>
      <tr>
        <th style="width:50%">Description</th>
        <th style="text-align:center">Qty</th>
        <th style="text-align:right">Unit Price</th>
        <th style="text-align:right">Tax %</th>
        <th style="text-align:right">Amount</th>
      </tr>
    </thead>
    <tbody>
      @foreach($items as $item)
      <tr>
        <td>{{ $item->description }}</td>
        <td style="text-align:center">{{ $item->quantity }}</td>
        <td style="text-align:right">
          {{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}
        </td>
        <td style="text-align:center">{{ $item->tax_rate }}%</td>
        <td style="text-align:right">
          {{ $invoice->currency }} {{ number_format($item->total, 2) }}
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- Totals -->
  <div class="totals">
    <div class="totals-row">
      <span class="label">Subtotal</span>
      <span class="value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
    </div>
    @if($invoice->tax_amount > 0)
    <div class="totals-row">
      <span class="label">Tax</span>
      <span class="value">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span>
    </div>
    @endif
    @if($invoice->discount_amount > 0)
    <div class="totals-row">
      <span class="label">Discount</span>
      <span class="value" style="color:#16a34a">
        -{{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}
      </span>
    </div>
    @endif
    <div class="totals-row grand">
      <span class="label">Total</span>
      <span class="value">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</span>
    </div>
  </div>

  <!-- QR Code -->
  @include('pdf.partials.qr', ['qrCode' => $qrCode, 'standard' => $standard ?? 'none'])

  <!-- Notes -->
  @if($invoice->notes)
  <div class="notes">
    <div class="notes-label">Notes</div>
    <div class="notes-text">{{ $invoice->notes }}</div>
  </div>
  @endif

  <!-- Footer -->
  <div class="footer">
    Thank you for your business.
    @if(!$company)
    <div class="watermark">Generated by AkɔntLite</div>
    @endif
  </div>

</div>
</body>
</html>