{{-- Botón "Exportar a Excel": va dentro del encabezado de la vista (no ocupa una fila propia).
     Vuelve a pedir la misma pantalla, con sus filtros, en formato CSV --}}
<a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
   class="shrink-0 inline-flex items-center gap-2 px-3 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-medium rounded-lg shadow-sm">
    <i class="fas fa-file-excel text-emerald-600"></i> Exportar a Excel
</a>
