@extends('employee/layouts/base')

@section('title', 'Órdenes de Compra')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Órdenes de Compra</h1>
            <p class="text-sm text-slate-500 mt-0.5">Pedidos a proveedores</p>
        </div>
        <a href="{{ route('employee.purchase-orders.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm"><i class="fas fa-plus text-xs"></i> Nueva Orden</a>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.purchase-orders.index') }}" method="GET" class="flex gap-3">
            <select name="estado" class="sm:w-56 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los estados</option>
                @foreach(\App\Models\PurchaseOrder::STATUS_LABELS as $k => $l)<option value="{{ $k }}" {{ request('estado') === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">N°</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Proveedor</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Fecha</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Ítems</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total est.</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                    <th class="px-5 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $o)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $o->number }}</td>
                        <td class="px-5 py-3 text-xs text-slate-800">{{ $o->supplier?->name }}</td>
                        <td class="px-5 py-3 text-xs text-slate-600 hidden md:table-cell">{{ $o->created_at->format('d/m/Y') }}@if($o->expected_date)<p class="text-[10px] text-slate-400">entrega {{ $o->expected_date->format('d/m') }}</p>@endif</td>
                        <td class="px-5 py-3 text-center text-xs text-slate-700 hidden sm:table-cell">{{ $o->items_count }}</td>
                        <td class="px-5 py-3 text-right text-xs font-semibold text-slate-800">S/ {{ number_format($o->total, 2) }}</td>
                        <td class="px-5 py-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ ['pending' => 'bg-amber-50 text-amber-700', 'received' => 'bg-emerald-50 text-emerald-700', 'cancelled' => 'bg-slate-100 text-slate-500'][$o->status] }}">{{ $o->status_label }}</span></td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('employee.purchase-orders.show', $o) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600" title="Ver"><i class="fas fa-eye text-xs"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-14 text-center text-sm text-slate-400">No hay órdenes de compra</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $orders->links() }}</div>@endif
    </div>
</div>
@endsection
