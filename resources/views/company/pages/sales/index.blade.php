@extends('company/layouts/base')

@section('title', 'Punto de Venta')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col overflow-hidden">

    {{-- ════════════════════════════════════════════════════════
         CONTENEDOR PRINCIPAL POS
         ════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col overflow-hidden rounded-xl border border-slate-200 shadow-sm bg-white">

        {{-- ── Topbar ──────────────────────────────────────────── --}}
        <div class="shrink-0 flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center">
                    <i class="fas fa-cash-register text-white text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800 leading-none">Punto de Venta</p>
                    <p class="text-xs text-slate-400 mt-0.5">Facturación rápida</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-200 bg-slate-50">
                    <i class="fas fa-receipt text-slate-400 text-xs"></i>
                    <span class="text-xs text-slate-500">Nro Comprobante</span>
                    <input type="text" id="nroComprobante"
                           class="w-24 text-xs font-mono text-slate-700 bg-transparent focus:outline-none"
                           placeholder="Automático">
                </div>
                <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors" title="Imprimir">
                    <i class="fas fa-print text-xs"></i>
                </button>
                <button id="btnNuevaVenta"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-600 hover:bg-slate-50 transition-colors" title="Nueva venta">
                    <i class="fas fa-rotate-left text-xs"></i> Nueva venta
                </button>
            </div>
        </div>

        {{-- ── Cuerpo: tres columnas ───────────────────────────── --}}
        <div class="flex-1 grid grid-cols-[35%_35%_30%] overflow-hidden min-h-0">

            {{-- ┌──────────────────────────────────────────────────┐
                 │  COL 1 — Catálogo de productos                   │
                 └──────────────────────────────────────────────────┘ --}}
            <div class="flex flex-col border-r border-slate-200 overflow-hidden min-h-0">

                {{-- Buscador --}}
                <div class="shrink-0 p-3 border-b border-slate-100 bg-slate-50">
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        <input id="inputBusqueda" type="text"
                               class="w-full pl-8 pr-10 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Buscar por código, nombre o principio activo...">
                        <button id="btnLimpiarBusqueda"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors hidden">
                            <i class="fas fa-xmark text-xs"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between mt-2 px-0.5">
                        <p class="text-xs text-slate-400">
                            <span id="contadorProductos">{{ $productos->count() }}</span> productos disponibles
                        </p>
                        <label class="flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer">
                            <input type="checkbox" id="soloConStock" class="w-3 h-3 rounded" style="accent-color:#16a34a" checked>
                            Solo con stock
                        </label>
                    </div>
                </div>

                {{-- Tabla de productos --}}
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-xs">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-left px-4 py-2.5 font-semibold text-slate-400 uppercase tracking-wider">Producto</th>
                                <th class="text-right px-3 py-2.5 font-semibold text-slate-400 uppercase tracking-wider">Precio</th>
                                <th class="text-center px-3 py-2.5 font-semibold text-slate-400 uppercase tracking-wider">Stock</th>
                                <th class="px-2 py-2.5 w-8"></th>
                            </tr>
                        </thead>
                        <tbody id="tablaProductos" class="divide-y divide-slate-100">
                            @forelse($productos as $producto)
                            <tr class="hover:bg-emerald-50/40 transition-colors cursor-default producto-row"
                                data-code="{{ $producto->code }}"
                                data-search="{{ strtolower($producto->came . ' ' . $producto->code . ' ' . $producto->active_ingredient) }}"
                                data-stock="{{ $producto->stock_actual }}"
                                data-name="{{ $producto->nombre }}"
                                data-price="{{ $producto->unit_sale_price }}">
                                <td class="px-4 py-2.5">
                                    <p class="font-medium text-slate-800 leading-tight">{{ $producto->nombre }}</p>
                                    <p class="text-slate-400 mt-0.5 font-mono">{{ $producto->code }}
                                        @if($producto->laboratorio)
                                            · <span class="font-sans">{{ $producto->laboratorio->name }}</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold text-slate-700 whitespace-nowrap">
                                    S/ {{ number_format($producto->unit_sale_price, 2) }}
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold
                                        {{ $producto->stock_actual <= ($producto->stock_minimum ?? 0)
                                            ? 'bg-amber-100 text-amber-700'
                                            : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ (int)$producto->stock_actual }}
                                    </span>
                                </td>
                                <td class="px-2 py-2.5">
                                    @php
                                        $presData = $producto->presentaciones->where('status', 1)->map(function($p) {
                                            return [
                                                'id'     => $p->id,
                                                'label'  => optional($p->unidadMedida)->name ?? 'Presentación',
                                                'amount' => (float) $p->equivalent_amount,
                                                'price'  => (float) $p->sale_price,
                                            ];
                                        })->values();
                                    @endphp
                                    <button class="btn-agregar w-7 h-7 flex items-center justify-center rounded-lg bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white transition-all"
                                            data-code="{{ $producto->code }}"
                                            data-name="{{ $producto->nombre }}"
                                            data-price="{{ $producto->unit_sale_price }}"
                                            data-stock="{{ (int)$producto->stock_actual }}"
                                            data-presentations='@json($presData)'
                                            title="Agregar al carrito">
                                        <i class="fas fa-plus text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center">
                                    <i class="fas fa-pills text-4xl text-slate-200 mb-3 block"></i>
                                    <p class="text-sm text-slate-400">No hay productos con stock disponible</p>
                                </td>
                            </tr>
                            @endforelse
                            <tr id="sinResultados" class="hidden">
                                <td colspan="4" class="py-12 text-center">
                                    <i class="fas fa-magnifying-glass text-3xl text-slate-200 mb-2 block"></i>
                                    <p class="text-sm text-slate-400">Sin resultados</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ┌──────────────────────────────────────────────────┐
                 │  COL 2 — Carrito                                 │
                 └──────────────────────────────────────────────────┘ --}}
            <div class="flex flex-col overflow-hidden border-r border-slate-200 min-h-0">

                {{-- Cabecera carrito --}}
                <div class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b border-slate-100 bg-slate-50">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-cart-shopping text-emerald-600 text-xs"></i>
                        <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Detalle de Venta</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="contadorCarrito"
                              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                            0 ítems
                        </span>
                        <button id="btnVaciarCarrito"
                                class="text-xs text-slate-400 hover:text-red-500 transition-colors hidden" title="Vaciar carrito">
                            <i class="fas fa-trash text-xs"></i>
                        </button>
                    </div>
                </div>

                {{-- Tabla carrito (scrollable) --}}
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 z-10">
                            <tr class="bg-white border-b border-slate-100">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">Producto</th>
                                <th class="text-center px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-28">Cantidad</th>
                                <th class="text-right px-3 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-24">P. Unit.</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-400 uppercase tracking-wider w-24">Total</th>
                                <th class="w-8 px-2 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody id="carrito" class="divide-y divide-slate-100">
                            <tr id="carritoVacio">
                                <td colspan="5" class="py-16 text-center">
                                    <i class="fas fa-cart-shopping text-5xl text-slate-100 mb-3 block"></i>
                                    <p class="text-sm text-slate-400 font-medium">Carrito vacío</p>
                                    <p class="text-xs text-slate-300 mt-1">Agrega productos desde el panel izquierdo</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ┌──────────────────────────────────────────────────┐
                 │  COL 3 — Pago / Cliente                          │
                 └──────────────────────────────────────────────────┘ --}}
            <div class="flex flex-col bg-slate-50/30 overflow-hidden min-h-0">

                {{-- Cabecera pago --}}
                <div class="shrink-0 flex items-center gap-2 px-4 py-2.5 border-b border-slate-100 bg-slate-50">
                    <i class="fas fa-wallet text-emerald-600 text-xs"></i>
                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Pago</span>
                </div>

                {{-- Contenido scrollable --}}
                <div class="flex-1 overflow-y-auto p-4 space-y-5">

                    {{-- Inputs ocultos para los valores seleccionados --}}
                    <input type="hidden" id="tipoPago" value="1">
                    <input type="hidden" id="tipoComprobante" value="3">

                    {{-- ─ Tipo de Pago ──────────────────────────────── --}}
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Tipo de Pago</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="btn-pago btn-pago-activo flex flex-col items-center gap-1.5 py-3 px-1 rounded-xl border-2 transition-all" data-value="1">
                                <i class="fas fa-money-bills text-base"></i>
                                <span class="text-[11px] font-semibold leading-none">Efectivo</span>
                            </button>
                            <button class="btn-pago btn-pago-inactivo flex flex-col items-center gap-1.5 py-3 px-1 rounded-xl border-2 transition-all" data-value="2">
                                <i class="fas fa-credit-card text-base"></i>
                                <span class="text-[11px] font-semibold leading-none">Tarjeta</span>
                            </button>
                            <button class="btn-pago btn-pago-inactivo flex flex-col items-center gap-1.5 py-3 px-1 rounded-xl border-2 transition-all" data-value="3">
                                <i class="fas fa-building-columns text-base"></i>
                                <span class="text-[11px] font-semibold leading-none">Transferencia</span>
                            </button>
                            <button class="btn-pago btn-pago-inactivo flex flex-col items-center gap-1.5 py-3 px-1 rounded-xl border-2 transition-all" data-value="4">
                                <i class="fas fa-mobile-screen-button text-base"></i>
                                <span class="text-[11px] font-semibold leading-none">Yape</span>
                            </button>
                        </div>
                    </div>

                    {{-- ─ Comprobante ───────────────────────────────── --}}
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Comprobante</p>
                        <div class="flex flex-col gap-1.5">
                            <button class="btn-comprobante btn-comprobante-activo flex items-center gap-2 px-3 py-2 rounded-lg border-2 text-sm font-medium transition-all" data-value="3">
                                <i class="fas fa-file-lines text-xs"></i>
                                Nota de Venta
                            </button>
                            <button class="btn-comprobante btn-comprobante-inactivo flex items-center gap-2 px-3 py-2 rounded-lg border-2 text-sm font-medium transition-all" data-value="1">
                                <i class="fas fa-receipt text-xs"></i>
                                Boleta
                            </button>
                            <button class="btn-comprobante btn-comprobante-inactivo flex items-center gap-2 px-3 py-2 rounded-lg border-2 text-sm font-medium transition-all" data-value="2">
                                <i class="fas fa-file-invoice text-xs"></i>
                                Factura
                            </button>
                        </div>
                    </div>

                    {{-- ─ Separador ─────────────────────────────────── --}}
                    <div class="border-t border-slate-200"></div>

                    {{-- ─ Cliente ───────────────────────────────────── --}}
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            <i class="fas fa-user"></i> Cliente
                        </label>
                        <input type="text" id="cliente"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="Cliente general">
                    </div>

                    {{-- ─ DNI / RUC ─────────────────────────────────── --}}
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            <i class="fas fa-id-card"></i> DNI / RUC
                        </label>
                        <div class="relative">
                            <input type="text" id="documento" maxlength="11"
                                   class="w-full pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                   placeholder="DNI (8 dígitos) o RUC (11)">
                            <span id="dniEstado" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-sm hidden"></span>
                        </div>
                        <p id="dniMensaje" class="text-xs mt-1 hidden"></p>
                    </div>

                    {{-- ─ Nro Operación (no-efectivo) ───────────────── --}}
                    <div id="seccionNroOp" class="hidden">
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            <i class="fas fa-hashtag"></i> Nro Operación
                        </label>
                        <input type="text" id="nroOperacion"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                               placeholder="Ref. de pago">
                    </div>

                </div>
            </div>

        </div>

        {{-- ── Footer: totales + acción ────────────────────────── --}}
        <div class="shrink-0 flex items-center justify-between border-t border-slate-200 px-5 py-3 bg-white gap-6">

            <button id="btnTerminarVenta"
                    class="px-8 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl transition-all flex items-center gap-2 shadow-sm"
                    disabled>
                <i class="fas fa-circle-check text-sm"></i>
                Confirmar Venta
                <kbd class="text-emerald-200 text-xs font-normal bg-white/10 px-1.5 py-0.5 rounded ml-1">F5</kbd>
            </button>

            <div class="flex items-center gap-8 ml-auto">
                <div class="text-right space-y-0.5">
                    <p class="text-xs text-slate-400">Sub Total</p>
                    <p class="text-xs text-slate-400">IGV (18%)</p>
                    <p class="text-sm font-bold text-emerald-600">Total</p>
                </div>
                <div class="text-right min-w-20 space-y-0.5">
                    <p id="subTotal" class="text-xs text-slate-600">S/ 0.00</p>
                    <p id="igv"      class="text-xs text-slate-600">S/ 0.00</p>
                    <p id="total"    class="text-xl font-bold text-slate-800">S/ 0.00</p>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ── Modal: seleccionar cantidad / presentación ──────────────── --}}
