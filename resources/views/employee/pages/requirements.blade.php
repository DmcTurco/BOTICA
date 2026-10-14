@extends('employee/layouts/base')

@section('title', $title)

{{-- Pantalla de bloqueo: faltan datos previos para usar esta sección (App\Services\Requirements) --}}
@section('content-area')
<div class="flex-1 flex items-start justify-center">
    <div class="w-full max-w-xl bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="px-5 py-4 bg-amber-50 border-b border-amber-200 flex items-start gap-3">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-triangle-exclamation text-amber-600"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-slate-800">{{ $title }}: faltan datos previos</h1>
                <p class="text-sm text-slate-600 mt-0.5">Antes de continuar registra lo que está pendiente.</p>
            </div>
        </div>

        <ul class="divide-y divide-slate-100">
            @foreach($items as $item)
            <li class="px-5 py-3 flex items-center gap-3">
                @if($item['ok'])
                <i class="fas fa-circle-check text-emerald-500"></i>
                @else
                <i class="fas fa-circle-exclamation text-red-500"></i>
                @endif

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold {{ $item['ok'] ? 'text-slate-400' : 'text-slate-800' }}">{{ $item['label'] }}</p>
                    @unless($item['ok'])
                    <p class="text-xs text-slate-500 mt-0.5">{{ $item['hint'] }}</p>
                    @endunless
                </div>

                @if(!$item['ok'] && $item['url'])
                <a href="{{ $item['url'] }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors shrink-0">
                    <i class="fas fa-plus text-xs"></i> {{ $item['action'] }}
                </a>
                @elseif($item['ok'])
                <span class="text-xs text-emerald-600 font-semibold shrink-0">Listo</span>
                @endif
            </li>
            @endforeach
        </ul>

        @if($backUrl)
        <div class="px-5 py-3 bg-slate-50 border-t border-slate-200 flex justify-end">
            <a href="{{ $backUrl }}"
               class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Volver
            </a>
        </div>
        @endif
    </div>
</div>
@endsection
