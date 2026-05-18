<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{{ $voucherLabel }} #{{ $order->voucher_number ?? $order->id }}</title>
<style>
    @page {
        size: 80mm auto;
        margin: 3mm 4mm;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 11px;
        color: #000;
        width: 72mm;
    }

    .center  { text-align: center; }
    .right   { text-align: right; }
    .bold    { font-weight: bold; }
    .large   { font-size: 13px; }
    .small   { font-size: 10px; }

    .divider       { border-top: 1px dashed #000; margin: 4px 0; }
    .divider-solid { border-top: 1px solid #000;  margin: 4px 0; }

    .empresa-nombre { font-size: 15px; font-weight: bold; text-transform: uppercase; }
    .voucher-tipo   { font-size: 13px; font-weight: bold; margin: 4px 0 2px; }

    table { width: 100%; border-collapse: collapse; }
    .items-header td { font-weight: bold; padding: 2px 0; }
    .items-header    { border-top: 1px solid #000; border-bottom: 1px solid #000; margin: 3px 0; }

    td.qty   { width: 8mm;  text-align: right; }
    td.desc  { padding: 0 2mm; }
    td.price { width: 16mm; text-align: right; }
    td.total { width: 18mm; text-align: right; }

    .totals td { padding: 1px 0; }
    .totals td:first-child { font-weight: bold; }
    .totals td:last-child  { text-align: right; }
    .totals .gran-total td { font-size: 14px; font-weight: bold; }

    .footer { text-align: center; margin-top: 6px; font-size: 10px; }

    @media print {
        body { margin: 0; }
        .no-print { display: none; }
    }
</style>
</head>
<body>

{{-- Botón imprimir (no sale en papel) --}}
<div class="no-print" style="padding: 6px; background:#f0f0f0; margin-bottom:8px; text-align:center;">
    <button onclick="window.print()" style="padding:5px 18px; cursor:pointer; font-size:12px;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:5px 14px; cursor:pointer; font-size:12px; margin-left:6px;">✕ Cerrar</button>
</div>

{{-- Encabezado empresa --}}
<div class="center">
    <div class="empresa-nombre">{{ $order->company->name }}</div>
    @if($order->company->ruc)
    <div class="small bold">RUC: {{ $order->company->ruc }}</div>
    @endif
    @if($order->branch->address)
    <div class="small">{{ $order->branch->address }}</div>
    @endif
    @if($order->branch->phone)
    <div class="small">Tel: {{ $order->branch->phone }}</div>
    @endif
</div>

<div class="divider-solid"></div>

{{-- Tipo y número de comprobante --}}
<div class="center">
    <div class="voucher-tipo">{{ strtoupper($voucherLabel) }}</div>
    @if($order->voucher_number)
    <div class="bold">N° {{ $order->voucher_number }}</div>
    @endif
</div>

<div class="divider"></div>

{{-- Datos de la venta --}}
<table>
    <tr>
        <td class="bold">Fecha:</td>
        <td class="right">{{ $order->created_at->format('d/m/Y H:i') }}</td>
    </tr>
    <tr>
        <td class="bold">Cajero:</td>
        <td class="right">{{ $order->employee->name }}</td>
    </tr>
    @if($order->customer_name)
    <tr>
        <td class="bold">Cliente:</td>
        <td class="right">{{ $order->customer_name }}</td>
    </tr>
    @endif
    @if($order->customer_document)
    <tr>
        <td class="bold">{{ $order->documentType?->name ?? 'Doc' }}:</td>
        <td class="right">{{ $order->customer_document }}</td>
    </tr>
    @endif
</table>

<div class="divider"></div>

{{-- Items --}}
<table>
    <tr class="items-header">
        <td class="qty">Cant</td>
        <td class="desc">Descripción</td>
        <td class="price">P.Unit</td>
        <td class="total">Total</td>
    </tr>
    @foreach($order->items as $item)
    <tr>
        <td class="qty">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
        <td class="desc" style="font-size:10px;">{{ $item->product_name }}</td>
        <td class="price">{{ number_format($item->unit_price, 2) }}</td>
        <td class="total">{{ number_format($item->subtotal, 2) }}</td>
    </tr>
    @endforeach
</table>

<div class="divider"></div>

{{-- Totales --}}
<table class="totals">
    @if($order->igv > 0)
    <tr>
        <td>Subtotal</td>
        <td>S/ {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    <tr>
        <td>IGV (18%)</td>
        <td>S/ {{ number_format($order->igv, 2) }}</td>
    </tr>
    @endif
    <tr class="gran-total">
        <td>TOTAL</td>
        <td>S/ {{ number_format($order->total, 2) }}</td>
    </tr>
</table>

<div class="divider"></div>

{{-- Pago --}}
<table>
    <tr>
        <td class="bold">Pago:</td>
        <td class="right">{{ $paymentLabel }}</td>
    </tr>
    @if($order->operation_number)
    <tr>
        <td class="bold">Operación:</td>
        <td class="right">{{ $order->operation_number }}</td>
    </tr>
    @endif
</table>

<div class="divider-solid"></div>

<div class="footer">
    ¡Gracias por su preferencia!<br>
    {{ $order->branch->name }}
</div>

<script>
    // Impresión automática si la sede tiene auto_print activado
    window.addEventListener('load', function () {
        if (document.querySelector('.no-print')) return; // Solo auto si se cargó sin botones
    });
</script>
</body>
</html>
