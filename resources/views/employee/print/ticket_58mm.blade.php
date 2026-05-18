<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{{ $voucherLabel }} #{{ $order->voucher_number ?? $order->id }}</title>
<style>
    @page {
        size: 58mm auto;
        margin: 2mm 3mm;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: 'Courier New', Courier, monospace;
        font-size: 10px;
        color: #000;
        width: 52mm;
    }

    .center  { text-align: center; }
    .right   { text-align: right; }
    .bold    { font-weight: bold; }
    .small   { font-size: 9px; }

    .divider       { border-top: 1px dashed #000; margin: 3px 0; }
    .divider-solid { border-top: 1px solid #000;  margin: 3px 0; }

    .empresa-nombre { font-size: 13px; font-weight: bold; text-transform: uppercase; }
    .voucher-tipo   { font-size: 11px; font-weight: bold; margin: 3px 0 1px; }

    table { width: 100%; border-collapse: collapse; }
    td.qty   { width: 6mm;  text-align: right; }
    td.desc  { padding: 0 1mm; font-size: 9px; }
    td.price { width: 13mm; text-align: right; }
    td.total { width: 15mm; text-align: right; }

    .totals td:first-child { font-weight: bold; }
    .totals td:last-child  { text-align: right; }
    .totals .gran-total td { font-size: 12px; font-weight: bold; }

    .footer { text-align: center; margin-top: 5px; font-size: 9px; }

    @media print {
        body { margin: 0; }
        .no-print { display: none; }
    }
</style>
</head>
<body>

<div class="no-print" style="padding:5px; background:#f0f0f0; margin-bottom:6px; text-align:center;">
    <button onclick="window.print()" style="padding:4px 14px; cursor:pointer; font-size:11px;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:4px 10px; cursor:pointer; font-size:11px; margin-left:4px;">✕</button>
</div>

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

<div class="center">
    <div class="voucher-tipo">{{ strtoupper($voucherLabel) }}</div>
    @if($order->voucher_number)
    <div class="bold small">N° {{ $order->voucher_number }}</div>
    @endif
</div>

<div class="divider"></div>

<table>
    <tr><td class="bold">Fecha:</td><td class="right">{{ $order->created_at->format('d/m/Y H:i') }}</td></tr>
    @if($order->customer_name)
    <tr><td class="bold">Cliente:</td><td class="right small">{{ $order->customer_name }}</td></tr>
    @endif
    @if($order->customer_document)
    <tr><td class="bold small">{{ $order->documentType?->name ?? 'Doc' }}:</td><td class="right">{{ $order->customer_document }}</td></tr>
    @endif
</table>

<div class="divider"></div>

<table>
    <tr style="border-bottom:1px solid #000;">
        <td class="qty bold">Cnt</td>
        <td class="desc bold">Producto</td>
        <td class="total bold" style="text-align:right;">Total</td>
    </tr>
    @foreach($order->items as $item)
    <tr>
        <td class="qty">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
        <td class="desc">{{ $item->product_name }}</td>
        <td class="total">{{ number_format($item->subtotal, 2) }}</td>
    </tr>
    @endforeach
</table>

<div class="divider"></div>

<table class="totals">
    @if($order->igv > 0)
    <tr><td>Subtotal</td><td>S/ {{ number_format($order->subtotal, 2) }}</td></tr>
    <tr><td>IGV</td><td>S/ {{ number_format($order->igv, 2) }}</td></tr>
    @endif
    <tr class="gran-total">
        <td>TOTAL</td>
        <td>S/ {{ number_format($order->total, 2) }}</td>
    </tr>
    <tr><td class="small">{{ $paymentLabel }}</td><td></td></tr>
</table>

<div class="divider-solid"></div>

<div class="footer">
    ¡Gracias por su preferencia!
</div>

</body>
</html>
