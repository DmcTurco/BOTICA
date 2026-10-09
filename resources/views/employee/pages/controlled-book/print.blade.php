<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Libro de controlados {{ $month->format('Y-m') }}</title>
<style>
    @page { size: A4 landscape; margin: 12mm; }
    * { box-sizing: border-box; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    h2 { font-size: 12px; margin: 16px 0 4px; padding: 3px 0; border-bottom: 1px solid #333; }
    .sub { color: #555; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
    th, td { padding: 3px 5px; border: 1px solid #bbb; text-align: left; vertical-align: top; }
    th { background: #f0f0f0; font-size: 10px; text-transform: uppercase; }
    .c { text-align: center; } .b { font-weight: bold; }
    .firmas { display: flex; gap: 60px; margin-top: 40px; }
    .firmas div { flex: 1; border-top: 1px solid #333; text-align: center; padding-top: 3px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0'; @endphp

<div class="no-print" style="padding:8px; background:#f0f0f0; margin-bottom:10px; text-align:center;">
    <button onclick="window.print()" style="padding:5px 18px; cursor:pointer;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:5px 14px; cursor:pointer; margin-left:6px;">✕ Cerrar</button>
</div>

<h1>Libro de control de psicotrópicos y estupefacientes</h1>
<p class="sub">{{ $branch?->name }} · {{ $branch?->address }} · Período: {{ $month->translatedFormat('F Y') }}</p>

@forelse($sections as $s)
<h2>{{ $s['product']->name }} — {{ \App\Models\Product::CONTROLLED_LABELS[$s['product']->controlled_type] }} · Saldo inicial {{ $fmt($s['opening']) }} · Saldo final {{ $fmt($s['closing']) }}</h2>
<table>
    <thead><tr><th>Fecha</th><th>Documento</th><th>Paciente (documento)</th><th>Médico (CMP)</th><th>N° receta</th><th class="c">Entrada</th><th class="c">Salida</th><th class="c">Saldo</th></tr></thead>
    <tbody>
        @forelse($s['rows'] as $r)
        <tr>
            <td>{{ $r['date']->format('d/m/Y H:i') }}</td>
            <td>{{ $r['label'] }}</td>
            <td>@if($r['rx']){{ $r['rx']->patient_name }} ({{ $r['rx']->patient_document }})@endif</td>
            <td>@if($r['rx']){{ $r['rx']->doctor_name }} ({{ $r['rx']->doctor_license }})@endif</td>
            <td>{{ $r['rx']?->prescription_number }}</td>
            <td class="c">{{ $r['in'] ? $fmt($r['in']) : '' }}</td>
            <td class="c">{{ $r['out'] ? $fmt($r['out']) : '' }}</td>
            <td class="c b">{{ $fmt($r['balance']) }}</td>
        </tr>
        @empty
        <tr><td colspan="8" class="c">Sin movimientos en el mes</td></tr>
        @endforelse
    </tbody>
</table>
@empty
<p>No hay productos controlados registrados.</p>
@endforelse

<div class="firmas"><div>Químico farmacéutico — Director técnico</div><div>Fecha y sello</div></div>
</body>
</html>
