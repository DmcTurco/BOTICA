@extends('employee/layouts/base')

@section('title', 'Tipo de Cambio')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto max-w-4xl w-full mx-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Tipo de Cambio</h1>
        <p class="text-sm text-slate-500 mt-0.5">Dólar americano (USD) a soles. Registra el tipo de cambio de cada día.</p>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Compra hoy</p><p class="text-2xl font-bold text-slate-800">{{ $today ? number_format($today->buy_rate, 3) : '—' }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Venta hoy</p><p class="text-2xl font-bold text-slate-800">{{ $today ? number_format($today->sell_rate, 3) : '—' }}</p></div>
    </div>
    @unless($today)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800"><i class="fas fa-triangle-exclamation mr-1"></i> Aún no registraste el tipo de cambio de hoy.</div>
    @endunless

    <form action="{{ route('employee.exchange-rates.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        @csrf
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 rounded-t-xl text-xs font-semibold text-slate-600 uppercase tracking-wider">Registrar o actualizar</div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Fecha</label><input type="date" name="rate_date" required max="{{ today()->toDateString() }}" value="{{ old('rate_date', today()->toDateString()) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Compra</label><input type="number" name="buy_rate" required step="0.0001" min="0.0001" value="{{ old('buy_rate', $today?->buy_rate) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Venta</label><input type="number" name="sell_rate" required step="0.0001" min="0.0001" value="{{ old('sell_rate', $today?->sell_rate) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center justify-center gap-2"><i class="fas fa-save text-xs"></i> Guardar</button>
        </div>
    </form>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Compra</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Venta</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Registró</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rates as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5 text-xs text-slate-700">{{ $r->rate_date->format('d/m/Y') }}</td>
                    <td class="px-5 py-2.5 text-right text-xs font-medium">{{ number_format($r->buy_rate, 3) }}</td>
                    <td class="px-5 py-2.5 text-right text-xs font-medium">{{ number_format($r->sell_rate, 3) }}</td>
                    <td class="px-5 py-2.5 text-xs text-slate-500 hidden md:table-cell">{{ $r->employee?->name }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">Aún no hay tipos de cambio registrados</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rates->hasPages())<div class="px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $rates->links() }}</div>@endif
    </div>
</div>
@endsection
