@extends('employee/layouts/base')

@section('title', 'Punto de Venta')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
@php
    $editOrder = $editOrder ?? null;

    // Comprobantes que la sede puede emitir (con serie activa); al editar se muestran todos
    $voucherButtons = [
        3 => ['label' => 'Nota de Venta', 'icon' => 'fa-file-lines'],
        1 => ['label' => 'Boleta',        'icon' => 'fa-receipt'],
        2 => ['label' => 'Factura',       'icon' => 'fa-file-invoice'],
    ];
    $voucherButtons = array_intersect_key($voucherButtons, array_flip($availableVouchers ?? array_keys($voucherButtons)));
    $defaultVoucher = array_key_first($voucherButtons);
    $canDiscount = auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_APLICAR_DESCUENTO);

    // Ajustes de venta de la sede (Configuración): redondeo en efectivo y descuento por monto
    $salesCfg = array_merge(
        ['cash_rounding' => false, 'amount_discount' => false],
        auth()->guard('employee')->user()->branch()->with('config')->first()?->getSettingGroup('sales') ?? []
    );

    // Datos para el modo edición (null al registrar una venta nueva)
    $editConfig = $editOrder ? [
        'payment_type'      => $editOrder->payment_type,
        'operation_number'  => $editOrder->operation_number,
        'amount_received'   => $editOrder->amount_received,
        'voucher_type'      => $editOrder->voucher_type,
        'customer_name'     => $editOrder->customer_name,
        'customer_document' => $editOrder->customer_document,
        'document_type_id'  => $editOrder->document_type_id,
        'discount_percent'  => (float) $editOrder->discount_percent,
        'discount_amount'   => (float) $editOrder->discount_amount,
        'update_url'        => route('employee.orders.update-historical', $editOrder),
        'back_url'          => route('employee.cash-register.historical', $historicalCaja),
        'items'             => $editItems,
    ] : null;
