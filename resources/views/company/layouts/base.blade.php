<!DOCTYPE html>
<html lang="es">
@include('company/inc/head')
<body class="bg-slate-50 antialiased h-screen overflow-hidden">

    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

    @include('company/inc/sidebar')

    <div class="lg:ml-64 flex flex-col h-screen overflow-hidden">
        @include('company/inc/navbar')
        <main class="flex-1 flex flex-col overflow-hidden @yield('main-padding', 'p-4 md:p-6')">
            @yield('content-area')
        </main>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }
    </script>
    @include('company/inc/foot')
</body>
</html>
