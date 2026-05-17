@extends('employee/layouts/base')

@section('title', 'Detalle de Compra')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 max-w-4xl mx-auto w-full">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.purchases.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Detalle de Compra #{{ $purchase->id }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">{{ $purchase->purchased_at->format('d/m/Y') }}</p>
        </div>
    </div>

    {{-- Info del documento --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-4">Información del documento</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div>
                <p class="text-xs text-slate-400 mb-1">Tipo</p>
                <p class="text-sm font-medium text-slate-800">{{ $purchase->document_type_label }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">N° Documento</p>
                <p class="text-sm font-medium text-slate-800 font-mono">{{ $purchase->document_number ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Proveedor</p>
                <p class="text-sm font-medium text-slate-800">{{ $purchase->supplier ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-slate-400 mb-1">Fecha</p>
                <p class="text-sm font-medium text-slate-800">{{ $purchase->purchased_at->format('d/m/Y') }}</p>
            </div>
        </div>
        @if($purchase->notes)
        <div class="mt-4 p-3 bg-slate-50 rounded-lg">
            <p class="text-xs text-slate-400 mb-1">Observaciones</p>
            <p class="text-sm text-slate-600">{{ $purchase->notes }}</p>
        </div>
        @endif
    </div>

    {{-- Ítems --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200">
            <p class="text-sm font-semibold text-slate-800">Productos ingresados</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cantidad</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Costo Unit.</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Vencimiento</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Lote</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($purchase->items as $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <p class="font-medium text-slate-800">{{ $item->product->name ?? 'Producto eliminado' }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ $item->product_code }}</p>
                        </td>
                        <td class="px-5 py-3 text-center font-medium text-slate-700">{{ $item->quantity }}</td>
                        <td class="px-5 py-3 text-right text-slate-600 text-xs">S/. {{ number_format($item->unit_cost, 2) }}</td>
                        <td class="px-5 py-3 text-right font-medium text-slate-800">S/. {{ number_format($item->subtotal, 2) }}</td>
                        <td class="px-5 py-3 text-center text-xs text-slate-500 hidden md:table-cell">
                            {{ $item->expiration_date ? $item->expiration_date->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-5 py-3 text-center text-xs text-slate-500 hidden lg:table-cell">
                            {{ $item->batch ?: '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Resumen de totales --}}
        <div class="px-5 py-4 border-t border-slate-200 bg-slate-50 flex flex-col items-end gap-1.5">
            <div class="flex items-center gap-8 text-sm">
                <span class="text-slate-500">Subtotal</span>
                <span class="font-medium text-slate-800 w-28 text-right">S/. {{ number_format($purchase->subtotal, 2) }}</span>
            </div>
            <div class="flex items-center gap-8 text-sm">
                <span class="text-slate-500">IGV / Impuesto</span>
                <span class="font-medium text-slate-800 w-28 text-right">S/. {{ number_format($purchase->tax, 2) }}</span>
            </div>
            <div class="flex items-center gap-8 text-base font-bold border-t border-slate-200 pt-2 mt-1">
                <span class="text-slate-700">Total</span>
                <span class="text-emerald-700 w-28 text-right">S/. {{ number_format($purchase->total, 2) }}</span>
            </div>
        </div>
    </div>

</div>
@endsection
