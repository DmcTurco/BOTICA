@extends('employee/layouts/base')

@section('title', 'Comisiones por Vendedor')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Comisiones por Vendedor</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · sobre la venta neta (sin IGV)</p>
        </div>
        <form action="{{ route('employee.reports.commissions') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="date" name="fecha_desde" value="{{ $from->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $to->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="bg-emerald-600 rounded-xl p-4 text-white shrink-0 max-w-xs"><p class="text-xs text-emerald-200">Total de comisiones</p><p class="text-xl font-bold">{{ $money($totalCommission) }}</p></div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vendedor</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Docs.</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Venta neta</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">%</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Comisión</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5 text-xs font-medium text-slate-800">{{ $r->name }}</td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-600">{{ $r->documents }}</td>
                    <td class="px-5 py-2.5 text-right text-xs text-slate-700">{{ $money($r->net) }}</td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-600">{{ rtrim(rtrim(number_format($r->rate, 2), '0'), '.') ?: '0' }}%</td>
                    <td class="px-5 py-2.5 text-right text-xs font-semibold text-emerald-700">{{ $money($r->commission) }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No hay ventas ni comisiones configuradas en el rango</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
