@extends('employee/layouts/base')

@section('title', 'Récord General de Ventas')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Récord General de Ventas</h1>
            <p class="text-sm text-slate-500 mt-0.5">Ventas mes por mes · {{ $year }}</p>
        </div>
        <form action="{{ route('employee.reports.general') }}" method="GET" class="flex items-center gap-2">
            <select name="anio" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                @foreach($years as $y)<option value="{{ $y }}" {{ $y === $year ? 'selected' : '' }}>{{ $y }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Documentos</p><p class="text-lg font-bold text-slate-800">{{ $totals['count'] }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Subtotal (sin IGV)</p><p class="text-lg font-bold text-slate-800">{{ $money($totals['sub']) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">IGV</p><p class="text-lg font-bold text-slate-800">{{ $money($totals['igv']) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Total {{ $year }}</p><p class="text-lg font-bold">{{ $money($totals['total']) }}</p></div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Mes</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Docs.</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Subtotal</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">IGV</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                <th class="px-5 py-3 w-1/4 hidden sm:table-cell"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($months as $m)
                <tr class="{{ $m['count'] ? 'hover:bg-slate-50' : 'text-slate-300' }}">
                    <td class="px-5 py-2.5 text-xs capitalize">
                        @if($m['count'])<a href="{{ route('employee.reports.monthly', ['mes' => $m['date']->format('Y-m')]) }}" class="text-slate-700 hover:text-emerald-600">{{ $m['date']->translatedFormat('F') }}</a>@else{{ $m['date']->translatedFormat('F') }}@endif
                    </td>
                    <td class="px-5 py-2.5 text-center text-xs">{{ $m['count'] ?: '—' }}</td>
                    <td class="px-5 py-2.5 text-right text-xs hidden md:table-cell">{{ $m['count'] ? $money($m['sub']) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right text-xs hidden md:table-cell">{{ $m['count'] ? $money($m['igv']) : '—' }}</td>
                    <td class="px-5 py-2.5 text-right text-xs font-semibold {{ $m['count'] ? 'text-slate-800' : '' }}">{{ $m['count'] ? $money($m['total']) : '—' }}</td>
                    <td class="px-5 py-2.5 hidden sm:table-cell"><div class="h-2 bg-emerald-500 rounded" style="width: {{ round($m['total'] / $max * 100) }}%"></div></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
