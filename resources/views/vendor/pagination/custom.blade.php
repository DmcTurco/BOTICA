@if ($paginator->hasPages())
<nav class="flex items-center justify-between gap-4" aria-label="Paginación">

    {{-- Info de resultados --}}
    <p class="text-xs text-slate-500 shrink-0">
        Mostrando
        <span class="font-semibold text-slate-700">{{ $paginator->firstItem() }}</span>
        –
        <span class="font-semibold text-slate-700">{{ $paginator->lastItem() }}</span>
        de
        <span class="font-semibold text-slate-700">{{ $paginator->total() }}</span>
        resultados
    </p>

    {{-- Botones --}}
    <div class="flex items-center gap-1">

        {{-- Anterior --}}
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 border border-slate-200 cursor-not-allowed select-none">
                <i class="fas fa-chevron-left text-xs"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}"
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 border border-slate-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
        @endif

        {{-- Páginas --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="inline-flex items-center justify-center w-8 h-8 text-xs text-slate-400 select-none">…</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-semibold text-white bg-emerald-600 border border-emerald-600 select-none">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium text-slate-600 border border-slate-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Siguiente --}}
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}"
               class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-600 border border-slate-200 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200 transition-colors">
                <i class="fas fa-chevron-right text-xs"></i>
            </a>
        @else
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-300 border border-slate-200 cursor-not-allowed select-none">
                <i class="fas fa-chevron-right text-xs"></i>
            </span>
        @endif

    </div>
</nav>
@endif
