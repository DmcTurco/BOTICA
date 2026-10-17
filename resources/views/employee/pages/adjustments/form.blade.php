@extends('employee/layouts/base')

@section('title', 'Nuevo Ajuste de Inventario')
@section('main-padding', 'p-2 md:p-3')

@php
    $inputClass = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent';
    $mode       = old('mode', 'set');
    // Tipos de ajuste: [valor => [título, descripción, ícono]]
    $modes = [
        'set'      => ['Fijar stock contado', 'Inventario físico: el stock queda en lo que contaste', 'fa-clipboard-check'],
        'add'      => ['Sumar unidades', 'Entraron unidades que no son compra (devolución, hallazgo...)', 'fa-circle-plus'],
        'subtract' => ['Restar unidades', 'Merma, vencidos, rotura o pérdida', 'fa-circle-minus'],
    ];
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('employee.adjustments.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Nuevo Ajuste de Inventario</h1>
            <p class="text-sm text-slate-500 mt-0.5">Corrige el stock por conteo físico, merma, vencimiento o pérdida. Cada ajuste queda en el Kardex.</p>
        </div>
    </div>

    <form action="{{ route('employee.adjustments.store') }}" method="POST" id="formAjuste" class="flex-1 flex flex-col min-h-0">
        @csrf

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-scale-balanced text-sky-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos del ajuste</span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                {{-- Producto: buscador sobre los productos de la compañía con su stock en la sede --}}
                <div class="relative">
                    <label for="buscadorProducto" class="block text-sm font-medium text-slate-700 mb-1.5">Producto <span class="text-red-500">*</span></label>
                    <input type="hidden" name="product_code" id="productCode" value="{{ old('product_code') }}">
                    <input type="text" id="buscadorProducto" autocomplete="off" placeholder="Busca por nombre o código..."
                           class="{{ $inputClass }} @error('product_code') border-red-400 @enderror">
                    <div id="resultados" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-auto"></div>
                    <p id="infoStock" class="text-xs text-slate-500 mt-1"></p>
                    @error('product_code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Tipo de ajuste: una tarjeta por opción --}}
                <div>
                    <p class="block text-sm font-medium text-slate-700 mb-1.5">Tipo de ajuste <span class="text-red-500">*</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($modes as $value => [$title, $hint, $icon])
                        <label class="cursor-pointer">
                            <input type="radio" name="mode" value="{{ $value }}" class="peer sr-only" {{ $mode === $value ? 'checked' : '' }}>
                            <div class="h-full flex items-start gap-3 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-sky-500 peer-checked:bg-sky-50 hover:border-slate-300 transition-colors">
                                <i class="fas {{ $icon }} text-sky-600 mt-0.5"></i>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ $title }}</p>
                                    <p class="text-xs text-slate-500">{{ $hint }}</p>
                                </div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="quantity" class="block text-sm font-medium text-slate-700 mb-1.5" id="lblCantidad">Cantidad contada <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" id="quantity" min="0" step="0.01" value="{{ old('quantity') }}"
                               class="{{ $inputClass }} @error('quantity') border-red-400 @enderror">
                        <p id="previsualizacion" class="text-xs mt-1 text-slate-500"></p>
                        @error('quantity')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="reason" class="block text-sm font-medium text-slate-700 mb-1.5">Motivo <span class="text-red-500">*</span></label>
                        <select name="reason" id="reason" class="{{ $inputClass }}">
                            @foreach($reasons as $key => $label)
                                <option value="{{ $key }}" {{ old('reason') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-1.5">Nota <span class="text-slate-400 font-normal">(opcional)</span></label>
                        <input type="text" name="notes" id="notes" maxlength="255" value="{{ old('notes') }}" placeholder="Detalle opcional" class="{{ $inputClass }}">
                    </div>
                </div>

                {{-- Lote y vencimiento: solo cuando el ajuste puede aumentar el stock (al restar sale por FEFO) --}}
                <div id="bloqueLote" class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="batch" class="block text-sm font-medium text-slate-700 mb-1.5">Lote <span class="text-slate-400 font-normal">(si entran unidades)</span></label>
                        <input type="text" name="batch" id="batch" maxlength="30" value="{{ old('batch') }}" placeholder="Opcional" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="expiration_date" class="block text-sm font-medium text-slate-700 mb-1.5">Vencimiento <span class="text-slate-400 font-normal">(si entran unidades)</span></label>
                        <input type="date" name="expiration_date" id="expiration_date" value="{{ old('expiration_date') }}" class="{{ $inputClass }}">
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ \App\Support\ListUrl::to('employee.adjustments.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    Registrar ajuste
                </button>
            </div>

        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
const productos = @json($products);
const inpBuscar = document.getElementById('buscadorProducto');
const lista = document.getElementById('resultados');
const inpCodigo = document.getElementById('productCode');
const inpCantidad = document.getElementById('quantity');

const esc = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };
const actual = () => productos.find(p => p.code === inpCodigo.value);
const modo = () => document.querySelector('input[name="mode"]:checked')?.value || 'set';

// Vista previa: en cuánto queda el stock con el ajuste
function previsualizar() {
    const p = actual();
    const out = document.getElementById('previsualizacion');
    const info = document.getElementById('infoStock');
    if (!p) { out.textContent = ''; info.textContent = ''; return; }
    info.innerHTML = `Stock actual en tu sede: <strong class="text-slate-700">${p.stock}</strong> ${esc(p.unit || '')}`;
    const q = parseFloat(inpCantidad.value);
    if (isNaN(q)) { out.textContent = ''; return; }
    const nuevo = modo() === 'set' ? q : (modo() === 'add' ? p.stock + q : p.stock - q);
    out.innerHTML = `Quedará en <strong class="${nuevo < 0 ? 'text-red-600' : 'text-slate-700'}">${Math.round(nuevo * 100) / 100}</strong>`;
}

// La etiqueta de la cantidad y el bloque de lote dependen del tipo de ajuste
function segunModo() {
    document.getElementById('lblCantidad').innerHTML = (modo() === 'set' ? 'Cantidad contada' : 'Cantidad a ' + (modo() === 'add' ? 'sumar' : 'restar')) + ' <span class="text-red-500">*</span>';
    document.getElementById('bloqueLote').classList.toggle('hidden', modo() === 'subtract');
}

inpBuscar.addEventListener('input', () => {
    const q = inpBuscar.value.toLowerCase().trim();
    inpCodigo.value = '';
    previsualizar();
    if (!q) { lista.classList.add('hidden'); return; }
    const hallados = productos.filter(p => (p.name + ' ' + p.code).toLowerCase().includes(q)).slice(0, 30);
    lista.innerHTML = hallados.length ? hallados.map(p => `
        <button type="button" data-code="${esc(p.code)}" class="item w-full text-left px-3 py-2 hover:bg-sky-50 border-b border-slate-100 last:border-0">
            <p class="text-xs font-medium text-slate-800">${esc(p.name)}</p>
            <p class="text-[10px] text-slate-400 font-mono">${esc(p.code)} · stock ${p.stock}</p>
        </button>`).join('') : '<p class="px-3 py-3 text-xs text-slate-400">Sin resultados</p>';
    lista.classList.remove('hidden');
});
inpBuscar.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
lista.addEventListener('click', e => {
    const item = e.target.closest('.item');
    if (!item) return;
    inpCodigo.value = item.dataset.code;
    inpBuscar.value = actual().name;
    lista.classList.add('hidden');
    previsualizar();
    inpCantidad.focus();
});
document.addEventListener('click', e => { if (!e.target.closest('#buscadorProducto') && !e.target.closest('#resultados')) lista.classList.add('hidden'); });
document.querySelectorAll('input[name="mode"]').forEach(r => r.addEventListener('change', () => { segunModo(); previsualizar(); }));
inpCantidad.addEventListener('input', previsualizar);

// Carga inicial (por si volvió con error de validación)
if (actual()) inpBuscar.value = actual().name;
segunModo();
previsualizar();
</script>
@endsection
