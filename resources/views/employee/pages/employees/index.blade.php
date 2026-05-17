@extends('employee/layouts/base', ['elementActive' => 'employees'])

@section('title', 'Empleados de mi Sede')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Empleados de mi Sede</h1>
            <p class="text-sm text-slate-500 mt-0.5">Gestiona el personal asignado a tu sede</p>
        </div>
        <a href="{{ route('employee.employees.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            Nuevo Empleado
        </a>
    </div>

    {{-- Mensajes flash --}}
    @if(session('success'))
    <div class="shrink-0 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl px-4 py-3 text-sm">
        <i class="fas fa-circle-check shrink-0"></i>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="shrink-0 flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
        <i class="fas fa-circle-exclamation shrink-0"></i>
        {{ session('error') }}
    </div>
    @endif

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
            <p class="text-xs text-slate-400 mt-1">Crea el primer empleado para esta sede</p>
            <a href="{{ route('employee.employees.create') }}"
               class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <i class="fas fa-plus text-xs"></i> Nuevo Empleado
            </a>
        </div>
        @else
        <div class="overflow-auto flex-1">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white border-b border-slate-100 z-10">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Empleado</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Rol</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($employees as $employee)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-sky-100 rounded-full flex items-center justify-center shrink-0">
                                    <span class="text-sm font-bold text-sky-700">
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
                                <a href="{{ route('employee.employees.edit', $employee) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-sky-50 hover:border-sky-200 hover:text-sky-600 transition-colors"
                                   title="Editar">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                <form method="POST" action="{{ route('employee.employees.destroy', $employee) }}"
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

        @if($employees->hasPages())
        <div class="shrink-0 px-5 py-3 border-t border-slate-100">
            {{ $employees->links() }}
        </div>
        @endif
        @endif

    </div>

</div>
@endsection
