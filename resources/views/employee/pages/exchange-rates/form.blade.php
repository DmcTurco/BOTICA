@extends('employee/layouts/base')

@section('title', 'Registrar Tipo de Cambio')
@section('main-padding', 'p-2 md:p-3')

@php
    $inputClass = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent';
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('employee.exchange-rates.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Registrar Tipo de Cambio</h1>
            <p class="text-sm text-slate-500 mt-0.5">Dólar americano (USD) a soles. Si la fecha ya tiene un tipo de cambio, se actualiza.</p>
        </div>
    </div>

    <form action="{{ route('employee.exchange-rates.store') }}" method="POST" class="flex-1 flex flex-col min-h-0">
        @csrf

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-dollar-sign text-sky-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Tipo de cambio del día</span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                @if($today)
                <div class="p-3 bg-sky-50 border border-sky-200 rounded-lg text-sm text-sky-800">
                    <i class="fas fa-circle-info mr-1"></i>
                    Hoy ya está registrado (compra {{ number_format($today->buy_rate, 3) }} · venta {{ number_format($today->sell_rate, 3) }}). Si lo guardas de nuevo, se corrige.
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="rate_date" class="block text-sm font-medium text-slate-700 mb-1.5">Fecha <span class="text-red-500">*</span></label>
                        <input type="date" name="rate_date" id="rate_date" max="{{ today()->toDateString() }}" value="{{ old('rate_date', today()->toDateString()) }}"
                               class="{{ $inputClass }} @error('rate_date') border-red-400 @enderror">
                        @error('rate_date')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="buy_rate" class="block text-sm font-medium text-slate-700 mb-1.5">Compra <span class="text-red-500">*</span></label>
                        <input type="number" name="buy_rate" id="buy_rate" step="0.0001" min="0.0001" value="{{ old('buy_rate', $today?->buy_rate) }}"
                               placeholder="Ej: 3.705" class="{{ $inputClass }} @error('buy_rate') border-red-400 @enderror">
                        @error('buy_rate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="sell_rate" class="block text-sm font-medium text-slate-700 mb-1.5">Venta <span class="text-red-500">*</span></label>
                        <input type="number" name="sell_rate" id="sell_rate" step="0.0001" min="0.0001" value="{{ old('sell_rate', $today?->sell_rate) }}"
                               placeholder="Ej: 3.712" class="{{ $inputClass }} @error('sell_rate') border-red-400 @enderror">
                        @error('sell_rate')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ \App\Support\ListUrl::to('employee.exchange-rates.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    Guardar tipo de cambio
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
