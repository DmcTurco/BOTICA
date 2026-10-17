@extends('employee/layouts/base')

@section('title', 'Nueva Compra')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
    <div class="flex-1 flex flex-col gap-3 min-h-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('employee.purchases.index') }}"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Nueva Compra</h1>
                <p class="text-sm text-slate-500 mt-0.5">Registra una boleta o factura para incrementar el stock</p>
            </div>
        </div>

        <form action="{{ route('employee.purchases.store') }}" method="POST" id="formCompra"
            class="flex-1 flex flex-col min-h-0 gap-3">
            @csrf
            @if ($order)
                <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">
                <div class="shrink-0 bg-sky-50 border border-sky-200 text-sky-800 rounded-xl px-4 py-2.5 text-sm">
                    <i class="fas fa-file-circle-check mr-1"></i> Recibiendo la orden <strong>{{ $order->number }}</strong>: revisa cantidades, costos, lotes y vencimientos antes de registrar.
                </div>
            @endif
            @include('employee.partials.alerts')

            {{-- Datos del documento: compacto y plegable para dejar el alto a los productos --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-4 py-3 shrink-0">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider shrink-0">Datos del documento</p>
                    {{-- Resumen visible solo con el bloque plegado --}}
                    <p id="resumenDocumento" class="hidden flex-1 min-w-0 truncate text-xs text-slate-600"></p>
                    <button type="button" id="btnPlegarDocumento"
                        class="shrink-0 inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium text-slate-500 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition-colors">
                        <span>Ocultar</span> <i class="fas fa-chevron-up text-[10px]"></i>
                    </button>
                </div>
                <div id="camposDocumento" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mt-2">

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Tipo <span
                                class="text-red-500">*</span></label>
                        <select name="document_type"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <option value="1" {{ old('document_type', 1) == 1 ? 'selected' : '' }}>Boleta</option>
                            <option value="2" {{ old('document_type') == 2 ? 'selected' : '' }}>Factura</option>
                            <option value="3" {{ old('document_type') == 3 ? 'selected' : '' }}>Nota de ingreso
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">N° Documento</label>
                        <input type="text" name="document_number" value="{{ old('document_number') }}"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="Ej: B001-00123">
                    </div>

                    {{-- Proveedor: un solo campo que busca entre los registrados; si no existe, se crea con el nombre escrito --}}
                    @php
                        $selectedSupplierId = old('supplier_id', $order?->supplier_id);
                        $supplierName = old('supplier', $suppliers->firstWhere('id', $selectedSupplierId)?->name);
                    @endphp
                    <div class="relative">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Proveedor</label>
                        <input type="hidden" name="supplier_id" id="supplierId" value="{{ $selectedSupplierId }}">
                        <input type="text" name="supplier" id="supplierText" value="{{ $supplierName }}" autocomplete="off"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="Buscar o escribir un proveedor nuevo">
                        <div id="supplierList"
                            class="absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-slate-200 rounded-lg shadow-lg max-h-56 overflow-auto hidden"></div>
                        <p id="supplierHint" class="text-[11px] mt-1 text-slate-400">Opcional</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Condición de pago</label>
                        <select name="payment_condition" id="paymentCondition"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <option value="cash" {{ old('payment_condition', 'cash') === 'cash' ? 'selected' : '' }}>Contado</option>
                            <option value="credit" {{ old('payment_condition') === 'credit' ? 'selected' : '' }}>Crédito</option>
                        </select>
                        <input type="date" name="due_date" id="dueDate" value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}"
                            class="mt-1 w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent hidden"
                            title="Fecha límite de pago">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Fecha <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="purchased_at" value="{{ old('purchased_at', date('Y-m-d')) }}"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Observaciones</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="Notas opcionales...">
                    </div>

                </div>
            </div>

            {{-- Pestañas (tablet y laptops angostas, < 1280 px): un panel a la vez, a pantalla completa --}}
            <div class="xl:hidden shrink-0 grid grid-cols-2 gap-1 p-1 bg-slate-200/70 rounded-xl" id="pestanasCompra">
                <button type="button" data-panel="catalogo"
                    class="pestana-compra px-3 py-2 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 bg-white text-emerald-700 shadow-sm">
                    <i class="fas fa-boxes-stacked text-xs"></i> Catálogo
                </button>
                <button type="button" data-panel="seleccionados"
                    class="pestana-compra px-3 py-2 text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2 text-slate-600">
                    <i class="fas fa-cart-shopping text-xs"></i> Seleccionados
                    <span id="contadorPestana" class="px-1.5 py-0.5 bg-emerald-600 text-white rounded-full text-[10px] font-bold">0</span>
                </button>
            </div>

            {{-- Panel principal: catálogo (40 %) y seleccionados (60 %, donde se trabaja) --}}
            <div class="flex-1 flex gap-3 min-h-0 flex-col xl:flex-row">

                {{-- ── COLUMNA IZQUIERDA: Catálogo de productos ──────────── --}}
                <div data-panel-compra="catalogo"
                    class="flex-1 xl:flex-2 min-w-0 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

                    {{-- Cabecera con buscador --}}
                    <div class="px-4 py-3 border-b border-slate-200 shrink-0">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Catálogo de productos
                        </p>
                        <div class="relative">
                            <i
                                class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                            <input type="text" id="buscadorProducto"
                                class="w-full pl-8 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                placeholder="Buscar por nombre o código...">
                        </div>
                    </div>

                    {{-- Lista de productos --}}
                    <div class="flex-1 min-h-0 overflow-auto">
                        <table class="w-full text-sm" id="tablaProductos">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th
                                        class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Producto</th>
                                    <th
                                        class="text-center px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">
                                        Stock</th>
                                    <th
                                        class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider w-24">
                                        P. Compra</th>
                                    <th class="w-12 px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" id="cuerpoProductos">
                                @foreach ($productos as $p)
                                    @php
                                        // Presentaciones distintas a la unidad base (las de 1 unidad son la misma opción)
                                        $presData = $p->presentations->filter(fn ($x) => (float) $x->equivalent_amount != 1.0)->map(fn ($x) => [
                                            'id'     => $x->id,
                                            'label'  => $x->unit?->name ?? 'Presentación',
                                            'amount' => (float) $x->equivalent_amount,
                                        ])->values();
                                    @endphp
                                    <tr class="hover:bg-emerald-50 transition-colors cursor-pointer fila-producto"
                                        data-code="{{ $p->code }}" data-barcode="{{ $p->barcode }}" data-name="{{ $p->name }}"
                                        data-unit="{{ $p->unit?->name ?? 'Unidad' }}"
                                        data-presentations="{{ json_encode($presData) }}"
                                        data-cost="{{ $p->purchase_price ?? 0 }}">
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-slate-800 text-xs">{{ $p->name }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ $p->code }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ ($p->branchStocks->first()?->stock_actual ?? 0) <= ($p->branchStocks->first()?->stock_minimum ?? 0) ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                {{ $p->branchStocks->first()?->stock_actual ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-xs text-slate-500">
                                            S/. {{ number_format($p->purchase_price ?? 0, 2) }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <button type="button"
                                                class="w-7 h-7 flex items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-colors btn-agregar mx-auto"
                                                title="Agregar a la compra">
                                                <i class="fas fa-plus text-[10px]"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Pie: total de productos --}}
                    <div class="px-4 py-2 border-t border-slate-100 bg-slate-50 shrink-0">
                        <p class="text-xs text-slate-400"><span id="contadorProductos">{{ count($productos) }}</span>
                            productos disponibles</p>
                    </div>
                </div>

                {{-- ── COLUMNA DERECHA: Productos seleccionados ──────────── --}}
                <div data-panel-compra="seleccionados"
                    class="max-xl:hidden flex-1 xl:flex-3 min-w-0 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

                    {{-- Cabecera --}}
                    <div class="px-4 py-3 border-b border-slate-200 shrink-0 flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                            Productos seleccionados
                            <span id="contadorSeleccionados"
                                class="ml-1.5 px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">0</span>
                        </p>
                        <button type="button" id="btnLimpiar"
                            class="text-xs text-slate-400 hover:text-red-500 transition-colors hidden">
                            <i class="fas fa-trash text-[10px] mr-1"></i> Limpiar todo
                        </button>
                    </div>

                    @error('items')
                        <p class="text-red-500 text-xs px-4 pt-2">{{ $message }}</p>
                    @enderror

                    {{-- Lista scrollable --}}
                    <div class="flex-1 min-h-0 overflow-auto" id="contenedorSeleccionados">

                        {{-- Estado vacío --}}
                        <div id="emptyState" class="flex flex-col items-center justify-center h-full py-16 text-slate-300">
                            <i class="fas fa-cart-plus text-4xl mb-3"></i>
                            <p class="text-sm font-medium">Sin productos</p>
                            <p class="text-xs mt-1">Selecciona productos del catálogo</p>
                        </div>

                        {{-- Seleccionados: cada producto en dos líneas (datos arriba, campos abajo) para que
                             el nombre se lea completo a cualquier ancho. Las filas las genera el JS. --}}
                        <div id="tablaSeleccionados" class="hidden">
                            <div id="filasSeleccionados" class="divide-y divide-slate-100"></div>
                        </div>
                    </div>

                    {{-- Pie compacto en una fila: subtotal + IGV | total + botón guardar (deja el alto a la lista) --}}
                    <div class="border-t border-slate-200 bg-slate-50 px-4 py-2.5 shrink-0 flex flex-wrap items-center gap-x-5 gap-y-2">
                        <div class="text-xs text-slate-500">
                            Subtotal <span id="resumenSubtotal" class="ml-1 font-medium text-slate-700">S/. 0.00</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <label for="tax">IGV</label>
                            <div class="relative w-24">
                                <span
                                    class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">S/.</span>
                                <input type="number" id="tax" name="tax" min="0" step="0.01"
                                    value="{{ old('tax', 0) }}"
                                    class="w-full pl-7 pr-2 py-1 text-xs border border-slate-300 rounded-lg bg-white text-right focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    oninput="recalcularTotales()">
                            </div>
                        </div>
                        <div class="ml-auto flex items-center gap-4">
                            <p class="text-sm font-bold text-slate-700">
                                Total <span id="resumenTotal" class="ml-1 text-lg text-emerald-700">S/. 0.00</span>
                            </p>
                            <button type="submit"
                                class="px-5 py-2.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm flex items-center justify-center gap-2">
                                <i class="fas fa-save text-xs"></i> Registrar compra
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </form>
    </div>
