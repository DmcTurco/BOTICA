@extends('company/layouts/base', ['elementActive' => 'employees'])

@section('title', isset($employee) ? 'Editar Empleado' : 'Nuevo Empleado')
@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('company.employees.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors">
            <i class="fas fa-arrow-left text-xs"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">
                {{ isset($employee) ? 'Editar Empleado' : 'Nuevo Empleado' }}
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ isset($employee) ? $employee->name : 'Completa los datos del nuevo usuario' }}
            </p>
        </div>
    </div>

    {{-- Formulario --}}
    <form method="POST"
          action="{{ isset($employee) ? route('company.employees.update', $employee) : route('company.employees.store') }}"
          class="flex-1 flex flex-col min-h-0">
        @csrf
        @isset($employee) @method('PUT') @endisset

        <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden min-h-0">

            {{-- Campos (scrollable) --}}
            <div class="flex-1 min-h-0 overflow-auto p-6 space-y-5">

                {{-- Nombre --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name"
                           value="{{ old('name', $employee->name ?? '') }}"
                           placeholder="Juan Pérez López"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('name') border-red-400 @enderror">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email"
                           value="{{ old('email', $employee->email ?? '') }}"
                           placeholder="empleado@farmacia.com"
                           class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('email') border-red-400 @enderror">
                    @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Sede y Rol --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Sede <span class="text-red-500">*</span>
                        </label>
                        <select name="branch_id"
                                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white @error('branch_id') border-red-400 @enderror">
                            <option value="">Selecciona una sede</option>
                            @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ old('branch_id', $employee->branch_id ?? '') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Rol <span class="text-red-500">*</span>
                        </label>
                        <select name="role_id"
                                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white @error('role_id') border-red-400 @enderror">
                            <option value="">Selecciona un rol</option>
                            @foreach($roles as $role)
                            <option value="{{ $role->id }}"
                                {{ old('role_id', $employee->role_id ?? '') == $role->id ? 'selected' : '' }}>
                                {{ $role->description }}
                            </option>
                            @endforeach
                        </select>
                        @error('role_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Contraseña --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Contraseña {{ isset($employee) ? '' : '*' }}
                            @isset($employee)
                            <span class="text-xs font-normal text-slate-400">(dejar en blanco para no cambiar)</span>
                            @endisset
                        </label>
                        <input type="password" name="password"
                               placeholder="{{ isset($employee) ? '••••••' : 'Mínimo 6 caracteres' }}"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('password') border-red-400 @enderror">
                        @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Confirmar contraseña
                        </label>
                        <input type="password" name="password_confirmation"
                               placeholder="Repite la contraseña"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    </div>
                </div>

            </div>

            {{-- Botones (footer fijo) --}}
            <div class="shrink-0 border-t border-slate-100 px-6 py-4 flex items-center gap-3 bg-white">
                <button type="submit"
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    {{ isset($employee) ? 'Guardar cambios' : 'Crear Empleado' }}
                </button>
                <a href="{{ route('company.employees.index') }}"
                   class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
            </div>

        </div>
    </form>

</div>
@endsection
