{{-- Botón "Exportar a Excel": vuelve a pedir la misma pantalla (con sus filtros) en formato CSV --}}
<div class="flex justify-end shrink-0 -mb-1">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
       class="inline-flex items-center gap-2 px-3 py-1.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-medium rounded-lg">
        <i class="fas fa-file-excel text-emerald-600"></i> Exportar a Excel
    </a>
</div>
