@extends('company/layouts/base')

@section('title', 'Nueva Compra')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
    <div class="flex-1 flex flex-col gap-3 min-h-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('company.purchases.index') }}"
                class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 transition-colors">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Nueva Compra</h1>
                <p class="text-sm text-slate-500 mt-0.5">Registra una boleta o factura para incrementar el stock</p>
            </div>
        </div>

        <form action="{{ route('company.purchases.store') }}" method="POST" id="formCompra"
            class="flex-1 flex flex-col min-h-0 gap-3">
            @csrf

            {{-- Datos del documento --}}
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm px-5 py-4 shrink-0">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Datos del documento</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3">

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

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Proveedor</label>
                        <input type="text" name="supplier" value="{{ old('supplier') }}"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="Nombre del proveedor">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Fecha <span
                                class="text-red-500">*</span></label>
                        <input type="date" name="purchased_at" value="{{ old('purchased_at', date('Y-m-d')) }}"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>

                    <div class="col-span-2 sm:col-span-4 lg:col-span-1">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Observaciones</label>
                        <input type="text" name="notes" value="{{ old('notes') }}"
                            class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                            placeholder="Notas opcionales...">
                    </div>

                </div>
            </div>

            {{-- Panel principal: dos columnas --}}
            <div class="flex-1 flex gap-3 min-h-0 flex-col lg:flex-row">

                {{-- ── COLUMNA IZQUIERDA: Catálogo de productos ──────────── --}}
                <div
                    class="lg:w-1/2 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

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
                    <div class="flex-1 overflow-auto">
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
                                    <tr class="hover:bg-emerald-50 transition-colors cursor-pointer fila-producto"
                                        data-code="{{ $p->code }}" data-name="{{ $p->came }}"
                                        data-cost="{{ $p->purchase_price ?? 0 }}">
                                        <td class="px-4 py-2.5">
                                            <p class="font-medium text-slate-800 text-xs">{{ $p->came }}</p>
                                            <p class="text-[10px] text-slate-400 font-mono">{{ $p->code }}</p>
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $p->stock_actual <= ($p->stock_minimum ?? 0) ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                                {{ $p->stock_actual }}
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
                <div
                    class="lg:w-1/2 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

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
                    <div class="flex-1 overflow-auto" id="contenedorSeleccionados">

                        {{-- Estado vacío --}}
                        <div id="emptyState" class="flex flex-col items-center justify-center h-full py-16 text-slate-300">
                            <i class="fas fa-cart-plus text-4xl mb-3"></i>
                            <p class="text-sm font-medium">Sin productos</p>
                            <p class="text-xs mt-1">Selecciona productos del catálogo</p>
                        </div>

                        {{-- Tabla de seleccionados --}}
                        <table class="w-full text-sm hidden" id="tablaSeleccionados">
                            <thead class="sticky top-0 z-10">
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th
                                        class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Producto</th>
                                    <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-500 w-20">Cant.
                                    </th>
                                    <th class="text-right px-3 py-2.5 text-xs font-semibold text-slate-500 w-28">Costo
                                        Unit.</th>
                                    <th class="text-right px-3 py-2.5 text-xs font-semibold text-slate-500 w-24">Subtotal
                                    </th>
                                    <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-500 w-32">
                                        Vencimiento</th>
                                    <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-500 w-24">Lote</th>
                                    <th class="w-10 px-3 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody id="filasSeleccionados" class="divide-y divide-slate-100">
                                {{-- Filas generadas por JS --}}
                            </tbody>
                        </table>
                    </div>

                    {{-- Pie: totales + IGV + botón guardar --}}
                    <div class="border-t border-slate-200 bg-slate-50 px-4 py-3 shrink-0 space-y-2">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span>Subtotal</span>
                            <span id="resumenSubtotal" class="font-medium text-slate-700">S/. 0.00</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <label for="tax">IGV / Impuesto</label>
                            <div class="relative w-28">
                                <span
                                    class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">S/.</span>
                                <input type="number" id="tax" name="tax" min="0" step="0.01"
                                    value="{{ old('tax', 0) }}"
                                    class="w-full pl-7 pr-2 py-1 text-xs border border-slate-300 rounded-lg bg-white text-right focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    oninput="recalcularTotales()">
                            </div>
                        </div>
                        <div class="flex items-center justify-between font-bold text-sm border-t border-slate-200 pt-2">
                            <span class="text-slate-700">Total</span>
                            <span id="resumenTotal" class="text-emerald-700">S/. 0.00</span>
                        </div>
                        <button type="submit"
                            class="w-full mt-1 px-4 py-2.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm flex items-center justify-center gap-2">
                            <i class="fas fa-save text-xs"></i> Registrar compra
                        </button>
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

        // ── Estado de los ítems seleccionados ────────────────────────────────────────
        // Map<productCode, { name, quantity, unitCost, idx }>
        const seleccionados = new Map();
        let itemIdx = 0;

        // ── Buscador del catálogo ────────────────────────────────────────────────────
        document.getElementById('buscadorProducto').addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            let visible = 0;
            document.querySelectorAll('.fila-producto').forEach(fila => {
                const texto = fila.dataset.name.toLowerCase() + fila.dataset.code.toLowerCase();
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
                    quantity: 1,
                    unitCost: cost,
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

            const tbody = document.getElementById('filasSeleccionados');
            const tr = document.createElement('tr');
            tr.className = 'divide-y divide-slate-50 hover:bg-slate-50 transition-colors';
            tr.dataset.code = code;
            tr.innerHTML = `
            <td class="px-4 py-2.5">
                <input type="hidden" name="items[${idx}][product_code]" value="${code}">
                <p class="font-medium text-slate-800 text-xs leading-tight">${item.name}</p>
                <p class="text-[10px] text-slate-400 font-mono">${code}</p>
            </td>
            <td class="px-3 py-2.5">
                <input type="number" name="items[${idx}][quantity]"
                    value="${item.quantity}" min="1" step="1" required
                    data-code="${code}" data-field="quantity"
                    class="w-full px-2 py-1.5 text-xs border border-slate-300 rounded-lg text-center focus:outline-none focus:ring-2 focus:ring-emerald-500 input-seleccionado">
            </td>
            <td class="px-3 py-2.5">
                <div class="relative">
                    <span class="absolute left-1.5 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]">S/.</span>
                    <input type="number" name="items[${idx}][unit_cost]"
                        value="${item.unitCost.toFixed(2)}" min="0" step="0.01" required
                        data-code="${code}" data-field="unitCost"
                        class="w-full pl-6 pr-2 py-1.5 text-xs border border-slate-300 rounded-lg text-right focus:outline-none focus:ring-2 focus:ring-emerald-500 input-seleccionado">
                </div>
            </td>
            <td class="px-3 py-2.5 text-right text-xs font-medium text-slate-700 celda-subtotal" data-code="${code}">
                S/. ${(item.quantity * item.unitCost).toFixed(2)}
            </td>
            <td class="px-3 py-2.5">
                <input type="date" name="items[${idx}][expiration_date]"
                    class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </td>
            <td class="px-3 py-2.5">
                <input type="text" name="items[${idx}][batch]"
                    class="w-full px-2 py-1.5 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    placeholder="Ej: L-24">
            </td>
            <td class="px-3 py-2.5 text-center">
                <button type="button" data-code="${code}"
                        class="btn-quitar w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-500 transition-colors mx-auto">
                    <i class="fas fa-trash text-[10px] pointer-events-none"></i>
                </button>
            </td>
            `;

            tbody.appendChild(tr);
        }

        // ── Actualiza los inputs de una fila ya existente ───────────────────────────
        function actualizarFilaSeleccionada(code) {
            const item = seleccionados.get(code);
            const fila = document.querySelector(`#filasSeleccionados tr[data-code="${code}"]`);
            if (!fila) return;
            fila.querySelector(`input[data-field="quantity"]`).value = item.quantity;
            const subtotal = item.quantity * item.unitCost;
            fila.querySelector(`.celda-subtotal`).textContent = 'S/. ' + subtotal.toFixed(2);
        }

        // ── Delegación de eventos en la tabla de seleccionados ──────────────────────
        document.getElementById('filasSeleccionados').addEventListener('input', function(e) {
            const input = e.target;
            if (!input.classList.contains('input-seleccionado')) return;
            const code = input.dataset.code;
            const field = input.dataset.field;
            const item = seleccionados.get(code);
            if (!item) return;

            if (field === 'quantity') item.quantity = parseInt(input.value) || 1;
            if (field === 'unitCost') item.unitCost = parseFloat(input.value) || 0;
            seleccionados.set(code, item);

            const fila = document.querySelector(`#filasSeleccionados tr[data-code="${code}"]`);
            fila.querySelector('.celda-subtotal').textContent =
                'S/. ' + (item.quantity * item.unitCost).toFixed(2);

            recalcularTotales();
        });

        // Quitar ítem
        document.getElementById('filasSeleccionados').addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-quitar');
            if (!btn) return;
            const code = btn.dataset.code;
            seleccionados.delete(code);
            document.querySelector(`#filasSeleccionados tr[data-code="${code}"]`).remove();
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

            recalcularTotales();
        }

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

        // ── Precarga del producto si llega desde la pantalla de edición ──────────────
        @if ($producto)
            document.addEventListener('DOMContentLoaded', () => {
                const fila = document.querySelector(`.fila-producto[data-code="{{ $producto->code }}"]`);
                if (fila) fila.querySelector('.btn-agregar').click();
            });
        @endif
    </script>
@endsection
