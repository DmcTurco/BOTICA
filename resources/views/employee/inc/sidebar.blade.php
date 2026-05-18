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
        @php $emp = auth()->guard('employee')->user(); @endphp
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3 mb-2">Ventas</p>
            <ul class="space-y-0.5">
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_ABRIR_CAJA))
                <li>
                    <a href="{{ route('employee.cash-register.show-open') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-register.show-open') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-lock-open w-4 text-center shrink-0"></i>
                        Apertura de caja
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_EDITAR_APERTURA))
                <li>
                    <a href="{{ route('employee.cash-register.edit') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.cash-register.edit') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-pen-to-square w-4 text-center shrink-0"></i>
                        Editar apertura
                    </a>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_CERRAR_CAJA))
                <li>
                    <button onclick="abrirModalCierre()"
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                                    text-slate-400 hover:bg-sky-600 hover:text-white text-left">
                        <i class="fas fa-lock w-4 text-center shrink-0"></i>
                        Cierre de caja
                    </button>
                </li>
                @endif
                @if($emp->hasPrivilege(\App\Models\Employee::PRIV_VER_VENTAS))
                <li>
                    <a href="{{ route('employee.orders.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                              {{ request()->routeIs('employee.orders.index') ? 'bg-sky-600 text-white' : 'text-slate-400 hover:bg-sky-600 hover:text-white' }}">
                        <i class="fas fa-cash-register w-4 text-center shrink-0"></i>
                        Ventas
                    </a>
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
            </ul>
        </div>

        {{-- Inventario — cada ítem se muestra según su privilegio específico --}}
        @php
            $hasInventory = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_INVENTARIO);
            $hasPurchases = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_COMPRAS);
            $hasKardex    = $emp->hasPrivilege(\App\Models\Employee::PRIV_VER_KARDEX);
        @endphp
        @if($hasInventory || $hasPurchases || $hasKardex)
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
            </ul>
        </div>
        @endif {{-- inventario --}}

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
