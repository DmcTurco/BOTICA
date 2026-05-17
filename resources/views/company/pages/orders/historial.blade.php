@extends('company/layouts/base', ['elementActive' => 'orders-historial'])

@section('title', 'Historial de Ventas')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Historial de Ventas</h1>
            <p class="text-sm text-slate-500 mt-0.5">Consulta y filtra todas las ventas registradas</p>
        </div>
        <a href="{{ route('company.orders.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-cash-register text-xs"></i> Punto de Venta
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('company.orders.historial') }}" method="GET">
            <div class="flex flex-col gap-3">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        <input type="text" name="buscar" value="{{ request('buscar') }}"
                               class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Cliente, documento o N° venta...">
                    </div>
                    <select name="tipo_comprobante"
                            class="sm:w-44 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">Comprobante</option>
                        <option value="1" {{ request('tipo_comprobante') == '1' ? 'selected' : '' }}>Boleta</option>
                        <option value="2" {{ request('tipo_comprobante') == '2' ? 'selected' : '' }}>Factura</option>
                        <option value="3" {{ request('tipo_comprobante') == '3' ? 'selected' : '' }}>Nota de Venta</option>
                    </select>
                    <select name="tipo_pago"
                            class="sm:w-40 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        <option value="">Tipo de pago</option>
                        <option value="1" {{ request('tipo_pago') == '1' ? 'selected' : '' }}>Efectivo</option>
                        <option value="2" {{ request('tipo_pago') == '2' ? 'selected' : '' }}>Tarjeta</option>
                        <option value="3" {{ request('tipo_pago') == '3' ? 'selected' : '' }}>Transferencia</option>
                        <option value="4" {{ request('tipo_pago') == '4' ? 'selected' : '' }}>Yape</option>
                    </select>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-500 whitespace-nowrap">Desde</label>
                        <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                               class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-500 whitespace-nowrap">Hasta</label>
                        <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                               class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                    <div class="flex gap-2 sm:ml-auto">
                        <button type="submit"
                                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <i class="fas fa-search text-xs"></i> Filtrar
                        </button>
                        @if(request()->hasAny(['buscar','tipo_comprobante','tipo_pago','fecha_desde','fecha_hasta']))
                        <a href="{{ route('company.orders.historial') }}"
                           class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                            <i class="fas fa-xmark text-xs"></i> Limpiar
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Cabecera del card --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Ventas registradas
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $orders->total() }} registros</span>
            </p>
        </div>

        {{-- Área scrollable --}}
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">N°</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Cliente</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Comprobante</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Pago</th>
                        <th class="text-right px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Total</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                    @php
                        $comprobantes = [1 => 'Boleta', 2 => 'Factura', 3 => 'Nota de Venta'];
                        $pagos        = [1 => 'Efectivo', 2 => 'Tarjeta', 3 => 'Transferencia', 4 => 'Yape'];
                        $pagoIcons    = [1 => 'fa-money-bill-wave', 2 => 'fa-credit-card', 3 => 'fa-building-columns', 4 => 'fa-mobile-screen'];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors cursor-pointer fila-venta"
                        data-id="{{ $order->id }}">
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">
                            <div>{{ $order->created_at->format('d/m/Y') }}</div>
                            <div class="text-slate-400">{{ $order->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 hidden md:table-cell">
                            <div class="font-medium text-slate-700 text-xs">{{ $order->customer_name ?: '—' }}</div>
                            <div class="text-slate-400 text-xs">{{ $order->customer_document ?: '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-center hidden lg:table-cell">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-sky-50 text-sky-700">
                                {{ $comprobantes[$order->voucher_type] ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center hidden lg:table-cell">
                            <span class="inline-flex items-center gap-1 text-xs text-slate-600">
                                <i class="fas {{ $pagoIcons[$order->payment_type] ?? 'fa-money-bill' }} text-slate-400 text-xs"></i>
                                {{ $pagos[$order->payment_type] ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800">
                            S/ {{ number_format($order->total, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($order->status)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Activa</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Anulada</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-emerald-50 hover:text-emerald-600 transition-colors btn-detalle"
                                    data-id="{{ $order->id }}" title="Ver detalle">
                                <i class="fas fa-eye text-xs"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-receipt text-3xl"></i>
                                <p class="text-sm">No hay ventas que coincidan con los filtros</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginador --}}
        @if($orders->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">
            {{ $orders->links() }}
        </div>
        @endif

    </div>
</div>

{{-- Modal detalle de venta --}}
<div id="modalDetalle" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl flex flex-col max-h-[90vh]">

        {{-- Header modal --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-receipt text-emerald-600 text-sm"></i>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-slate-800" id="modal-titulo">Detalle de Venta</h3>
                    <p class="text-xs text-slate-400" id="modal-fecha"></p>
                </div>
            </div>
            <button onclick="cerrarModalDetalle()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-colors">
                <i class="fas fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- Body modal --}}
        <div class="flex-1 overflow-y-auto p-6 space-y-5">

            {{-- Info principal --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-slate-50 rounded-lg px-3 py-2.5">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Comprobante</p>
                    <p class="text-sm font-semibold text-slate-700 mt-0.5" id="modal-comprobante"></p>
                </div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">N° Comprobante</p>
                    <p class="text-sm font-semibold text-slate-700 mt-0.5" id="modal-nrocomprobante"></p>
                </div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">Tipo de Pago</p>
                    <p class="text-sm font-semibold text-slate-700 mt-0.5" id="modal-pago"></p>
                </div>
                <div class="bg-slate-50 rounded-lg px-3 py-2.5">
                    <p class="text-[10px] text-slate-400 uppercase tracking-wider">N° Operación</p>
                    <p class="text-sm font-semibold text-slate-700 mt-0.5" id="modal-operacion"></p>
                </div>
            </div>

            {{-- Cliente --}}
            <div class="border border-slate-200 rounded-lg px-4 py-3">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Cliente</p>
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-slate-100 rounded-full flex items-center justify-center shrink-0">
                        <i class="fas fa-user text-slate-400 text-xs"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-800" id="modal-cliente-nombre"></p>
                        <p class="text-xs text-slate-400" id="modal-cliente-documento"></p>
                    </div>
                </div>
            </div>

            {{-- Tabla de productos --}}
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Productos</p>
                <div class="rounded-lg border border-slate-200 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">P. Unit</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cant.</th>
                                <th class="text-right px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase tracking-wider">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modal-items" class="divide-y divide-slate-100">
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Totales --}}
            <div class="flex justify-end">
                <div class="w-56 space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-500">
                        <span>Subtotal</span>
                        <span id="modal-subtotal" class="font-medium text-slate-700"></span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>IGV (18%)</span>
                        <span id="modal-igv" class="font-medium text-slate-700"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 mt-2">
                        <span class="font-semibold text-slate-800">Total</span>
                        <span id="modal-total" class="font-bold text-emerald-700 text-base"></span>
                    </div>
                </div>
            </div>

        </div>

        {{-- Footer modal --}}
        <div class="px-6 py-4 border-t border-slate-200 shrink-0 flex justify-end">
            <button onclick="cerrarModalDetalle()"
                    class="px-5 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

{{-- Spinner de carga --}}
<div id="modalLoader" class="fixed inset-0 bg-black/30 z-50 items-center justify-center" style="display:none!important">
    <div class="bg-white rounded-xl p-6 flex items-center gap-3 shadow-xl">
        <i class="fas fa-spinner fa-spin text-emerald-600"></i>
        <span class="text-sm text-slate-700">Cargando...</span>
    </div>
</div>

@endsection

@section('scripts')
<script>
const COMPROBANTES = { 1: 'Boleta', 2: 'Factura', 3: 'Nota de Venta' };
const PAGOS        = { 1: 'Efectivo', 2: 'Tarjeta', 3: 'Transferencia', 4: 'Yape' };

function cerrarModalDetalle() {
    document.getElementById('modalDetalle').style.setProperty('display', 'none', 'important');
}

function mostrarLoader(show) {
    document.getElementById('modalLoader').style.setProperty('display', show ? 'flex' : 'none', 'important');
}

function fmt(num) {
    return 'S/ ' + parseFloat(num).toFixed(2);
}

function abrirDetalle(id) {
    mostrarLoader(true);

    fetch(`{{ url('company/orders') }}/${id}/detalle`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(v => {
        mostrarLoader(false);

        document.getElementById('modal-titulo').textContent    = `Venta #${String(v.id).padStart(5, '0')}`;
        document.getElementById('modal-fecha').textContent     = v.created_at;
        document.getElementById('modal-comprobante').textContent = COMPROBANTES[v.voucher_type] ?? '—';
        document.getElementById('modal-nrocomprobante').textContent = v.voucher_number ?? '—';
        document.getElementById('modal-pago').textContent      = PAGOS[v.payment_type] ?? '—';
        document.getElementById('modal-operacion').textContent = v.operation_number ?? '—';
        document.getElementById('modal-cliente-nombre').textContent   = v.customer_name ?? 'Sin nombre';
        document.getElementById('modal-cliente-documento').textContent = v.customer_document ?? 'Sin documento';
        document.getElementById('modal-subtotal').textContent  = fmt(v.subtotal);
        document.getElementById('modal-igv').textContent       = fmt(v.igv);
        document.getElementById('modal-total').textContent     = fmt(v.total);

        const tbody = document.getElementById('modal-items');
        tbody.innerHTML = '';
        (v.items ?? []).forEach(d => {
            tbody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-2.5">
                        <div class="font-medium text-slate-700 text-xs">${d.product_name}</div>
                        <div class="text-slate-400 text-xs">${d.product_code}</div>
                    </td>
                    <td class="px-4 py-2.5 text-right text-xs text-slate-600">${fmt(d.unit_price)}</td>
                    <td class="px-4 py-2.5 text-right text-xs text-slate-600">${parseFloat(d.quantity)}</td>
                    <td class="px-4 py-2.5 text-right text-xs font-semibold text-slate-700">${fmt(d.subtotal)}</td>
                </tr>
            `);
        });

        document.getElementById('modalDetalle').style.setProperty('display', 'flex', 'important');
    })
    .catch(() => {
        mostrarLoader(false);
        alert('Error al cargar el detalle de la venta.');
    });
}

$(document).ready(function () {
    $(document).on('click', '.btn-detalle, .fila-venta td:not(:last-child)', function () {
        const id = $(this).closest('tr').data('id');
        if (id) abrirDetalle(id);
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape') cerrarModalDetalle();
    });

    document.getElementById('modalDetalle').addEventListener('click', function (e) {
        if (e.target === this) cerrarModalDetalle();
    });
});
</script>
@endsection
