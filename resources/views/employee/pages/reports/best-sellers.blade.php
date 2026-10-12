@extends('employee/layouts/base')

@section('title', 'Productos Más Vendidos')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $fmt   = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Productos Más Vendidos</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>
        </div>
        <form action="{{ route('employee.reports.best-sellers') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="date" name="fecha_desde" value="{{ $from->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $to->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <select name="top" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                @foreach([10, 20, 50, 100] as $n)<option value="{{ $n }}" {{ $limit === $n ? 'selected' : '' }}>Top {{ $n }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-12">#</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Unidades</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Venta neta</th>
                <th class="px-5 py-3 w-1/4 hidden sm:table-cell"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $i => $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5 text-center text-xs text-slate-400">{{ $i + 1 }}</td>
                    <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $names[$r->product_code] ?? $r->product_code }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $r->product_code }}</p></td>
                    <td class="px-5 py-2.5 text-center text-xs font-semibold text-slate-800">{{ $fmt($r->qty) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs text-slate-700">{{ $money($r->net) }}</td>
                    <td class="px-5 py-2.5 hidden sm:table-cell"><div class="h-2 bg-emerald-500 rounded" style="width: {{ round($r->qty / $max * 100) }}%"></div></td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No hay ventas en el rango</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
