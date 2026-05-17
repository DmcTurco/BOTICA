<!DOCTYPE html>
<html lang="es">
@include('employee/inc/head')
<body class="bg-slate-50 antialiased">

    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

    @include('employee/inc/sidebar')

    <div class="lg:ml-64 flex flex-col h-screen">
        @include('employee/inc/navbar')
        <main class="flex-1 min-h-0 flex flex-col @yield('main-padding', 'p-4 md:p-6') @yield('main-class', 'overflow-auto')">
            @yield('content-area')
        </main>
    </div>

    @include('employee/inc/foot')

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }
    </script>
</body>
</html>
