@extends('employee/layouts/base')

@section('title', 'Compras')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Compras</h1>
            <p class="text-sm text-slate-500 mt-0.5">Historial de ingresos de stock</p>
        </div>
        <a href="{{ route('employee.purchases.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nueva Compra
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.purchases.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="Proveedor o número de documento...">
                </div>
                <select name="tipo"
                        class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Todos los tipos</option>
                    <option value="1" {{ request('tipo') == '1' ? 'selected' : '' }}>Boleta</option>
                    <option value="2" {{ request('tipo') == '2' ? 'selected' : '' }}>Factura</option>
                    <option value="3" {{ request('tipo') == '3' ? 'selected' : '' }}>Nota de ingreso</option>
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search text-xs"></i> Filtrar
                </button>
                @if(request()->hasAny(['buscar','tipo']))
                <a href="{{ route('employee.purchases.index') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-xmark text-xs"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Cabecera del card --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Historial de compras
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $compras->total() }} registros</span>
            </p>
        </div>

        {{-- Área scrollable --}}
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">N° Documento</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Proveedor</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ítems</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($compras as $compra)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3 text-slate-600 text-xs">
                            {{ $compra->purchased_at->format('d/m/Y') }}
                        </td>
                        <td class="px-5 py-3">
                            @php
                                $badge = match($compra->document_type) {
                                    1 => ['bg-sky-50 text-sky-700',   'Boleta'],
                                    2 => ['bg-violet-50 text-violet-700', 'Factura'],
                                    default => ['bg-slate-100 text-slate-600', 'Nota de ingreso'],
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[0] }}">
                                {{ $badge[1] }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500 text-xs font-mono hidden md:table-cell">
                            {{ $compra->document_number ?: '—' }}
                        </td>
                        <td class="px-5 py-3 text-slate-600 text-xs hidden lg:table-cell">
                            {{ $compra->supplier ?: '—' }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">
                                {{ $compra->items->count() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right text-slate-800 text-sm font-semibold">
                            S/. {{ number_format($compra->total, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end">
                                <a href="{{ route('employee.purchases.show', $compra->id) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600 transition-colors" title="Ver detalle">
                                    <i class="fas fa-eye text-xs"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-truck-ramp-box text-3xl"></i>
                                <p class="text-sm">No hay compras registradas</p>
                                <a href="{{ route('employee.purchases.create') }}" class="text-emerald-600 text-sm hover:underline">Registrar primera compra</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginador --}}
        @if($compras->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">
            {{ $compras->withQueryString()->links() }}
        </div>
        @endif

    </div>
</div>
@endsection
