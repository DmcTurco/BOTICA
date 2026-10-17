@extends('employee/layouts/base')

@section('title', 'Historial de Precios')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0 flex-wrap">
        <a href="{{ \App\Support\ListUrl::to('employee.prices.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div class="flex-1 min-w-0">
            <h1 class="text-xl font-bold text-slate-800">Historial de Cambios de Precio</h1>
            <p class="text-sm text-slate-500 mt-0.5">Cada cambio de precio de compra o de venta, con quién lo hizo y el valor anterior.</p>
        </div>
        <form action="{{ route('employee.prices.history') }}" method="GET" class="flex items-center gap-2">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Producto o código..."
                       class="w-56 pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    {{-- Tabla: ocupa el alto disponible y hace scroll por dentro --}}
    <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Precio</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cambio</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Registró</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($changes as $c)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2.5 text-xs text-slate-500">{{ $c->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $c->product?->name ?? $c->product_code }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $c->product_code }}</p></td>
                        <td class="px-5 py-2.5 text-xs text-slate-500 hidden sm:table-cell">{{ \App\Models\PriceChange::FIELD_LABELS[$c->field] ?? $c->field }}</td>
                        <td class="px-5 py-2.5 text-xs text-slate-700">S/ {{ number_format($c->old_value, 2) }} → <strong>S/ {{ number_format($c->new_value, 2) }}</strong></td>
                        <td class="px-5 py-2.5 text-xs text-slate-500 hidden md:table-cell">{{ $c->employee?->name }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-400">Aún no hay cambios de precio registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($changes->hasPages())<div class="shrink-0 px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $changes->links() }}</div>@endif
    </div>
</div>
@endsection
