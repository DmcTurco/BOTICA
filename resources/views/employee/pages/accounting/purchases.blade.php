@extends('employee/layouts/base')

@section('title', 'Registro de Compras')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Registro de Compras</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $month->translatedFormat('F Y') }} · listo para tu contador</p>
        </div>
        <form action="{{ route('employee.accounting.purchases') }}" method="GET" class="flex items-center gap-2">
            <input type="month" name="mes" value="{{ $month->format('Y-m') }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
            <a href="{{ route('employee.accounting.purchases', ['mes' => $month->format('Y-m'), 'export' => 'csv']) }}" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg"><i class="fas fa-file-excel text-xs mr-1 text-emerald-600"></i> Exportar a Excel</a>
        </form>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-3 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Subtotal</p><p class="text-base font-bold text-slate-800">S/ {{ $money($totals['subtotal']) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">IGV</p><p class="text-base font-bold text-slate-800">S/ {{ $money($totals['tax']) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Total compras</p><p class="text-base font-bold">S/ {{ $money($totals['total']) }}</p></div>
    </div>

    <div class="flex-1 min-h-0 overflow-auto bg-white rounded-xl border border-slate-200 shadow-sm">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Documento</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Proveedor</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">IGV</th>
                <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Condición</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($purchases as $p)
                <tr class="hover:bg-slate-50 {{ (int) $p->status === 0 ? 'text-slate-300 line-through' : '' }}">
                    <td class="px-4 py-2 text-xs">{{ $p->purchased_at->format('d/m/Y') }}</td>
                    <td class="px-4 py-2 text-xs"><a href="{{ route('employee.purchases.show', $p) }}" class="hover:text-emerald-600">{{ $p->document_type_label }} {{ $p->document_number }}</a></td>
                    <td class="px-4 py-2 text-xs hidden md:table-cell">{{ $p->supplierRecord?->name ?? $p->supplier ?: '—' }}<span class="text-[10px] text-slate-400"> {{ $p->supplierRecord?->ruc }}</span></td>
                    <td class="px-4 py-2 text-right text-xs hidden lg:table-cell">{{ $money($p->tax) }}</td>
                    <td class="px-4 py-2 text-right text-xs font-semibold">{{ $money($p->total) }}</td>
                    <td class="px-4 py-2 text-xs hidden sm:table-cell">{{ $p->payment_condition === 'credit' ? 'Crédito' : 'Contado' }}{{ (int) $p->status === 0 ? ' · anulada' : '' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400">No hay compras en el mes</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
