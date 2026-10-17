@extends('employee/layouts/base')

@section('title', 'Registrar Venta Perdida')
@section('main-padding', 'p-2 md:p-3')

@php
    $inputClass = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent';
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('employee.lost-sales.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Registrar Venta Perdida</h1>
            <p class="text-sm text-slate-500 mt-0.5">Anota lo que el cliente pidió y no pudiste vender, para saber qué reponer o incorporar.</p>
        </div>
    </div>

    <form action="{{ route('employee.lost-sales.store') }}" method="POST" class="flex-1 flex flex-col min-h-0">
        @csrf

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-circle-minus text-sky-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos de lo pedido</span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    {{-- Producto: del catálogo (se guarda su código) o escrito libre si no lo tenemos --}}
                    <div class="sm:col-span-2">
                        <label for="productName" class="block text-sm font-medium text-slate-700 mb-1.5">Producto pedido <span class="text-red-500">*</span></label>
                        <input type="text" name="product_name" id="productName" maxlength="150" list="catalogo" value="{{ old('product_name') }}"
                               placeholder="Nombre del producto (del catálogo o libre)" class="{{ $inputClass }} @error('product_name') border-red-400 @enderror">
                        <input type="hidden" name="product_code" id="productCode" value="{{ old('product_code') }}">
                        <datalist id="catalogo">@foreach($products as $p)<option value="{{ $p['name'] }}">@endforeach</datalist>
                        <p id="hintCatalogo" class="text-xs text-slate-400 mt-1">Si no está en el catálogo, escríbelo igual: sirve para saber qué incorporar.</p>
                        @error('product_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700 mb-1.5">Cantidad <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" id="quantity" min="0.01" step="0.01" value="{{ old('quantity', 1) }}"
                               class="{{ $inputClass }} @error('quantity') border-red-400 @enderror">
                        @error('quantity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="reason" class="block text-sm font-medium text-slate-700 mb-1.5">Motivo <span class="text-red-500">*</span></label>
                        <select name="reason" id="reason" class="{{ $inputClass }}">
                            @foreach(\App\Models\LostSale::REASONS as $k => $label)<option value="{{ $k }}" {{ old('reason') === $k ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1.5">Nota <span class="text-slate-400 font-normal">(opcional)</span></label>
                        <input type="text" name="notes" id="notes" maxlength="255" value="{{ old('notes') }}"
                               placeholder="Ej. el cliente aceptó una alternativa" class="{{ $inputClass }}">
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ \App\Support\ListUrl::to('employee.lost-sales.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    Registrar
                </button>
            </div>

        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
// Si el nombre coincide con un producto del catálogo, se guarda también su código
const catalogo = @json($products);
document.getElementById('productName').addEventListener('input', function () {
    const p = catalogo.find(x => x.name.toLowerCase() === this.value.trim().toLowerCase());
    document.getElementById('productCode').value = p ? p.code : '';
    const hint = document.getElementById('hintCatalogo');
    hint.innerHTML = p
        ? '<i class="fas fa-circle-check text-emerald-500"></i> Producto del catálogo'
        : 'Si no está en el catálogo, escríbelo igual: sirve para saber qué incorporar.';
});
</script>
@endsection
