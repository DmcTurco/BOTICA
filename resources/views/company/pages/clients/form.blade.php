@extends('company/layouts/base')

@section('title', isset($client) ? 'Editar Cliente' : 'Nuevo Cliente')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('company.clients.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">
                {{ isset($client) ? 'Editar Cliente' : 'Nuevo Cliente' }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ isset($client) ? "Código: {$client->code} · Modifica los datos del cliente" : 'Completa los datos para registrar un cliente' }}
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
    <form action="{{ isset($client) ? route('company.clients.update', $client) : route('company.clients.store') }}"
          method="POST"
          class="flex-1 flex flex-col">
        @csrf
        @if(isset($client)) @method('PUT') @endif

        <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Cabecera del card --}}
            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-user text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos del Cliente</span>
            </div>

            {{-- Cuerpo scrollable --}}
            <div class="flex-1 overflow-auto p-6 space-y-5">

                {{-- Nombre --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                        Nombre / Razón social <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" required maxlength="150"
                           value="{{ old('name', $client->name ?? '') }}"
                           class="w-full px-3 py-2.5 text-sm border {{ $errors->has('name') ? 'border-red-400' : 'border-slate-300' }} rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="Ej: Juan Pérez García">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Tipo de documento --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tipo de documento</label>
                    <div class="flex flex-wrap gap-2" id="docTypeChips">
                        <button type="button"
                                data-id=""
                                onclick="seleccionarTipoDoc(this)"
                                class="chip-doc px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors border-slate-300 bg-white text-slate-500 hover:border-emerald-500 hover:text-emerald-700">
                            Sin documento
                        </button>
                        @foreach($documentTypes as $dt)
                        <button type="button"
                                data-id="{{ $dt->id }}"
                                onclick="seleccionarTipoDoc(this)"
                                class="chip-doc px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors border-slate-300 bg-white text-slate-500 hover:border-emerald-500 hover:text-emerald-700">
                            {{ $dt->name }}
                        </button>
                        @endforeach
                    </div>
                    <input type="hidden" id="document_type_id" name="document_type_id"
                           value="{{ old('document_type_id', $client->document_type_id ?? '') }}">
                </div>

                {{-- Número de documento --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="document_number" class="block text-sm font-medium text-slate-700 mb-1.5">Número de documento</label>
                        <input type="text" id="document_number" name="document_number" maxlength="20"
                               value="{{ old('document_number', $client->document_number ?? '') }}"
                               class="w-full px-3 py-2.5 text-sm border {{ $errors->has('document_number') ? 'border-red-400' : 'border-slate-300' }} rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Ej: 12345678">
                        @error('document_number') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Teléfono --}}
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">
                            <i class="fas fa-phone text-slate-400 text-xs mr-1"></i> Teléfono
                        </label>
                        <input type="text" id="phone" name="phone" maxlength="20"
                               value="{{ old('phone', $client->phone ?? '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Ej: 999 888 777">
                        @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Email y Dirección --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                            <i class="fas fa-envelope text-slate-400 text-xs mr-1"></i> Correo electrónico
                        </label>
                        <input type="email" id="email" name="email" maxlength="100"
                               value="{{ old('email', $client->email ?? '') }}"
                               class="w-full px-3 py-2.5 text-sm border {{ $errors->has('email') ? 'border-red-400' : 'border-slate-300' }} rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Ej: cliente@correo.com">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-slate-700 mb-1.5">
                            <i class="fas fa-location-dot text-slate-400 text-xs mr-1"></i> Dirección
                        </label>
                        <input type="text" id="address" name="address" maxlength="200"
                               value="{{ old('address', $client->address ?? '') }}"
                               class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                               placeholder="Ej: Av. Principal 123, Lima">
                        @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Estado --}}
                <div class="flex items-center gap-3 py-1">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="statusToggle" name="status" value="1" class="sr-only peer"
                               {{ old('status', isset($client) ? $client->status : '1') == '1' ? 'checked' : '' }}>
                        <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:border-slate-300 after:border after:rounded-full
                                    after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                    <label for="statusToggle" class="text-sm font-medium text-slate-700 cursor-pointer">Cliente activo</label>
                </div>

                {{-- Información adicional al editar --}}
                @if(isset($client))
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Información adicional</p>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Código</p>
                            <p class="text-sm font-bold text-slate-800 font-mono">{{ $client->code }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Fecha creación</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $client->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Última actualización</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $client->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            {{-- Footer de acciones --}}
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ route('company.clients.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    {{ isset($client) ? 'Actualizar Cliente' : 'Guardar Cliente' }}
                </button>
            </div>

        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
// ── Chips de tipo de documento ──────────────────────────────────
const CHIP_ACTIVO   = ['border-emerald-500', 'bg-emerald-50', 'text-emerald-700'];
const CHIP_INACTIVO = ['border-slate-300',   'bg-white',      'text-slate-500'];

function seleccionarTipoDoc(btn) {
    document.querySelectorAll('.chip-doc').forEach(c => {
        c.classList.remove(...CHIP_ACTIVO);
        c.classList.add(...CHIP_INACTIVO);
    });
    btn.classList.remove(...CHIP_INACTIVO);
    btn.classList.add(...CHIP_ACTIVO);
    document.getElementById('document_type_id').value = btn.dataset.id;
}

// Inicializar chip según valor actual
document.addEventListener('DOMContentLoaded', function () {
    const valorActual = document.getElementById('document_type_id').value;
    const chips = document.querySelectorAll('.chip-doc');
    let hayActivo = false;
    chips.forEach(c => {
        if (c.dataset.id == valorActual) {
            seleccionarTipoDoc(c);
            hayActivo = true;
        }
    });
    // Si no hay coincidencia, seleccionar "Sin documento"
    if (!hayActivo && chips.length > 0) {
        seleccionarTipoDoc(chips[0]);
    }
});
</script>
@endsection
