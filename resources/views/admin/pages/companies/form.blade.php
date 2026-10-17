@extends('admin/layouts/base')

@php $isEdit = isset($company); @endphp

@section('title', $isEdit ? 'Editar Empresa' : 'Nueva Empresa')
@section('main-class', 'overflow-hidden')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('admin.companies.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">{{ $isEdit ? 'Editar Empresa' : 'Nueva Empresa' }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $isEdit
                    ? $company->name . ' · ' . $company->branches_count . ' sedes · ' . $company->employees_count . ' empleados'
                    : 'Se crea con su primera sede y sus series de comprobantes listas para vender' }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ $isEdit ? route('admin.companies.update', $company) : route('admin.companies.store') }}"
          class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Campos (scroll interno; los botones quedan fijos abajo) --}}
        <div class="flex-1 min-h-0 overflow-auto p-6 space-y-6">

            {{-- Datos de la empresa --}}
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Datos de la botica</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $company->name ?? '') }}" placeholder="Botica San José"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('name') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">RUC</label>
                        <input type="text" name="ruc" maxlength="11" value="{{ old('ruc', $company->ruc ?? '') }}" placeholder="20123456789"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg font-mono focus:outline-none focus:ring-2 focus:ring-violet-500 @error('ruc') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone', $company->phone ?? '') }}" placeholder="987 654 321"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('phone') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección fiscal</label>
                        <input type="text" name="address" value="{{ old('address', $company->address ?? '') }}" placeholder="Av. Principal 123, Lima"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('address') border-red-400 @else border-slate-200 @enderror">
                    </div>
                </div>
            </div>

            {{-- Acceso al panel de empresa --}}
            <div class="border-t border-slate-100 pt-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Acceso al panel de empresa</p>
                <p class="text-xs text-slate-500 mb-3">Con este correo y contraseña la botica entra en <span class="font-mono">/company/login</span>.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Correo <span class="text-red-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email', $company->email ?? '') }}" placeholder="botica@correo.com"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('email') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Contraseña @unless($isEdit)<span class="text-red-500">*</span>@endunless
                            @if($isEdit)<span class="text-xs font-normal text-slate-400">(en blanco = no cambiar)</span>@endif
                        </label>
                        <input type="password" name="password" autocomplete="new-password" placeholder="{{ $isEdit ? '••••••' : 'Mínimo 8 caracteres' }}"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('password') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirmar contraseña</label>
                        <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repite la contraseña"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>
            </div>

            {{-- Primera sede (solo al crear) --}}
            @unless($isEdit)
            <div class="border-t border-slate-100 pt-5">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Primera sede</p>
                <p class="text-xs text-slate-500 mb-3">Se crea con sus series B001, F001, NV01, BC01 y FC01. Las demás sedes las crea la propia empresa.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nombre de la sede <span class="text-red-500">*</span></label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', 'Sede Principal') }}"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('branch_name') border-red-400 @else border-slate-200 @enderror">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Dirección de la sede</label>
                        <input type="text" name="branch_address" value="{{ old('branch_address') }}" placeholder="Igual a la fiscal si es la única"
                               class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500 @error('branch_address') border-red-400 @else border-slate-200 @enderror">
                    </div>
                </div>
            </div>
            @endunless

        </div>

        {{-- Botones (pie fijo) --}}
        <div class="shrink-0 border-t border-slate-100 px-6 py-4 flex items-center gap-3 bg-slate-50">
            <button type="submit"
                    class="px-5 py-2.5 bg-violet-700 hover:bg-violet-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                {{ $isEdit ? 'Guardar cambios' : 'Crear Empresa' }}
            </button>
            <a href="{{ \App\Support\ListUrl::to('admin.companies.index') }}"
               class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-white transition-colors">
                Cancelar
            </a>
        </div>
    </form>

</div>
@endsection
