@extends('employee/layouts/base')

@section('title', isset($unit) ? 'Editar Unidad' : 'Nueva Unidad')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
@php
    $inputClass = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent';
@endphp
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('employee.units.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">
                {{ isset($unit) ? 'Editar Unidad' : 'Nueva Unidad' }}
                @if(isset($unit))
                    <span class="text-slate-400 font-normal">—</span>
                    <span class="text-emerald-700">{{ $unit->name }}</span>
                @endif
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ isset($unit) ? 'Modifica los datos de la unidad de medida' : 'Completa los datos para crear una unidad de medida' }}
            </p>
        </div>
    </div>

    <form action="{{ isset($unit) ? route('employee.units.update', $unit) : route('employee.units.store') }}"
          method="POST" class="flex-1 flex flex-col min-h-0">
        @csrf
        @if(isset($unit)) @method('PUT') @endif

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-ruler text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos de la Unidad</span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" maxlength="50"
                               value="{{ old('name', $unit->name ?? '') }}"
                               class="{{ $inputClass }} {{ $errors->has('name') ? 'border-red-400' : '' }}"
                               placeholder="Ej: Tableta, Caja, Frasco">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="abbreviation" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Abreviatura <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="abbreviation" name="abbreviation" maxlength="10"
                               value="{{ old('abbreviation', $unit->abbreviation ?? '') }}"
                               class="{{ $inputClass }} uppercase {{ $errors->has('abbreviation') ? 'border-red-400' : '' }}"
                               placeholder="Ej: TAB">
                        @error('abbreviation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="max-w-md">
                    <label for="sunat_code" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Código SUNAT (catálogo 03) <span class="text-red-500">*</span>
                    </label>
                    <select id="sunat_code" name="sunat_code" data-search required class="{{ $inputClass }}">
                        @foreach($sunatCodes as $code => $label)
                            <option value="{{ $code }}" {{ old('sunat_code', $unit->sunat_code ?? 'NIU') === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-slate-400 mt-1">Es el código con el que la unidad se informa en los comprobantes electrónicos.</p>
                    @error('sunat_code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 py-1">
                    <input type="hidden" name="status" value="0">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="status" name="status" value="1" class="sr-only peer"
                               {{ (int) old('status', isset($unit) ? $unit->status : 1) === 1 ? 'checked' : '' }}>
                        <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sky-500 rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:border-slate-300 after:border after:rounded-full
                                    after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                    </label>
                    <label for="status" class="text-sm font-medium text-slate-700 cursor-pointer">Unidad activa</label>
                </div>

                @if(isset($unit))
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Información adicional</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Productos que la usan</p>
                            <p class="text-lg font-bold text-slate-800">{{ $unit->productos_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Presentaciones que la usan</p>
                            <p class="text-lg font-bold text-slate-800">{{ $unit->presentaciones_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Fecha creación</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $unit->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Última actualización</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $unit->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ \App\Support\ListUrl::to('employee.units.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    {{ isset($unit) ? 'Actualizar Unidad' : 'Guardar Unidad' }}
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
