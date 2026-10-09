@extends('employee/layouts/base')

@section('title', 'Ventas por Rango de Fechas')
@section('main-padding', 'p-2 md:p-3')

@php $money = fn ($n) => 'S/ ' . number_format($n, 2); @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Ventas por Rango de Fechas</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p>
        </div>
        <form action="{{ route('employee.reports.range') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="date" name="fecha_desde" value="{{ $from->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $to->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Documentos</p><p class="text-lg font-bold text-slate-800">{{ $totals['count'] }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Subtotal</p><p class="text-lg font-bold text-slate-800">{{ $money($totals['sub']) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">IGV</p><p class="text-lg font-bold text-slate-800">{{ $money($totals['igv']) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Descuentos</p><p class="text-lg font-bold text-slate-800">{{ $money($totals['discount']) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Total</p><p class="text-lg font-bold">{{ $money($totals['total']) }}</p></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
        @foreach([['Por comprobante', $byVoucher], ['Por forma de pago', $byPayment]] as [$title, $data])
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">{{ $title }}</p>
            @php $top = max(1, $data->max('total')); @endphp
            @foreach($data as $row)
            <div class="mb-3 last:mb-0">
                <div class="flex justify-between text-xs mb-1"><span class="text-slate-600">{{ $row['label'] }} <span class="text-slate-400">({{ $row['count'] }})</span></span><span class="font-semibold text-slate-800">{{ $money($row['total']) }}</span></div>
                <div class="h-2 bg-slate-100 rounded"><div class="h-2 bg-emerald-500 rounded" style="width: {{ round($row['total'] / $top * 100) }}%"></div></div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Ventas por día</p></div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @forelse($byDay as $d)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2 text-xs text-slate-700">{{ $d['date']->translatedFormat('D d/m/Y') }}</td>
                    <td class="px-5 py-2 text-center text-xs text-slate-500">{{ $d['count'] }} docs.</td>
                    <td class="px-5 py-2 text-right text-xs font-semibold text-slate-800">{{ $money($d['total']) }}</td>
                </tr>
                @empty
                <tr><td class="px-5 py-10 text-center text-sm text-slate-400">No hay ventas en el rango</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
