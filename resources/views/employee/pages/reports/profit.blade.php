@extends('employee/layouts/base')

@section('title', 'Reporte de Utilidad')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $fmt   = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Reporte de Utilidad</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>
        </div>
        <form action="{{ route('employee.reports.profit') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="date" name="fecha_desde" value="{{ $from->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $to->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="grid grid-cols-3 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Ventas netas (sin IGV)</p><p class="text-lg font-bold text-slate-800">{{ $money($sales) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Costo de lo vendido</p><p class="text-lg font-bold text-slate-800">{{ $money($cost) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Utilidad {{ $sales > 0 ? '· ' . round($profit / $sales * 100, 1) . '%' : '' }}</p><p class="text-lg font-bold">{{ $money($profit) }}</p></div>
    </div>

    <p class="text-xs text-slate-400 shrink-0"><i class="fas fa-circle-info mr-1"></i> El costo se calcula con el precio de compra actual de cada producto, no con el precio al que se compró cada lote.</p>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unid.</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Venta</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Costo</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Utilidad</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Margen</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $r->name }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $r->code }}</p></td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($r->qty) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs text-slate-600 hidden md:table-cell">{{ $money($r->sales) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs text-slate-600 hidden md:table-cell">{{ $money($r->cost) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs font-semibold {{ $r->profit < 0 ? 'text-red-600' : 'text-emerald-700' }}">{{ $money($r->profit) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs {{ $r->margin < 0 ? 'text-red-600' : 'text-slate-600' }}">{{ $r->margin }}%</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No hay ventas en el rango</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
