@extends('employee/layouts/base', ['elementActive' => 'employees'])
@use('Illuminate\Support\Str')

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
            <div class="flex-1 min-h-0 overflow-auto p-6 space-y-4">

                {{-- ── Fila 1: Nombre / Email / Rol ── --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

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

                </div>

                {{-- ── Fila 2: Contraseñas ── --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                {{-- Nota de sede --}}
                <div class="flex items-center gap-2 px-3 py-2 bg-sky-50 border border-sky-100 rounded-lg text-xs text-sky-700">
                    <i class="fas fa-info-circle shrink-0"></i>
                    El empleado quedará asignado automáticamente a <strong>tu sede actual</strong>.
                </div>

                {{-- ── Privilegios — solo visibles cuando el rol es Empleado (3) ── --}}
                <div id="privileges-section" class="{{ old('role_id', $employee->role_id ?? '') == 3 ? '' : 'hidden' }}">

                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-semibold text-slate-700">
                            Privilegios del empleado
                            <span class="text-xs font-normal text-slate-400 ml-1">— selecciona qué puede hacer</span>
                        </span>
                        <button type="button" onclick="toggleAllPrivileges()"
                                class="text-xs text-sky-600 hover:text-sky-800 font-medium underline underline-offset-2">
                            Seleccionar / Limpiar todos
                        </button>
                    </div>

                    {{-- Grid de grupos en 2 columnas --}}
                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

                        @php
                            $currentPrivileges = old('privileges', $employee->privileges ?? []);

                            $colorMap = [
                                'sky'     => ['bg' => 'bg-sky-50',     'border_h' => 'border-sky-200',    'icon' => 'text-sky-600',    'header' => 'text-sky-700',    'active' => 'bg-sky-50 border-sky-300'],
                                'emerald' => ['bg' => 'bg-emerald-50', 'border_h' => 'border-emerald-200','icon' => 'text-emerald-600','header' => 'text-emerald-700','active' => 'bg-emerald-50 border-emerald-300'],
                                'violet'  => ['bg' => 'bg-violet-50',  'border_h' => 'border-violet-200', 'icon' => 'text-violet-600', 'header' => 'text-violet-700', 'active' => 'bg-violet-50 border-violet-300'],
                                'amber'   => ['bg' => 'bg-amber-50',   'border_h' => 'border-amber-200',  'icon' => 'text-amber-600',  'header' => 'text-amber-700',  'active' => 'bg-amber-50 border-amber-300'],
                                'indigo'  => ['bg' => 'bg-indigo-50',  'border_h' => 'border-indigo-200', 'icon' => 'text-indigo-600', 'header' => 'text-indigo-700', 'active' => 'bg-indigo-50 border-indigo-300'],
                                'rose'    => ['bg' => 'bg-rose-50',    'border_h' => 'border-rose-200',   'icon' => 'text-rose-600',   'header' => 'text-rose-700',   'active' => 'bg-rose-50 border-rose-300'],
                                'orange'  => ['bg' => 'bg-orange-50',  'border_h' => 'border-orange-200', 'icon' => 'text-orange-600', 'header' => 'text-orange-700', 'active' => 'bg-orange-50 border-orange-300'],
                                'slate'   => ['bg' => 'bg-slate-100',  'border_h' => 'border-slate-300',  'icon' => 'text-slate-500',  'header' => 'text-slate-700',  'active' => 'bg-slate-100 border-slate-400'],
                            ];
                        @endphp

                        @foreach($privilegesGroups as $groupName => $group)
                        @php
                            $c       = $colorMap[$group['color']] ?? $colorMap['sky'];
                            $groupId = 'group-' . Str::slug($groupName);

                            // Solo los ítems ready=true cuentan para el checkbox "Todos"
                            $readyKeys = array_keys(array_filter($group['items'], fn($i) => $i['ready']));
                            $checkedReadyCount = count(array_intersect($readyKeys, $currentPrivileges));
                            $allReady = count($readyKeys) > 0 && $checkedReadyCount === count($readyKeys);
                            $someReady = $checkedReadyCount > 0 && !$allReady;
                        @endphp

                        <div class="border border-slate-200 rounded-xl overflow-hidden">

                            {{-- Header del grupo --}}
                            <div class="flex items-center justify-between px-4 py-2.5 {{ $c['bg'] }} border-b {{ $c['border_h'] }}">
                                <div class="flex items-center gap-2">
                                    <i class="fas {{ $group['icon'] }} text-sm {{ $c['icon'] }}"></i>
                                    <span class="text-sm font-semibold {{ $c['header'] }}">{{ $groupName }}</span>
                                </div>
                                @if(count($readyKeys) > 0)
                                <label class="flex items-center gap-1.5 cursor-pointer select-none">
                                    <input type="checkbox"
                                           id="{{ $groupId }}-all"
                                           class="w-3.5 h-3.5 rounded border-slate-300"
                                           data-group="{{ $groupId }}"
                                           onchange="toggleGroup('{{ $groupId }}', this.checked)"
                                           {{ $allReady ? 'checked' : '' }}>
                                    <span class="text-xs {{ $c['header'] }} font-medium">Todos</span>
                                </label>
                                @endif
                            </div>

                            {{-- Items en 4 columnas --}}
                            <div id="{{ $groupId }}" class="grid grid-cols-2 xl:grid-cols-4">
                                @foreach($group['items'] as $key => $item)
                                @php
                                    $ready   = $item['ready'];
                                    $checked = $ready && in_array($key, $currentPrivileges, true);
                                @endphp

                                @if($ready)
                                {{-- Item activo --}}
                                <label id="label-{{ $key }}"
                                       class="flex items-center gap-2 px-3 py-2 cursor-pointer border border-transparent transition-colors
                                              {{ $checked ? $c['active'] : 'hover:bg-slate-50' }}
                                              border-b border-r border-slate-100">
                                    <input type="checkbox"
                                           name="privileges[]"
                                           value="{{ $key }}"
                                           data-group="{{ $groupId }}"
                                           data-color="{{ $group['color'] }}"
                                           class="w-3.5 h-3.5 rounded border-slate-300 shrink-0"
                                           {{ $checked ? 'checked' : '' }}
                                           onchange="togglePrivilegeLabel(this)">
                                    <span class="text-xs text-slate-700 leading-tight">{{ $item['label'] }}</span>
                                </label>
                                @else
                                {{-- Item deshabilitado (próximamente) --}}
                                <div id="label-{{ $key }}"
                                     class="flex items-center gap-2 px-3 py-2 border-b border-r border-slate-100 opacity-50 cursor-not-allowed select-none">
                                    <input type="checkbox"
                                           disabled
                                           class="w-3.5 h-3.5 rounded border-slate-300 shrink-0 cursor-not-allowed">
                                    <span class="text-xs text-slate-400 leading-tight">{{ $item['label'] }}</span>
                                </div>
                                @endif

                                @endforeach
                            </div>

                        </div>
                        @endforeach

                    </div>

                    {{-- Leyenda --}}
                    <div class="flex items-center gap-4 mt-3 text-xs text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded border border-slate-300 bg-white inline-block"></span>
                            Disponible
                        </span>
                        <span class="flex items-center gap-1.5 opacity-50">
                            <span class="w-3 h-3 rounded border border-slate-300 bg-white inline-block"></span>
                            Próximamente
                        </span>
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
    // Inicializar indeterminate en los "Todos" que corresponda
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[id$="-all"]').forEach(cb => {
            const groupId = cb.dataset.group;
            if (groupId) syncGroupAllCheckbox(groupId);
        });
    });

    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon  = document.getElementById(iconId);
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye',      !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
    }

    // Muestra u oculta el bloque de privilegios según el rol seleccionado
    function togglePrivileges(roleId) {
        const section = document.getElementById('privileges-section');
        if (String(roleId) === '3') {
            section.classList.remove('hidden');
        } else {
            section.classList.add('hidden');
            section.querySelectorAll('input[name="privileges[]"]').forEach(cb => {
                cb.checked = false;
                applyLabelStyle(cb, false);
            });
        }
    }

    // Mapa de colores por nombre de color de grupo
    const COLOR_CLASSES = {
        sky:     ['bg-sky-50',     'border-sky-300'],
        emerald: ['bg-emerald-50', 'border-emerald-300'],
        violet:  ['bg-violet-50',  'border-violet-300'],
        amber:   ['bg-amber-50',   'border-amber-300'],
        indigo:  ['bg-indigo-50',  'border-indigo-300'],
        rose:    ['bg-rose-50',    'border-rose-300'],
        orange:  ['bg-orange-50',  'border-orange-300'],
        slate:   ['bg-slate-100',  'border-slate-400'],
    };

    function applyLabelStyle(checkbox, active) {
        const label = document.getElementById('label-' + checkbox.value);
        if (!label) return;
        const color  = checkbox.dataset.color || 'sky';
        const [bgCls, borderCls] = COLOR_CLASSES[color] || COLOR_CLASSES['sky'];
        if (active) {
            label.classList.add(bgCls, borderCls);
            label.classList.remove('hover:bg-slate-50');
        } else {
            label.classList.remove(bgCls, borderCls);
            label.classList.add('hover:bg-slate-50');
        }
    }

    function togglePrivilegeLabel(checkbox) {
        applyLabelStyle(checkbox, checkbox.checked);
        syncGroupAllCheckbox(checkbox.dataset.group);
    }

    // Marca/desmarca solo los checkboxes habilitados (ready) del grupo
    function toggleGroup(groupId, checked) {
        const group = document.getElementById(groupId);
        if (!group) return;
        group.querySelectorAll('input[name="privileges[]"]:not(:disabled)').forEach(cb => {
            cb.checked = checked;
            applyLabelStyle(cb, checked);
        });
    }

    // Sincroniza el estado del checkbox "Todos" según los ítems habilitados
    function syncGroupAllCheckbox(groupId) {
        if (!groupId) return;
        const group      = document.getElementById(groupId);
        const allCb      = document.getElementById(groupId + '-all');
        if (!group || !allCb) return;
        const enabledItems = Array.from(group.querySelectorAll('input[name="privileges[]"]:not(:disabled)'));
        if (enabledItems.length === 0) return;
        const checkedCount = enabledItems.filter(cb => cb.checked).length;
        allCb.checked       = checkedCount === enabledItems.length;
        allCb.indeterminate = checkedCount > 0 && checkedCount < enabledItems.length;
    }

    // Selecciona o limpia todos los privilegios habilitados
    function toggleAllPrivileges() {
        const section    = document.getElementById('privileges-section');
        const allEnabled = Array.from(section.querySelectorAll('input[name="privileges[]"]:not(:disabled)'));
        const anyChecked = allEnabled.some(cb => cb.checked);
        allEnabled.forEach(cb => {
            cb.checked = !anyChecked;
            applyLabelStyle(cb, !anyChecked);
        });
        // Sincronizar todos los "Todos"
        document.querySelectorAll('[id$="-all"]').forEach(cb => {
            if (cb.dataset.group) syncGroupAllCheckbox(cb.dataset.group);
        });
    }
</script>
@endsection