<div id="modalAgregar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-sm">

        {{-- Header --}}
        <div class="flex items-start justify-between p-5 border-b border-slate-100">
            <div class="flex-1 min-w-0 pr-3">
                <p id="modalNombre" class="text-sm font-bold text-slate-800 leading-tight truncate"></p>
                <p id="modalCodigo" class="text-xs text-slate-400 font-mono mt-0.5"></p>
            </div>
            <button onclick="cerrarModalAgregar()"
                    class="w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 transition-colors shrink-0">
                <i class="fas fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">

            {{-- Presentaciones (solo si existen) --}}
            <div id="seccionPresentaciones" class="hidden">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Presentación</p>
                <div id="listaPresentaciones" class="space-y-1.5 max-h-40 overflow-y-auto"></div>
            </div>

            {{-- Cantidad --}}
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Cantidad</p>
                <div class="flex items-center gap-2">
                    <button id="modalBtnDec"
                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors text-lg font-light select-none">
                        −
                    </button>
                    <input id="modalCantidad" type="number" min="1" value="1"
                           class="flex-1 text-center text-xl font-bold border border-slate-200 rounded-lg py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <button id="modalBtnInc"
                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors text-lg font-light select-none">
                        +
                    </button>
                </div>
                <p class="text-xs text-slate-400 mt-1.5 text-center">
                    Stock disponible: <span id="modalStock" class="font-semibold text-slate-600"></span>
                </p>
            </div>

            {{-- Precio + acción --}}
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <div>
                    <p class="text-xs text-slate-400">Precio unitario</p>
                    <p id="modalPrecio" class="text-2xl font-bold text-slate-800">S/ 0.00</p>
                </div>
                <button id="modalBtnAgregar"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-bold rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-plus text-xs"></i> Agregar
                </button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// ── Estilos de botones de pago / comprobante ──────────────────
