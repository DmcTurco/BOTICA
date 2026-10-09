@extends('employee/layouts/base')

@section('title', 'Lotes por Vencer')
@section('main-padding', 'p-2 md:p-3')

@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0'; @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Lotes por Vencer</h1>
            <p class="text-sm text-slate-500 mt-0.5">Compras y preparados que vencen en los próximos {{ $days }} días (o ya vencieron) y aún tienen stock</p>
        </div>
        <form action="{{ route('employee.reports.expiring') }}" method="GET" class="flex items-center gap-2">
            <select name="dias" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                @foreach([30, 60, 90, 180, 365] as $n)<option value="{{ $n }}" {{ $days === $n ? 'selected' : '' }}>{{ $n }} días</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-2 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Lotes en el período</p><p class="text-lg font-bold text-slate-800">{{ $rows->count() }}</p></div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4"><p class="text-xs text-red-600">Ya vencidos</p><p class="text-lg font-bold text-red-700">{{ $expired }}</p></div>
    </div>

    <p class="text-xs text-slate-400 shrink-0"><i class="fas fa-circle-info mr-1"></i> El sistema no lleva stock por lote: se muestra cada lote ingresado que vence, siempre que el producto aún tenga stock. Si ya vendiste ese lote, revisa el stock físico y ajústalo.</p>

    <div class="flex-1 min-h-0 overflow-auto bg-white rounded-xl border border-slate-200 shadow-sm">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Origen / lote</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cant. ingresada</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vence</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5"><a href="{{ $r['link'] }}" class="text-xs font-medium text-slate-800 hover:text-emerald-600">{{ $r['name'] }}</a>@if($r['stock'] !== null)<p class="text-[10px] text-slate-400">Stock actual: {{ $fmt($r['stock']) }}</p>@endif</td>
                    <td class="px-5 py-2.5 text-xs text-slate-600 hidden sm:table-cell">{{ $r['origin'] }}<p class="text-[10px] text-slate-400 font-mono">{{ $r['batch'] ?: 'sin lote' }}</p></td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($r['qty']) }}</td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $r['expires']->format('d/m/Y') }}</td>
                    <td class="px-5 py-2.5 text-center">
                        @if($r['days'] < 0)<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Vencido hace {{ abs($r['days']) }} d</span>
                        @elseif($r['days'] <= 30)<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Vence en {{ $r['days'] }} d</span>
                        @else<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600">Vence en {{ $r['days'] }} d</span>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400"><i class="fas fa-circle-check text-3xl text-emerald-400 mb-2"></i><p>No hay lotes por vencer en el período</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
