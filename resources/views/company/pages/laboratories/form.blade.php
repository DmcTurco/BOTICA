@extends('company/layouts/base')

@section('title', isset($laboratorio) ? 'Editar Laboratorio' : 'Nuevo Laboratorio')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('company.laboratories.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">
                {{ isset($laboratorio) ? 'Editar Laboratorio' : 'Nuevo Laboratorio' }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ isset($laboratorio) ? 'Modifica los datos del laboratorio' : 'Completa los datos para registrar un laboratorio' }}
            </p>
        </div>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
    <div class="shrink-0 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm space-y-1">
        @foreach ($errors->all() as $error)
            <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}</p>
        @endforeach
    </div>
    @endif

    {{-- Formulario --}}
    <form action="{{ isset($laboratorio) ? route('company.laboratories.update', $laboratorio->id) : route('company.laboratories.store') }}"
          method="POST"
          class="flex-1 flex flex-col">
        @csrf
        @if(isset($laboratorio)) @method('PUT') @endif

        <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Cabecera del card --}}
            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-flask text-sky-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos del Laboratorio</span>
            </div>

            {{-- Cuerpo scrollable --}}
            <div class="flex-1 overflow-auto p-6 space-y-5">

                {{-- Nombre --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="nombre" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="nombre" name="nombre" required maxlength="100"
                               value="{{ old('nombre', $laboratorio->name ?? '') }}"
                               class="w-full px-3 py-2.5 text-sm border {{ $errors->has('nombre') ? 'border-red-400' : 'border-slate-300' }} rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Ej: Bayer, Pfizer, Farmex">
                        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="pais" class="block text-sm font-medium text-slate-700 mb-1.5">País de origen</label>
                        <input type="text" id="pais" name="pais" maxlength="80"
                               value="{{ old('pais', $laboratorio->country ?? '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Ej: Perú, Alemania, EE.UU.">
                        @error('pais') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Descripción --}}
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-slate-700 mb-1.5">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" maxlength="255"
                              class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none"
                              placeholder="Breve descripción del laboratorio...">{{ old('descripcion', $laboratorio->description ?? '') }}</textarea>
                    @error('descripcion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Contacto --}}
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Datos de Contacto <span class="font-normal text-slate-400 normal-case">(opcional)</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="telefono" class="block text-sm font-medium text-slate-700 mb-1.5">
                                <i class="fas fa-phone text-slate-400 text-xs mr-1"></i> Teléfono
                            </label>
                            <input type="text" id="telefono" name="telefono" maxlength="30"
                                   value="{{ old('telefono', $laboratorio->phone ?? '') }}"
                                   class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                   placeholder="+51 999 999 999">
                            @error('telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                                <i class="fas fa-envelope text-slate-400 text-xs mr-1"></i> Email
                            </label>
                            <input type="email" id="email" name="email" maxlength="100"
                                   value="{{ old('email', $laboratorio->email ?? '') }}"
                                   class="w-full px-3 py-2.5 text-sm border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-300' }} rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                   placeholder="contacto@laboratorio.com">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Estado --}}
                <div class="flex items-center gap-3 py-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="activo" name="activo" value="1" class="sr-only peer"
                               {{ old('activo', isset($laboratorio) ? $laboratorio->status : '1') == '1' ? 'checked' : '' }}>
                        <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:border-slate-300 after:border after:rounded-full
                                    after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                    <label for="activo" class="text-sm font-medium text-slate-700 cursor-pointer">Laboratorio activo</label>
                </div>

                {{-- Info adicional al editar --}}
                @if(isset($laboratorio))
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Información adicional</p>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Productos asociados</p>
                            <p class="text-lg font-bold text-slate-800">{{ $productosCount ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Fecha creación</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $laboratorio->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Última actualización</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $laboratorio->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            {{-- Footer de acciones --}}
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ route('company.laboratories.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    {{ isset($laboratorio) ? 'Actualizar Laboratorio' : 'Guardar Laboratorio' }}
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