@endsection

@section('scripts')
    <script>
        // Evitar que Enter envíe el formulario accidentalmente
        document.getElementById('formCompra').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });

        // Condición de pago: la fecha límite solo aplica a compras a crédito
        const condicion = document.getElementById('paymentCondition');
        function actualizarCondicion() {
            document.getElementById('dueDate').classList.toggle('hidden', condicion.value !== 'credit');
        }
        condicion.addEventListener('change', actualizarCondicion);
        actualizarCondicion();

        // ── Proveedor: buscador único ────────────────────────────────────────────────
        // Si se elige uno de la lista queda vinculado (supplier_id); si no existe, se registra con el nombre escrito.
        const proveedores = @json($suppliers->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'ruc' => $s->ruc])->values());
        const provId = document.getElementById('supplierId');
        const provTexto = document.getElementById('supplierText');
        const provLista = document.getElementById('supplierList');
        const provHint = document.getElementById('supplierHint');

        function pintarHintProveedor() {
            const nombre = provTexto.value.trim();
            if (provId.value) {
                provHint.innerHTML = '<i class="fas fa-circle-check text-emerald-500"></i> Proveedor registrado';
                provHint.className = 'text-[11px] mt-1 text-emerald-600';
            } else if (nombre) {
                provHint.innerHTML = '<i class="fas fa-circle-plus text-sky-500"></i> Se registrará como proveedor nuevo';
                provHint.className = 'text-[11px] mt-1 text-sky-600';
            } else {
                provHint.textContent = 'Opcional';
                provHint.className = 'text-[11px] mt-1 text-slate-400';
            }
        }

        function mostrarProveedores() {
            const q = provTexto.value.toLowerCase().trim();
            const coincidencias = proveedores.filter(p => !q || p.name.toLowerCase().includes(q) || (p.ruc || '').includes(q)).slice(0, 8);
            provLista.innerHTML = '';
            coincidencias.forEach(p => {
                const op = document.createElement('div');
                op.className = 'px-3 py-2 text-sm cursor-pointer hover:bg-emerald-50 flex justify-between gap-2';
                op.innerHTML = '<span></span><span class="text-xs text-slate-400 font-mono"></span>';
                op.children[0].textContent = p.name;
                op.children[1].textContent = p.ruc || '';
                op.addEventListener('mousedown', e => {
                    e.preventDefault();
                    provId.value = p.id;
                    provTexto.value = p.name;
                    provLista.classList.add('hidden');
                    pintarHintProveedor();
                });
                provLista.appendChild(op);
            });
            if (q && !proveedores.some(p => p.name.toLowerCase() === q)) {
                const nuevo = document.createElement('div');
                nuevo.className = 'px-3 py-2 text-sm text-sky-700 bg-sky-50 border-t border-slate-100';
                nuevo.innerHTML = '<i class="fas fa-plus text-xs mr-1"></i> Usar como proveedor nuevo: <strong></strong>';
                nuevo.querySelector('strong').textContent = provTexto.value.trim();
                provLista.appendChild(nuevo);
            }
            provLista.classList.toggle('hidden', provLista.children.length === 0);
        }

        // Al escribir se desvincula el proveedor elegido: lo escrito manda
        provTexto.addEventListener('input', () => { provId.value = ''; mostrarProveedores(); pintarHintProveedor(); });
        provTexto.addEventListener('focus', mostrarProveedores);
        provTexto.addEventListener('blur', () => {
            provLista.classList.add('hidden');
            // Si lo escrito coincide exactamente con uno registrado, se vincula
            const igual = proveedores.find(p => p.name.toLowerCase() === provTexto.value.trim().toLowerCase());
            if (igual) { provId.value = igual.id; provTexto.value = igual.name; }
            pintarHintProveedor();
        });
        pintarHintProveedor();

        // ── Estado de los ítems seleccionados ────────────────────────────────────────
        // Map<productCode, { name, quantity, unitCost, idx }>
        const seleccionados = new Map();
        let itemIdx = 0;

        // ── Buscador del catálogo ────────────────────────────────────────────────────
        document.getElementById('buscadorProducto').addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('.fila-producto').forEach(fila => {
                const texto = fila.dataset.name.toLowerCase() + ' ' + fila.dataset.code.toLowerCase() + ' ' + (fila.dataset.barcode || '').toLowerCase();
                const mostrar = texto.includes(q);
                fila.style.display = mostrar ? '' : 'none';
                if (mostrar) visible++;
            });
            document.getElementById('contadorProductos').textContent = visible;
        });

        // ── Agregar producto al hacer clic en la fila o en el botón ─────────────────
        document.getElementById('cuerpoProductos').addEventListener('click', function(e) {
            const fila = e.target.closest('.fila-producto');
            if (!fila) return;

            const code = fila.dataset.code;
            const name = fila.dataset.name;
            const cost = parseFloat(fila.dataset.cost) || 0;
            const unit = fila.dataset.unit || 'Unidad';
            const presentations = JSON.parse(fila.dataset.presentations || '[]');

            if (seleccionados.has(code)) {
                // Si ya está, incrementar cantidad
                const item = seleccionados.get(code);
                item.quantity++;
                seleccionados.set(code, item);
                actualizarFilaSeleccionada(code);
            } else {
                // Agregar nuevo ítem
                seleccionados.set(code, {
                    name,
                    unit,
                    presentations,
                    baseCost: cost,       // costo por unidad base del producto
                    factor: 1,            // unidades base que trae la presentación elegida
                    quantity: 1,
                    unitCost: cost,       // costo por presentación elegida
                    idx: itemIdx++
                });
                renderizarFilaSeleccionada(code);
            }

            actualizarUI();
        });

        // ── Renderiza una fila nueva en la tabla de seleccionados ───────────────────
        function renderizarFilaSeleccionada(code) {
            const item = seleccionados.get(code);
            const idx = item.idx;

            const lista = document.getElementById('filasSeleccionados');
            const fila = document.createElement('div');
            fila.className = 'item-seleccionado px-4 py-3 hover:bg-slate-50 transition-colors';
            fila.dataset.code = code;
            fila.innerHTML = `
            <input type="hidden" name="items[${idx}][product_code]" value="${code}">
            <div class="flex items-start gap-3">
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-slate-800 text-sm leading-tight nombre-item"></p>
                    <p class="text-[10px] text-slate-400 font-mono">${code}</p>
                </div>
                ${item.presentations.length ? `
                <select name="items[${idx}][presentation_id]" data-code="${code}" data-field="presentation"
                    class="input-seleccionado w-40 shrink-0 px-2 py-1 text-xs border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="" data-factor="1">${item.unit} (1 unidad)</option>
                    ${item.presentations.map(p => `<option value="${p.id}" data-factor="${p.amount}">${p.label} (${p.amount} unid.)</option>`).join('')}
                </select>` : ''}
                <p class="shrink-0 w-24 text-right text-sm font-semibold text-slate-700 celda-subtotal" data-code="${code}">
                    S/. ${(item.quantity * item.unitCost).toFixed(2)}
                </p>
                <button type="button" data-code="${code}" title="Quitar"
                        class="btn-quitar shrink-0 w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors">
                    <i class="fas fa-trash text-[10px] pointer-events-none"></i>
                </button>
            </div>
            <p class="text-[10px] text-emerald-700 mt-0.5 hint-unidades" data-code="${code}"></p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2">
                <label class="block">
                    <span class="block text-[10px] font-medium text-slate-500 mb-0.5">Cantidad</span>
                    <input type="number" name="items[${idx}][quantity]"
                        value="${item.quantity}" min="1" step="1" required
                        data-code="${code}" data-field="quantity"
                        class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-emerald-500 input-seleccionado">
                </label>
                <label class="block">
                    <span class="block text-[10px] font-medium text-slate-500 mb-0.5">Costo c/u</span>
                    <span class="relative block">
                        <span class="absolute left-1.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">S/.</span>
                        <input type="number" name="items[${idx}][unit_cost]"
                            value="${item.unitCost.toFixed(2)}" min="0" step="0.01" required
                            data-code="${code}" data-field="unitCost"
                            class="w-full pl-6 pr-2 py-1.5 text-xs border border-slate-300 rounded-lg text-right focus:outline-none focus:ring-2 focus:ring-emerald-500 input-seleccionado">
                    </span>
                </label>
                <label class="block">
                    <span class="block text-[10px] font-medium text-slate-500 mb-0.5">Vencimiento</span>
                    <input type="date" name="items[${idx}][expiration_date]"
                        class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </label>
                <label class="block">
                    <span class="block text-[10px] font-medium text-slate-500 mb-0.5">Lote</span>
                    <input type="text" name="items[${idx}][batch]"
                        class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        placeholder="Ej: L-24">
                </label>
            </div>
            `;
            // El nombre va como texto (no HTML) por si trae caracteres especiales
            fila.querySelector('.nombre-item').textContent = item.name;

            lista.appendChild(fila);
            pintarHintUnidades(code);
        }

        // ── Resumen en unidades: "= 50 unidades · S/ 0.15 c/u" (solo si hay otra presentación) ──
        function pintarHintUnidades(code) {
            const item = seleccionados.get(code);
            const hint = document.querySelector(`.hint-unidades[data-code="${CSS.escape(code)}"]`);
            if (!item || !hint) return;
            if (item.factor === 1) { hint.textContent = ''; return; }
            const unidades = Math.round(item.quantity * item.factor * 100) / 100;
            const costoUnidad = item.factor ? item.unitCost / item.factor : 0;
            hint.textContent = `= ${unidades} ${item.unit.toLowerCase()}(s) · S/. ${costoUnidad.toFixed(2)} c/u`;
        }

        // ── Actualiza los inputs de una fila ya existente ───────────────────────────
        function actualizarFilaSeleccionada(code) {
            const item = seleccionados.get(code);
            const fila = document.querySelector(`#filasSeleccionados .item-seleccionado[data-code="${code}"]`);
            if (!fila) return;
            fila.querySelector(`input[data-field="quantity"]`).value = item.quantity;
            const subtotal = item.quantity * item.unitCost;
            fila.querySelector(`.celda-subtotal`).textContent = 'S/. ' + subtotal.toFixed(2);
            pintarHintUnidades(code);
        }

        // ── Delegación de eventos en la tabla de seleccionados ──────────────────────
        document.getElementById('filasSeleccionados').addEventListener('input', function(e) {
            const input = e.target;
            if (!input.classList.contains('input-seleccionado')) return;
            const code = input.dataset.code;
            const field = input.dataset.field;
            const item = seleccionados.get(code);
            if (!item) return;

            const fila = document.querySelector(`#filasSeleccionados .item-seleccionado[data-code="${code}"]`);

            if (field === 'quantity') item.quantity = parseInt(input.value) || 1;
            if (field === 'unitCost') item.unitCost = parseFloat(input.value) || 0;
            if (field === 'presentation') {
                // Al cambiar de presentación el costo se recalcula: costo por unidad × unidades que trae
                item.factor = parseFloat(input.selectedOptions[0].dataset.factor) || 1;
                item.unitCost = Math.round(item.baseCost * item.factor * 100) / 100;
                fila.querySelector('input[data-field="unitCost"]').value = item.unitCost.toFixed(2);
            }
            seleccionados.set(code, item);

            fila.querySelector('.celda-subtotal').textContent =
                'S/. ' + (item.quantity * item.unitCost).toFixed(2);
            pintarHintUnidades(code);

            recalcularTotales();
        });

        // Quitar ítem
        document.getElementById('filasSeleccionados').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-quitar');
            if (!btn) return;
            const code = btn.dataset.code;
            seleccionados.delete(code);
            document.querySelector(`#filasSeleccionados .item-seleccionado[data-code="${code}"]`).remove();
            actualizarUI();
        });

        // Limpiar todo
        document.getElementById('btnLimpiar').addEventListener('click', function() {
            seleccionados.clear();
            document.getElementById('filasSeleccionados').innerHTML = '';
            actualizarUI();
        });

        // ── Actualizar UI general ────────────────────────────────────────────────────
        function actualizarUI() {
            const count = seleccionados.size;
            const empty = document.getElementById('emptyState');
            const tabla = document.getElementById('tablaSeleccionados');
            const btnLim = document.getElementById('btnLimpiar');

            empty.classList.toggle('hidden', count > 0);
            tabla.classList.toggle('hidden', count === 0);
            btnLim.classList.toggle('hidden', count === 0);
            document.getElementById('contadorSeleccionados').textContent = count;
            document.getElementById('contadorPestana').textContent = count;

            recalcularTotales();
        }

        // ── Pestañas (< 1280 px): muestra un panel a la vez. En pantallas anchas se ven ambos ──
        const pestanas = document.querySelectorAll('.pestana-compra');
        function mostrarPanel(panel) {
            document.querySelectorAll('[data-panel-compra]').forEach(p => {
                p.classList.toggle('max-xl:hidden', p.dataset.panelCompra !== panel);
            });
            pestanas.forEach(b => {
                const activa = b.dataset.panel === panel;
                b.classList.toggle('bg-white', activa);
                b.classList.toggle('text-emerald-700', activa);
                b.classList.toggle('shadow-sm', activa);
                b.classList.toggle('text-slate-600', !activa);
            });
        }
        pestanas.forEach(b => b.addEventListener('click', () => mostrarPanel(b.dataset.panel)));

        // Al agregar desde el catálogo en tablet, el contador de la pestaña "late" para que se note
        document.getElementById('cuerpoProductos').addEventListener('click', e => {
            if (!e.target.closest('.fila-producto')) return;
            const badge = document.getElementById('contadorPestana');
            badge.classList.remove('animate-ping-once');
            void badge.offsetWidth;
            badge.classList.add('animate-ping-once');
        });

        // ── Datos del documento: plegar / desplegar con un resumen de lo llenado ────────
        const camposDoc = document.getElementById('camposDocumento');
        const resumenDoc = document.getElementById('resumenDocumento');
        const btnPlegar = document.getElementById('btnPlegarDocumento');
        function textoResumenDocumento() {
            const f = document.getElementById('formCompra');
            const tipo = f.document_type.selectedOptions[0]?.text.trim();
            const partes = [tipo, f.document_number.value.trim(), f.supplier.value.trim(),
                f.payment_condition.selectedOptions[0]?.text.trim(), f.purchased_at.value.split('-').reverse().join('/')];
            return partes.filter(Boolean).join(' · ');
        }
        // En pantallas bajas (laptop 14"), al agregar el primer producto el bloque se pliega solo
        // para dejar espacio a la lista; se puede volver a abrir con "Mostrar".
        let plegadoAutomatico = false;
        document.getElementById('cuerpoProductos').addEventListener('click', e => {
            if (plegadoAutomatico || !e.target.closest('.fila-producto') || window.innerHeight >= 800) return;
            plegadoAutomatico = true;
            if (!camposDoc.classList.contains('hidden')) btnPlegar.click();
        });

        btnPlegar.addEventListener('click', () => {
            const plegar = !camposDoc.classList.contains('hidden');
            camposDoc.classList.toggle('hidden', plegar);
            resumenDoc.classList.toggle('hidden', !plegar);
            resumenDoc.textContent = plegar ? textoResumenDocumento() : '';
            btnPlegar.innerHTML = plegar
                ? '<span>Mostrar</span> <i class="fas fa-chevron-down text-[10px]"></i>'
                : '<span>Ocultar</span> <i class="fas fa-chevron-up text-[10px]"></i>';
        });

        // ── Calcular totales ─────────────────────────────────────────────────────────
        function recalcularTotales() {
            let subtotal = 0;
            seleccionados.forEach(item => {
                subtotal += item.quantity * item.unitCost;
            });
            const tax = parseFloat(document.getElementById('tax').value) || 0;
            const total = subtotal + tax;
            document.getElementById('resumenSubtotal').textContent = 'S/. ' + subtotal.toFixed(2);
            document.getElementById('resumenTotal').textContent = 'S/. ' + total.toFixed(2);
        }

        // ── Precarga de los productos de una orden de compra ─────────────────────────
        const precarga = @json($preload);
        document.addEventListener('DOMContentLoaded', () => {
            precarga.forEach(p => {
                const fila = document.querySelector(`.fila-producto[data-code="${CSS.escape(p.code)}"]`);
                if (!fila) return;
                fila.click();
                const item = seleccionados.get(p.code);
                item.quantity = p.qty;
                item.unitCost = p.cost;
                seleccionados.set(p.code, item);
                actualizarFilaSeleccionada(p.code);
                document.querySelector(`#filasSeleccionados .item-seleccionado[data-code="${CSS.escape(p.code)}"] input[data-field="unitCost"]`).value = p.cost.toFixed(2);
                recalcularTotales();
            });
        });

        // ── Precarga del producto si llega desde la pantalla de edición ──────────────
        @if ($producto)
            document.addEventListener('DOMContentLoaded', () => {
                const fila = document.querySelector(`.fila-producto[data-code="{{ $producto->code }}"]`);
                if (fila) fila.querySelector('.btn-agregar').click();
            });
        @endif
    </script>
@endsection
