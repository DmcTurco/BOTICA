@extends('company/layouts/base', ['elementActive' => 'branches'])

@section('title', 'Sedes')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Sedes</h1>
            <p class="text-sm text-slate-500 mt-0.5">Administra las sucursales de tu farmacia</p>
        </div>
        <a href="{{ route('company.branches.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i>
            Nueva Sede
        </a>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        <div class="shrink-0 flex items-center justify-between px-5 py-3 border-b border-slate-100 bg-slate-50">
            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                {{ $branches->count() }} {{ $branches->count() === 1 ? 'sede' : 'sedes' }}
            </p>
        </div>

        @if($branches->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-16">
            <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                <i class="fas fa-building text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-medium text-slate-500">Sin sedes registradas</p>
            <p class="text-xs text-slate-400 mt-1">Crea tu primera sede para comenzar</p>
            <a href="{{ route('company.branches.create') }}"
               class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors">
                <i class="fas fa-plus text-xs"></i> Nueva Sede
            </a>
        </div>
        @else
        <div class="overflow-auto flex-1">
            <table class="w-full text-sm">
                <thead class="sticky top-0 bg-white border-b border-slate-100 z-10">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Nombre</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Dirección</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Teléfono</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Email</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Estado</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($branches as $branch)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="fas fa-building text-emerald-600 text-xs"></i>
                                </div>
                                <span class="font-semibold text-slate-800">{{ $branch->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $branch->address ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $branch->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 text-xs">{{ $branch->email ?: '—' }}</td>
                        <td class="px-4 py-3 text-center">
                            @if($branch->status)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> Activa
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400 inline-block"></span> Inactiva
                            </span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('company.branches.edit', $branch) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-sky-50 hover:border-sky-200 hover:text-sky-600 transition-colors"
                                   title="Editar">
                                    <i class="fas fa-pen text-xs"></i>
                                </a>
                                <form method="POST" action="{{ route('company.branches.destroy', $branch) }}"
                                      onsubmit="return confirm('¿Eliminar la sede {{ addslashes($branch->name) }}?')">
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
        @endif

    </div>

</div>
@endsection
