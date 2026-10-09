@extends('employee/layouts/base')

@section('title', 'Caja #' . $caja->id)
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $diff  = (float) ($caja->difference ?? 0);
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-4xl mx-auto w-full">

    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.cash-closures.index') }}" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors"><i class="fas fa-arrow-left text-sm"></i></a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Caja del {{ $caja->register_date->format('d/m/Y') }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">#{{ $caja->id }} · {{ $caja->employee?->name }} · {{ $caja->status ? 'Abierta' : 'Cerrada' }}</p>
        </div>
    </div>

    @include('employee.partials.alerts')

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Apertura</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->opening_amount) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Ventas ({{ $ordersCount }})</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->totalOrders()) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Efectivo esperado</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->status ? $caja->expectedCash() : ($caja->expected_amount ?? 0)) }}</p></div>
        <div class="rounded-xl p-4 {{ $caja->status ? 'bg-slate-100' : ($diff < 0 ? 'bg-red-50' : ($diff > 0 ? 'bg-amber-50' : 'bg-emerald-50')) }}">
            <p class="text-xs text-slate-500">Diferencia</p>
            <p class="text-lg font-bold {{ $caja->status ? 'text-slate-400' : ($diff < 0 ? 'text-red-600' : ($diff > 0 ? 'text-amber-600' : 'text-emerald-700')) }}">{{ $caja->status ? '—' : (($diff > 0 ? '+' : '') . number_format($diff, 2)) }}</p>
            @if(!$caja->status)<p class="text-[10px] text-slate-500">Contado {{ $money($caja->closing_amount ?? 0) }}</p>@endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Ventas por forma de pago</p>
            <div class="space-y-2">
                @foreach($paymentLabels as $type => $label)
                <div class="flex justify-between text-sm"><span class="text-slate-600">{{ $label }}</span><span class="font-medium text-slate-800">{{ $money($paymentTotals[$type]) }}</span></div>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Cuadre del efectivo</p>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-slate-600">Apertura</span><span class="font-medium">{{ $money($caja->opening_amount) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">+ Ventas en efectivo</span><span class="font-medium">{{ $money($caja->totalCash()) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">+ Otros ingresos</span><span class="font-medium text-emerald-700">{{ $money($caja->otherIncome()) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-600">− Gastos</span><span class="font-medium text-red-600">{{ $money($caja->expenses()) }}</span></div>
            </div>
        </div>
    </div>

    @if($caja->movements->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200"><p class="text-sm font-semibold text-slate-800">Gastos e ingresos</p></div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @foreach($caja->movements as $m)
                <tr class="{{ $m->status ? '' : 'opacity-50 line-through' }}">
                    <td class="px-5 py-2.5 text-xs text-slate-500">{{ $m->created_at->format('H:i') }}</td>
                    <td class="px-5 py-2.5 text-xs text-slate-800">{{ $m->concept }}</td>
                    <td class="px-5 py-2.5 text-xs text-slate-500">{{ $m->employee?->name }}</td>
                    <td class="px-5 py-2.5 text-right text-xs font-semibold {{ $m->type === 'income' ? 'text-emerald-700' : 'text-red-600' }}">{{ $m->type === 'income' ? '+' : '−' }} {{ $money($m->amount) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($caja->notes)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5"><p class="text-xs text-slate-400 mb-1">Observaciones</p><p class="text-sm text-slate-700">{{ $caja->notes }}</p></div>
    @endif
</div>
@endsection
