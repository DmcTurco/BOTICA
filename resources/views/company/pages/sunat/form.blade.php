@extends('company/layouts/base', ['elementActive' => 'sunat'])

@section('title', 'Facturación SUNAT')
@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
@php
    // Clases de los campos (mismas que el resto de formularios del panel)
    $input = 'w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent';
    $faltantes = $setting->missingFields();
@endphp
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Facturación electrónica (SUNAT)</h1>
        <p class="text-sm text-slate-500 mt-0.5">
            Datos fiscales, credenciales SOL y certificado digital de {{ $company->name }}.
        </p>
    </div>

    {{-- Mensajes --}}
    @if(session('success'))
    <div class="shrink-0 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
        <i class="fas fa-circle-check mr-1.5"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="shrink-0 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        <i class="fas fa-circle-exclamation mr-1.5"></i>{{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="shrink-0 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 space-y-1">
        <p class="font-semibold">Revisa los datos:</p>
        @foreach($errors->all() as $error)
            <p><i class="fas fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('company.sunat.update') }}" enctype="multipart/form-data"
          class="flex-1 flex flex-col min-h-0">
        @csrf
        @method('PUT')

        <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

            <div class="flex-1 min-h-0 overflow-auto p-6 space-y-8">

                {{-- Estado --}}
                @if($setting->isReady())
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-800">
                    <i class="fas fa-circle-check mr-1.5"></i>
                    <strong>Configuración completa.</strong>
                    Ambiente actual: <strong>{{ $setting->environment === 'production' ? 'Producción' : 'Pruebas (beta)' }}</strong>.
                </div>
                @else
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                    <i class="fas fa-triangle-exclamation mr-1.5"></i>
                    <strong>Configuración incompleta.</strong>
                    @if($setting->certificateExpired())
                        El certificado digital está vencido.
                    @endif
                    @if(count($faltantes))
                        Falta: {{ implode(', ', $faltantes) }}.
                    @endif
                </div>
                @endif

                {{-- ── 1. Datos fiscales ─────────────────────────── --}}
                <section class="space-y-5">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">1. Datos fiscales</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">RUC <span class="text-red-500">*</span></label>
                            <input type="text" name="ruc" maxlength="11" inputmode="numeric"
                                   value="{{ old('ruc', $company->ruc) }}" placeholder="20123456789"
                                   class="{{ $input }} @error('ruc') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Razón social <span class="text-red-500">*</span></label>
                            <input type="text" name="legal_name" maxlength="150"
                                   value="{{ old('legal_name', $setting->legal_name) }}"
                                   class="{{ $input }} @error('legal_name') border-red-400 @enderror">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre comercial</label>
                            <input type="text" name="trade_name" maxlength="150"
                                   value="{{ old('trade_name', $setting->trade_name) }}"
                                   class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección fiscal <span class="text-red-500">*</span></label>
                            <input type="text" name="fiscal_address" maxlength="200"
                                   value="{{ old('fiscal_address', $setting->fiscal_address) }}"
                                   class="{{ $input }} @error('fiscal_address') border-red-400 @enderror">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Ubigeo (INEI, 6 dígitos) <span class="text-red-500">*</span></label>
                            <input type="text" name="ubigeo" maxlength="6" inputmode="numeric"
                                   value="{{ old('ubigeo', $setting->ubigeo) }}" placeholder="150101"
                                   class="{{ $input }} @error('ubigeo') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Departamento <span class="text-red-500">*</span></label>
                            <input type="text" name="department" maxlength="60"
                                   value="{{ old('department', $setting->department) }}" placeholder="LIMA"
                                   class="{{ $input }} @error('department') border-red-400 @enderror">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Provincia <span class="text-red-500">*</span></label>
                            <input type="text" name="province" maxlength="60"
                                   value="{{ old('province', $setting->province) }}" placeholder="LIMA"
                                   class="{{ $input }} @error('province') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Distrito <span class="text-red-500">*</span></label>
                            <input type="text" name="district" maxlength="60"
                                   value="{{ old('district', $setting->district) }}" placeholder="LIMA"
                                   class="{{ $input }} @error('district') border-red-400 @enderror">
                        </div>
                    </div>
                </section>

                {{-- ── 2. Credenciales SOL ───────────────────────── --}}
                <section class="space-y-5">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">2. Credenciales SOL</h2>
                    <p class="text-xs text-slate-500">
                        Usa un <strong>usuario SOL secundario</strong> con permiso de facturación electrónica, no el principal.
                        La clave se guarda cifrada y no se vuelve a mostrar.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Usuario SOL</label>
                            <input type="text" name="sol_user" maxlength="50" autocomplete="off"
                                   value="{{ old('sol_user', $setting->sol_user) }}"
                                   class="{{ $input }} @error('sol_user') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Clave SOL</label>
                            <input type="password" name="sol_password" maxlength="100" autocomplete="new-password"
                                   placeholder="{{ $setting->hasSolCredentials() ? '•••••••• (guardada; déjala vacía para conservarla)' : '' }}"
                                   class="{{ $input }}">
                        </div>
                    </div>
                </section>

                {{-- ── 3. Certificado digital ────────────────────── --}}
                <section class="space-y-5">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">3. Certificado digital</h2>

                    @if($setting->hasCertificate())
                    <div class="p-4 rounded-xl border text-sm
                                {{ $setting->certificateExpired() ? 'bg-red-50 border-red-200 text-red-700' : ($setting->certificateExpiresSoon() ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-slate-50 border-slate-200 text-slate-700') }}">
                        <p><i class="fas fa-certificate mr-1.5"></i><strong>Certificado cargado:</strong> {{ $setting->certificate_subject }}</p>
                        <p class="text-xs mt-1">
                            Vence el {{ $setting->certificate_expires_at?->format('d/m/Y') }}
                            @if($setting->certificateExpired()) — <strong>VENCIDO</strong>
                            @elseif($setting->certificateExpiresSoon()) — vence pronto, renuévalo
                            @endif
                        </p>
                    </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                {{ $setting->hasCertificate() ? 'Reemplazar certificado (.pfx / .p12)' : 'Certificado (.pfx / .p12)' }}
                            </label>
                            <input type="file" name="certificate" accept=".pfx,.p12"
                                   class="{{ $input }} @error('certificate') border-red-400 @enderror">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Clave del certificado</label>
                            <input type="password" name="certificate_password" maxlength="100" autocomplete="new-password"
                                   class="{{ $input }} @error('certificate_password') border-red-400 @enderror">
                        </div>
                    </div>
                    <p class="text-xs text-slate-400">
                        El archivo se valida al guardar y queda cifrado en el servidor. Para el ambiente de pruebas no es obligatorio.
                    </p>
                </section>

                {{-- ── 4. Ambiente y activación ──────────────────── --}}
                <section class="space-y-4">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">4. Ambiente y activación</h2>

                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="environment" value="beta" class="accent-emerald-600"
                                   {{ old('environment', $setting->environment ?? 'beta') === 'beta' ? 'checked' : '' }}>
                            <span class="text-sm text-slate-700"><strong>Pruebas (beta)</strong> — los comprobantes no tienen validez tributaria</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="environment" value="production" class="accent-emerald-600"
                                   {{ old('environment', $setting->environment ?? 'beta') === 'production' ? 'checked' : '' }}>
                            <span class="text-sm text-slate-700"><strong>Producción</strong> — comprobantes reales enviados a SUNAT</span>
                        </label>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" class="accent-emerald-600"
                               {{ old('enabled', $setting->enabled) ? 'checked' : '' }}>
                        <span class="text-sm text-slate-700">Emisión electrónica activa</span>
                    </label>
                    <p class="text-xs text-slate-400">
                        Para activarla o usar producción deben estar completos el usuario y la clave SOL y el certificado digital.
                    </p>
                </section>

            </div>

            {{-- Botones (footer fijo) --}}
            <div class="shrink-0 border-t border-slate-100 px-6 py-4 flex items-center gap-3 bg-white">
                <button type="submit"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Guardar configuración
                </button>
                <a href="{{ route('company.home') }}"
                   class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
            </div>

        </div>
    </form>

</div>
@endsection
