{{-- Menú lateral plegable (solo pantallas ≥ 1024 px; ver resources/css/app.css).
     Se incluye al inicio del <body> para aplicar el estado guardado antes de pintar y evitar el parpadeo.
     El estado se recuerda en este navegador. --}}
<script>
    (function () {
        try {
            if (localStorage.getItem('sidebar-collapsed') === '1') document.documentElement.classList.add('sidebar-collapsed');
        } catch (e) {}
    })();

    // Con el menú plegado solo se ven íconos: el nombre de cada opción aparece al pasar el mouse
    function syncSidebarTitles() {
        var collapsed = document.documentElement.classList.contains('sidebar-collapsed');
        document.querySelectorAll('#sidebar nav a, #sidebar form button').forEach(function (el) {
            if (collapsed) el.title = el.textContent.replace(/\s+/g, ' ').trim();
            else el.removeAttribute('title');
        });
    }

    function toggleSidebarCollapse() {
        var collapsed = document.documentElement.classList.toggle('sidebar-collapsed');
        try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch (e) {}
        syncSidebarTitles();
    }

    document.addEventListener('DOMContentLoaded', syncSidebarTitles);
</script>
