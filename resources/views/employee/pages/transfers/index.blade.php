@extends('employee/layouts/base')

@section('title', 'Traspasos')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Traspasos entre sedes</h1>
            <p class="text-sm text-slate-500 mt-0.5">Mercadería enviada y recibida por tu sede</p>
        </div>
        <a href="{{ route('employee.transfers.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nuevo Traspaso
        </a>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.transfers.index') }}" method="GET" class="flex gap-3">
            <select name="direccion" class="sm:w-56 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="">Enviados y recibidos</option>
                <option value="enviados" {{ request('direccion') === 'enviados' ? 'selected' : '' }}>Solo enviados</option>
                <option value="recibidos" {{ request('direccion') === 'recibidos' ? 'selected' : '' }}>Solo recibidos</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2">
                <i class="fas fa-filter text-xs"></i> Filtrar
            </button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">N°</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Origen → Destino</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ítems</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transfers as $t)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-xs text-slate-700">#{{ $t->id }}</td>
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 text-xs text-slate-800">{{ $t->fromBranch->name }} <i class="fas fa-arrow-right text-[9px] text-slate-400 mx-1"></i> {{ $t->toBranch->name }}</td>
                        <td class="px-5 py-3 text-center text-xs text-slate-700">{{ $t->items_count }}</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $t->isActive() ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">{{ $t->isActive() ? 'Vigente' : 'Anulado' }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('employee.transfers.show', $t) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600" title="Ver detalle">
                                <i class="fas fa-eye text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400">No hay traspasos registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $transfers->links() }}</div>
        @endif
    </div>
</div>
@endsection
