@extends('employee/layouts/base')

@section('title', 'Productos sin Rotación')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $fmt   = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.');
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Productos sin Rotación</h1>
            <p class="text-sm text-slate-500 mt-0.5">Con stock en tu sede y sin ventas en los últimos {{ $days }} días</p>
        </div>
        <form action="{{ route('employee.reports.no-rotation') }}" method="GET" class="flex items-center gap-2">
            <select name="dias" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                @foreach([30, 60, 90, 180, 365] as $n)<option value="{{ $n }}" {{ $days === $n ? 'selected' : '' }}>{{ $n }} días</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="grid grid-cols-2 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Productos parados</p><p class="text-lg font-bold text-slate-800">{{ $products->count() }}</p></div>
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-4"><p class="text-xs text-amber-700">Dinero inmovilizado (a costo)</p><p class="text-lg font-bold text-amber-800">{{ $money($totalValue) }}</p></div>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Categoría</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Valor a costo</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $p->name }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $p->code }} @if($p->laboratory) · {{ $p->laboratory->name }} @endif</p></td>
                        <td class="px-5 py-2.5 text-xs text-slate-600 hidden md:table-cell">{{ $p->category?->name ?? '—' }}</td>
                        <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $fmt($p->stock_actual) }}</td>
                        <td class="px-5 py-2.5 text-right text-xs font-semibold text-slate-800">{{ $money($p->stock_value) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-400"><i class="fas fa-circle-check text-3xl text-emerald-400 mb-2"></i><p>Todo lo que tienes en stock se vendió en el período</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