const PAGO_ACTIVO   = ['border-emerald-500','bg-emerald-50','text-emerald-700'];
const PAGO_INACTIVO = ['border-slate-200','bg-white','text-slate-500'];

function activarBtn(selector, $clicked) {
    $(selector).each(function() {
        $(this).removeClass(PAGO_ACTIVO.join(' ')).addClass(PAGO_INACTIVO.join(' '));
    });
    $clicked.removeClass(PAGO_INACTIVO.join(' ')).addClass(PAGO_ACTIVO.join(' '));
}

// ── Tipo de pago ──────────────────────────────────────────────
$(document).on('click', '.btn-pago', function() {
    activarBtn('.btn-pago', $(this));
    const val = $(this).data('value');
    $('#tipoPago').val(val);
    // Mostrar Nro Operación solo para métodos no-efectivo
    $('#seccionNroOp').toggleClass('hidden', val == 1);
});

// ── Tipo de comprobante ───────────────────────────────────────
$(document).on('click', '.btn-comprobante', function() {
    activarBtn('.btn-comprobante', $(this));
    $('#tipoComprobante').val($(this).data('value'));
});

// Aplicar estilos iniciales a los botones activos al cargar
$('.btn-pago-activo').removeClass(PAGO_INACTIVO.join(' ')).addClass(PAGO_ACTIVO.join(' '));
$('.btn-pago-inactivo').removeClass(PAGO_ACTIVO.join(' ')).addClass(PAGO_INACTIVO.join(' '));
$('.btn-comprobante-activo').removeClass(PAGO_INACTIVO.join(' ')).addClass(PAGO_ACTIVO.join(' '));
$('.btn-comprobante-inactivo').removeClass(PAGO_ACTIVO.join(' ')).addClass(PAGO_INACTIVO.join(' '));

