<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{{ $voucherLabel }} {{ $order->voucher_number ?? '#'.$order->id }}</title>
<style>
    @page {
        size: A4;
        margin: 15mm 15mm 20mm;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 11px;
        color: #1a1a1a;
    }

    /* ── Header principal ── */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding-bottom: 12px;
        border-bottom: 2px solid #2563eb;
        margin-bottom: 14px;
    }

    .empresa-info h1 {
        font-size: 18px;
        font-weight: 800;
        color: #1e3a8a;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .empresa-info .ruc-line {
        font-size: 11px;
        font-weight: 700;
        color: #1e3a8a;
        margin-top: 2px;
    }
    .empresa-info p { color: #555; margin-top: 2px; font-size: 10px; }

    /* ── Caja del comprobante (esquina derecha) ── */
    .voucher-box {
        text-align: center;
        border: 2px solid #2563eb;
        padding: 10px 14px;
        border-radius: 6px;
        min-width: 160px;
    }
    .voucher-box .ruc-emisor {
        font-size: 10px;
        font-weight: 700;
        color: #555;
        margin-bottom: 4px;
    }
    .voucher-box .tipo  { font-size: 13px; font-weight: 800; color: #1e3a8a; text-transform: uppercase; }
    .voucher-box .nro   { font-size: 13px; font-weight: 700; color: #111; margin-top: 2px; letter-spacing: .5px; }
    .voucher-box .fecha { font-size: 10px; color: #555; margin-top: 4px; }

    /* ── Cliente ── */
    .cliente-section {
        background: #f8faff;
        border: 1px solid #dbeafe;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 14px;
    }
    .cliente-section h3 {
        font-size: 10px;
        font-weight: 700;
        color: #2563eb;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 6px;
    }
    .cliente-grid { display: grid; grid-template-columns: 140px 1fr; gap: 4px 12px; }
    .cliente-grid .lbl { font-weight: 600; color: #444; }
    .cliente-grid .val { color: #111; }

    /* ── Tabla de ítems ── */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 14px;
    }
    .items-table thead tr {
        background: #1e3a8a;
        color: #fff;
    }
    .items-table thead th {
        padding: 7px 8px;
        text-align: left;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .items-table thead th.right { text-align: right; }
    .items-table thead th.center { text-align: center; }

    .items-table tbody tr:nth-child(even) { background: #f0f4ff; }
    .items-table tbody td {
        padding: 6px 8px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 11px;
    }
    .items-table tbody td.right  { text-align: right; }
    .items-table tbody td.center { text-align: center; }

    /* ── Totales + QR ── */
    .bottom-section {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 16px;
        gap: 16px;
    }

    /* QR code */
    .qr-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    .qr-section canvas { border: 1px solid #dbeafe; border-radius: 4px; }
    .qr-section .qr-label {
        font-size: 8px;
        color: #777;
        text-align: center;
        max-width: 100px;
        line-height: 1.3;
    }

    /* Totales */
    .totals-box {
        border: 1px solid #dbeafe;
        border-radius: 6px;
        overflow: hidden;
        min-width: 200px;
    }
    .totals-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 12px;
        font-size: 11px;
        border-bottom: 1px solid #e5e7eb;
    }
    .totals-row:last-child { border-bottom: none; }
    .totals-row.gran-total {
        background: #1e3a8a;
        color: #fff;
        font-size: 13px;
        font-weight: 800;
        padding: 8px 12px;
    }
    .totals-row .lbl { font-weight: 600; }

    /* ── Pago ── */
    .pago-section { margin-bottom: 14px; }
    .pago-badge {
        display: inline-block;
        background: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 10px;
        font-weight: 700;
    }

    /* ── Nota legal (identificación adquirente) ── */
    .legal-note {
        font-size: 9px;
        color: #777;
        border-top: 1px dashed #cbd5e1;
        padding-top: 6px;
        margin-bottom: 10px;
        font-style: italic;
    }

    /* ── Footer ── */
    .footer {
        border-top: 1px solid #e5e7eb;
        padding-top: 10px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        font-size: 9px;
        color: #777;
    }
    .footer .agradecimiento { font-size: 11px; font-weight: 700; color: #1e3a8a; }

    @media print {
        body { margin: 0; }
        .no-print { display: none; }
    }
</style>
</head>
<body>

{{-- Botones de acción (no salen en papel) --}}
<div class="no-print" style="padding:8px; background:#f0f0f0; margin-bottom:10px; text-align:center;">
    <button onclick="window.print()" style="padding:6px 20px; cursor:pointer; font-size:12px; background:#1e3a8a; color:#fff; border:none; border-radius:4px;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:6px 16px; cursor:pointer; font-size:12px; margin-left:8px; border-radius:4px; border:1px solid #ccc;">✕ Cerrar</button>
</div>

{{-- ── Header ── --}}
<div class="header">

    {{-- Datos del emisor --}}
    <div class="empresa-info">
        <h1>{{ $order->company->name }}</h1>
        @if($order->company->ruc)
        <div class="ruc-line">RUC: {{ $order->company->ruc }}</div>
        @endif
        @if($order->company->address)
        <p>📍 {{ $order->company->address }}</p>
        @elseif($order->branch->address)
        <p>📍 {{ $order->branch->address }}</p>
        @endif
        @if($order->branch->phone)
        <p>📞 {{ $order->branch->phone }}</p>
        @endif
        @if($order->branch->email)
        <p>✉ {{ $order->branch->email }}</p>
        @endif
        <p style="margin-top:4px; color:#555;">Sede: <strong>{{ $order->branch->name }}</strong></p>
    </div>

    {{-- Caja del comprobante --}}
    <div class="voucher-box">
        @if($order->company->ruc)
        <div class="ruc-emisor">RUC {{ $order->company->ruc }}</div>
        @endif
        <div class="tipo">{{ $voucherLabel }}</div>
        @if($order->voucher_number)
        <div class="nro">{{ $order->voucher_number }}</div>
        @else
        <div class="nro">#{{ str_pad($order->id, 8, '0', STR_PAD_LEFT) }}</div>
        @endif
        <div class="fecha">{{ $order->created_at->format('d/m/Y') }}</div>
    </div>

</div>

{{-- ── Datos del adquirente ── --}}
<div class="cliente-section">
    <h3>Datos del adquirente</h3>
    <div class="cliente-grid">
        <span class="lbl">Nombre / Razón social:</span>
        <span class="val">{{ $order->customer_name ?: 'Cliente general' }}</span>

        @if($order->customer_document)
        <span class="lbl">{{ $order->documentType?->name ?? 'Documento' }}:</span>
        <span class="val">{{ $order->customer_document }}</span>
        @endif

        <span class="lbl">Fecha de emisión:</span>
        <span class="val">{{ $order->created_at->format('d/m/Y H:i') }}</span>

        <span class="lbl">Forma de pago:</span>
        <span class="val">{{ $paymentLabel }}{{ $order->operation_number ? ' — Op. ' . $order->operation_number : '' }}</span>
    </div>
</div>

{{-- ── Ítems ── --}}
<table class="items-table">
    <thead>
        <tr>
            <th class="center" style="width:8%">Cant.</th>
            <th class="center" style="width:6%">Und.</th>
            <th style="width:10%">Código</th>
            <th>Descripción del bien / servicio</th>
            <th class="right" style="width:13%">P. Unitario</th>
            <th class="right" style="width:13%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        @foreach($order->items as $item)
        <tr>
            <td class="center">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}</td>
            <td class="center" style="font-size:10px; color:#555;">NIU</td>
            <td class="center" style="font-size:10px; color:#555;">{{ $item->product_code }}</td>
            <td>{{ $item->product_name }}</td>
            <td class="right">S/ {{ number_format($item->unit_price, 2) }}</td>
            <td class="right">S/ {{ number_format($item->subtotal, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Totales + QR ── --}}
<div class="bottom-section">

    {{-- QR SUNAT --}}
    <div class="qr-section">
        <canvas id="qrCanvas"></canvas>
        <div class="qr-label">Representación impresa del comprobante electrónico</div>
    </div>

    {{-- Totales --}}
    <div class="totals-box">
        @if($order->igv > 0)
        <div class="totals-row">
            <span class="lbl">Op. Gravadas</span>
            <span>S/ {{ number_format($order->subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span class="lbl">IGV (18%)</span>
            <span>S/ {{ number_format($order->igv, 2) }}</span>
        </div>
        @else
        <div class="totals-row">
            <span class="lbl">Op. Exoneradas</span>
            <span>S/ {{ number_format($order->total, 2) }}</span>
        </div>
        @endif
        <div class="totals-row gran-total">
            <span>IMPORTE TOTAL</span>
            <span>S/ {{ number_format($order->total, 2) }}</span>
        </div>
    </div>

</div>

{{-- ── Pago ── --}}
<div class="pago-section">
    <span class="pago-badge">✓ Pagado con {{ $paymentLabel }}</span>
</div>

{{-- Nota legal: identificación del adquirente cuando total > 700 --}}
@if($order->total > 700 && !$order->customer_document)
<div class="legal-note">
    ⚠ Según normativa SUNAT, para operaciones mayores a S/ 700.00 se requiere identificar al adquirente con número de DNI o RUC.
</div>
@endif

{{-- ── Footer ── --}}
<div class="footer">
    <div>
        <div class="agradecimiento">¡Gracias por su preferencia!</div>
        <div>Documento generado el {{ now()->format('d/m/Y H:i') }}</div>
        <div style="margin-top:3px; font-style:italic;">Representación impresa de la {{ $voucherLabel }}</div>
    </div>
    <div style="text-align:right;">
        @if($order->company->ruc)
        <div>RUC: {{ $order->company->ruc }}</div>
        @endif
        <div>{{ $order->company->name }}</div>
        <div>{{ $order->branch->name }}</div>
    </div>
</div>

{{-- ── QR Code (generado en cliente, no requiere red externa) ── --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    /**
     * Contenido del QR según estructura recomendada SUNAT:
     * RUC|TIPO|SERIE-CORRELATIVO|IGV|TOTAL|FECHA|TIPO_DOC_ADQUIRENTE|NRO_DOC_ADQUIRENTE|
     */
    var qrData = [
        "{{ $order->company->ruc ?? '' }}",
        "{{ $order->voucher_type }}",
        "{{ $order->voucher_number ?? $order->id }}",
        "{{ number_format($order->igv, 2) }}",
        "{{ number_format($order->total, 2) }}",
        "{{ $order->created_at->format('Y-m-d') }}",
        "{{ $order->documentType?->code ?? '0' }}",
        "{{ $order->customer_document ?? '' }}"
    ].join('|') + '|';

    new QRCode(document.getElementById('qrCanvas'), {
        text: qrData,
        width: 100,
        height: 100,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
</script>

</body>
</html>