@endphp
<div class="flex-1 min-h-0 flex flex-col overflow-hidden">

    {{-- ════════════════════════════════════════════════════════
         CONTENEDOR PRINCIPAL POS
         ════════════════════════════════════════════════════════ --}}
    <div class="flex-1 min-h-0 flex flex-col overflow-hidden rounded-xl border border-slate-200 shadow-sm bg-white">

        {{-- ── Aviso: venta sobre una caja histórica ───────────── --}}
        @if($historicalCaja)
        <div class="shrink-0 flex items-center gap-3 px-5 py-2.5 bg-amber-50 border-b border-amber-200 text-amber-800">
            <i class="fas fa-clock-rotate-left text-amber-600"></i>
            <p class="text-xs leading-relaxed">
                @if($editOrder)
                <strong>Editando la venta {{ $editOrder->voucher_number ?? '#' . $editOrder->id }}</strong>
                de la caja histórica del {{ $historicalCaja->register_date->format('d/m/Y') }}.
                Al guardar se ajusta el stock. El cliente y el comprobante no se pueden modificar.
                @else
                <strong>Caja histórica del {{ $historicalCaja->register_date->format('d/m/Y') }}.</strong>
                Las ventas se registrarán con esa fecha y quedarán pendientes de validación del administrador.
                @endif
            </p>
            <a href="{{ route('employee.cash-register.historical', $historicalCaja) }}"
               class="ml-auto text-xs font-semibold underline whitespace-nowrap">Volver a la caja</a>
        </div>
        @endif

        {{-- ── Topbar ──────────────────────────────────────────── --}}
        <div class="shrink-0 flex items-center justify-between px-5 py-3 border-b border-slate-200 bg-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-sky-600 flex items-center justify-center">
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
                    <span class="text-xs text-slate-500">Comprobante</span>
                    <span id="nroComprobante"
                          class="text-xs font-mono text-slate-400 italic">— automático —</span>
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
                               class="w-full pl-8 pr-10 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                               placeholder="Escanea el código de barras o busca por nombre...">
                        <button id="btnLimpiarBusqueda"
                                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors hidden">
                            <i class="fas fa-xmark text-xs"></i>
                        </button>
                    </div>
                    <div class="flex items-center justify-between mt-2 px-0.5">
                        <p class="text-xs text-slate-400">
                            <span id="contadorProductos">{{ $products->count() }}</span> productos disponibles
                        </p>
                        <label class="flex items-center gap-1.5 text-xs text-slate-500 cursor-pointer">
                            <input type="checkbox" id="soloConStock" class="w-3 h-3 rounded" style="accent-color:#16a34a" checked>
                            Solo con stock
                        </label>
                    </div>
                </div>

                {{-- Tabla de productos --}}
                <div class="flex-1 min-h-0 overflow-auto">
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
                            @forelse($products as $product)
                            <tr class="hover:bg-sky-50/40 transition-colors cursor-default producto-row"
                                data-code="{{ $product->code }}"
                                data-barcode="{{ $product->barcode }}"
                                data-search="{{ strtolower($product->name . ' ' . $product->code . ' ' . $product->barcode . ' ' . $product->active_ingredient) }}"
                                data-stock="{{ $product->branchStocks->first()?->stock_actual ?? 0 }}"
                                data-name="{{ $product->name }}"
                                data-afectacion="{{ $product->igv_affectation }}"
                                data-price="{{ $product->unit_sale_price }}">
                                <td class="px-4 py-2.5">
                                    <p class="font-medium text-slate-800 leading-tight">{{ $product->name }}</p>
                                    <p class="text-slate-400 mt-0.5 font-mono">{{ $product->code }}
                                        @if($product->laboratory)
                                            · <span class="font-sans">{{ $product->laboratory->name }}</span>
                                        @endif
                                    </p>
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold text-slate-700 whitespace-nowrap">
                                    S/ {{ number_format($product->unit_sale_price, 2) }}
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold
                                        {{ ($product->branchStocks->first()?->stock_actual ?? 0) <= ($product->branchStocks->first()?->stock_minimum ?? 0)
                                            ? 'bg-amber-100 text-amber-700'
                                            : 'bg-emerald-100 text-emerald-700' }}">
                                        {{ (int)($product->branchStocks->first()?->stock_actual ?? 0) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2.5">
                                    @php
                                        $presData = $product->presentations->where('status', 1)->map(function($p) {
                                            return [
                                                'id'     => $p->id,
                                                'label'  => optional($p->unit)->name ?? 'Presentación',
                                                'amount' => (float) $p->equivalent_amount,
                                                'price'  => (float) $p->sale_price,
                                            ];
                                        })->values();
                                    @endphp
                                    <button class="btn-agregar w-7 h-7 flex items-center justify-center rounded-lg bg-sky-600 hover:bg-sky-700 active:scale-95 text-white transition-all"
                                            data-code="{{ $product->code }}"
                                            data-name="{{ $product->name }}"
                                            data-price="{{ $product->unit_sale_price }}"
                                            data-afectacion="{{ $product->igv_affectation }}"
                                            data-receta="{{ $product->recipe_level }}"
                                            data-ingredient="{{ $product->active_ingredient }}"
                                            data-stock="{{ (int)($product->branchStocks->first()?->stock_actual ?? 0) }}"
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
                                    <button type="button" id="btnBuscarPrincipio" class="mt-2 text-xs font-medium text-sky-600 hover:text-sky-700"><i class="fas fa-right-left mr-1"></i> Buscar productos con ese principio activo</button>
                                    <div id="altSinResultados" class="mt-3 space-y-1 max-w-sm mx-auto text-left"></div>
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
                <div class="flex-1 min-h-0 overflow-auto">
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
                    <input type="hidden" id="tipoComprobante" value="{{ $defaultVoucher }}">
                    <input type="hidden" id="tipoDocumentoId" value="">

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
                            @unless($editOrder)
                            <button class="btn-pago btn-pago-inactivo col-span-2 flex items-center justify-center gap-2 py-2.5 px-1 rounded-xl border-2 transition-all" data-value="5">
                                <i class="fas fa-handshake text-base"></i>
                                <span class="text-[11px] font-semibold leading-none">Crédito (fiado)</span>
                            </button>
                            @endunless
                        </div>
                        <div id="seccionCredito" class="hidden mt-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200">
                            <p class="text-[11px] text-amber-800 mb-1.5">Requiere un cliente registrado con línea de crédito. No aplica a facturas.</p>
                            <label class="block text-[10px] font-bold text-amber-700 uppercase tracking-widest mb-1">Vence el</label>
                            <input type="date" id="creditDueDate" value="{{ date('Y-m-d', strtotime('+30 days')) }}" min="{{ date('Y-m-d') }}"
                                   class="w-full px-2 py-1.5 text-sm border border-amber-300 rounded-lg bg-white">
                        </div>
                    </div>

                    {{-- ─ Comprobante ───────────────────────────────── --}}
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2.5">Comprobante</p>
                        <div class="flex flex-col gap-1.5">
                            @foreach($voucherButtons as $value => $voucher)
                            <button class="btn-comprobante {{ $value === $defaultVoucher ? 'btn-comprobante-activo' : 'btn-comprobante-inactivo' }} flex items-center gap-2 px-3 py-2 rounded-lg border-2 text-sm font-medium transition-all" data-value="{{ $value }}">
                                <i class="fas {{ $voucher['icon'] }} text-xs"></i>
                                {{ $voucher['label'] }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- ─ Separador ─────────────────────────────────── --}}
                    <div class="border-t border-slate-200"></div>

                    {{-- ─ Cliente ───────────────────────────────────── --}}
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            <i class="fas fa-user"></i> Cliente
                        </label>
                        <input type="hidden" id="clienteId" value="">
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                            <input type="text" id="cliente" autocomplete="off"
                                   class="w-full pl-8 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                   placeholder="Buscar o escribir nombre...">
                            <button type="button" id="btnLimpiarCliente"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-300 hover:text-slate-500 transition-colors hidden"
                                    title="Limpiar cliente">
                                <i class="fas fa-xmark text-xs"></i>
                            </button>
                            {{-- Dropdown de sugerencias --}}
                            <div id="clienteDropdown"
                                 class="absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg z-50 hidden max-h-52 overflow-y-auto">
                            </div>
                        </div>
                        <p id="clienteSeleccionado" class="text-xs text-emerald-600 mt-1 hidden">
                            <i class="fas fa-circle-check mr-1"></i><span></span>
                        </p>
                    </div>

                    {{-- ─ Tipo de documento + Número ───────────────────── --}}
                    <div>
                        <label class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">
                            <i class="fas fa-id-card"></i> Documento
                        </label>

                        {{-- Chips de tipo de documento --}}
                        <div class="flex flex-wrap gap-1.5 mb-2" id="tiposDocumento">
                            @foreach($documentTypes as $dt)
                            <button type="button"
                                    class="btn-tipo-doc px-2.5 py-1 rounded-lg border text-xs font-medium transition-all
                                           border-slate-200 bg-white text-slate-500 hover:border-sky-400 hover:text-sky-700"
                                    data-id="{{ $dt->id }}"
                                    data-digits="{{ $dt->digits ?? '' }}"
                                    data-name="{{ $dt->name }}">
                                {{ $dt->name }}
                            </button>
                            @endforeach
                        </div>

                        {{-- Número de documento --}}
                        <div class="relative">
                            <input type="text" id="documento" maxlength="15"
                                   class="w-full pl-3 pr-8 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                                   placeholder="Número de documento">
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
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500"
                               placeholder="Ref. de pago">
                    </div>

                </div>
            </div>

        </div>

        {{-- ── Footer: totales + acción ────────────────────────── --}}
        <div class="shrink-0 flex items-center justify-between border-t border-slate-200 px-5 py-3 bg-white gap-6">

            <button id="btnTerminarVenta"
                    class="px-8 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-bold rounded-xl transition-all flex items-center gap-2 shadow-sm"
                    disabled>
                <i class="fas fa-circle-check text-sm"></i>
                {{ $editOrder ? 'Guardar Cambios' : 'Confirmar Venta' }}
                <kbd class="text-sky-200 text-xs font-normal bg-white/10 px-1.5 py-0.5 rounded ml-1">F5</kbd>
            </button>

            @if($canDiscount)
            <div class="flex items-center gap-2" title="Descuento porcentual aplicado a todos los productos (máx. 50%)">
                <label for="descuentoPct" class="text-xs font-medium text-slate-500">Desc. %</label>
                <input type="number" id="descuentoPct" min="0" max="50" step="1" inputmode="numeric" value="0"
                       class="w-16 px-2 py-1.5 text-sm text-center border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                @if($salesCfg['amount_discount'])
                <span class="text-xs text-slate-400">ó S/</span>
                <input type="number" id="descuentoMonto" min="0" step="0.10" inputmode="decimal" value="0" title="Descuento en soles sobre toda la venta (antes de IGV)"
                       class="w-20 px-2 py-1.5 text-sm text-center border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
                @endif
                <span class="text-xs text-slate-400 leading-tight">
                    @if($salesCfg['amount_discount'])
                        Usa uno solo.<br>Ej: 10 = 10% menos, o S/ 1.00 menos
                    @else
                        Número entero.<br>Ej: 10 = 10% menos en toda la venta
                    @endif
                </span>
            </div>
            @endif

            <div class="flex items-center gap-8 ml-auto">
                <div class="text-right space-y-0.5">
                    <p class="text-xs text-slate-400">Op. Gravada</p>
                    <p class="text-xs text-slate-400">Op. Exonerada</p>
                    <p class="text-xs text-slate-400">Descuento</p>
                    <p class="text-xs text-slate-400">IGV (18%)</p>
                    <p class="text-sm font-bold text-emerald-600">Total</p>
                    <p id="lblRedondeo" class="text-xs text-slate-400 hidden">Redondeo</p>
                    <p id="lblCobrar" class="text-sm font-bold text-slate-800 hidden">A cobrar</p>
                </div>
                <div class="text-right min-w-20 space-y-0.5">
                    <p id="opGravada"   class="text-xs text-slate-600">S/ 0.00</p>
                    <p id="opExonerada" class="text-xs text-slate-600">S/ 0.00</p>
                    <p id="descuento"  class="text-xs text-slate-600">S/ 0.00</p>
                    <p id="igv"         class="text-xs text-slate-600">S/ 0.00</p>
                    <p id="total"       class="text-xl font-bold text-slate-800">S/ 0.00</p>
                    <p id="redondeo"    class="text-xs text-slate-600 hidden">S/ 0.00</p>
                    <p id="cobrar"      class="text-lg font-bold text-slate-800 hidden">S/ 0.00</p>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ── Modal: receta médica (productos con receta o controlados) ───── --}}
<div id="modalReceta" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg">
        <div class="flex items-start gap-3 p-5 border-b border-slate-100">
            <div class="w-10 h-10 bg-sky-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-file-prescription text-sky-600 text-sm"></i></div>
            <div>
                <p class="text-sm font-bold text-slate-800">Receta médica</p>
                <p id="recetaAviso" class="text-xs text-slate-500 mt-0.5"></p>
            </div>
        </div>
        <div class="p-5 grid grid-cols-2 gap-3">
            <input type="hidden" id="recetaNivel" value="1">
            <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-medium text-slate-600 mb-1">Paciente <span class="text-red-500">*</span></label><input type="text" id="recetaPaciente" maxlength="150" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-medium text-slate-600 mb-1">Documento del paciente <span class="receta-controlado text-red-500">*</span></label><input type="text" id="recetaDocPaciente" maxlength="15" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-medium text-slate-600 mb-1">Médico <span class="text-red-500">*</span></label><input type="text" id="recetaMedico" maxlength="150" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-medium text-slate-600 mb-1">N° colegiatura (CMP) <span class="text-red-500">*</span></label><input type="text" id="recetaCmp" maxlength="20" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-medium text-slate-600 mb-1">N° de receta <span class="receta-controlado text-red-500">*</span></label><input type="text" id="recetaNumero" maxlength="30" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <div class="col-span-2 sm:col-span-1"><label class="block text-xs font-medium text-slate-600 mb-1">Fecha de la receta <span class="text-red-500">*</span></label><input type="date" id="recetaFecha" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <div class="col-span-2"><label class="block text-xs font-medium text-slate-600 mb-1">Establecimiento de salud</label><input type="text" id="recetaEstablecimiento" maxlength="150" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500"></div>
            <p id="recetaError" class="hidden col-span-2 text-xs text-red-600"></p>
        </div>
        <div class="flex justify-end gap-3 px-5 py-4 border-t border-slate-100">
            <button type="button" onclick="cerrarModalReceta()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="button" id="btnGuardarReceta" class="px-4 py-2 text-sm font-medium text-white bg-sky-600 hover:bg-sky-700 rounded-lg">Guardar y confirmar venta</button>
        </div>
    </div>
