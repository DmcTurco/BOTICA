<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Movimiento de caja {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</title>
<style>
    @page { size: A4; margin: 14mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    h2 { font-size: 13px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #333; }
    .sub { color: #555; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    th, td { padding: 4px 6px; border-bottom: 1px solid #ddd; text-align: left; }
    th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
    .r { text-align: right; } .b { font-weight: bold; }
    .neg { color: #b91c1c; } .pos { color: #047857; }
    .firmas { display: flex; gap: 40px; margin-top: 50px; }
    .firmas div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 4px; font-size: 11px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="no-print" style="padding:8px; background:#f0f0f0; margin-bottom:10px; text-align:center;">
    <button onclick="window.print()" style="padding:5px 18px; cursor:pointer;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:5px 14px; cursor:pointer; margin-left:6px;">✕ Cerrar</button>
</div>

<h1>Movimiento de caja del {{ \Illuminate\Support\Carbon::parse($date)->format('d/m/Y') }}</h1>
<p class="sub">{{ $branch?->name }}</p>

@forelse($rows as $row)
@php $r = $row['register']; @endphp
<h2>Caja #{{ $r->id }} · {{ $r->employee?->name }} · {{ $r->status ? 'Abierta' : 'Cerrada' }}</h2>
<table>
    <tr><th>Concepto</th><th class="r">Monto</th></tr>
    <tr><td>Apertura</td><td class="r">S/ {{ number_format($r->opening_amount, 2) }}</td></tr>
    @foreach($labels as $type => $label)
    <tr><td>Ventas — {{ $label }}</td><td class="r">S/ {{ number_format($row['payments'][$type], 2) }}</td></tr>
    @endforeach
    <tr><td>Otros ingresos</td><td class="r pos">+ S/ {{ number_format($row['income'], 2) }}</td></tr>
    <tr><td>Gastos</td><td class="r neg">− S/ {{ number_format($row['expenses'], 2) }}</td></tr>
    <tr class="b"><td>Efectivo esperado</td><td class="r">S/ {{ number_format($row['expected'], 2) }}</td></tr>
    @if(!$r->status)
    <tr><td>Efectivo contado</td><td class="r">S/ {{ number_format($r->closing_amount ?? 0, 2) }}</td></tr>
    <tr class="b"><td>Diferencia</td><td class="r {{ ($r->difference ?? 0) < 0 ? 'neg' : '' }}">S/ {{ number_format($r->difference ?? 0, 2) }}</td></tr>
    @endif
</table>

@if($r->movements->isNotEmpty())
<table>
    <tr><th>Hora</th><th>Gasto / ingreso</th><th class="r">Monto</th></tr>
    @foreach($r->movements as $m)
    <tr><td>{{ $m->created_at->format('H:i') }}</td><td>{{ $m->concept }}</td><td class="r {{ $m->type === 'income' ? 'pos' : 'neg' }}">{{ $m->type === 'income' ? '+' : '−' }} S/ {{ number_format($m->amount, 2) }}</td></tr>
    @endforeach
</table>
@endif
@empty
<p>No hay cajas registradas en esta fecha.</p>
@endforelse

<div class="firmas"><div>Cajero</div><div>Administrador</div></div>
</body>
</html>