// ── Estado del carrito ────────────────────────────────────────
let carrito = {};

// ── Filtrado en tiempo real ───────────────────────────────────
$('#inputBusqueda').on('input', function() {
    const term = $(this).val().trim().toLowerCase();
    $('#btnLimpiarBusqueda').toggleClass('hidden', !term);
    filtrarProductos();
});

$('#btnLimpiarBusqueda').on('click', function() {
    $('#inputBusqueda').val('').trigger('input').focus();
});

$('#soloConStock').on('change', filtrarProductos);

function filtrarProductos() {
    const term  = $('#inputBusqueda').val().trim().toLowerCase();
    const stock = $('#soloConStock').is(':checked');
    let visible = 0;

    $('.producto-row').each(function() {
        const matchTerm  = !term  || $(this).data('search').includes(term);
        const matchStock = !stock || parseInt($(this).data('stock')) > 0;
        const show = matchTerm && matchStock;
        $(this).toggleClass('hidden', !show);
        if (show) visible++;
    });

    $('#sinResultados').toggleClass('hidden', visible > 0);
    $('#contadorProductos').text(visible);
}

// ── Modal de cantidad / presentación ─────────────────────────
let modalProducto   = null;
let modalPrecio     = 0;

$(document).on('click', '.btn-agregar', function() {
    const code          = $(this).data('code');
    const name          = $(this).data('name');
    const price         = parseFloat($(this).data('price'));
    const stock         = parseInt($(this).data('stock'));
    const presentations = $(this).data('presentations') || [];

    modalProducto = { code, name, stock };
    modalPrecio   = price;

    $('#modalNombre').text(name);
    $('#modalCodigo').text(code);
    $('#modalCantidad').val(1).attr('max', stock);
    $('#modalStock').text(stock);
    $('#modalPrecio').text('S/ ' + price.toFixed(2));

    const $lista = $('#listaPresentaciones').empty();
    if (presentations.length > 0) {
        $('#seccionPresentaciones').removeClass('hidden');
        $lista.append(opcionPresentacion('base', 'Unidad', '', price, true));
        presentations.forEach(p => {
            $lista.append(opcionPresentacion(p.id, p.label, 'x' + p.amount + ' uds', p.price, false));
        });
    } else {
        $('#seccionPresentaciones').addClass('hidden');
    }

    document.getElementById('modalAgregar').style.setProperty('display', 'flex', 'important');
    setTimeout(() => $('#modalCantidad').focus().select(), 50);
});

