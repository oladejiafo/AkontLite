<!DOCTYPE html>
<html><head><style>
body { font-family: sans-serif; font-size: 13px; }
.row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
</style></head><body>
<h2>VAT Summary</h2>
<p>{{ $from }} to {{ $to }}</p>
<div class="row"><span>Output VAT</span><span>{{ number_format($outputVat, 2) }} {{ $currency }}</span></div>
<div class="row"><span>Input VAT</span><span>{{ number_format($inputVat, 2) }} {{ $currency }}</span></div>
<div class="row"><strong>VAT Payable</strong><strong>{{ number_format($vatPayable, 2) }} {{ $currency }}</strong></div>
</body></html>