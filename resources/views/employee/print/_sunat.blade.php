{{-- Representación impresa SUNAT para tickets: QR, hash y estado del envío.
     Variables: $order, $qrSvg (null si no es boleta/factura electrónica), $voucherLabel, $qrWidth (px) --}}
@if($qrSvg)
<style>
    .qr-box svg { width: 100%; height: auto; display: block; }
</style>
<div class="divider"></div>
<div style="text-align:center;">
    <div class="qr-box" style="width: {{ $qrWidth ?? 100 }}px; margin: 4px auto 3px;">{!! $qrSvg !!}</div>
    <div style="font-size:9px;">Representación impresa de la {{ $voucherLabel }} electrónica</div>
    @if($order->sunat_hash)
    <div style="font-size:8px; word-break:break-all; margin-top:2px;">Hash: {{ $order->sunat_hash }}</div>
    @endif
    @if($order->sunat_status === \App\Models\Order::SUNAT_ACCEPTED)
    <div style="font-size:9px; margin-top:2px;">Aceptado por SUNAT</div>
    @elseif($order->sunat_status !== \App\Models\Order::SUNAT_NOT_APPLICABLE)
    <div style="font-size:9px; margin-top:2px;">Pendiente de envío a SUNAT</div>
    @endif
</div>
@endif
