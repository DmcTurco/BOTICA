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
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-start justify-between gap-3 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ $config['title'] }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Stock de tu sede, valorizado a precio de compra y a precio de venta (por unidad)</p>
        </div>
        @include('employee.partials.export-button')
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route($routeName) }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <select data-search name="category" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todas las categorías</option>
                @foreach($categories as $c)<option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <select data-search name="laboratory" class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los laboratorios</option>
                @foreach($laboratories as $l)<option value="{{ $l->id }}" {{ request('laboratory') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-search text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-2.5"><p class="text-xs text-slate-400">Productos</p><p class="text-lg font-bold text-slate-800">{{ $productCount }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-2.5"><p class="text-xs text-slate-400">Unidades en stock</p><p class="text-lg font-bold text-slate-800">{{ $fmt($totalUnits) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-2.5"><p class="text-xs text-slate-400">Valor a costo</p><p class="text-lg font-bold text-slate-800">{{ $money($totalCost) }}</p></div>
        <div class="bg-sky-600 rounded-xl px-4 py-2.5 text-white"><p class="text-xs text-sky-200">Valor a precio de venta</p><p class="text-lg font-bold">{{ $money($totalSale) }}</p></div>
    </div>

    {{-- Una sola tabla que ocupa el resto de la pantalla, con scroll propio y encabezado fijo --}}
    <div class="flex-1 min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
        @if($productCount === 0)
        <div class="flex-1 flex items-center justify-center p-10 text-sm text-slate-400">No hay productos para mostrar</div>
        @else
        <div class="flex-1 min-h-0 overflow-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Producto</th>
                @unless($config['by_lab'])<th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Laboratorio</th>@endunless
                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Stock</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden sm:table-cell">P. compra</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap">Valor costo</th>
                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Valor venta</th>
            </tr></thead>
            @foreach($groups as $groupName => $items)
            <tbody class="divide-y divide-slate-100">
                @if($config['by_lab'])
                <tr class="bg-sky-50/70 border-y border-slate-200">
                    <td colspan="5" class="px-5 py-2">
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-slate-800"><i class="fas fa-flask text-slate-400 text-xs mr-1"></i> {{ $groupName }}</p>
                            @php $lab = $labTotals[(int) $items->first()->laboratory_id] ?? null; @endphp
                            {{-- Totales del laboratorio completo, aunque siga en la página siguiente --}}
                            <p class="text-xs text-slate-500 whitespace-nowrap">{{ $lab->products ?? $items->count() }} prod. · costo <strong class="text-slate-800">{{ $money($lab->cost ?? $items->sum('cost_value')) }}</strong></p>
                        </div>
                    </td>
                </tr>
                @endif
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
            @endforeach
        </table>
        </div>
        @if($products->hasPages())
        <div class="shrink-0 px-4 py-2 border-t border-slate-200 text-xs">{{ $products->links() }}</div>
        @endif
        @endif
    </div>
</div>
@endsection
