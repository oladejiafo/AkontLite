@if($qrCode)
<div class="qr-section">
    <div class="qr-box">
        {{-- QR rendered as a simple placeholder; actual QR image generated client-side --}}
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ urlencode($qrCode) }}" 
             width="80" height="80" alt="E-Invoice QR" />
    </div>
    <div class="qr-meta">
        <p class="qr-label">E-Invoice QR Code</p>
        @if(isset($standard) && $standard === 'ZATCA')
        <p class="qr-desc">ZATCA Compliant</p>
        @elseif(isset($standard) && $standard === 'FIRS')
        <p class="qr-desc">FIRS Compliant</p>
        @endif
    </div>
</div>
@endif