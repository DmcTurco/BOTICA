@if ($paginator->hasPages())
<nav class="flex items-center justify-end gap-2" aria-label="Paginación">
    @if ($paginator->onFirstPage())
        <span class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg text-xs text-slate-300 border border-slate-200 cursor-not-allowed select-none">
            <i class="fas fa-chevron-left text-[10px]"></i> Anterior
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}"
           class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg text-xs font-medium text-slate-600 border border-slate-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
            <i class="fas fa-chevron-left text-[10px]"></i> Anterior
        </a>
    @endif

    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}"
           class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg text-xs font-medium text-slate-600 border border-slate-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
            Siguiente <i class="fas fa-chevron-right text-[10px]"></i>
        </a>
    @else
        <span class="inline-flex items-center gap-1.5 px-3 h-8 rounded-lg text-xs text-slate-300 border border-slate-200 cursor-not-allowed select-none">
            Siguiente <i class="fas fa-chevron-right text-[10px]"></i>
        </span>
    @endif
</nav>
@endif
