<!DOCTYPE html>
<html><head><style>
body { font-family: sans-serif; font-size: 12px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
th { background: #2dc4b6; color: #fff; }
</style></head><body>
<h2>Expenses Report</h2>
<p>{{ $from }} to {{ $to }}</p>
<table>
<thead><tr><th>Receipt #</th><th>Vendor</th><th>Date</th><th>Total</th></tr></thead>
<tbody>
@foreach($receipts as $r)
<tr>
<td>{{ $r->receipt_number }}</td>
<td>{{ $r->vendor_name }}</td>
<td>{{ $r->receipt_date }}</td>
<td>{{ number_format($r->total_amount, 2) }} {{ $r->currency }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>