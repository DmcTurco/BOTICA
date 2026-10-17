@extends('employee/layouts/base')

@section('title', 'Tipo de Cambio')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header: el registro se hace en su propio formulario --}}
    <div class="flex items-center justify-between gap-3 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Tipo de Cambio</h1>
            <p class="text-sm text-slate-500 mt-0.5">Dólar americano (USD) a soles. Registra el tipo de cambio de cada día.</p>
        </div>
        <a href="{{ route('employee.exchange-rates.create') }}"
           class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Registrar tipo de cambio
        </a>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-2 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-2.5"><p class="text-xs text-slate-400">Compra hoy</p><p class="text-xl font-bold text-slate-800">{{ $today ? number_format($today->buy_rate, 3) : '—' }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-2.5"><p class="text-xs text-slate-400">Venta hoy</p><p class="text-xl font-bold text-slate-800">{{ $today ? number_format($today->sell_rate, 3) : '—' }}</p></div>
    </div>
    @unless($today)
    <div class="shrink-0 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800"><i class="fas fa-triangle-exclamation mr-1"></i> Aún no registraste el tipo de cambio de hoy.</div>
    @endunless

    <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto"><table class="w-full text-sm">
            <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
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
        </table></div>
        @if($rates->hasPages())<div class="px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $rates->links() }}</div>@endif
    </div>
</div>
@endsection