</div>

{{-- ── Modal: cobro en efectivo (con cuánto paga y vuelto) ───── --}}
<div id="modalCobro" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        {{-- Cabecera con el total a cobrar --}}
        <div class="relative text-center px-5 pt-5 pb-6" style="background:linear-gradient(135deg,#059669,#10b981)">
            <button type="button" onclick="cerrarModalCobro()" class="absolute top-3 right-3 w-7 h-7 flex items-center justify-center rounded-lg text-white hover:bg-white/20">
                <i class="fas fa-xmark text-sm"></i>
            </button>
            <div class="w-11 h-11 mx-auto rounded-full flex items-center justify-center" style="background:rgba(255,255,255,.2)">
                <i class="fas fa-money-bill-wave text-white"></i>
            </div>
            <p class="text-[10px] font-bold uppercase tracking-widest mt-3" style="color:rgba(255,255,255,.8)">Total a cobrar</p>
            <p id="cobroTotal" class="text-4xl font-bold text-white mt-0.5">S/ 0.00</p>
        </div>

        <div class="p-5 space-y-4">
            <div>
                <label for="cobroPagaCon" class="block text-xs font-medium text-slate-600 mb-1.5">Paga con</label>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-lg font-semibold text-slate-400">S/</span>
                    <input type="number" id="cobroPagaCon" min="0" step="0.10" inputmode="decimal" autocomplete="off"
                           class="w-full pl-11 pr-3 py-3 text-2xl font-bold text-center text-slate-800 border-2 border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500"
                           placeholder="0.00">
                </div>
                <div id="cobroRapidos" class="flex flex-wrap justify-center gap-1.5 mt-2.5"></div>
            </div>

            {{-- Tarjeta de vuelto: cambia de color según el estado (vacío / vuelto / falta) --}}
            <div id="cobroVueltoBox" class="rounded-2xl py-4 px-4 flex items-center gap-4"
                 style="background:#f8fafc;border:2px dashed #cbd5e1;transition:all .2s">
                <div id="cobroVueltoIcon" class="w-12 h-12 rounded-full flex items-center justify-center shrink-0"
                     style="background:#e2e8f0;color:#94a3b8;transition:all .2s">
                    <i class="fas fa-coins text-lg"></i>
                </div>
                <div class="flex-1 text-left">
                    <p id="cobroVueltoLbl" class="text-[10px] font-bold uppercase tracking-widest" style="color:#94a3b8">Vuelto</p>
                    <p id="cobroVuelto" class="text-3xl font-bold leading-tight" style="color:#cbd5e1">S/ 0.00</p>
                </div>
            </div>

            <p id="cobroError" class="hidden text-xs text-red-600 text-center"></p>
            <p class="text-[11px] text-slate-400 text-center">Puedes dejar «Paga con» vacío si no quieres registrar el monto.</p>
        </div>

        <div class="flex justify-end gap-3 px-5 py-4 border-t border-slate-100 bg-slate-50">
            <button type="button" onclick="cerrarModalCobro()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 rounded-lg">Cancelar</button>
            <button type="button" id="btnConfirmarCobro" class="px-5 py-2 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm">
                <i class="fas fa-check mr-1"></i> Confirmar venta
            </button>
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
                           class="flex-1 text-center text-xl font-bold border border-slate-200 rounded-lg py-1.5 focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <button id="modalBtnInc"
                            class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors text-lg font-light select-none">
                        +
                    </button>
                </div>
                <p class="text-xs text-slate-400 mt-1.5 text-center">
                    Stock disponible: <span id="modalStock" class="font-semibold text-slate-600"></span>
                </p>
            </div>

            {{-- Alternativas con el mismo principio activo --}}
            <div id="altBox" class="hidden">
                <button type="button" id="btnAlternativas" class="text-xs font-medium text-sky-600 hover:text-sky-700"><i class="fas fa-right-left mr-1"></i> Ver alternativas con el mismo principio activo</button>
                <div id="altLista" class="mt-2 space-y-1"></div>
            </div>

            {{-- Precio + acción --}}
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <div>
                    <p class="text-xs text-slate-400">Precio unitario</p>
                    <p id="modalPrecio" class="text-2xl font-bold text-slate-800">S/ 0.00</p>
                </div>
                <button id="modalBtnAgregar"
                        class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 active:bg-sky-800 text-white text-sm font-bold rounded-xl transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-plus text-xs"></i> Agregar
                </button>
            </div>

        </div>
    </div>
</div>
{{-- ── Modal de venta exitosa ─────────────────────────────────── --}}
<div id="modalSaleSuccess"
     class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4"
     style="display:none!important">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm text-center overflow-hidden">

        {{-- Icono de éxito --}}
        <div class="bg-emerald-50 px-6 pt-8 pb-6">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-circle-check text-emerald-600 text-3xl"></i>
            </div>
            <h3 class="text-lg font-bold text-slate-800">¡Venta registrada!</h3>
            <p class="text-sm text-slate-500 mt-1">Comprobante generado correctamente</p>
        </div>

        {{-- Número de comprobante --}}
        <div class="px-6 py-5 border-b border-slate-100">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">N° Comprobante</p>
            <p id="saleVoucherNumber" class="text-xl font-bold text-emerald-700 font-mono"></p>
        </div>

        {{-- Acciones --}}
        <div class="px-6 py-5 flex flex-col gap-3">
            <button type="button" id="btnPrintSale"
                    class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <i class="fas fa-print text-xs"></i>
                Imprimir comprobante
            </button>
            <button type="button" id="btnNewSale"
                    class="w-full flex items-center justify-center gap-2 px-5 py-3 border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium rounded-xl transition-colors">
                <i class="fas fa-rotate-left text-xs"></i>
                Nueva venta
            </button>
        </div>

    </div>
</div>

@endsection

@section('scripts')
<script>
// ── Estilos de botones de pago / comprobante / tipo de doc ───
const PAGO_ACTIVO   = ['border-sky-500','bg-sky-50','text-sky-700'];
const PAGO_INACTIVO = ['border-slate-200','bg-white','text-slate-500'];
const DOC_ACTIVO    = ['border-sky-500','bg-sky-50','text-sky-700'];
const DOC_INACTIVO  = ['border-slate-200','bg-white','text-slate-500'];

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
    $('#seccionNroOp').toggleClass('hidden', val == 1 || val == 5);
    $('#seccionCredito').toggleClass('hidden', val != 5);
    actualizarTotales();
});

// ── Tipo de comprobante ───────────────────────────────────────
$(document).on('click', '.btn-comprobante', function() {
    activarBtn('.btn-comprobante', $(this));
    $('#tipoComprobante').val($(this).data('value'));

    // Factura (2) → forzar selección de RUC si hay documento
    if ($(this).data('value') == 2) {
        seleccionarTipoDocPorId(3); // RUC
    }
});

// ── Tipo de documento del cliente ────────────────────────────
function seleccionarTipoDocPorId(id) {
    const $btn = $('.btn-tipo-doc[data-id="' + id + '"]');
    if (!$btn.length) return;
    $('.btn-tipo-doc').removeClass(DOC_ACTIVO.join(' ')).addClass(DOC_INACTIVO.join(' '));
    $btn.removeClass(DOC_INACTIVO.join(' ')).addClass(DOC_ACTIVO.join(' '));
    $('#tipoDocumentoId').val(id);

    // Ajustar maxlength según dígitos esperados
    const digits = $btn.data('digits');
    $('#documento').attr('maxlength', digits ? digits : 20);
}