function opcionPresentacion(val, label, sub, price, checked) {
    const active = checked ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 hover:border-slate-300';
    return `
        <label class="flex items-center gap-2.5 p-2.5 rounded-lg border ${active} cursor-pointer presentacion-opcion transition-colors">
            <input type="radio" name="modalPresentacion" value="${val}" data-price="${price}" ${checked ? 'checked' : ''}
                   class="text-emerald-600 shrink-0">
            <span class="flex-1 min-w-0">
                <span class="text-sm font-medium text-slate-700">${label}</span>
                ${sub ? `<span class="text-xs text-slate-400 ml-1">(${sub})</span>` : ''}
            </span>
            <span class="text-sm font-semibold text-slate-700 whitespace-nowrap">S/ ${parseFloat(price).toFixed(2)}</span>
        </label>`;
}

$(document).on('change', 'input[name="modalPresentacion"]', function() {
    modalPrecio = parseFloat($(this).data('price'));
    $('#modalPrecio').text('S/ ' + modalPrecio.toFixed(2));
    $('.presentacion-opcion').each(function() {
        const checked = $(this).find('input').is(':checked');
        $(this).toggleClass('border-emerald-500 bg-emerald-50', checked)
               .toggleClass('border-slate-200', !checked);
    });
});

$('#modalBtnDec').on('click', function() {
    const v = parseInt($('#modalCantidad').val()) || 1;
    if (v > 1) $('#modalCantidad').val(v - 1);
});

$('#modalBtnInc').on('click', function() {
    const v   = parseInt($('#modalCantidad').val()) || 1;
    const max = modalProducto ? modalProducto.stock : 9999;
    if (v < max) $('#modalCantidad').val(v + 1);
});

$('#modalCantidad').on('keydown', function(e) {
    if (e.key === 'Enter') $('#modalBtnAgregar').trigger('click');
});

$('#modalBtnAgregar').on('click', function() {
    if (!modalProducto) return;
    const { code, name, stock } = modalProducto;
    const price = modalPrecio;
    const qty   = Math.min(Math.max(parseInt($('#modalCantidad').val()) || 1, 1), stock);

    if (carrito[code]) {
        carrito[code].qty   = Math.min(carrito[code].qty + qty, stock);
        carrito[code].price = price;
    } else {
        carrito[code] = { name, price, stock, qty };
    }

    cerrarModalAgregar();
    renderCarrito();
});

function cerrarModalAgregar() {
    document.getElementById('modalAgregar').style.setProperty('display', 'none', 'important');
    modalProducto = null;
}

$(document).on('keydown', function(e) {
    if (e.key === 'Escape') cerrarModalAgregar();
});

