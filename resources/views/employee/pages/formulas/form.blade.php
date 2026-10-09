@extends('employee/layouts/base')

@section('title', isset($formula) ? 'Editar Fórmula' : 'Nueva Fórmula')
@section('main-padding', 'p-2 md:p-3')

@php
    $isEdit = isset($formula);

    // Catálogo para el buscador de insumos y el selector de producto final
    $catalogData = $products->map(fn ($p) => [
        'code' => $p->code,
        'name' => $p->name,
        'unit' => $p->unit?->abbreviation,
        'cost' => (float) $p->purchase_price,
    ])->values()->all();

    // Insumos actuales (old() si hubo error de validación)
    $ingredientsData = old('ingredients', $currentIngredients);
    $ingredientsData = array_values($ingredientsData);

    $sellInPos = old('sell_in_pos', $isEdit ? $formula->sell_in_pos : false);
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ $isEdit ? route('employee.formulas.show', $formula) : route('employee.formulas.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ $isEdit ? 'Editar Fórmula' : 'Nueva Fórmula' }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Define los insumos, las cantidades y lo que resulta al final</p>
        </div>
    </div>

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700 flex items-center gap-2 shrink-0">
        <i class="fas fa-circle-exclamation text-red-400"></i> {{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="shrink-0 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm space-y-1">
        @foreach($errors->all() as $error)
            <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form action="{{ $isEdit ? route('employee.formulas.update', $formula) : route('employee.formulas.store') }}"
          method="POST" id="formFormula" class="flex-1 min-h-0 overflow-auto space-y-3">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Datos generales --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 rounded-t-xl">
                <i class="fas fa-vial text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos de la fórmula</span>
                <span class="ml-auto text-xs font-mono text-slate-400">{{ $code }}</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-6 gap-4">

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nombre del preparado <span class="text-red-500">*</span></label>
                    <input type="text" name="name" required maxlength="150"
                           value="{{ old('name', $formula->name ?? '') }}"
                           placeholder="Ej: Crema de urea al 10%"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Forma farmacéutica</label>
                    <input type="text" name="pharmaceutical_form" maxlength="50" list="formas"
                           value="{{ old('pharmaceutical_form', $formula->pharmaceutical_form ?? '') }}"
                           placeholder="Crema, jarabe, cápsulas, solución..."
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <datalist id="formas">
                        @foreach(['Crema', 'Ungüento', 'Gel', 'Loción', 'Solución', 'Jarabe', 'Suspensión', 'Cápsulas', 'Tabletas', 'Óvulos', 'Polvo'] as $forma)
                            <option value="{{ $forma }}">
                        @endforeach
                    </datalist>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Rendimiento (resultado final) <span class="text-red-500">*</span></label>
                    <input type="number" name="yield_quantity" required min="0.01" step="0.01"
                           value="{{ old('yield_quantity', $formula->yield_quantity ?? '') }}"
                           placeholder="Ej: 100"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Unidad del resultado</label>
                    <select name="yield_unit_id"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">— Sin unidad —</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ (string) old('yield_unit_id', $formula->yield_unit_id ?? '') === (string) $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }} ({{ $unit->abbreviation }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Vida útil (días)</label>
                    <input type="number" name="shelf_life_days" min="1" max="3650" step="1"
                           value="{{ old('shelf_life_days', $formula->shelf_life_days ?? '') }}"
                           placeholder="30"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <div class="sm:col-span-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Estado</label>
                    <select name="status"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="1" {{ (string) old('status', $formula->status ?? 1) === '1' ? 'selected' : '' }}>Activa</option>
                        <option value="0" {{ (string) old('status', $formula->status ?? 1) === '0' ? 'selected' : '' }}>Inactiva</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Conservación</label>
                    <input type="text" name="storage" maxlength="255"
                           value="{{ old('storage', $formula->storage ?? '') }}"
                           placeholder="Ej: Envase hermético, proteger de la luz, T° ambiente"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Descripción / indicación</label>
                    <input type="text" name="description" maxlength="1000"
                           value="{{ old('description', $formula->description ?? '') }}"
                           placeholder="Uso o indicación del preparado"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>
            </div>
        </div>

        {{-- Venta en el POS --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 rounded-t-xl">
                <i class="fas fa-cash-register text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Venta en el punto de venta</span>
            </div>
            <div class="p-5 space-y-4">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="sell_in_pos" value="1" id="sellInPos" {{ $sellInPos ? 'checked' : '' }}
                           class="mt-0.5 w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>
                        <span class="block text-sm font-medium text-slate-700">Este preparado se vende en el POS</span>
                        <span class="block text-xs text-slate-500">Al preparar un lote, el resultado ingresa al stock del producto elegido y se vende como cualquier otro. Si lo dejas apagado, solo se registra el lote y se descuentan los insumos.</span>
                    </span>
                </label>

                <div id="bloqueProducto" class="{{ $sellInPos ? '' : 'hidden' }} max-w-xl">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Producto del catálogo que recibe el stock <span class="text-red-500">*</span></label>
                    <select name="product_code" id="productCode"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">— Selecciona un producto —</option>
                        @foreach($products as $p)
                            <option value="{{ $p->code }}" {{ (string) old('product_code', $formula->product_code ?? '') === (string) $p->code ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->code }})
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">
                        ¿No existe aún? <a href="{{ route('employee.products.create') }}" target="_blank" class="text-emerald-600 hover:underline">Crea el producto</a> con su precio de venta y vuelve a esta pantalla.
                    </p>
                </div>
            </div>
        </div>

        {{-- Insumos --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 rounded-t-xl">
                <i class="fas fa-mortar-pestle text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Insumos (por fórmula base)</span>
                <span id="contadorInsumos" class="px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">0</span>
            </div>
            <div class="p-5 space-y-3">

                {{-- Buscador de insumos --}}
                <div class="relative max-w-xl">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" id="buscadorInsumo" autocomplete="off"
                           class="w-full pl-8 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="Busca un producto para agregarlo como insumo...">
                    <div id="resultadosInsumo"
                         class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-64 overflow-auto"></div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Insumo</th>
                                <th class="text-center px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">Cantidad</th>
                                <th class="text-left px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nota (opcional)</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="filasInsumos" class="divide-y divide-slate-100"></tbody>
                    </table>
                    <p id="vacioInsumos" class="text-center text-sm text-slate-400 py-6">Aún no hay insumos. Búscalos arriba para agregarlos.</p>
                </div>
            </div>
        </div>

        {{-- Modo de preparación --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 rounded-t-xl">
                <i class="fas fa-list-ol text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Modo de preparación</span>
            </div>
            <div class="p-5">
                <textarea name="procedure" rows="5" maxlength="5000"
                          placeholder="1. Fundir la fase oleosa a 70 °C...&#10;2. Incorporar la fase acuosa agitando..."
                          class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">{{ old('procedure', $formula->procedure ?? '') }}</textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3 pb-2">
            <a href="{{ $isEdit ? route('employee.formulas.show', $formula) : route('employee.formulas.index') }}"
               class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancelar</a>
            <button type="submit"
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-save text-xs"></i> {{ $isEdit ? 'Guardar cambios' : 'Registrar fórmula' }}
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
const catalogo = @json($catalogData);
const insumosIniciales = @json($ingredientsData);

const filas = document.getElementById('filasInsumos');
const buscador = document.getElementById('buscadorInsumo');
const resultados = document.getElementById('resultadosInsumo');
let idx = 0;

function escapar(t) {
    const d = document.createElement('div');
    d.textContent = t ?? '';
    return d.innerHTML;
}

// Agrega una fila de insumo (si ya está, no se duplica)
function agregarInsumo(codigo, cantidad, nota) {
    if (filas.querySelector(`tr[data-code="${CSS.escape(codigo)}"]`)) return;
    const p = catalogo.find(x => x.code === codigo);
    if (!p) return;
    const i = idx++;
    const tr = document.createElement('tr');
    tr.dataset.code = codigo;
    tr.innerHTML = `
        <td class="px-3 py-2">
            <input type="hidden" name="ingredients[${i}][product_code]" value="${escapar(codigo)}">
            <p class="font-medium text-slate-800 text-xs">${escapar(p.name)}</p>
            <p class="text-[10px] text-slate-400 font-mono">${escapar(codigo)}</p>
        </td>
        <td class="px-3 py-2">
            <div class="flex items-center gap-1">
                <input type="number" name="ingredients[${i}][quantity]" value="${cantidad ?? ''}" min="0.01" step="0.01" required
                       class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <span class="text-xs text-slate-400 w-8">${escapar(p.unit || '')}</span>
            </div>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="ingredients[${i}][notes]" value="${escapar(nota || '')}" maxlength="255"
                   class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"
                   placeholder="Ej: tamizar, disolver en caliente">
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" class="btn-quitar w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors mx-auto">
                <i class="fas fa-trash text-[10px] pointer-events-none"></i>
            </button>
        </td>`;
    filas.appendChild(tr);
    actualizarContador();
}

function actualizarContador() {
    const n = filas.querySelectorAll('tr').length;
    document.getElementById('contadorInsumos').textContent = n;
    document.getElementById('vacioInsumos').classList.toggle('hidden', n > 0);
}

filas.addEventListener('click', e => {
    const btn = e.target.closest('.btn-quitar');
    if (!btn) return;
    btn.closest('tr').remove();
    actualizarContador();
});

// Buscador con lista desplegable
function mostrarResultados() {
    const q = buscador.value.toLowerCase().trim();
    if (q.length < 1) { resultados.classList.add('hidden'); return; }
    const hallados = catalogo
        .filter(p => (p.name + ' ' + p.code).toLowerCase().includes(q))
        .slice(0, 30);
    resultados.innerHTML = hallados.length
        ? hallados.map(p => `
            <button type="button" data-code="${escapar(p.code)}"
                    class="item-resultado w-full text-left px-3 py-2 hover:bg-emerald-50 border-b border-slate-100 last:border-0">
                <p class="text-xs font-medium text-slate-800">${escapar(p.name)}</p>
                <p class="text-[10px] text-slate-400 font-mono">${escapar(p.code)} · ${escapar(p.unit || 'sin unidad')}</p>
            </button>`).join('')
        : '<p class="px-3 py-3 text-xs text-slate-400">Sin resultados</p>';
    resultados.classList.remove('hidden');
}
buscador.addEventListener('input', mostrarResultados);
buscador.addEventListener('keydown', e => { if (e.key === 'Enter') e.preventDefault(); });
resultados.addEventListener('click', e => {
    const item = e.target.closest('.item-resultado');
    if (!item) return;
    agregarInsumo(item.dataset.code, '', '');
    buscador.value = '';
    resultados.classList.add('hidden');
    // Enfoca la cantidad del insumo recién agregado
    const ultima = filas.lastElementChild;
    if (ultima) ultima.querySelector('input[type=number]').focus();
});
document.addEventListener('click', e => {
    if (!e.target.closest('#buscadorInsumo') && !e.target.closest('#resultadosInsumo')) {
        resultados.classList.add('hidden');
    }
});

// Interruptor de venta en POS
document.getElementById('sellInPos').addEventListener('change', function () {
    document.getElementById('bloqueProducto').classList.toggle('hidden', !this.checked);
});

// Carga inicial (edición o regreso por error de validación)
insumosIniciales.forEach(i => agregarInsumo(i.product_code, i.quantity, i.notes));
actualizarContador();
</script>
@endsection
