<!DOCTYPE html>
<html lang="es">
@include('employee/inc/head')
<body class="panel-employee bg-slate-50 antialiased">
    @include('partials/sidebar-collapse')

    <div id="sidebar-overlay" onclick="toggleSidebar()"
         class="fixed inset-0 bg-black/50 z-20 hidden lg:hidden"></div>

    @include('employee/inc/sidebar')

    {{-- app-shell / app-main: alto visible real y alto mínimo de página (ver resources/css/app.css) --}}
    <div class="app-content lg:ml-64 flex flex-col h-screen app-shell overflow-hidden">
        @include('employee/inc/navbar')
        @include('partials/subscription-banner', ['company' => auth()->guard('employee')->user()?->company])
        <main class="app-main flex-1 min-h-0 flex flex-col @yield('main-padding', 'p-4 md:p-6')">
            @yield('content-area')
        </main>
    </div>

    @include('partials/alert-modal')
    @include('partials/search-select')
    @include('employee/inc/foot')

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.toggle('hidden');
    }
    </script>
</body>
</html>
