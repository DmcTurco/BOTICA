@extends('company/layouts/base', ['elementActive' => 'reports'])

@section('title', 'Resumen por sedes')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Resumen por sedes</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · todas las sedes activas</p>
        </div>
        <form action="{{ route('company.reports.branches') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="date" name="fecha_desde" value="{{ $from->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $to->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl"><i class="fas fa-file-excel text-xs mr-1 text-emerald-600"></i> Exportar a Excel</a>
        </form>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Ventas del período</p><p class="text-lg font-bold">{{ $money($totals['gross']) }}</p><p class="text-[11px] text-emerald-200">{{ $totals['documents'] }} documentos</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Inventario a costo</p><p class="text-lg font-bold text-slate-800">{{ $money($totals['inventory']) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Por cobrar / por pagar</p><p class="text-sm font-bold text-slate-800">{{ $money($totals['receivable']) }} <span class="text-slate-300">/</span> {{ $money($totals['payable']) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Vencido a costo</p><p class="text-lg font-bold {{ $totals['expired'] > 0 ? 'text-red-600' : 'text-slate-800' }}">{{ $money($totals['expired']) }}</p></div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Sede</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Docs.</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Inventario</th>
                <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Bajo mín.</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vencido</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Por cobrar</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Por pagar</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Dif. cajas</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 text-xs font-medium text-slate-800">{{ $r->name }}</td>
                    <td class="px-4 py-3 text-center text-xs text-slate-600">{{ $r->documents }}</td>
                    <td class="px-4 py-3 text-right text-xs font-semibold text-slate-800">{{ $money($r->gross) }}</td>
                    <td class="px-4 py-3 text-right text-xs text-slate-600">{{ $money($r->inventory) }}</td>
                    <td class="px-4 py-3 text-center text-xs {{ $r->low_stock ? 'text-amber-600 font-semibold' : 'text-slate-400' }}">{{ $r->low_stock }}</td>
                    <td class="px-4 py-3 text-right text-xs {{ $r->expired > 0 ? 'text-red-600 font-semibold' : 'text-slate-400' }}">{{ $money($r->expired) }}</td>
                    <td class="px-4 py-3 text-right text-xs text-slate-600">{{ $money($r->receivable) }}</td>
                    <td class="px-4 py-3 text-right text-xs text-slate-600">{{ $money($r->payable) }}</td>
                    <td class="px-4 py-3 text-right text-xs {{ $r->cash_diff < 0 ? 'text-red-600 font-semibold' : ($r->cash_diff > 0 ? 'text-amber-600' : 'text-slate-400') }}">{{ $money($r->cash_diff) }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-5 py-12 text-center text-sm text-slate-400">No hay sedes activas</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Los 10 productos más vendidos de la empresa</p></div>
        <table class="w-full text-sm"><tbody class="divide-y divide-slate-100">
            @forelse($top as $i => $p)
            <tr>
                <td class="px-5 py-2 text-xs text-slate-400 w-10">{{ $i + 1 }}</td>
                <td class="px-5 py-2 text-xs font-medium text-slate-800">{{ $p->name }}<span class="text-[10px] text-slate-400 font-mono"> {{ $p->product_code }}</span></td>
                <td class="px-5 py-2 text-center text-xs text-slate-700">{{ rtrim(rtrim(number_format($p->qty, 2), '0'), '.') }} unid.</td>
                <td class="px-5 py-2 text-right text-xs text-slate-600">{{ $money($p->net) }}</td>
            </tr>
            @empty
            <tr><td class="px-5 py-8 text-center text-sm text-slate-400">Sin ventas en el período</td></tr>
            @endforelse
        </tbody></table>
    </div>
</div>
@endsection
