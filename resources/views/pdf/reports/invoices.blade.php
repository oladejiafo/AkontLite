<!DOCTYPE html>
<html><head><style>
body { font-family: sans-serif; font-size: 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background: #2dc4b6; color: #fff; }
</style></head><body>
<h2>Invoices Report</h2>
<p>{{ $from }} to {{ $to }}</p>
<table>
<thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
<tbody>
@foreach($invoices as $inv)
<tr>
<td>{{ $inv->invoice_number }}</td>
<td>{{ $inv->issue_date }}</td>
<td>{{ number_format($inv->total_amount, 2) }} {{ $inv->currency }}</td>
<td>{{ ucfirst($inv->status) }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>