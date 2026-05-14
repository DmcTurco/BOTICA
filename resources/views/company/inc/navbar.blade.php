<header class="sticky top-0 bg-white border-b border-slate-200 z-10 h-16 flex items-center px-4 md:px-6 gap-4 shrink-0">

    <button onclick="toggleSidebar()" class="lg:hidden text-slate-500 hover:text-slate-700 p-1 rounded-md transition-colors">
        <i class="fas fa-bars text-lg"></i>
    </button>

    @php
        $pageTitle = match(true) {
            request()->routeIs('company.home')             => 'Dashboard',
            request()->routeIs('company.orders.historial')  => 'Historial de Ventas',
            request()->routeIs('company.orders.*')          => 'Ventas',
            request()->routeIs('company.products.*')       => 'Productos',
            request()->routeIs('company.categories.*')     => 'Categorías',
            request()->routeIs('company.laboratories.*')   => 'Laboratorios',
            default                                        => 'Dashboard',
        };
    @endphp
    <div class="flex-1">
        <p class="text-sm font-semibold text-slate-800">{{ $pageTitle }}</p>
    </div>

    <div class="flex items-center gap-2">

        {{-- Badge de estado de caja --}}
        <div id="cajaBadge" class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium">
            <span class="w-2 h-2 rounded-full" id="cajaIndicador"></span>
            <span id="cajaTexto">Cargando...</span>
        </div>

        {{-- Botón cerrar caja (solo visible si hay caja abierta) --}}
        <button id="btnCerrarCaja"
                onclick="abrirModalCierre()"
                class="hidden items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-50 border border-red-200
                       text-red-600 text-xs font-medium hover:bg-red-100 transition-colors">
            <i class="fas fa-lock text-xs"></i>
            Cerrar Caja
        </button>

        <div class="relative hidden sm:block">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            <input type="text" placeholder="Buscar..."
                   class="pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent w-44 transition">
        </div>

        <button class="w-9 h-9 flex items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 transition-colors" title="Notificaciones">
            <i class="fas fa-bell text-sm"></i>
        </button>

        <div class="flex items-center gap-2 pl-3 border-l border-slate-200">
            <div class="w-8 h-8 bg-emerald-600 rounded-full flex items-center justify-center shrink-0">
                <span class="text-white text-xs font-semibold">
                    {{ strtoupper(substr(auth()->guard('company')->user()?->name ?? 'U', 0, 1)) }}
                </span>
            </div>
            <p class="hidden sm:block text-xs font-medium text-slate-700 max-w-24 truncate">
                {{ auth()->guard('company')->user()?->name ?? 'Usuario' }}
            </p>
        </div>
    </div>

</header>

{{-- Modal cierre de caja --}}
<div id="modalCierreCaja"
     class="fixed inset-0 bg-black/60 z-50 items-center justify-center p-4"
     style="display:none!important">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">

        <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
            <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                <i class="fas fa-lock text-red-500"></i>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-800">Cierre de Caja</h3>
                <p class="text-xs text-slate-400 mt-0.5">Cuenta el efectivo y registra el cierre</p>
            </div>
        </div>

        <form action="{{ route('company.cash-register.close') }}" method="POST" class="p-6 space-y-4">
            @csrf

            {{-- Resumen de la caja actual --}}
            <div class="bg-slate-50 rounded-xl p-4 space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Apertura</span>
                    <span id="cierre-apertura" class="font-medium">—</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Total en órdenes</span>
                    <span id="cierre-total" class="font-medium text-emerald-700">—</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Abierta desde</span>
                    <span id="cierre-hora" class="font-medium">—</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Monto contado (S/)</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-sm">S/</span>
                    <input type="number" name="closing_amount" step="0.01" min="0"
                           class="w-full pl-9 pr-4 py-3 text-lg font-semibold border border-slate-300 rounded-xl
                                  focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent"
                           placeholder="0.00">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Observaciones (opcional)</label>
                <textarea name="notes" rows="2"
                          class="w-full px-4 py-2.5 text-sm border border-slate-300 rounded-xl resize-none
                                 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent"
                          placeholder="Ej: faltante por vuelto, etc."></textarea>
            </div>

            <div class="flex gap-3 pt-1">
                <button type="button" onclick="cerrarModalCierre()"
                        class="flex-1 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium rounded-xl transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="flex-1 py-2.5 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-lock text-xs"></i> Cerrar Caja
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Consulta el estado de la caja al cargar y actualiza el badge del navbar
async function actualizarEstadoCaja() {
    try {
        const res  = await fetch('{{ route("company.cash-register.status") }}');
        const data = await res.json();
        const badge   = document.getElementById('cajaBadge');
        const indicador = document.getElementById('cajaIndicador');
        const texto   = document.getElementById('cajaTexto');
        const btnCerrar = document.getElementById('btnCerrarCaja');

        if (data.open) {
            badge.className     = 'hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium border-emerald-200 bg-emerald-50 text-emerald-700';
            indicador.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
            texto.textContent   = 'Caja abierta · S/ ' + parseFloat(data.total_orders).toFixed(2);
            btnCerrar.style.display = 'flex';

            // Guardar datos para el modal de cierre
            document.getElementById('cierre-apertura').textContent = 'S/ ' + parseFloat(data.opening_amount).toFixed(2);
            document.getElementById('cierre-total').textContent    = 'S/ ' + parseFloat(data.total_orders).toFixed(2);
            document.getElementById('cierre-hora').textContent     = data.opened_at;
        } else {
            badge.className     = 'hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg border text-xs font-medium border-slate-200 bg-slate-50 text-slate-500';
            indicador.className = 'w-2 h-2 rounded-full bg-slate-300';
            texto.textContent   = 'Sin caja abierta';
            btnCerrar.style.display = 'none';
        }
    } catch (e) {
        console.warn('No se pudo obtener estado de caja', e);
    }
}

function abrirModalCierre() {
    document.getElementById('modalCierreCaja').style.setProperty('display', 'flex', 'important');
}

function cerrarModalCierre() {
    document.getElementById('modalCierreCaja').style.setProperty('display', 'none', 'important');
}

// Actualizar al cargar y cada 60 segundos
document.addEventListener('DOMContentLoaded', actualizarEstadoCaja);
setInterval(actualizarEstadoCaja, 60000);
</script>
