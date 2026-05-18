<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nota de Venta #{{ $order->voucher_number ?? $order->id }}</title>
<style>
    @page {
        size: A5;
        margin: 10mm 12mm;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 10px;
        color: #1a1a1a;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 2px solid #000;
        padding-bottom: 8px;
        margin-bottom: 10px;
    }
    .empresa h1 { font-size: 15px; font-weight: 800; text-transform: uppercase; }
    .empresa p  { font-size: 9px; color: #555; margin-top: 1px; }

    .nota-box {
        border: 2px solid #000;
        padding: 6px 10px;
        text-align: center;
        min-width: 100px;
    }
    .nota-box .titulo { font-size: 12px; font-weight: 800; text-transform: uppercase; }
    .nota-box .nro    { font-size: 11px; font-weight: 700; margin-top: 2px; }
    .nota-box .fecha  { font-size: 9px; color: #444; margin-top: 3px; }

    .info-row {
        display: flex;
        gap: 4px;
        margin-bottom: 3px;
        font-size: 10px;
    }
    .info-row .lbl { font-weight: 700; min-width: 55px; }

    .divider { border-top: 1px dashed #555; margin: 8px 0; }

    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    thead tr { background: #111; color: #fff; }
    thead th { padding: 4px 6px; font-size: 9px; text-transform: uppercase; }
    thead th.right { text-align: right; }
    tbody td { padding: 4px 6px; border-bottom: 1px solid #ddd; font-size: 10px; }
    tbody td.right  { text-align: right; }
    tbody td.center { text-align: center; }

    .totals { width: 140px; margin-left: auto; }
    .totals tr td { padding: 3px 6px; }
    .totals tr td:first-child { font-weight: 700; }
    .totals tr td:last-child  { text-align: right; }
    .totals .total-final td { font-size: 12px; font-weight: 800; border-top: 2px solid #000; padding-top: 5px; }

    .footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 6px; font-size: 9px; color: #666; display: flex; justify-content: space-between; }

    @media print {
        body { margin: 0; }
        .no-print { display: none; }
    }
</style>
</head>
<body>

<div class="no-print" style="padding:6px; background:#f0f0f0; margin-bottom:8px; text-align:center;">
    <button onclick="window.print()" style="padding:5px 16px; cursor:pointer; font-size:11px;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:5px 12px; cursor:pointer; font-size:11px; margin-left:6px;">✕ Cerrar</button>
</div>

{{-- Header --}}
<div class="header">
    <div class="empresa">
        <h1>{{ $order->company->name }}</h1>
        @if($order->branch->address)<p>{{ $order->branch->address }}</p>@endif
        @if($order->branch->phone)<p>Tel: {{ $order->branch->phone }}</p>@endif
        <p style="margin-top:3px;">Sede: <strong>{{ $order->branch->name }}</strong></p>
    </div>
    <div class="nota-box">
        <div class="titulo">Nota de Venta</div>
        @if($order->voucher_number)
        <div class="nro">{{ $order->voucher_number }}</div>
        @else
        <div class="nro">#{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
        @endif
        <div class="fecha">{{ $order->created_at->format('d/m/Y H:i') }}</div>
    </div>
</div>

{{-- Info cliente + vendedor --}}
<div class="info-row">
    <span class="lbl">Cliente:</span>
    <span>{{ $order->customer_name ?: 'Cliente general' }}</span>
</div>
@if($order->customer_document)
<div class="info-row">
    <span class="lbl">{{ $order->documentType?->name ?? 'Doc' }}:</span>
    <span>{{ $order->customer_document }}</span>
</div>
@endif
<div class="info-row">
    <span class="lbl">Vendedor:</span>
    <span>{{ $order->employee->name }}</span>
</div>
<div class="info-row">
    <span class="lbl">Pago:</span>
    <span>{{ $paymentLabel }}{{ $order->operation_number ? ' — Op: ' . $order->operation_number : '' }}</span>
</div>

<div class="divider"></div>

{{-- Items --}}
<table>
    <thead>
        <tr>
            <th style="width:9%">Cant</th>
            <th>Descripción</th>
            <th class="right" style="width:15%">P.Unit</th>
            <th class="right" style="width:16%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td class="center">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td>{{ $item->product_name }}</td>
            <td class="right">S/ {{ number_format($item->unit_price, 2) }}</td>
            <td class="right">S/ {{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- Totales --}}
<table class="totals">
    @if($order->igv > 0)
    <tr>
        <td>Subtotal</td>
        <td>S/ {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    <tr>
        <td>IGV</td>
        <td>S/ {{ number_format($order->igv, 2) }}</td>
    </tr>
    @endif
    <tr class="total-final">
        <td>TOTAL</td>
        <td>S/ {{ number_format($order->total, 2) }}</td>
    </tr>
</table>

{{-- Footer --}}
<div class="footer">
    <span>Documento interno — no válido como comprobante tributario</span>
    <span>{{ $order->company->name }}</span>
</div>

</body>
</html>
