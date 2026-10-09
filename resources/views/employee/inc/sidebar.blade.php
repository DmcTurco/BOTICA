<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white border-r border-slate-200 z-30 flex flex-col -translate-x-full lg:translate-x-0 transition-transform duration-300">

    <div class="flex items-center gap-3 h-16 px-5 border-b border-slate-200 shrink-0">
        <div class="w-8 h-8 bg-sky-600 rounded-lg flex items-center justify-center shrink-0">
            <i class="fas fa-plus text-white text-sm"></i>
        </div>
        <div>
            <p class="font-bold text-slate-800 text-sm leading-none">BOTICA</p>
            <p class="text-xs text-slate-400 mt-0.5">Panel Empleado</p>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-5">

        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Principal</p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('employee.home') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.home') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-gauge-high w-4 text-center shrink-0"></i>
                        Dashboard
                    </a>
                </li>
            </ul>
        </div>

        {{-- Ventas — ítems controlados por privilegios --}}
        @php
            $emp = auth()->guard('employee')->user();
            // Sin caja abierta, las opciones de ventas se bloquean
            $cajaAbierta = \App\Models\CashRegister::currentOpen($emp->id)->exists();

            // Cajas históricas abiertas del empleado (para poder volver a ellas desde el menú)
            $cajasHistoricas = $emp->hasPrivilege(\App\Models\Employee::PRIV_ABRIR_CAJA)
                ? \App\Models\CashRegister::where('employee_id', $emp->id)
                    ->where('status', 1)
                    ->where('approval_status', \App\Models\CashRegister::APPROVAL_PENDING)
                    ->orderBy('register_date')
                    ->get(['id', 'register_date'])
                : collect();
            $historicaActiva = request()->route('cashRegister')?->id;
        @endphp
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Ventas</p>
            <ul class="space-y-0.5">
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_ABRIR_CAJA))
                <li>
                    <a href="{{ route('employee.cash-register.show-open') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-register.show-open') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-lock-open w-4 text-center shrink-0"></i>
                        {{ $cajaAbierta ? 'Abrir caja pasada' : 'Apertura de caja' }}
                    </a>
                </li>
                @foreach($cajasHistoricas as $historica)
                <li>
                    <a href="{{ route('employee.cash-register.historical', $historica) }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ $historicaActiva === $historica->id ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-clock-rotate-left w-4 text-center shrink-0 {{ $historicaActiva === $historica->id ? '' : 'text-amber-500' }}"></i>
                        Caja del {{ $historica->register_date->format('d/m/Y') }}
                    </a>
                </li>
                @endforeach
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_EDITAR_APERTURA))
                <li>
                    @if($cajaAbierta)
                    <a href="{{ route('employee.cash-register.edit') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-register.edit') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-pen-to-square w-4 text-center shrink-0"></i>
                        Editar apertura
                    </a>
                    @else
                    <span title="Abre tu caja primero"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 cursor-not-allowed select-none">
                        <i class="fas fa-pen-to-square w-4 text-center shrink-0"></i>
                        Editar apertura
                        <i class="fas fa-lock ml-auto text-[10px]"></i>
                    </span>
                    @endif
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_CERRAR_CAJA))
                <li>
                    @if($cajaAbierta)
                    <a href="{{ route('employee.cash-register.show-close') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-register.show-close') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-lock w-4 text-center shrink-0"></i>
                        Cierre de caja
                    </a>
                    @else
                    <span title="Abre tu caja primero"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 cursor-not-allowed select-none">
                        <i class="fas fa-lock w-4 text-center shrink-0"></i>
                        Cierre de caja
                        <i class="fas fa-lock ml-auto text-[10px]"></i>
                    </span>
                    @endif
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_VER_VENTAS))
                <li>
                    @if($cajaAbierta)
                    <a href="{{ route('employee.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.orders.index') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-cash-register w-4 text-center shrink-0"></i>
                        Ventas
                    </a>
                    @else
                    <span title="Abre tu caja primero"
                          class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 cursor-not-allowed select-none">
                        <i class="fas fa-cash-register w-4 text-center shrink-0"></i>
                        Ventas
                        <i class="fas fa-lock ml-auto text-[10px]"></i>
                    </span>
                    @endif
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_VER_HISTORIAL))
                <li>
                    <a href="{{ route('employee.orders.historial') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.orders.historial') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-receipt w-4 text-center shrink-0"></i>
                        Historial
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_GESTIONAR_CLIENTES))
                <li>
                    <a href="{{ route('employee.clients.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.clients.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-users w-4 text-center shrink-0"></i>
                        Clientes
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_GASTOS_DIA) || $emp->hasPrivilege(\App\Models\Employee::PRIV_OTROS_INGRESOS))
                <li>
                    <a href="{{ route('employee.cash-movements.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-movements.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-money-bill-transfer w-4 text-center shrink-0"></i>
                        Gastos e ingresos
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_VER_CIERRES_CAJA))
                <li>
                    <a href="{{ route('employee.cash-closures.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-closures.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-clipboard-check w-4 text-center shrink-0"></i>
                        Cierres de caja
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_VENTA_PERDIDA_SIN_STOCK))
                <li>
                    <a href="{{ route('employee.lost-sales.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.lost-sales.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-circle-minus w-4 text-center shrink-0"></i>
                        Ventas perdidas
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_VER_RECETAS))
                <li>
                    <a href="{{ route('employee.prescriptions.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.prescriptions.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-file-prescription w-4 text-center shrink-0"></i>
                        Registro de recetas
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_CUENTAS_COBRAR))
                <li>
                    <a href="{{ route('employee.receivables.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.receivables.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-hand-holding-dollar w-4 text-center shrink-0"></i>
                        Cuentas por cobrar
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_TIPO_CAMBIO))
                <li>
                    <a href="{{ route('employee.exchange-rates.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.exchange-rates.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-dollar-sign w-4 text-center shrink-0"></i>
                        Tipo de cambio
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_ADMIN_CORRELATIVOS))
                <li>
                    <a href="{{ route('employee.series.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.series.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-list-ol w-4 text-center shrink-0"></i>
                        Correlativos
                    </a>
                </li>
                @endif
            </ul>
        </div>

        {{-- Inventario — cada ítem se muestra según su privilegio específico --}}
        @php
            $hasInventory = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_INVENTARIO);
            $hasPurchases = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_COMPRAS);
            $hasKardex    = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_KARDEX);
            $hasAdjust    = $emp->hasPrivilege(\App\Models\Employee::PRIV_AJUSTE_INVENTARIO);
            $hasReplenish = $emp->hasPrivilege(\App\Models\Employee::PRIV_PRODUCTOS_REPOSICION);
            $hasTransfer  = $emp->hasPrivilege(\App\Models\Employee::PRIV_TRASPASO_SALIDA);
            $hasSuppliers = $emp->hasPrivilege(\App\Models\Employee::PRIV_PROVEEDORES);
            $hasPayables  = $emp->hasPrivilege(\App\Models\Employee::PRIV_CUENTAS_PAGAR);
            $hasControlled = $emp->hasPrivilege(\App\Models\Employee::PRIV_LIBRO_CONTROLADOS);
        @endphp
        @if($hasInventory || $hasPurchases || $hasKardex || $hasAdjust || $hasReplenish || $hasTransfer || $hasSuppliers || $hasPayables || $hasControlled)
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Inventario</p>
            <ul class="space-y-0.5">
                @if($hasInventory)
                <li>
                    <a href="{{ route('employee.products.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.products.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-pills w-4 text-center shrink-0"></i>
                        Productos
                    </a>
                </li>
                <li>
                    <a href="{{ route('employee.categories.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.categories.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-tags w-4 text-center shrink-0"></i>
                        Categorías
                    </a>
                </li>
                <li>
                    <a href="{{ route('employee.laboratories.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.laboratories.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-flask w-4 text-center shrink-0"></i>
                        Laboratorios
                    </a>
                </li>
                <li>
                    <a href="{{ route('employee.units.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.units.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-ruler-combined w-4 text-center shrink-0"></i>
                        Unidades
                    </a>
                </li>
                @endif
                @if($hasPurchases)
                <li>
                    <a href="{{ route('employee.purchases.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.purchases.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-truck-ramp-box w-4 text-center shrink-0"></i>
                        Compras
                    </a>
                </li>
                @endif
                @if($hasSuppliers)
                <li>
                    <a href="{{ route('employee.suppliers.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.suppliers.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-truck w-4 text-center shrink-0"></i>
                        Proveedores
                    </a>
                </li>
                @endif
                @if($hasPurchases)
                <li>
                    <a href="{{ route('employee.purchase-orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.purchase-orders.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-file-circle-check w-4 text-center shrink-0"></i>
                        Órdenes de compra
                    </a>
                </li>
                @endif
                @if($hasPayables)
                <li>
                    <a href="{{ route('employee.payables.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.payables.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-file-invoice-dollar w-4 text-center shrink-0"></i>
                        Cuentas por pagar
                    </a>
                </li>
                @endif
                @if($hasControlled)
                <li>
                    <a href="{{ route('employee.controlled-book.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.controlled-book.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-book-medical w-4 text-center shrink-0"></i>
                        Libro de controlados
                    </a>
                </li>
                @endif
                @if($hasKardex)
                <li>
                    <a href="{{ route('employee.kardex.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.kardex.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-chart-gantt w-4 text-center shrink-0"></i>
                        Kardex
                    </a>
                </li>
                @endif
                @if($hasAdjust)
                <li>
                    <a href="{{ route('employee.adjustments.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.adjustments.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-scale-balanced w-4 text-center shrink-0"></i>
                        Ajuste de inventario
                    </a>
                </li>
                @endif
                @if($hasReplenish)
                <li>
                    <a href="{{ route('employee.replenishment.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.replenishment.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-triangle-exclamation w-4 text-center shrink-0"></i>
                        Reposición
                    </a>
                </li>
                @endif
                @if($hasTransfer)
                <li>
                    <a href="{{ route('employee.transfers.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.transfers.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-right-left w-4 text-center shrink-0"></i>
                        Traspasos
                    </a>
                </li>
                @endif
            </ul>
        </div>
        @endif {{-- inventario --}}

        {{-- Laboratorio — fórmulas magistrales y preparaciones --}}
        @php
            $hasFormulas    = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_FORMULAS);
            $hasProductions = $emp->hasPrivilege(\App\Models\Employee::PRIV_PRODUCIR_FORMULAS);
        @endphp
        @if($hasFormulas || $hasProductions)
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Laboratorio</p>
            <ul class="space-y-0.5">
                @if($hasFormulas)
                <li>
                    <a href="{{ route('employee.formulas.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.formulas.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-vial w-4 text-center shrink-0"></i>
                        Fórmulas
                    </a>
                </li>
                @endif
                @if($hasProductions)
                <li>
                    <a href="{{ route('employee.productions.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.productions.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-flask-vial w-4 text-center shrink-0"></i>
                        Preparaciones
                    </a>
                </li>
                @endif
            </ul>
        </div>
        @endif {{-- laboratorio --}}

        {{-- Reportes y herramientas — cada enlace aparece según su privilegio --}}
        @php
            $E = \App\Models\Employee::class;
            $linkGroups = [
                'Reportes' => [
                    ['employee.reports.monthly',                $E::PRIV_RECORD_MENSUAL_VENTAS,    'fa-calendar-days',  'Récord mensual'],
                    ['employee.reports.general',                $E::PRIV_RECORD_GENERAL_VENTAS,    'fa-chart-line',     'Récord general'],
                    ['employee.reports.documents',              $E::PRIV_VER_DOCS_MES,             'fa-file-lines',     'Documentos del mes'],
                    ['employee.reports.range',                  $E::PRIV_REPORTES_POR_FECHA,       'fa-calendar-week',  'Ventas por fechas'],
                    ['employee.reports.best-sellers',           $E::PRIV_REPORTE_MAS_VENDIDOS,     'fa-ranking-star',   'Más vendidos'],
                    ['employee.reports.no-rotation',            $E::PRIV_PRODUCTOS_SIN_ROTACION,   'fa-hourglass-half', 'Sin rotación'],
                    ['employee.reports.profit',                 $E::PRIV_REPORTE_UTILIDAD,         'fa-sack-dollar',    'Utilidad'],
                    ['employee.reports.commissions',            $E::PRIV_REPORTE_COMISIONES,       'fa-percent',        'Comisiones'],
                    ['employee.audit.index',                    $E::PRIV_VER_BITACORA,             'fa-clipboard-list', 'Bitácora'],
                    ['employee.accounting.sales',               $E::PRIV_REGISTROS_CONTABLES,      'fa-book',           'Registro de ventas'],
                    ['employee.accounting.purchases',           $E::PRIV_REGISTROS_CONTABLES,      'fa-book-open',      'Registro de compras'],
                    ['employee.reports.expiring',               $E::PRIV_REPORTE_VENCIMIENTOS,     'fa-calendar-xmark', 'Lotes y vencimientos'],
                    ['employee.reports.inventory.total',        $E::PRIV_INVENTARIO_TOTAL,         'fa-boxes-stacked',  'Inventario total'],
                    ['employee.reports.inventory.stock',        $E::PRIV_INVENTARIO_TOTAL_STOCK,   'fa-box-open',       'Inventario con stock'],
                    ['employee.reports.inventory.laboratory',   $E::PRIV_INV_LAB_TOTAL,            'fa-flask',          'Inventario por laboratorio'],
                    ['employee.reports.inventory.laboratory-stock', $E::PRIV_INV_LAB_STOCK,        'fa-flask-vial',     'Inv. por laboratorio (stock)'],
                    ['employee.reports.inventory.valued',       $E::PRIV_REPORTE_INV_VALORIZADO,   'fa-coins',          'Inventario valorizado'],
                ],
                'Herramientas' => [
                    ['employee.prices.index',        $E::PRIV_ACTUALIZACION_PRECIO, 'fa-tags',            'Actualizar precios'],
                    ['employee.commissions.settings', $E::PRIV_ADMIN_COMISION,      'fa-hand-holding-dollar', 'Administrar comisiones'],
                    ['employee.local.edit',          $E::PRIV_EDITAR_DATOS_LOCAL,   'fa-store',           'Datos del local'],
                ],
            ];
        @endphp
        @foreach($linkGroups as $groupTitle => $links)
            @php $visible = collect($links)->filter(fn ($l) => $emp->hasPrivilege($l[1])); @endphp
            @if($visible->isNotEmpty())
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">{{ $groupTitle }}</p>
                <ul class="space-y-0.5">
                    @foreach($visible as [$linkRoute, $linkPriv, $linkIcon, $linkLabel])
                    <li>
                        <a href="{{ route($linkRoute) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                                  {{ request()->routeIs($linkRoute) ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                            <i class="fas {{ $linkIcon }} w-4 text-center shrink-0"></i>
                            {{ $linkLabel }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
        @endforeach

        {{-- Administración — solo visible para branch_admin (role_id = 2) --}}
        @if(auth()->guard('employee')->user()?->isBranchAdmin())
        @php
            $pendingCount = \App\Models\CashRegister::where('company_id', $emp->company_id)
                ->where('branch_id', $emp->branch_id)
                ->where('approval_status', \App\Models\CashRegister::APPROVAL_PENDING)
                ->where('status', 0)
                ->count();
        @endphp
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Administración</p>
            <ul class="space-y-0.5">
                <li>
                    <a href="{{ route('employee.approvals.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.approvals.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-vault w-4 text-center shrink-0"></i>
                        Aprobaciones
                        @if($pendingCount > 0)
                        <span class="ml-auto inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-black
                                     {{ request()->routeIs('employee.approvals.*') ? 'bg-white text-sky-600' : 'bg-amber-500 text-white' }}">
                            {{ $pendingCount > 9 ? '9+' : $pendingCount }}
                        </span>
                        @endif
                    </a>
                </li>
                <li>
                    <a href="{{ route('employee.employees.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.employees.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-user-gear w-4 text-center shrink-0"></i>
                        Empleados
                    </a>
                </li>
                <li>
                    <a href="{{ route('employee.settings.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.settings.*') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-sliders w-4 text-center shrink-0"></i>
                        Configuración
                    </a>
                </li>
            </ul>
        </div>
        @endif

    </nav>

    <div class="px-3 py-3 border-t border-slate-200">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 transition-colors">
                <i class="fas fa-right-from-bracket w-4 text-center text-sm"></i>
                Cerrar sesión
            </button>
        </form>
    </div>
</aside>
