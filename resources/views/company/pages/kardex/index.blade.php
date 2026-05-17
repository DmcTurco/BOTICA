@extends('company/layouts/base')

@section('title', 'Kardex')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Kardex</h1>
            <p class="text-sm text-slate-500 mt-0.5">Historial de movimientos de stock por producto</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('company.kardex.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3">

                {{-- Selector de producto --}}
                <div class="flex-1 relative">
                    <i class="fas fa-pills absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <select name="producto" id="selectProducto"
                            class="w-full pl-8 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">— Seleccionar producto —</option>
                        @foreach($productos as $p)
                            <option value="{{ $p->code }}" {{ request('producto') == $p->code ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Fecha desde --}}
                <div>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                           class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                {{-- Fecha hasta --}}
                <div>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                           class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search text-xs"></i> Consultar
                </button>

                @if(request()->hasAny(['producto','fecha_desde','fecha_hasta']))
                <a href="{{ route('company.kardex.index') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-xmark text-xs"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    @if($producto)

    {{-- Tarjeta resumen del producto --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 shrink-0">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                    <i class="fas fa-pills text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <p class="font-semibold text-slate-800">{{ $producto->name }}</p>
                    <p class="text-xs text-slate-400 font-mono">{{ $producto->code }}</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <p class="text-xs text-slate-400 mb-0.5">Total entradas</p>
                    <p class="text-lg font-bold text-emerald-600">
                        {{ $movimientos->where('type', 'entrada')->sum('quantity') }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-slate-400 mb-0.5">Total salidas</p>
                    <p class="text-lg font-bold text-red-500">
                        {{ $movimientos->where('type', 'salida')->sum('quantity') }}
                    </p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-slate-400 mb-0.5">Stock actual</p>
                    <p class="text-lg font-bold text-slate-800">{{ $producto->stock_actual ?? 0 }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-slate-400 mb-0.5">Movimientos</p>
                    <p class="text-lg font-bold text-slate-600">{{ $movimientos->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabla kardex --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Movimientos
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $movimientos->count() }} registros</span>
            </p>
        </div>

        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">Fecha</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">Tipo</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Referencia</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-28">Costo Unit.</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24 bg-emerald-50">Entradas</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24 bg-red-50">Salidas</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movimientos as $mov)
                    <tr class="hover:bg-slate-50 transition-colors">

                        {{-- Fecha y hora --}}
                        <td class="px-5 py-3">
                            <p class="text-xs text-slate-700">{{ $mov->created_at->format('d/m/Y') }}</p>
                            <p class="text-[10px] text-slate-400">{{ $mov->created_at->format('H:i') }}</p>
                        </td>

                        {{-- Badge tipo --}}
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $mov->tipo_badge['class'] }}">
                                {{ $mov->tipo_badge['label'] }}
                            </span>
                        </td>

                        {{-- Referencia --}}
                        <td class="px-5 py-3">
                            <p class="text-xs text-slate-600">{{ $mov->referencia_label }}</p>
                            @if($mov->notes)
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $mov->notes }}</p>
                            @endif
                        </td>

                        {{-- Costo unitario --}}
                        <td class="px-5 py-3 text-right text-xs text-slate-500">
                            S/. {{ number_format($mov->unit_cost, 2) }}
                        </td>

                        {{-- Entrada --}}
                        <td class="px-5 py-3 text-center bg-emerald-50/30">
                            @if($mov->type === 'entrada')
                                <span class="font-semibold text-emerald-700">+{{ $mov->quantity }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>

                        {{-- Salida --}}
                        <td class="px-5 py-3 text-center bg-red-50/30">
                            @if($mov->type === 'salida')
                                <span class="font-semibold text-red-600">-{{ $mov->quantity }}</span>
                            @elseif($mov->type === 'ajuste')
                                <span class="font-semibold text-amber-600">{{ $mov->quantity }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>

                        {{-- Saldo --}}
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold
                                {{ $mov->balance <= ($producto->stock_minimum ?? 0) ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-700' }}">
                                {{ $mov->balance }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-chart-gantt text-3xl"></i>
                                <p class="text-sm">No hay movimientos registrados para este producto</p>
                                @if(request()->hasAny(['fecha_desde','fecha_hasta']))
                                    <p class="text-xs">Prueba ajustando el rango de fechas</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>

    @else

    {{-- Estado inicial: sin producto seleccionado --}}
    <div class="flex-1 flex items-center justify-center bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="flex flex-col items-center gap-3 text-slate-400 py-20">
            <i class="fas fa-chart-gantt text-5xl"></i>
            <p class="text-base font-medium text-slate-500">Selecciona un producto</p>
            <p class="text-sm">Elige un producto del selector para ver su historial de movimientos</p>
        </div>
    </div>

    @endif

</div>
@endsection