// ── Cambiar cantidad en carrito ───────────────────────────────
$(document).on('input', '.input-cantidad', function() {
    const code = $(this).data('code');
    const val  = parseInt($(this).val()) || 1;
    carrito[code].qty = Math.min(Math.max(val, 1), carrito[code].stock);
    $(this).val(carrito[code].qty);
    actualizarTotales();
});

$(document).on('click', '.btn-dec', function() {
    const code = $(this).data('code');
    if (carrito[code].qty > 1) { carrito[code].qty--; renderCarrito(); }
});

$(document).on('click', '.btn-inc', function() {
    const code = $(this).data('code');
    if (carrito[code].qty < carrito[code].stock) { carrito[code].qty++; renderCarrito(); }
});

// ── Eliminar del carrito ──────────────────────────────────────
$(document).on('click', '.btn-quitar', function() {
    delete carrito[$(this).data('code')];
    renderCarrito();
});

$('#btnVaciarCarrito').on('click', function() {
    carrito = {};
    renderCarrito();
});

// ── Renderizar carrito ────────────────────────────────────────
function renderCarrito() {
    const $tbody  = $('#carrito');
    const $vacio  = $('#carritoVacio');
    const keys    = Object.keys(carrito);
    const hasItems = keys.length > 0;

    $tbody.find('tr:not(#carritoVacio)').remove();

    if (!hasItems) {
        $vacio.removeClass('hidden');
        $('#btnVaciarCarrito').addClass('hidden');
        $('#btnTerminarVenta').prop('disabled', true);
    } else {
        $vacio.addClass('hidden');
        $('#btnVaciarCarrito').removeClass('hidden');
        $('#btnTerminarVenta').prop('disabled', false);

        keys.forEach((code) => {
            const item    = carrito[code];
            const importe = (item.price * item.qty).toFixed(2);
            $tbody.append(`
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-2.5">
                        <p class="text-sm font-medium text-slate-800">${item.name}</p>
                        <p class="text-xs text-slate-400 font-mono">${code}</p>
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center justify-center gap-1">
                            <button class="btn-dec w-6 h-6 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors text-xs" data-code="${code}">−</button>
                            <input class="input-cantidad w-10 text-center text-sm font-semibold border border-slate-200 rounded py-0.5 focus:outline-none focus:ring-1 focus:ring-emerald-500" data-code="${code}" value="${item.qty}" min="1" max="${item.stock}">
                            <button class="btn-inc w-6 h-6 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors text-xs" data-code="${code}">+</button>
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-right text-xs text-slate-500">S/ ${item.price.toFixed(2)}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-slate-800">S/ ${importe}</td>
                    <td class="px-2 py-2.5">
                        <button class="btn-quitar w-6 h-6 flex items-center justify-center rounded text-slate-300 hover:bg-red-50 hover:text-red-500 transition-colors" data-code="${code}">
                            <i class="fas fa-xmark text-xs"></i>
                        </button>
                    </td>
                </tr>`);
        });
    }

    actualizarTotales();
    actualizarContadorCarrito();
}

function actualizarTotales() {
    let sub = 0;
    Object.values(carrito).forEach(i => sub += i.price * i.qty);
    const igv   = sub * 0.18;
    const total = sub + igv;
    $('#subTotal').text('S/ ' + sub.toFixed(2));
    $('#igv').text('S/ ' + igv.toFixed(2));
    $('#total').text('S/ ' + total.toFixed(2));
}

function actualizarContadorCarrito() {
    const total = Object.values(carrito).reduce((s, i) => s + i.qty, 0);
    $('#contadorCarrito').text(total + (total === 1 ? ' ítem' : ' ítems'));
}

// ── Nueva venta ───────────────────────────────────────────────
function nuevaVenta() {
    carrito = {};
    renderCarrito();
    $('#inputBusqueda').val('').trigger('input');
    $('#cliente, #documento, #nroOperacion, #nroComprobante').val('');
    $('#dniEstado').addClass('hidden').html('');
    $('#dniMensaje').addClass('hidden').text('');
    location.reload();
}

$('#btnNuevaVenta').on('click', nuevaVenta);

