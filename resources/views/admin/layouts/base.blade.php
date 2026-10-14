<!DOCTYPE html>
<html lang="es">
@include('admin/inc/head')
<body class="bg-slate-50 antialiased h-screen overflow-hidden">

    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

    @include('admin/inc/sidebar')

    {{-- Igual que los paneles de empresa y empleado: ocupa toda la pantalla y el contenido hace scroll adentro --}}
    <div class="lg:ml-64 flex flex-col h-screen overflow-hidden">
        @include('admin/inc/navbar')
        <main class="flex-1 min-h-0 flex flex-col @yield('main-padding', 'p-2 md:p-3') @yield('main-class', 'overflow-auto')">
            @yield('content-area')
        </main>
    </div>

    {{-- Modal de avisos (éxito / errores de validación), igual que en los otros paneles --}}
    @include('partials/alert-modal')
    @include('admin/inc/foot')

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }
    </script>
</body>
</html>
