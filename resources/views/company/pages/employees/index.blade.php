@extends('company/layouts/base', ['elementActive' => 'employees'])

@section('title', 'Empleados')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Empleados</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gestiona el personal de todas tus sedes</p>
        </div>
        <a href="{{ route('company.employees.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            Nuevo Empleado
        </a>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="shrink-0 flex flex-wrap items-end gap-3 bg-white border border-slate-200 rounded-xl px-4 py-3 shadow-sm">
        <div class="flex-1 min-w-40">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Buscar</label>
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                       placeholder="Nombre o email..."
                       class="w-full pl-8 pr-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
        </div>
        <div class="min-w-40">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Sede</label>
            <select name="sede" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                <option value="">Todas las sedes</option>
                @foreach($branches as $branch)
                <option value="{{ $branch->id }}" {{ request('sede') == $branch->id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="min-w-36">
            <label class="block text-xs font-semibold text-slate-500 mb-1">Rol</label>
            <select name="rol" class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 bg-white">
                <option value="">Todos los roles</option>
                @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ request('rol') == $role->id ? 'selected' : '' }}>
                    {{ $role->description }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Filtrar
            </button>
            @if(request()->hasAny(['buscar','sede','rol']))
            <a href="{{ route('company.employees.index') }}"
               class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                Limpiar
            </a>
            @endif
        </div>
    </form>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="shrink-0 flex items-center justify-between px-5 py-3 border-b border-slate-100 bg-slate-50">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                {{ $employees->total() }} {{ $employees->total() === 1 ? 'empleado' : 'empleados' }}
            </p>
            <p class="text-xs text-slate-400">Página {{ $employees->currentPage() }} de {{ $employees->lastPage() }}</p>
        </div>

        @if($employees->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-16">
            <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                <i class="fas fa-user-slash text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-medium text-slate-500">Sin empleados registrados</p>
            <p class="text-xs text-slate-400 mt-1">Crea el primer empleado para tu farmacia</p>
            <a href="{{ route('company.employees.create') }}"
               class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <i class="fas fa-plus text-xs"></i> Nuevo Empleado
            </a>
        </div>
        @else
        <div class="overflow-auto flex-1">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white border-b border-slate-100 z-10">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Empleado</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Sede</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Rol</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($employees as $employee)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-emerald-100 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-sm font-bold text-emerald-700">
                                        {{ strtoupper(substr($employee->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $employee->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                                <i class="fas fa-building text-slate-300"></i>
                                {{ $employee->branch->name ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $roleColors = [
                                    2 => 'bg-violet-100 text-violet-700',
                                    3 => 'bg-sky-100 text-sky-700',
                                ];
                                $roleNames = [
                                    2 => 'Admin Sede',
                                    3 => 'Empleado',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $roleColors[$employee->role_id] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ $roleNames[$employee->role_id] ?? $employee->role->name }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('company.employees.edit', $employee) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-sky-50 hover:border-sky-200 hover:text-sky-600 transition-colors"
                                   title="Editar">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                <form method="POST" action="{{ route('company.employees.destroy', $employee) }}"
                                      onsubmit="return confirm('¿Eliminar a {{ addslashes($employee->name) }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 hover:bg-red-50 hover:border-red-200 hover:text-red-500 transition-colors"
                                            title="Eliminar">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        @if($employees->hasPages())
        <div class="shrink-0 px-5 py-3 border-t border-slate-100">
            {{ $employees->links() }}
        </div>
        @endif
        @endif

    </div>

</div>
@endsection