$(document).on('click', '.btn-tipo-doc', function() {
    seleccionarTipoDocPorId($(this).data('id'));
    $('#documento').focus();
});

// Aplicar estilos iniciales a los botones activos al cargar
$('.btn-pago-activo').removeClass(PAGO_INACTIVO.join(' ')).addClass(PAGO_ACTIVO.join(' '));
$('.btn-pago-inactivo').removeClass(PAGO_ACTIVO.join(' ')).addClass(PAGO_INACTIVO.join(' '));
$('.btn-comprobante-activo').removeClass(PAGO_INACTIVO.join(' ')).addClass(PAGO_ACTIVO.join(' '));
$('.btn-comprobante-inactivo').removeClass(PAGO_ACTIVO.join(' ')).addClass(PAGO_INACTIVO.join(' '));

// ── Estado del carrito ────────────────────────────────────────
let carrito = {};

// ── Modo edición de una venta de caja histórica ───────────────
const EDIT_ORDER = @json($editConfig);

// Cliente predeterminado: ventas al público general (DNI 00000000)
const PUBLIC_CLIENT = {!! json_encode([
    'id'          => $publicClient->id,
    'name'        => $publicClient->name,
    'document'    => $publicClient->document_number,
    'doc_type_id' => $publicClient->document_type_id,
]) !!};

const BTN_LABEL = EDIT_ORDER ? 'Guardar Cambios' : 'Confirmar Venta';

// ── Filtrado en tiempo real ───────────────────────────────────
$('#inputBusqueda').on('input', function() {
    const term = $(this).val().trim().toLowerCase();
    $('#btnLimpiarBusqueda').toggleClass('hidden', !term);
    filtrarProductos();
});

