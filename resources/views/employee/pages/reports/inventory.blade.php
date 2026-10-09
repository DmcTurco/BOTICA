@extends('employee/layouts/base')

@section('title', $config['title'])
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $fmt   = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0';
    $routeName = [
        'total' => 'employee.reports.inventory.total', 'stock' => 'employee.reports.inventory.stock',
        'laboratory' => 'employee.reports.inventory.laboratory', 'laboratory-stock' => 'employee.reports.inventory.laboratory-stock',
        'valued' => 'employee.reports.inventory.valued',
    ][$variant];
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">{{ $config['title'] }}</h1>
        <p class="text-sm text-slate-500 mt-0.5">Stock de tu sede, valorizado a precio de compra y a precio de venta (por unidad)</p>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route($routeName) }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <select name="category" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todas las categorías</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <select name="laboratory" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los laboratorios</option>
                @foreach($laboratories as $l)<option value="{{ $l->id }}" {{ request('laboratory') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-search text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Productos</p><p class="text-lg font-bold text-slate-800">{{ $productCount }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Unidades en stock</p><p class="text-lg font-bold text-slate-800">{{ $fmt($totalUnits) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Valor a costo</p><p class="text-lg font-bold text-slate-800">{{ $money($totalCost) }}</p></div>
        <div class="bg-emerald-600 rounded-xl p-4 text-white"><p class="text-xs text-emerald-200">Valor a precio de venta</p><p class="text-lg font-bold">{{ $money($totalSale) }}</p></div>
    </div>

    @forelse($groups as $groupName => $items)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($config['by_lab'])
        <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <p class="text-sm font-semibold text-slate-800"><i class="fas fa-flask text-slate-400 text-xs mr-1"></i> {{ $groupName }}</p>
            <p class="text-xs text-slate-500">{{ $items->count() }} prod. · costo <strong class="text-slate-800">{{ $money($items->sum('cost_value')) }}</strong></p>
        </div>
        @endif
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="{{ $config['by_lab'] ? '' : 'bg-slate-50' }} border-b border-slate-200">
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                @unless($config['by_lab'])<th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Laboratorio</th>@endunless
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">P. compra</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Valor costo</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Valor venta</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($items as $p)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2"><p class="text-xs font-medium text-slate-800">{{ $p->name }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $p->code }}@if($p->category) · {{ $p->category->name }}@endif</p></td>
                    @unless($config['by_lab'])<td class="px-5 py-2 text-xs text-slate-600 hidden md:table-cell">{{ $p->laboratory?->name ?? '—' }}</td>@endunless
                    <td class="px-5 py-2 text-center text-xs {{ $p->stock_actual <= 0 ? 'text-red-500' : 'text-slate-700' }}">{{ $fmt($p->stock_actual) }} {{ $p->unit?->abbreviation }}</td>
                    <td class="px-5 py-2 text-right text-xs text-slate-600 hidden sm:table-cell">{{ $money($p->purchase_price) }}</td>
                    <td class="px-5 py-2 text-right text-xs font-medium text-slate-800">{{ $money($p->cost_value) }}</td>
                    <td class="px-5 py-2 text-right text-xs text-slate-600 hidden md:table-cell">{{ $money($p->sale_value) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @empty
    <div class="bg-white rounded-xl border border-slate-200 p-10 text-center text-sm text-slate-400">No hay productos para mostrar</div>
    @endforelse
</div>
@endsection
