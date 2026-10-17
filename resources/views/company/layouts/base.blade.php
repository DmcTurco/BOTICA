<!DOCTYPE html>
<html lang="es">
@include('company/inc/head')
<body class="bg-slate-50 antialiased h-screen app-shell overflow-hidden">
    @include('partials/sidebar-collapse')

    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

    @include('company/inc/sidebar')

    {{-- app-shell / app-main: alto visible real y alto mínimo de página (ver resources/css/app.css) --}}
    <div class="app-content lg:ml-64 flex flex-col h-screen app-shell overflow-hidden">
        @include('company/inc/navbar')
        @include('partials/subscription-banner', ['company' => auth()->guard('company')->user(), 'showPaymentInfo' => true])

        {{-- ── Mensajes flash globales ─────────────────────────────── --}}
        {{-- Éxito, errores y validación se muestran en el modal de alerta --}}
        @if(session('info') || session('warning'))
        <div id="flashContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-2 w-80">

            @if(session('info'))
            <div class="flash-msg flex items-start gap-3 px-4 py-3 bg-white border border-sky-200 rounded-xl shadow-lg">
                <div class="w-7 h-7 bg-sky-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fas fa-info text-sky-500 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800">{{ session('info') }}</p>
                </div>
                <button onclick="this.closest('.flash-msg').remove()" class="text-slate-300 hover:text-slate-500 shrink-0 mt-0.5">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>
            @endif

            @if(session('warning'))
            <div class="flash-msg flex items-start gap-3 px-4 py-3 bg-white border border-amber-200 rounded-xl shadow-lg">
                <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center shrink-0 mt-0.5">
                    <i class="fas fa-triangle-exclamation text-amber-500 text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-800">{{ session('warning') }}</p>
                </div>
                <button onclick="this.closest('.flash-msg').remove()" class="text-slate-300 hover:text-slate-500 shrink-0 mt-0.5">
                    <i class="fas fa-xmark text-xs"></i>
                </button>
            </div>
            @endif

        </div>

        <script>
            // Auto-ocultar mensajes flash después de 5 segundos
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.flash-msg').forEach(function (msg) {
                    setTimeout(function () {
                        msg.style.transition = 'opacity 0.4s ease';
                        msg.style.opacity = '0';
                        setTimeout(function () { msg.remove(); }, 400);
                    }, 5000);
                });
            });
        </script>
        @endif
        {{-- ─────────────────────────────────────────────────────────── --}}

        <main class="app-main flex-1 min-h-0 flex flex-col @yield('main-padding', 'p-4 md:p-6')">
            @yield('content-area')
        </main>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }
    </script>
    @include('partials/alert-modal')
    @include('partials/search-select')
    @include('company/inc/foot')
</body>
</html>
