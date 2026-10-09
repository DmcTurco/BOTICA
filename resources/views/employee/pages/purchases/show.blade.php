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

    @include('employee.partials.alerts')

    @if((int) $purchase->status === 0)
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700">
        <i class="fas fa-ban mr-1"></i> Compra anulada el {{ $purchase->voided_at?->format('d/m/Y H:i') }} — {{ $purchase->void_reason }}
    </div>
    @elseif(auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_ELIMINAR_GUIA_ING))
    <div class="flex justify-end">
        <button type="button" onclick="document.getElementById('modalAnular').style.setProperty('display','flex','important')"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-red-50 border border-red-200 text-red-600 text-sm font-medium rounded-lg transition-colors">
            <i class="fas fa-ban text-xs"></i> Anular compra
        </button>
    </div>
    @endif

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

@if((int) $purchase->status === 1 && auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_ELIMINAR_GUIA_ING))
<div id="modalAnular" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form action="{{ route('employee.purchases.void', $purchase) }}" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        @csrf
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-ban text-red-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Anular compra #{{ $purchase->id }}</h3>
        </div>
        <p class="text-sm text-slate-600">Se descontará del stock lo que ingresó con esta compra. Solo es posible si esas unidades aún están en la sede.</p>
        <input type="text" name="void_reason" required maxlength="255" placeholder="Motivo de la anulación"
               class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('modalAnular').style.setProperty('display','none','important')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Anular compra</button>
        </div>
    </form>
</div>
@endif
@endsection
