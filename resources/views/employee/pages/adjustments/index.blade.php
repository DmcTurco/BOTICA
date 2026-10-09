@extends('employee/layouts/base')

@section('title', 'Ajuste de Inventario')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Ajuste de Inventario</h1>
        <p class="text-sm text-slate-500 mt-0.5">Corrige el stock por conteo físico, merma, vencimiento o pérdida. Cada ajuste queda en el Kardex.</p>
    </div>

    @include('employee.partials.alerts')

    <form action="{{ route('employee.adjustments.store') }}" method="POST" id="formAjuste"
          class="bg-white rounded-xl border border-slate-200 shadow-sm shrink-0">
        @csrf
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 rounded-t-xl">
            <i class="fas fa-scale-balanced text-emerald-600 text-xs"></i>
            <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Nuevo ajuste</span>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-6 gap-4">

            <div class="sm:col-span-3 relative">
                <label class="block text-xs font-medium text-slate-600 mb-1">Producto <span class="text-red-500">*</span></label>
                <input type="hidden" name="product_code" id="productCode" value="{{ old('product_code') }}">
                <input type="text" id="buscadorProducto" autocomplete="off" placeholder="Busca por nombre o código..."
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                <div id="resultados" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-auto"></div>
                <p id="infoStock" class="text-xs text-slate-500 mt-1"></p>
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo de ajuste <span class="text-red-500">*</span></label>
                <select name="mode" id="mode"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="set" {{ old('mode', 'set') === 'set' ? 'selected' : '' }}>Fijar el stock contado (inventario físico)</option>
                    <option value="add" {{ old('mode') === 'add' ? 'selected' : '' }}>Sumar unidades</option>
                    <option value="subtract" {{ old('mode') === 'subtract' ? 'selected' : '' }}>Restar unidades</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1" id="lblCantidad">Cantidad contada <span class="text-red-500">*</span></label>
                <input type="number" name="quantity" id="quantity" min="0" step="0.01" required value="{{ old('quantity') }}"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <p id="previsualizacion" class="text-xs mt-1 text-slate-500"></p>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Motivo <span class="text-red-500">*</span></label>
                <select name="reason"
                        class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    @foreach($reasons as $key => $label)
                        <option value="{{ $key }}" {{ old('reason') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Nota</label>
                <input type="text" name="notes" maxlength="255" value="{{ old('notes') }}" placeholder="Detalle opcional"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Lote (al sumar unidades)</label>
                <input type="text" name="batch" maxlength="30" value="{{ old('batch') }}" placeholder="Opcional"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="sm:col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Vencimiento (al sumar unidades)</label>
                <input type="date" name="expiration_date" value="{{ old('expiration_date') }}"
                       class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="sm:col-span-6 flex justify-end">
                <button type="submit"
                        class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                    <i class="fas fa-save text-xs"></i> Registrar ajuste
                </button>
            </div>
        </div>
    </form>

    {{-- Historial --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-200">
            <p class="text-sm font-semibold text-slate-800">Últimos ajustes de la sede</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                        <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ajuste</th>
                        <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo</th>
                        <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($adjustments as $mov)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-2.5 text-xs text-slate-600">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-2.5 text-xs text-slate-800">{{ $mov->product?->name ?? $mov->product_code }}</td>
                        <td class="px-5 py-2.5 text-center">
                            <span class="font-semibold text-xs {{ $mov->quantity >= 0 ? 'text-emerald-700' : 'text-red-600' }}">{{ $mov->quantity > 0 ? '+' : '' }}{{ $mov->quantity }}</span>
                        </td>
                        <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $mov->balance }}</td>
                        <td class="px-5 py-2.5 text-xs text-slate-500 hidden md:table-cell">{{ $mov->notes }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-sm text-slate-400">Aún no hay ajustes registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($adjustments->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $adjustments->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
const productos = @json($products);
const inpBuscar = document.getElementById('buscadorProducto');
const lista = document.getElementById('resultados');
const inpCodigo = document.getElementById('productCode');
const selModo = document.getElementById('mode');
const inpCantidad = document.getElementById('quantity');

const esc = t => { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; };
const actual = () => productos.find(p => p.code === inpCodigo.value);

function previsualizar() {
    const p = actual();
    const out = document.getElementById('previsualizacion');
    const info = document.getElementById('infoStock');
    if (!p) { out.textContent = ''; info.textContent = ''; return; }
    info.innerHTML = `Stock actual en tu sede: <strong class="text-slate-700">${p.stock}</strong> ${esc(p.unit || '')}`;
    const q = parseFloat(inpCantidad.value);
    if (isNaN(q)) { out.textContent = ''; return; }
    const nuevo = selModo.value === 'set' ? q : (selModo.value === 'add' ? p.stock + q : p.stock - q);
    out.innerHTML = `Quedará en <strong class="${nuevo < 0 ? 'text-red-600' : 'text-slate-700'}">${Math.round(nuevo * 100) / 100}</strong>`;
}

function etiquetaCantidad() {
    document.getElementById('lblCantidad').innerHTML = (selModo.value === 'set' ? 'Cantidad contada' : 'Cantidad a ' + (selModo.value === 'add' ? 'sumar' : 'restar')) + ' <span class="text-red-500">*</span>';
}

inpBuscar.addEventListener('input', () => {
    const q = inpBuscar.value.toLowerCase().trim();
    if (!q) { lista.classList.add('hidden'); return; }
    const hallados = productos.filter(p => (p.name + ' ' + p.code).toLowerCase().includes(q)).slice(0, 30);
    lista.innerHTML = hallados.length ? hallados.map(p => `
        <button type="button" data-code="${esc(p.code)}" class="item w-full text-left px-3 py-2 hover:bg-emerald-50 border-b border-slate-100 last:border-0">
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
selModo.addEventListener('change', () => { etiquetaCantidad(); previsualizar(); });
inpCantidad.addEventListener('input', previsualizar);

// Carga inicial (por si volvió con error de validación)
if (actual()) inpBuscar.value = actual().name;
etiquetaCantidad();
previsualizar();
</script>
@endsection
