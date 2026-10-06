@extends('company/layouts/base', ['elementActive' => 'branches'])

@section('title', isset($branch) ? 'Editar Sede' : 'Nueva Sede')
@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('company.branches.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">
                {{ isset($branch) ? 'Editar Sede' : 'Nueva Sede' }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ isset($branch) ? $branch->name : 'Completa los datos de la sucursal' }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <form method="POST"
          action="{{ isset($branch) ? route('company.branches.update', $branch) : route('company.branches.store') }}"
          class="flex-1 flex flex-col min-h-0">
        @csrf
        @isset($branch) @method('PUT') @endisset

        <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

            {{-- Campos (scrollable) --}}
            <div class="flex-1 min-h-0 overflow-auto p-6 space-y-5">

                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $branch->name ?? '') }}"
                           placeholder="Ej: Sede Centro, Sucursal Los Olivos..."
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('name') border-red-400 @enderror">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dirección --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección</label>
                    <input type="text" name="address"
                           value="{{ old('address', $branch->address ?? '') }}"
                           placeholder="Av. Ejemplo 123, Piso 2..."
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('address') border-red-400 @enderror">
                    @error('address')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Teléfono y Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono</label>
                        <input type="text" name="phone"
                               value="{{ old('phone', $branch->phone ?? '') }}"
                               placeholder="01-234567 / 987654321"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('phone') border-red-400 @enderror">
                        @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email de contacto</label>
                        <input type="email" name="email"
                               value="{{ old('email', $branch->email ?? '') }}"
                               placeholder="sede@farmacia.com"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('email') border-red-400 @enderror">
                        @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Código de establecimiento SUNAT --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Código de establecimiento SUNAT <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="sunat_establishment_code" maxlength="4" inputmode="numeric"
                           value="{{ old('sunat_establishment_code', $branch->sunat_establishment_code ?? '0000') }}"
                           placeholder="0000"
                           class="w-full sm:w-40 px-3 py-2.5 text-sm font-mono border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('sunat_establishment_code') border-red-400 @enderror">
                    <p class="text-xs text-slate-400 mt-1">
                        4 dígitos. <strong>0000</strong> es el domicilio fiscal; las demás sedes usan el código del anexo registrado en SUNAT (0001, 0002...).
                    </p>
                    @error('sunat_establishment_code')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Series de comprobantes (solo al editar; al crear se generan solas) --}}
                @isset($branch)
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Series de comprobantes</label>
                    <p class="text-xs text-slate-400 mb-3">
                        Cada sede numera sus comprobantes con sus propias series. Puedes cambiar una serie solo mientras no haya emitido comprobantes.
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @foreach($series as $serie)
                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-1">{{ $serie->name }}</label>
                            @if($serie->current_number > 0)
                                <input type="text" value="{{ $serie->series }}" readonly
                                       class="w-full px-3 py-2.5 text-sm font-mono border border-slate-200 rounded-lg bg-slate-50 text-slate-500">
                                <p class="text-[11px] text-slate-400 mt-1">
                                    En uso · último: {{ $serie->series }}-{{ str_pad($serie->current_number, $serie->digits, '0', STR_PAD_LEFT) }}
                                </p>
                            @else
                                <input type="text" name="series[{{ $serie->id }}]" maxlength="4"
                                       value="{{ old('series.' . $serie->id, $serie->series) }}"
                                       class="w-full px-3 py-2.5 text-sm font-mono uppercase border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('series.' . $serie->id) border-red-400 @enderror">
                                <p class="text-[11px] text-slate-400 mt-1">Sin usar todavía</p>
                            @endif
                            @error('series.' . $serie->id)
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        @endforeach
                    </div>
                </div>
                @endisset

                {{-- Estado --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Estado</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="1"
                                   {{ old('status', $branch->status ?? 1) == 1 ? 'checked' : '' }}
                                   class="accent-emerald-600">
                            <span class="text-sm text-slate-700">Activa</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="status" value="0"
                                   {{ old('status', $branch->status ?? 1) == 0 ? 'checked' : '' }}
                                   class="accent-emerald-600">
                            <span class="text-sm text-slate-700">Inactiva</span>
                        </label>
                    </div>
                </div>

            </div>

            {{-- Botones (footer fijo) --}}
            <div class="shrink-0 border-t border-slate-100 px-6 py-4 flex items-center gap-3 bg-white">
                <button type="submit"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    {{ isset($branch) ? 'Guardar cambios' : 'Crear Sede' }}
                </button>
                <a href="{{ route('company.branches.index') }}"
                   class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
            </div>

        </div>
    </form>

</div>
@endsection