// ── Confirmar venta (AJAX) ────────────────────────────────────
$('#btnTerminarVenta').on('click', function() {
    const items = Object.entries(carrito).map(([code, item]) => ({
        code:  code,
        name:  item.name,
        price: item.price,
        qty:   item.qty,
    }));

    const sub   = parseFloat($('#subTotal').text().replace('S/ ', '')) || 0;
    const igv   = parseFloat($('#igv').text().replace('S/ ', ''))      || 0;
    const total = parseFloat($('#total').text().replace('S/ ', ''))    || 0;

    const payload = {
        items,
        customer_name:     $('#cliente').val(),
        customer_document: $('#documento').val(),
        payment_type:      $('#tipoPago').val(),
        voucher_type:      $('#tipoComprobante').val(),
        voucher_number:    $('#nroComprobante').val(),
        operation_number:  $('#nroOperacion').val(),
        subtotal: sub,
        igv:      igv,
        total:    total,
    };

    const $btn = $(this).prop('disabled', true).text('Procesando...');

    $.ajax({
        url:         '{{ route("company.sales.store") }}',
        method:      'POST',
        contentType: 'application/json',
        headers:     { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data:        JSON.stringify(payload),
        success: function(res) {
            mostrarToast('success', res.message);
            carrito = {};
            renderCarrito();
            $('#inputBusqueda').val('').trigger('input');
            $('#cliente, #documento, #nroOperacion, #nroComprobante').val('');
            $.get(window.location.href, function(html) {
                const $nuevaTabla = $(html).find('#tablaProductos');
                $('#tablaProductos').html($nuevaTabla.html());
                filtrarProductos();
            });
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message ?? 'Error al registrar la venta.';
            mostrarToast('error', msg);
        },
        complete: function() {
            const hasItems = Object.keys(carrito).length > 0;
            $btn.prop('disabled', !hasItems)
                .html('<i class="fas fa-circle-check text-sm"></i> Confirmar Venta <kbd class="text-emerald-200 text-xs font-normal bg-white/10 px-1.5 py-0.5 rounded ml-1">F5</kbd>');
        }
    });
});

// ── Toast de notificación ─────────────────────────────────────
function mostrarToast(tipo, mensaje) {
    const color  = tipo === 'success' ? 'bg-emerald-600' : 'bg-red-600';
    const icon   = tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const $toast = $(`
        <div class="fixed top-4 right-4 z-[100] flex items-center gap-3 px-4 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${color} transition-all">
            <i class="fas ${icon}"></i>
            <span>${mensaje}</span>
        </div>`);
    $('body').append($toast);
    setTimeout(() => $toast.fadeOut(300, function() { $(this).remove(); }), 3500);
}

// ── Teclado: F5 = terminar venta, F2 = foco búsqueda ─────────
$(document).on('keydown', function(e) {
    if (e.key === 'F5') { e.preventDefault(); $('#btnTerminarVenta').not(':disabled').trigger('click'); }
    if (e.key === 'F2') { e.preventDefault(); $('#inputBusqueda').focus(); }
});

// ── Consulta DNI / RUC ────────────────────────────────────────
let dniTimer = null;

$('#documento').on('input', function() {
    const num = $(this).val().replace(/\D/g, '');
    $(this).val(num); // solo dígitos
    clearTimeout(dniTimer);

    $('#dniEstado').addClass('hidden').html('');
    $('#dniMensaje').addClass('hidden').text('');

    if (num.length !== 8 && num.length !== 11) return;

    $('#dniEstado').removeClass('hidden').html('<i class="fas fa-spinner fa-spin text-slate-400"></i>');

    dniTimer = setTimeout(function() {
        $.ajax({
            url:    '{{ route("company.consultar-documento") }}',
            method: 'GET',
            data:   { numero: num },
            success: function(res) {
                if (res.success && res.nombre) {
                    $('#cliente').val(res.nombre);
                    $('#dniEstado').html('<i class="fas fa-circle-check text-emerald-500"></i>');
                    $('#dniMensaje').removeClass('hidden').addClass('text-emerald-600').text(res.nombre);
                } else {
                    $('#dniEstado').html('<i class="fas fa-circle-xmark text-slate-300"></i>');
                    $('#dniMensaje').removeClass('hidden').addClass('text-slate-400').text('No encontrado');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message ?? 'Error de consulta';
                $('#dniEstado').html('<i class="fas fa-circle-xmark text-red-400"></i>');
                $('#dniMensaje').removeClass('hidden').addClass('text-red-400').text(msg);
            }
        });
    }, 400);
});
</script>
@endsection
