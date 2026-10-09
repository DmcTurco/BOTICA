@extends('employee/layouts/base')

@section('title', 'Documentos del Mes')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $voucherLabels = [1 => 'Boleta', 2 => 'Factura', 3 => 'Nota de venta'];
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Documentos del Mes</h1>
        <p class="text-sm text-slate-500 mt-0.5">Comprobantes emitidos en {{ $month->translatedFormat('F Y') }} (sin los anulados)</p>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.reports.documents') }}" method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-end flex-wrap">
            <div><label class="block text-xs text-slate-500 mb-1">Mes</label><input type="month" name="mes" value="{{ $month->format('Y-m') }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Comprobante</label>
                <select name="tipo" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                    <option value="">Todos</option>
                    @foreach($voucherLabels as $k => $l)<option value="{{ $k }}" {{ request('tipo') == $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Estado SUNAT</label>
                <select name="sunat" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                    <option value="">Todos</option>
                    @foreach(\App\Models\Order::SUNAT_LABELS as $k => $l)<option value="{{ $k }}" {{ request('sunat') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200 shrink-0 flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-500">
            <span><strong class="text-slate-800">{{ $totals['count'] }}</strong> documentos</span>
            <span>Subtotal <strong class="text-slate-800">{{ $money($totals['sub']) }}</strong></span>
            <span>IGV <strong class="text-slate-800">{{ $money($totals['igv']) }}</strong></span>
            @if($totals['discount'] > 0)<span>Descuentos <strong class="text-slate-800">{{ $money($totals['discount']) }}</strong></span>@endif
            <span>Total <strong class="text-emerald-700">{{ $money($totals['total']) }}</strong></span>
        </div>
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Comprobante</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Cliente</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">SUNAT</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $o)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2.5 text-xs text-slate-600">{{ $o->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $voucherLabels[$o->voucher_type] ?? '—' }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $o->voucher_number }}</p></td>
                        <td class="px-5 py-2.5 text-xs text-slate-600 hidden md:table-cell">{{ $o->customer_name ?: '—' }}</td>
                        <td class="px-5 py-2.5 text-center hidden sm:table-cell"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">{{ \App\Models\Order::SUNAT_LABELS[$o->sunat_status] ?? $o->sunat_status }}</span></td>
                        <td class="px-5 py-2.5 text-right text-xs font-semibold text-slate-800">{{ $money($o->total) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">No hay documentos con esos filtros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $orders->links() }}</div>@endif
    </div>
</div>
@endsection