// Lector de código de barras: escribe el código y envía Enter. Si coincide exacto con el
// código de barras o el código interno de un producto, se abre directo para agregarlo
$('#inputBusqueda').on('keydown', function(e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();

    const term = $(this).val().trim().toLowerCase();
    if (!term) return;

    const $row = $('.producto-row').filter(function() {
        // attr y no data(): data() convierte "0012345" en el número 12345 y pierde los ceros
        return ($(this).attr('data-barcode') || '').toLowerCase() === term
            || ($(this).attr('data-code') || '').toLowerCase() === term;
    }).first();

    if ($row.length) {
        $(this).val('').trigger('input');
        $row.find('.btn-agregar').trigger('click');
    }
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

// ── Alternativas por principio activo ────────────────────────
function pintarAlternativas($destino, lista) {
    $destino.html(lista.length ? lista.map(p => `
        <button type="button" class="alt-item w-full text-left px-3 py-2 rounded-lg border border-slate-200 hover:bg-sky-50 hover:border-sky-300 transition-colors" data-name="${$('<div>').text(p.name).html()}">
            <span class="block text-xs font-medium text-slate-800">${$('<div>').text(p.name).html()}</span>
            <span class="block text-[10px] text-slate-500">${$('<div>').text(p.laboratory || 'sin laboratorio').html()} · S/ ${p.price.toFixed(2)} · stock ${p.stock}</span>
        </button>`).join('') : '<p class="text-xs text-slate-400">No hay otros productos con ese principio activo y stock.</p>');
}

$('#btnAlternativas').on('click', function () {
    $.getJSON('{{ route("employee.pos.alternatives") }}', { code: modalProducto.code }, lista => pintarAlternativas($('#altLista'), lista));
});

$('#btnBuscarPrincipio').on('click', function () {
    $.getJSON('{{ route("employee.pos.alternatives") }}', { q: $('#inputBusqueda').val().trim() }, lista => pintarAlternativas($('#altSinResultados'), lista));
});

// Elegir una alternativa: se cierra el modal y la lista se filtra a ese producto
$(document).on('click', '.alt-item', function () {
    cerrarModalAgregar();
    $('#inputBusqueda').val($(this).data('name')).trigger('input');
});

// ── Presentaciones y stock ────────────────────────────────────
// El carrito tiene una línea por producto + presentación (clave "código|id" o "código|base"):
// así la unidad y el blíster del mismo producto quedan separados, cada uno con su precio.
// El stock del producto está en unidades base; cada línea ocupa cantidad × factor
// (1 blíster x10 = 10 unidades), y entre todas sus líneas no pueden superar el stock.
function claveLinea(code, presentationId) {
    return code + '|' + (presentationId || 'base');
}

// Unidades base que ya ocupan en el carrito las líneas de un producto (sin contar una de ellas)
function unidadesEnCarrito(code, exceptoClave) {
    return Object.entries(carrito).reduce((s, [k, i]) => s + (i.code === code && k !== exceptoClave ? i.qty * i.factor : 0), 0);
}

// Cantidad máxima de una presentación según el stock libre del producto
function maxCantidad(code, factor, stock, exceptoClave) {
    return Math.floor(Math.max(stock - unidadesEnCarrito(code, exceptoClave), 0) / factor + 1e-9);
}

function escapar(texto) {
    return $('<div>').text(texto == null ? '' : String(texto)).html();
}

// ── Modal de cantidad / presentación ─────────────────────────
let modalProducto   = null;
let modalPrecio     = 0;
let modalPresentacion = { id: null, factor: 1, label: '' };

// Cantidad máxima con la presentación elegida en el modal (lo ya agregado de esa misma
// presentación se suma al agregar, así que cuenta como ocupado)
function modalMax() {
    if (!modalProducto) return 9999;
    return maxCantidad(modalProducto.code, modalPresentacion.factor, modalProducto.stock, null);
}

function pintarStockModal() {
    const max = modalMax();
    $('#modalCantidad').attr('max', max);
    $('#modalStock').text(modalPresentacion.factor > 1
        ? `${modalProducto.stock} unid. · alcanza para ${max}`
        : `${max} unid.`);
}

$(document).on('click', '.btn-agregar', function() {
    const code          = $(this).data('code');
    const name          = String($(this).data('name'));
    const price         = parseFloat($(this).data('price'));
    // Al editar una venta, sus líneas traen el stock que ya tenía descontado (stock + lo vendido)
    const enCarrito     = Object.values(carrito).find(i => i.code === code);
    const stock         = Math.max(parseInt($(this).data('stock')) || 0, enCarrito ? enCarrito.stock : 0);
    const afectacion    = String($(this).data('afectacion') || '20');
    const receta        = parseInt($(this).data('receta')) || 0;
    const ingredient    = String($(this).data('ingredient') || '');
    const presentations = $(this).data('presentations') || [];

    modalProducto = { code, name, stock, afectacion, receta, ingredient };
    $('#altLista').empty();
    $('#altBox').toggleClass('hidden', !ingredient);
    modalPrecio   = price;
    modalPresentacion = { id: null, factor: 1, label: '' };

    $('#modalNombre').text(name);
    $('#modalCodigo').text(code);
    $('#modalCantidad').val(1);
    $('#modalPrecio').text('S/ ' + price.toFixed(2));

    const $lista = $('#listaPresentaciones').empty();
    if (presentations.length > 0) {
        $('#seccionPresentaciones').removeClass('hidden');
        // Si hay una presentación de 1 unidad al mismo precio que la base, es la misma opción:
        // se muestra una sola vez con el nombre de la presentación (ej: Tableta)
        const duplicada = presentations.find(p => parseFloat(p.amount) === 1 && Math.abs(parseFloat(p.price) - price) < 0.005);
        $lista.append(opcionPresentacion('base', duplicada ? duplicada.label : 'Unidad', '', price, 1, true));
        presentations.forEach(p => {
            if (p === duplicada) return;
            $lista.append(opcionPresentacion(p.id, p.label, 'x' + p.amount + ' uds', p.price, parseFloat(p.amount), false));
        });
    } else {
        $('#seccionPresentaciones').addClass('hidden');
    }

    pintarStockModal();
    document.getElementById('modalAgregar').style.setProperty('display', 'flex', 'important');
    setTimeout(() => $('#modalCantidad').focus().select(), 50);
});

function opcionPresentacion(val, label, sub, price, factor, checked) {
    const active = checked ? 'border-sky-500 bg-sky-50' : 'border-slate-200 hover:border-slate-300';
    return `
        <label class="flex items-center gap-2.5 p-2.5 rounded-lg border ${active} cursor-pointer presentacion-opcion transition-colors">
            <input type="radio" name="modalPresentacion" value="${val}" data-price="${price}" data-factor="${factor}"
                   data-label="${escapar(val === 'base' ? '' : label)}" ${checked ? 'checked' : ''}
                   class="text-emerald-600 shrink-0">
            <span class="flex-1 min-w-0">
                <span class="text-sm font-medium text-slate-700">${escapar(label)}</span>
                ${sub ? `<span class="text-xs text-slate-400 ml-1">(${sub})</span>` : ''}
            </span>
            <span class="text-sm font-semibold text-slate-700 whitespace-nowrap">S/ ${parseFloat(price).toFixed(2)}</span>
        </label>`;
}

$(document).on('change', 'input[name="modalPresentacion"]', function() {
    modalPrecio = parseFloat($(this).data('price'));
    modalPresentacion = {
        id:     $(this).val() === 'base' ? null : parseInt($(this).val()),
        factor: parseFloat($(this).data('factor')) || 1,
        label:  String($(this).attr('data-label') || ''),
    };
    $('#modalPrecio').text('S/ ' + modalPrecio.toFixed(2));
    $('.presentacion-opcion').each(function() {
        const checked = $(this).find('input').is(':checked');
        $(this).toggleClass('border-sky-500 bg-sky-50', checked)
               .toggleClass('border-slate-200', !checked);
    });
    pintarStockModal();
    const max = modalMax();
    if ((parseInt($('#modalCantidad').val()) || 1) > max) $('#modalCantidad').val(Math.max(max, 1));
});

$('#modalBtnDec').on('click', function() {
    const v = parseInt($('#modalCantidad').val()) || 1;
    if (v > 1) $('#modalCantidad').val(v - 1);
});

$('#modalBtnInc').on('click', function() {
    const v = parseInt($('#modalCantidad').val()) || 1;
    if (v < modalMax()) $('#modalCantidad').val(v + 1);
});

$('#modalBtnAgregar').on('click', function() {
    if (!modalProducto) return;
    const { code, name, stock, afectacion, receta } = modalProducto;
    const { id: presentationId, factor, label } = modalPresentacion;
    const max = modalMax();

    if (max < 1) {
        window.showAlertModal('Stock insuficiente', [`No queda stock de «${name}» para agregar ${label ? 'esa presentación' : 'otra unidad'}.`]);
        return;
    }

    const qty   = Math.min(Math.max(parseInt($('#modalCantidad').val()) || 1, 1), max);
    const clave = claveLinea(code, presentationId);

    if (carrito[clave]) {
        carrito[clave].qty += qty;
    } else {
        carrito[clave] = { code, name, label, presentationId, factor, price: modalPrecio, stock, qty, afectacion, receta };
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
// Máximo de una línea: lo que deja libre el resto de líneas del mismo producto
function maxLinea(clave) {
    const i = carrito[clave];
    return maxCantidad(i.code, i.factor, i.stock, clave);
}

$(document).on('input', '.input-cantidad', function() {
    const clave = $(this).data('key');
    const val   = parseInt($(this).val()) || 1;
    carrito[clave].qty = Math.min(Math.max(val, 1), Math.max(maxLinea(clave), 1));
    $(this).val(carrito[clave].qty);
    actualizarTotales();
});

$(document).on('click', '.btn-dec', function() {
    const clave = $(this).data('key');
    if (carrito[clave].qty > 1) { carrito[clave].qty--; renderCarrito(); }
});

$(document).on('click', '.btn-inc', function() {
    const clave = $(this).data('key');
    if (carrito[clave].qty < maxLinea(clave)) { carrito[clave].qty++; renderCarrito(); }
});

// ── Eliminar del carrito ──────────────────────────────────────
$(document).on('click', '.btn-quitar', function() {
    delete carrito[$(this).data('key')];
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

        keys.forEach((clave) => {
            const item    = carrito[clave];
            const importe = (item.price * item.qty).toFixed(2);
            // La presentación (Blíster x10, Caja x100...) se muestra junto al nombre
            const presentacion = item.label
                ? `<span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded bg-sky-50 text-sky-700 text-[10px] font-semibold">${escapar(item.label)}${item.factor > 1 ? ' x' + item.factor : ''}</span>`
                : '';
            $tbody.append(`
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-2.5">
                        <p class="text-sm font-medium text-slate-800">${escapar(item.name)}${presentacion}</p>
                        <p class="text-xs text-slate-400 font-mono">${escapar(item.code)}</p>
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center justify-center gap-1">
                            <button class="btn-dec w-6 h-6 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors text-xs" data-key="${escapar(clave)}">−</button>
                            <input class="input-cantidad w-10 text-center text-sm font-semibold border border-slate-200 rounded py-0.5 focus:outline-none focus:ring-1 focus:ring-sky-500" data-key="${escapar(clave)}" value="${item.qty}" min="1" max="${maxLinea(clave)}">
                            <button class="btn-inc w-6 h-6 flex items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors text-xs" data-key="${escapar(clave)}">+</button>
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-right text-xs text-slate-500">S/ ${item.price.toFixed(2)}</td>
                    <td class="px-4 py-2.5 text-right font-semibold text-slate-800">S/ ${importe}</td>
                    <td class="px-2 py-2.5">
                        <button class="btn-quitar w-6 h-6 flex items-center justify-center rounded text-slate-300 hover:bg-red-50 hover:text-red-500 transition-colors" data-key="${escapar(clave)}">
                            <i class="fas fa-xmark text-xs"></i>
                        </button>
                    </td>
                </tr>`);
        });
    }

    actualizarTotales();
    actualizarContadorCarrito();
}

// Porcentaje de descuento ingresado (0 si no hay campo o el valor no es válido)
function porcentajeDescuento() {
    const v = parseInt($('#descuentoPct').val(), 10);
    return isNaN(v) ? 0 : Math.min(Math.max(v, 0), 50);
}

// Descuento en soles ingresado (0 si no hay campo o el valor no es válido)
function montoDescuento() {
    const v = parseFloat($('#descuentoMonto').val());
    return isNaN(v) ? 0 : Math.max(redondear(v), 0);
}

// Solo enteros en el porcentaje: se quita cualquier decimal que se escriba (10.5 → 10)
$('#descuentoPct').on('input', function () {
    const limpio = String(this.value).replace(/[^0-9]/g, '');
    if (limpio !== this.value) this.value = limpio;
    if (limpio !== '' && parseInt(limpio, 10) > 0) $('#descuentoMonto').val(0);   // porcentaje o monto, no ambos
    actualizarTotales();
});

$('#descuentoMonto').on('input', function () {
    if (montoDescuento() > 0) $('#descuentoPct').val(0);
    actualizarTotales();
});

// Redondeo a 2 decimales (igual que el servidor)
function redondear(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}

// Totales de la última actualización (se envían al servidor, que los vuelve a calcular)
let totalesVenta = { sub: 0, igv: 0, total: 0 };

// Ajuste de la sede: redondeo en efectivo (hacia abajo al múltiplo de S/ 0.10)
const REDONDEO_EFECTIVO = @json((bool) $salesCfg['cash_rounding']);

// 10 = gravado (IGV 18%) · 20 = exonerado · 30 = inafecto
function actualizarTotales() {
    let gravada = 0, exonerada = 0, igv = 0, descuento = 0;
    const pct   = porcentajeDescuento();
    const monto = Math.min(montoDescuento(), 999999999);
    const lineas = Object.values(carrito);
    const brutoTotal = lineas.reduce((s, i) => s + redondear(i.price * i.qty), 0);
    let repartido = 0;

    lineas.forEach((i, idx) => {
        const bruto = redondear(i.price * i.qty);
        let desc;
        if (monto > 0 && brutoTotal > 0) {
            // Descuento por monto: proporcional al importe de cada línea; la última absorbe el centavo que sobre
            desc = idx === lineas.length - 1 ? redondear(monto - repartido) : redondear(monto * bruto / brutoTotal);
            repartido += desc;
        } else {
            desc = redondear(bruto * pct / 100);
        }
        const base = redondear(bruto - desc);
        descuento += desc;

        if (i.afectacion === '10') {
            gravada += base;
            igv     += redondear(base * 0.18);
        } else {
            exonerada += base;   // exonerado e inafecto no pagan IGV
        }
    });

    const sub   = redondear(gravada + exonerada);
    const total = redondear(sub + igv);

    totalesVenta = { sub, igv: redondear(igv), total, descuento: redondear(descuento) };

    $('#descuento').text((descuento > 0 ? '− ' : '') + 'S/ ' + redondear(descuento).toFixed(2));

    $('#opGravada').text('S/ ' + redondear(gravada).toFixed(2));
    $('#opExonerada').text('S/ ' + redondear(exonerada).toFixed(2));
    $('#igv').text('S/ ' + redondear(igv).toFixed(2));
    $('#total').text('S/ ' + total.toFixed(2));

    // Redondeo en efectivo: solo si la sede lo activó, el pago es en efectivo y hay céntimos que perdonar
    const redondeo = (REDONDEO_EFECTIVO && $('#tipoPago').val() == 1) ? (Math.round(total * 100) % 10) / 100 : 0;
    $('#lblRedondeo, #redondeo').toggleClass('hidden', redondeo === 0);
    $('#lblCobrar, #cobrar').toggleClass('hidden', redondeo === 0);
    $('#redondeo').text('− S/ ' + redondeo.toFixed(2));
    $('#cobrar').text('S/ ' + redondear(total - redondeo).toFixed(2));

    totalACobrar = redondear(total - redondeo);
    actualizarVuelto();
}

// ── Cobro en efectivo: modal "Paga con" + vuelto ──────────────
// totalACobrar = total menos el redondeo; montoRecibido/cobroListo se reinician al terminar la venta
let totalACobrar  = 0;
let montoRecibido = null;
let cobroListo    = false;

function abrirModalCobro() {
    $('#cobroTotal').text('S/ ' + totalACobrar.toFixed(2));
    $('#cobroError').addClass('hidden').text('');

    // Billetes rápidos: solo los que cubren el total
    const billetes = [10, 20, 50, 100, 200].filter(b => b >= totalACobrar);
    $('#cobroRapidos').html(
        '<button type="button" class="cobro-rapido px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-300 bg-emerald-50 text-emerald-700" data-monto="' + totalACobrar.toFixed(2) + '">Exacto</button>' +
        billetes.map(b => '<button type="button" class="cobro-rapido px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-100" data-monto="' + b + '">S/ ' + b + '</button>').join('')
    );

    $('#cobroPagaCon').val(EDIT_ORDER && EDIT_ORDER.amount_received ? EDIT_ORDER.amount_received : '');
    actualizarVuelto();
    document.getElementById('modalCobro').style.setProperty('display', 'flex', 'important');
    setTimeout(() => $('#cobroPagaCon').trigger('focus'), 50);
}
function cerrarModalCobro() {
    document.getElementById('modalCobro').style.setProperty('display', 'none', 'important');
}

// Vuelto = efectivo recibido − lo que se cobra (la tarjeta cambia de estilo: vacío / vuelto / falta)
function actualizarVuelto() {
    const recibido = parseFloat($('#cobroPagaCon').val());
    const estados = {
        vacio:  { box: 'background:#f8fafc;border:2px dashed #cbd5e1', icon: 'background:#e2e8f0;color:#94a3b8', lbl: '#94a3b8', num: '#cbd5e1', ico: 'fa-coins' },
        vuelto: { box: 'background:#ecfdf5;border:2px solid #6ee7b7',  icon: 'background:#10b981;color:#fff',    lbl: '#059669', num: '#047857', ico: 'fa-hand-holding-dollar' },
        falta:  { box: 'background:#fef2f2;border:2px solid #fca5a5',  icon: 'background:#ef4444;color:#fff',    lbl: '#dc2626', num: '#dc2626', ico: 'fa-triangle-exclamation' },
    };

    let clave = 'vacio', texto = 'Vuelto', monto = 0;
    if (!isNaN(recibido) && recibido > 0) {
        const vuelto = redondear(recibido - totalACobrar);
        clave = vuelto < 0 ? 'falta' : 'vuelto';
        texto = vuelto < 0 ? 'Falta' : 'Vuelto';
        monto = Math.abs(vuelto);
    }

    const e = estados[clave];
    $('#cobroVueltoBox').attr('style', e.box + ';transition:all .2s');
    $('#cobroVueltoIcon').attr('style', e.icon + ';transition:all .2s').find('i').attr('class', 'fas ' + e.ico + ' text-lg');
    $('#cobroVueltoLbl').text(texto).css('color', e.lbl);
    $('#cobroVuelto').css('color', e.num).text('S/ ' + monto.toFixed(2));
}

$('#cobroPagaCon').on('input', actualizarVuelto);
$(document).on('click', '.cobro-rapido', function () {
    $('#cobroPagaCon').val($(this).data('monto'));
    actualizarVuelto();
});

function confirmarCobro() {
    const recibido = parseFloat($('#cobroPagaCon').val());

    if (!isNaN(recibido) && recibido > 0 && redondear(recibido - totalACobrar) < 0) {
        $('#cobroError').removeClass('hidden').text('El efectivo recibido no alcanza para cubrir el total.');
        return;
    }

    montoRecibido = (!isNaN(recibido) && recibido > 0) ? recibido : null;
    cobroListo    = true;
    cerrarModalCobro();
    $('#btnTerminarVenta').trigger('click');
}
$('#btnConfirmarCobro').on('click', confirmarCobro);
$('#modalCobro').on('keydown', function (e) { if (e.key === 'Escape') cerrarModalCobro(); });

function actualizarContadorCarrito() {
    const total = Object.values(carrito).reduce((s, i) => s + i.qty, 0);
    $('#contadorCarrito').text(total + (total === 1 ? ' ítem' : ' ítems'));
}

// ── Nueva venta ───────────────────────────────────────────────
function nuevaVenta() {
    carrito = {};
    renderCarrito();
    $('#inputBusqueda').val('').trigger('input');
    $('#cliente, #documento, #nroOperacion').val('');
    $('#clienteId').val('');
    $('#btnLimpiarCliente').addClass('hidden');
    $('#clienteSeleccionado').addClass('hidden').find('span').text('');
    $('#clienteDropdown').addClass('hidden').empty();
    $('#nroComprobante').text('— automático —').removeClass('text-emerald-700 font-semibold').addClass('text-slate-400 italic');
    $('#dniEstado').addClass('hidden').html('');
    $('#dniMensaje').addClass('hidden').text('').removeClass('text-emerald-600 text-slate-400 text-red-400');
    $('#tipoDocumentoId').val('');
    $('.btn-tipo-doc').removeClass(DOC_ACTIVO.join(' ')).addClass(DOC_INACTIVO.join(' '));
    location.reload();
}

$('#btnNuevaVenta').on('click', nuevaVenta);

// ── Confirmar venta (AJAX) ────────────────────────────────────
// Receta médica: se pide al confirmar si el carrito tiene productos con receta (1) o controlados (2)
let recetaDatos = null;
function nivelReceta() {
    return Object.values(carrito).reduce((max, i) => Math.max(max, i.receta || 0), 0);
}
function abrirModalReceta(nivel) {
    $('#recetaNivel').val(nivel);
    $('#recetaAviso').text(nivel === 2
        ? 'La venta incluye productos CONTROLADOS: todos los datos de la receta son obligatorios y quedan en el libro de controlados.'
        : 'La venta incluye productos que se venden con receta médica.');
    $('.receta-controlado').toggleClass('hidden', nivel < 2);
    $('#recetaError').addClass('hidden').text('');
    if (!$('#recetaFecha').val()) $('#recetaFecha').val(new Date().toISOString().slice(0, 10));
    document.getElementById('modalReceta').style.setProperty('display', 'flex', 'important');
}
function cerrarModalReceta() {
    document.getElementById('modalReceta').style.setProperty('display', 'none', 'important');
}
$('#btnGuardarReceta').on('click', function () {
    const nivel = parseInt($('#recetaNivel').val()) || 1;
    const d = {
        patient_name: $('#recetaPaciente').val().trim(), patient_document: $('#recetaDocPaciente').val().trim(),
        doctor_name: $('#recetaMedico').val().trim(), doctor_license: $('#recetaCmp').val().trim(),
        establishment: $('#recetaEstablecimiento').val().trim(), prescription_number: $('#recetaNumero').val().trim(),
        prescription_date: $('#recetaFecha').val(),
    };
    const faltan = [];
    if (!d.patient_name) faltan.push('paciente');
    if (!d.doctor_name) faltan.push('médico');
    if (!d.doctor_license) faltan.push('CMP');
    if (!d.prescription_date) faltan.push('fecha');
    if (nivel >= 2 && !d.patient_document) faltan.push('documento del paciente');
    if (nivel >= 2 && !d.prescription_number) faltan.push('N° de receta');
    if (faltan.length) { $('#recetaError').removeClass('hidden').text('Completa: ' + faltan.join(', ') + '.'); return; }
    recetaDatos = d;
    cerrarModalReceta();
    $('#btnTerminarVenta').trigger('click');
});

$('#btnTerminarVenta').on('click', function() {
    const nivelRx = nivelReceta();
    if (nivelRx > 0 && !EDIT_ORDER && !recetaDatos) { abrirModalReceta(nivelRx); return; }

    // Pago en efectivo: preguntar con cuánto paga y mostrar el vuelto antes de cerrar la venta
    if ($('#tipoPago').val() == 1 && !cobroListo) { abrirModalCobro(); return; }

    // El servidor toma precio y unidades de la presentación desde la base de datos;
    // el precio se envía solo para detectar si cambió mientras la página estaba abierta
    const items = Object.values(carrito).map(item => ({
        code:            item.code,
        presentation_id: item.presentationId,
        name:            item.name,
        price:           item.price,
        qty:             item.qty,
    }));

    const { sub, igv, total } = totalesVenta;

    const payload = {
        items,
        client_id:         $('#clienteId').val()        || null,
        customer_name:     $('#cliente').val(),
        document_type_id:  $('#tipoDocumentoId').val()  || null,
        customer_document: $('#documento').val(),
        payment_type:      $('#tipoPago').val(),
        voucher_type:      $('#tipoComprobante').val(),
        voucher_number:    $('#nroComprobante').val(),
        operation_number:  $('#nroOperacion').val(),
        amount_received:   $('#tipoPago').val() == 1 ? montoRecibido : null,
        subtotal: sub,
        igv:      igv,
        total:    total,
        discount_percent: porcentajeDescuento(),
        discount_value:   montoDescuento(),
        prescription: nivelRx > 0 ? recetaDatos : null,
        credit_due_date: $('#tipoPago').val() == 5 ? $('#creditDueDate').val() : null,
        historical: @json($historicalCaja?->id),   // caja histórica (null = caja normal)
    };

    const $btn = $(this).prop('disabled', true).text('Procesando...');

    $.ajax({
        url:         EDIT_ORDER ? EDIT_ORDER.update_url : '{{ route("employee.orders.store") }}',
        method:      EDIT_ORDER ? 'PUT' : 'POST',
        contentType: 'application/json',
        headers:     { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data:        JSON.stringify(payload),
        success: function(res) {
            // Edición: avisar y volver a la caja histórica
            if (EDIT_ORDER) {
                mostrarToast('success', res.message ?? 'Venta actualizada.');
                setTimeout(function() { window.location.href = EDIT_ORDER.back_url; }, 700);
                return;
            }

            const numComp = res.voucher_number ?? '';

            // Mostrar el número en el topbar
            $('#nroComprobante').text(numComp).removeClass('text-slate-400 italic').addClass('text-emerald-700 font-semibold');
            setTimeout(function() {
                $('#nroComprobante').text('— automático —').removeClass('text-emerald-700 font-semibold').addClass('text-slate-400 italic');
            }, 6000);

            // Abrir modal de éxito con opción de imprimir
            openSaleSuccessModal(res.order_id, numComp);

            carrito = {};
            recetaDatos = null;
            $('#modalReceta input').not('#recetaNivel').val('');
            renderCarrito();
            $('#inputBusqueda').val('').trigger('input');
            $('#cliente, #documento, #nroOperacion').val('');
            $('#clienteId').val('');
            $('#btnLimpiarCliente').addClass('hidden');
            $('#clienteSeleccionado').addClass('hidden').find('span').text('');
            $('#clienteDropdown').addClass('hidden').empty();
            $('#tipoDocumentoId').val('');
            $('.btn-tipo-doc').removeClass(DOC_ACTIVO.join(' ')).addClass(DOC_INACTIVO.join(' '));
            aplicarClientePublico();
            $('#dniEstado').addClass('hidden').html('');
            $('#dniMensaje').addClass('hidden').text('').removeClass('text-emerald-600 text-slate-400 text-red-400');
            $.get(window.location.href, function(html) {
                const $nuevaTabla = $(html).find('#tablaProductos');
                $('#tablaProductos').html($nuevaTabla.html());
                filtrarProductos();
            });
        },
        error: function(xhr) {
            const msg = xhr.responseJSON?.message ?? (EDIT_ORDER ? 'Error al guardar los cambios.' : 'Error al registrar la venta.');
            mostrarToast('error', msg);
        },
        complete: function() {
            cobroListo    = false;
            montoRecibido = null;
            const hasItems = Object.keys(carrito).length > 0;
            $btn.prop('disabled', !hasItems)
                .html('<i class="fas fa-circle-check text-sm"></i> ' + BTN_LABEL + ' <kbd class="text-sky-200 text-xs font-normal bg-white/10 px-1.5 py-0.5 rounded ml-1">F5</kbd>');
        }
    });
});

// ── Precargar la venta que se está editando ───────────────────
if (EDIT_ORDER) {
    // El nombre guardado ya incluye la presentación (ej. "Paracetamol (Blíster x10)"), por eso sin etiqueta aparte
    EDIT_ORDER.items.forEach(function(i) {
        carrito[claveLinea(i.code, i.presentation_id)] = {
            code: i.code, name: i.name, label: '', presentationId: i.presentation_id || null,
            factor: parseFloat(i.factor) || 1, price: i.price, stock: i.stock, qty: i.qty,
            afectacion: String(i.afectacion), receta: parseInt(i.receta) || 0,
        };
    });
    renderCarrito();

    // Pago
    $('.btn-pago[data-value="' + EDIT_ORDER.payment_type + '"]').trigger('click');
    $('#nroOperacion').val(EDIT_ORDER.operation_number ?? '');
    // Un descuento con decimales (ej: 3.57%) solo puede venir de un descuento por monto
    if (EDIT_ORDER.discount_percent) {
        if (Number.isInteger(EDIT_ORDER.discount_percent)) $('#descuentoPct').val(EDIT_ORDER.discount_percent);
        else $('#descuentoMonto').val(EDIT_ORDER.discount_amount);
        actualizarTotales();
    }

    // Comprobante y cliente: se muestran pero no se pueden modificar
    $('.btn-comprobante[data-value="' + EDIT_ORDER.voucher_type + '"]').trigger('click');
    $('#cliente').val(EDIT_ORDER.customer_name ?? '');
    $('#documento').val(EDIT_ORDER.customer_document ?? '');
    if (EDIT_ORDER.document_type_id) seleccionarTipoDocPorId(EDIT_ORDER.document_type_id);

    $('#cliente, #documento, #nroOperacion').prop('disabled', true).addClass('bg-slate-50');
    $('.btn-comprobante, .btn-tipo-doc').css({ 'pointer-events': 'none', 'opacity': '.6' });

    // El número operación solo se puede cambiar si el pago no es efectivo
    $('#nroOperacion').prop('disabled', false).removeClass('bg-slate-50');

    // No tiene sentido "Nueva venta" mientras se edita
    $('#btnNuevaVenta').addClass('hidden');
}

// Al abrir el POS (venta nueva) el cliente predeterminado ya viene elegido
if (!EDIT_ORDER) aplicarClientePublico();

// ── Config de impresión de la sede (inyectada desde PHP) ─────
const PRINT_CONFIG   = @json($printConfig);
const PRINT_BASE_URL = '{{ url("employee/orders") }}';

// ── Modal de venta exitosa ────────────────────────────────────
function openSaleSuccessModal(orderId, voucherNumber) {
    $('#saleVoucherNumber').text(voucherNumber || '#' + String(orderId).padStart(5, '0'));

    // Botón imprimir
    $('#btnPrintSale').off('click').on('click', function() {
        printSale(orderId);
    });

    // Botón nueva venta
    $('#btnNewSale').off('click').on('click', function() {
        closeSaleSuccessModal();
    });

    $('#modalSaleSuccess').css('display', 'flex');

    // Auto-imprimir si la sede lo tiene configurado
    if (PRINT_CONFIG.auto_print) {
        printSale(orderId);
    }
}

function closeSaleSuccessModal() {
    $('#modalSaleSuccess').css('display', 'none');
}

function printSale(orderId, template = null) {
    const url = template
        ? `${PRINT_BASE_URL}/${orderId}/print/${template}`
        : `${PRINT_BASE_URL}/${orderId}/print`;
    window.open(url, `print_${orderId}`, 'width=620,height=780,scrollbars=yes,resizable=yes');
}

// Cerrar modal al hacer click en el fondo
$('#modalSaleSuccess').on('click', function(e) {
    if (e.target === this) closeSaleSuccessModal();
});

// ── Toast de notificación ─────────────────────────────────────
function mostrarToast(tipo, mensaje, subtitulo = '') {
    const color = tipo === 'success' ? 'bg-sky-600' : 'bg-red-600';
    const icon  = tipo === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const sub   = subtitulo
        ? `<p class="text-xs text-white/80 font-mono mt-0.5">${subtitulo}</p>`
        : '';
    const $toast = $(`
        <div class="fixed top-4 right-4 z-[100] flex items-start gap-3 px-4 py-3 rounded-xl text-white text-sm font-medium shadow-lg ${color} transition-all">
            <i class="fas ${icon} mt-0.5 shrink-0"></i>
            <div>
                <p>${mensaje}</p>
                ${sub}
            </div>
        </div>`);
    $('body').append($toast);
    setTimeout(() => $toast.fadeOut(300, function() { $(this).remove(); }), 4500);
}

// ── Autocomplete de clientes ──────────────────────────────────
let clienteTimer = null;

$('#cliente').on('input', function() {
    const term = $(this).val().trim();
    clearTimeout(clienteTimer);

    // Escribir otro nombre cuando estaba el cliente público: ya no es ese cliente
    if (term && term !== PUBLIC_CLIENT.name) quitarClientePublico();

    // Si limpiaron el campo, resetear selección
    if (!term) {
        quitarClientePublico();
        limpiarClienteSeleccionado(false);
        $('#clienteDropdown').addClass('hidden').empty();
        return;
    }

    $('#btnLimpiarCliente').removeClass('hidden');

    if (term.length < 2) {
        $('#clienteDropdown').addClass('hidden').empty();
        return;
    }

    clienteTimer = setTimeout(function() {
        $.getJSON('{{ route("employee.clients.search") }}', { q: term }, function(data) {
            const $dd = $('#clienteDropdown').empty();
            if (!data.length) {
                $dd.html('<p class="px-4 py-3 text-xs text-slate-400 text-center">Sin resultados</p>').removeClass('hidden');
                return;
            }
            data.forEach(function(c) {
                const docInfo = c.document_type && c.document_number
                    ? `<span class="text-slate-400">${c.document_type} ${c.document_number}</span>`
                    : '';
                $dd.append(`
                    <button type="button"
                            class="cliente-opcion w-full text-left px-4 py-2.5 hover:bg-sky-50 transition-colors border-b border-slate-100 last:border-0"
                            data-id="${c.id}"
                            data-name="${c.name}"
                            data-doc-type-id="${c.document_type_id ?? ''}"
                            data-doc-number="${c.document_number ?? ''}">
                        <p class="text-sm font-medium text-slate-800">${c.name}</p>
                        <p class="text-xs mt-0.5 flex items-center gap-1.5">
                            <span class="font-mono text-slate-300">${c.code}</span>
                            ${docInfo}
                            ${c.phone ? `<span class="text-slate-300">· ${c.phone}</span>` : ''}
                        </p>
                    </button>`);
            });
            $dd.removeClass('hidden');
        });
    }, 280);
});

/** Deja el cliente público como cliente de la venta (predeterminado del POS) */
function aplicarClientePublico() {
    aplicarCliente(PUBLIC_CLIENT.id, PUBLIC_CLIENT.name, PUBLIC_CLIENT.doc_type_id, PUBLIC_CLIENT.document);
}

/** Si el cajero cambia el cliente a mano, deja de ser el cliente público (y sus datos fijos) */
function quitarClientePublico() {
    if ($('#clienteId').val() != PUBLIC_CLIENT.id) return;

    limpiarClienteSeleccionado(false);

    if ($('#documento').val() === PUBLIC_CLIENT.document) {
        $('#documento').val('');
        $('#tipoDocumentoId').val('');
        $('.btn-tipo-doc').removeClass(DOC_ACTIVO.join(' ')).addClass(DOC_INACTIVO.join(' '));
    }
}

$(document).on('click', '.cliente-opcion', function() {
    aplicarCliente($(this).data('id'), $(this).data('name'), $(this).data('doc-type-id'), $(this).data('doc-number'));
});

function aplicarCliente(id, name, docTypeId, docNumber) {
    // Llenar campos
    $('#cliente').val(name);
    $('#clienteId').val(id);
    $('#btnLimpiarCliente').removeClass('hidden');
    $('#clienteSeleccionado').removeClass('hidden').find('span').text(name);
    $('#clienteDropdown').addClass('hidden').empty();

    // Auto-seleccionar tipo de documento
    if (docTypeId) {
        seleccionarTipoDocPorId(docTypeId);
    }

    // Auto-rellenar número de documento
    if (docNumber) {
        $('#documento').val(docNumber);
        // Limpiar estado anterior del DNI lookup
        $('#dniEstado').addClass('hidden').html('');
        $('#dniMensaje').addClass('hidden').text('').removeClass('text-emerald-600 text-slate-400 text-red-400');
    }
}

$('#btnLimpiarCliente').on('click', function() {
    limpiarClienteSeleccionado(true);
});

function limpiarClienteSeleccionado(limpiarCampos) {
    $('#clienteId').val('');
    $('#clienteSeleccionado').addClass('hidden').find('span').text('');
    $('#btnLimpiarCliente').addClass('hidden');
    $('#clienteDropdown').addClass('hidden').empty();
    if (limpiarCampos) {
        $('#cliente').val('').focus();
        $('#documento').val('');
        $('#tipoDocumentoId').val('');
        $('.btn-tipo-doc').removeClass(DOC_ACTIVO.join(' ')).addClass(DOC_INACTIVO.join(' '));
        $('#dniEstado').addClass('hidden').html('');
        $('#dniMensaje').addClass('hidden').text('').removeClass('text-emerald-600 text-slate-400 text-red-400');
    }
}

// Cerrar dropdown al hacer click fuera
$(document).on('click', function(e) {
    if (!$(e.target).closest('#cliente, #clienteDropdown').length) {
        $('#clienteDropdown').addClass('hidden');
    }
});

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
    $('#dniMensaje').addClass('hidden').text('').removeClass('text-emerald-600 text-slate-400 text-red-400');

    // Auto-seleccionar tipo de documento por longitud
    if (num.length === 8) {
        seleccionarTipoDocPorId(1); // DNI
    } else if (num.length === 11) {
        seleccionarTipoDocPorId(3); // RUC
    }

    if (num.length !== 8 && num.length !== 11) return;

    $('#dniEstado').removeClass('hidden').html('<i class="fas fa-spinner fa-spin text-slate-400"></i>');

    dniTimer = setTimeout(function() {
        $.ajax({
            url:    '{{ route("employee.consultar-documento") }}',
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
