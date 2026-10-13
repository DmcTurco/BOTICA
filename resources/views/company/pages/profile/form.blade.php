@extends('company/layouts/base', ['elementActive' => 'profile'])

@section('title', 'Datos de la empresa')
@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
@php
    $input = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent';
@endphp
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Datos de la empresa</h1>
        <p class="text-sm text-slate-500 mt-0.5">El nombre de la botica aparece en la barra lateral de todos los paneles.</p>
    </div>

    <form method="POST" action="{{ route('company.profile.update') }}" class="flex-1 flex flex-col min-h-0">
        @csrf
        @method('PUT')

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-store text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Información general</span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Nombre de la botica <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" maxlength="150"
                               value="{{ old('name', $company->name) }}"
                               class="{{ $input }} {{ $errors->has('name') ? 'border-red-400' : '' }}"
                               placeholder="Ej: Botica Salud y Vida">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ruc" class="block text-sm font-medium text-slate-700 mb-1.5">RUC</label>
                        <input type="text" id="ruc" name="ruc" maxlength="11" inputmode="numeric"
                               value="{{ old('ruc', $company->ruc) }}"
                               class="{{ $input }} {{ $errors->has('ruc') ? 'border-red-400' : '' }}"
                               placeholder="11 dígitos">
                        @error('ruc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-slate-700 mb-1.5">Dirección</label>
                    <input type="text" id="address" name="address" maxlength="200"
                           value="{{ old('address', $company->address) }}"
                           class="{{ $input }}" placeholder="Dirección principal de la botica">
                    @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Teléfono</label>
                        <input type="text" id="phone" name="phone" maxlength="30"
                               value="{{ old('phone', $company->phone) }}"
                               class="{{ $input }}" placeholder="Ej: 987654321">
                        @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Correo <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" maxlength="255"
                               value="{{ old('email', $company->email) }}"
                               class="{{ $input }} {{ $errors->has('email') ? 'border-red-400' : '' }}"
                               placeholder="correo@botica.com">
                        <p class="text-xs text-slate-400 mt-1">Es también el correo con el que inicia sesión la empresa.</p>
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="text-xs text-slate-400">
                    <i class="fas fa-circle-info mr-1"></i>
                    Los datos fiscales de los comprobantes (razón social, dirección fiscal, SOL y certificado) se configuran en
                    <a href="{{ route('company.sunat.edit') }}" class="text-emerald-600 hover:underline">Facturación SUNAT</a>.
                </p>
            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-end shrink-0">
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i> Guardar cambios
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
