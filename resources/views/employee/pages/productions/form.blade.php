@extends('employee/layouts/base')

@section('title', 'Preparar Lote')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.productions.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Preparar Lote</h1>
            <p class="text-sm text-slate-500 mt-0.5">Elabora una fórmula: se descuentan los insumos y se registra el lote</p>
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

    <form action="{{ route('employee.productions.store') }}" method="POST" id="formProduccion"
          class="flex-1 min-h-0 overflow-auto space-y-3">
        @csrf

        {{-- Datos del lote --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center gap-2 rounded-t-xl">
                <i class="fas fa-flask-vial text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos del lote</span>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-6 gap-4">

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Fórmula <span class="text-red-500">*</span></label>
                    <select name="formula_id" id="formulaId" required
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">— Selecciona una fórmula —</option>
                        @foreach($formulasData as $f)
                            <option value="{{ $f['id'] }}">{{ $f['name'] }} ({{ $f['code'] }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cantidad a preparar <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="quantity_produced" id="cantidad" required min="0.01" step="0.01"
                               value="{{ old('quantity_produced') }}"
                               class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <span id="unidadCantidad" class="text-sm text-slate-500 w-12"></span>
                    </div>
                    <p id="ayudaCantidad" class="text-xs text-slate-400 mt-1">Por defecto, el rendimiento de la fórmula base.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Fecha de elaboración <span class="text-red-500">*</span></label>
                    <input type="date" name="produced_at" id="fechaElaboracion" required max="{{ date('Y-m-d') }}"
                           value="{{ old('produced_at', date('Y-m-d')) }}"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Vencimiento</label>
                    <input type="date" name="expiration_date" id="fechaVencimiento"
                           value="{{ old('expiration_date') }}"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <p id="ayudaVencimiento" class="text-xs text-slate-400 mt-1">Se calcula con la vida útil de la fórmula.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">N° de lote</label>
                    <input type="text" name="batch" maxlength="30" value="{{ old('batch') }}"
                           placeholder="Automático"
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>

                <div class="sm:col-span-6">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Observaciones</label>
                    <input type="text" name="notes" maxlength="500" value="{{ old('notes') }}"
                           placeholder="Notas opcionales (ej. paciente, solicitud del médico)..."
                           class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                </div>
            </div>
        </div>

        {{-- Resultado y consumo calculado --}}
        <div id="panelResumen" class="hidden space-y-3">

            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5">
                <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider mb-3">Resultado de esta preparación</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-emerald-700/70 mb-1">Se obtendrá</p>
                        <p class="text-lg font-bold text-emerald-900" id="resObtendra">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-700/70 mb-1">Costo total</p>
                        <p class="text-lg font-bold text-emerald-900" id="resCostoTotal">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-700/70 mb-1">Costo por unidad</p>
                        <p class="text-lg font-bold text-emerald-900" id="resCostoUnit">—</p>
                    </div>
                    <div>
                        <p class="text-xs text-emerald-700/70 mb-1">Destino</p>
                        <p class="text-sm font-semibold text-emerald-900" id="resDestino">—</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-200">
                    <p class="text-sm font-semibold text-slate-800">Insumos que se descontarán del stock de tu sede</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Insumo</th>
                                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Necesario</th>
                                <th class="text-center px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                                <th class="text-right px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Costo</th>
                            </tr>
                        </thead>
                        <tbody id="filasConsumo" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pb-2">
            <p id="avisoStock" class="hidden text-xs text-red-600 flex items-center gap-1">
                <i class="fas fa-triangle-exclamation"></i> Hay insumos sin stock suficiente
            </p>
            <a href="{{ route('employee.productions.index') }}"
               class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Cancelar</a>
            <button type="submit" id="btnGuardar"
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 disabled:cursor-not-allowed rounded-lg transition-colors shadow-sm flex items-center gap-2">
                <i class="fas fa-save text-xs"></i> Registrar preparación
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
const formulas = @json($formulasData);
const preseleccion = {{ $selectedFormula ?: 'null' }};
const oldFormula = @json(old('formula_id'));

const selFormula = document.getElementById('formulaId');
const inpCantidad = document.getElementById('cantidad');
const inpFecha = document.getElementById('fechaElaboracion');
const inpVence = document.getElementById('fechaVencimiento');
let vencimientoManual = {{ old('expiration_date') ? 'true' : 'false' }};

const fmt = n => (Math.round(n * 100) / 100).toLocaleString('es-PE', { maximumFractionDigits: 2 });
const money = n => 'S/. ' + n.toFixed(2);
function escapar(t) { const d = document.createElement('div'); d.textContent = t ?? ''; return d.innerHTML; }

function formulaActual() {
    return formulas.find(f => String(f.id) === selFormula.value);
}

// Calcula el vencimiento por defecto según la vida útil de la fórmula
function calcularVencimiento() {
    const f = formulaActual();
    if (vencimientoManual) return;
    if (f && f.shelf_life && inpFecha.value) {
        const d = new Date(inpFecha.value + 'T00:00:00');
        d.setDate(d.getDate() + parseInt(f.shelf_life));
        inpVence.value = d.toISOString().slice(0, 10);
    } else {
        inpVence.value = '';
    }
}

function recalcular() {
    const f = formulaActual();
    const panel = document.getElementById('panelResumen');
    const btn = document.getElementById('btnGuardar');
    if (!f) {
        panel.classList.add('hidden');
        btn.disabled = true;
        return;
    }
    const qty = parseFloat(inpCantidad.value) || 0;
    const mult = f.yield > 0 ? qty / f.yield : 0;
    let costo = 0, faltante = false;

    document.getElementById('filasConsumo').innerHTML = f.ingredients.map(i => {
        const necesario = Math.round(i.qty * mult * 100) / 100;
        const sub = necesario * i.cost;
        costo += sub;
        const falta = necesario > i.stock;
        if (falta) faltante = true;
        return `<tr class="${falta ? 'bg-red-50/60' : ''}">
            <td class="px-5 py-2.5 text-xs font-medium text-slate-800">${escapar(i.name || i.code)}</td>
            <td class="px-5 py-2.5 text-center text-xs text-slate-700">${fmt(necesario)} ${escapar(i.unit || '')}</td>
            <td class="px-5 py-2.5 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${falta ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-700'}">${fmt(i.stock)}</span>
            </td>
            <td class="px-5 py-2.5 text-right text-xs text-slate-600">${money(sub)}</td>
        </tr>`;
    }).join('');

    document.getElementById('resObtendra').textContent = qty > 0 ? `${fmt(qty)} ${f.unit || ''} de ${f.name}` : '—';
    document.getElementById('resCostoTotal').textContent = money(costo);
    document.getElementById('resCostoUnit').textContent = qty > 0 ? money(costo / qty) : '—';
    document.getElementById('resDestino').textContent = f.sell_in_pos
        ? `Ingresa al stock de «${f.product_name || 'producto'}» para venderse en el POS`
        : 'Solo se registra el lote (no se vende en el POS)';

    document.getElementById('avisoStock').classList.toggle('hidden', !faltante);
    btn.disabled = faltante || qty <= 0;
    panel.classList.remove('hidden');
}

selFormula.addEventListener('change', () => {
    const f = formulaActual();
    document.getElementById('unidadCantidad').textContent = f ? (f.unit || '') : '';
    if (f) {
        inpCantidad.value = f.yield;
        document.getElementById('ayudaCantidad').textContent = `Rendimiento de la fórmula base: ${fmt(f.yield)} ${f.unit || ''}`;
    }
    vencimientoManual = false;
    calcularVencimiento();
    recalcular();
});
inpCantidad.addEventListener('input', recalcular);
inpFecha.addEventListener('change', calcularVencimiento);
inpVence.addEventListener('input', () => { vencimientoManual = inpVence.value !== ''; });

// Evita que Enter envíe el formulario sin querer
document.getElementById('formProduccion').addEventListener('keydown', e => {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') e.preventDefault();
});

// Carga inicial: fórmula elegida desde el listado o devuelta por un error de validación
const inicial = oldFormula || preseleccion;
if (inicial) {
    const cantidadPrevia = inpCantidad.value;
    selFormula.value = String(inicial);
    selFormula.dispatchEvent(new Event('change'));
    if (cantidadPrevia) { inpCantidad.value = cantidadPrevia; recalcular(); }
} else {
    document.getElementById('btnGuardar').disabled = true;
}
</script>
@endsection
