<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiqueta {{ $production->batch }}</title>
<style>
    @page { size: 80mm auto; margin: 3mm 4mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #000; width: 72mm; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .empresa { font-size: 13px; font-weight: bold; text-transform: uppercase; }
    .nombre { font-size: 15px; font-weight: bold; margin: 4px 0 2px; }
    .divider { border-top: 1px dashed #000; margin: 5px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1.5px 0; vertical-align: top; }
    td:first-child { width: 24mm; font-weight: bold; }
    .lote { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
    .small { font-size: 9px; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print" style="padding:6px; background:#f0f0f0; margin-bottom:8px; text-align:center;">
    <button onclick="window.print()" style="padding:5px 18px; cursor:pointer; font-size:12px;">🖨 Imprimir</button>
    <button onclick="window.close()" style="padding:5px 14px; cursor:pointer; font-size:12px; margin-left:6px;">✕ Cerrar</button>
</div>

<div class="center">
    <p class="empresa">{{ $production->branch?->company?->name ?? config('app.name') }}</p>
    <p class="small">{{ $production->branch?->name }}</p>
    <p class="nombre">{{ $production->formula?->name }}</p>
    @if($production->formula?->pharmaceutical_form)
        <p>{{ $production->formula->pharmaceutical_form }}</p>
    @endif
</div>

<div class="divider"></div>

<table>
    <tr><td>Lote</td><td class="lote">{{ $production->batch }}</td></tr>
    <tr><td>Contenido</td><td>{{ rtrim(rtrim(number_format($production->quantity_produced, 2), '0'), '.') }} {{ $production->formula?->yieldUnit?->abbreviation }}</td></tr>
    <tr><td>Elaboración</td><td>{{ $production->produced_at->format('d/m/Y') }}</td></tr>
    <tr><td>Vence</td><td class="bold">{{ $production->expiration_date ? $production->expiration_date->format('d/m/Y') : '—' }}</td></tr>
    <tr><td>Elaboró</td><td>{{ $production->employee?->name }}</td></tr>
</table>

@if($production->formula?->storage)
<div class="divider"></div>
<p class="small"><span class="bold">Conservación:</span> {{ $production->formula->storage }}</p>
@endif

@if($production->formula?->description)
<p class="small" style="margin-top:3px;"><span class="bold">Indicación:</span> {{ $production->formula->description }}</p>
@endif

<div class="divider"></div>
<p class="center small">Preparado magistral · Uso externo bajo prescripción</p>
<p class="center small">Mantener fuera del alcance de los niños</p>

</body>
</html>
