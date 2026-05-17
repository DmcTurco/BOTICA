@extends('employee/layouts/base', ['elementActive' => 'employees'])

@section('title', isset($employee) ? 'Editar Empleado' : 'Nuevo Empleado')
@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ route('employee.employees.index') }}"
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
          action="{{ isset($employee) ? route('employee.employees.update', $employee) : route('employee.employees.store') }}"
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
                           class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent @error('name') border-red-400 @else border-slate-200 @enderror">
                    @error('name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Correo electrónico <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email"
                           value="{{ old('email', $employee->email ?? '') }}"
                           placeholder="empleado@farmacia.com"
                           class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent @error('email') border-red-400 @else border-slate-200 @enderror">
                    @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Rol --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Rol <span class="text-red-500">*</span>
                    </label>
                    <select name="role_id" id="role_id"
                            onchange="togglePrivileges(this.value)"
                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white @error('role_id') border-red-400 @enderror">
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

                {{-- Nota de sede --}}
                <div class="flex items-center gap-2 px-3 py-2.5 bg-sky-50 border border-sky-100 rounded-lg text-xs text-sky-700">
                    <i class="fas fa-info-circle shrink-0"></i>
                    El empleado quedará asignado automáticamente a <strong>tu sede actual</strong>.
                </div>

                {{-- Privilegios — solo visibles cuando el rol es Empleado (3) --}}
                <div id="privileges-section" class="{{ old('role_id', $employee->role_id ?? '') == 3 ? '' : 'hidden' }}">
                    <label class="block text-sm font-semibold text-slate-700 mb-3">
                        Privilegios del empleado
                        <span class="text-xs font-normal text-slate-400 ml-1">— selecciona qué puede hacer</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach($privileges as $key => $label)
                        @php
                            $checked = in_array($key, old('privileges', $employee->privileges ?? []), true);
                        @endphp
                        <label class="flex items-center gap-3 px-3 py-2.5 border rounded-lg cursor-pointer transition-colors
                                      {{ $checked ? 'border-sky-300 bg-sky-50' : 'border-slate-200 hover:border-sky-200 hover:bg-slate-50' }}"
                               id="label-{{ $key }}">
                            <input type="checkbox"
                                   name="privileges[]"
                                   value="{{ $key }}"
                                   class="w-4 h-4 text-sky-600 rounded border-slate-300 focus:ring-sky-500"
                                   {{ $checked ? 'checked' : '' }}
                                   onchange="togglePrivilegeLabel(this)">
                            <span class="text-sm text-slate-700">{{ $label }}</span>
                        </label>
                        @endforeach
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
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   placeholder="{{ isset($employee) ? '••••••••' : 'Mínimo 8 caracteres' }}"
                                   class="w-full px-3 py-2.5 pr-10 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent @error('password') border-red-400 @else border-slate-200 @enderror">
                            <button type="button" onclick="togglePassword('password', 'eye-password')"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                                <i id="eye-password" class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                        @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Confirmar contraseña
                        </label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                   placeholder="Repite la contraseña"
                                   class="w-full px-3 py-2.5 pr-10 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                            <button type="button" onclick="togglePassword('password_confirmation', 'eye-confirm')"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600">
                                <i id="eye-confirm" class="fas fa-eye text-sm"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Botones (footer fijo) --}}
            <div class="shrink-0 border-t border-slate-100 px-6 py-4 flex items-center gap-3 bg-white">
                <button type="submit"
                        class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    {{ isset($employee) ? 'Guardar cambios' : 'Crear Empleado' }}
                </button>
                <a href="{{ route('employee.employees.index') }}"
                   class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
            </div>

        </div>
    </form>

</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye',       !isHidden);
        icon.classList.toggle('fa-eye-slash',  isHidden);
    }

    // Muestra u oculta el bloque de privilegios según el rol seleccionado
    function togglePrivileges(roleId) {
        const section = document.getElementById('privileges-section');
        if (String(roleId) === '3') {
            section.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
            // Desmarcar todos los checkboxes al cambiar a otro rol
            section.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = false;
                togglePrivilegeLabel(cb);
            });
        }
    }

    // Cambia el estilo visual del label al marcar/desmarcar el checkbox
    function togglePrivilegeLabel(checkbox) {
        const label = document.getElementById('label-' + checkbox.value);
        if (!label) return;
        if (checkbox.checked) {
            label.classList.add('border-sky-300', 'bg-sky-50');
            label.classList.remove('border-slate-200');
        } else {
            label.classList.remove('border-sky-300', 'bg-sky-50');
            label.classList.add('border-slate-200');
        }
    }
</script>
@endsection
