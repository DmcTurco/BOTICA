@extends('admin/layouts/base')

@section('title', 'Empresas')
@section('main-class', 'overflow-hidden')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between gap-3 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Empresas</h1>
            <p class="text-sm text-slate-500 mt-0.5">Boticas clientes del sistema y su usuario de acceso</p>
        </div>
        <a href="{{ route('admin.companies.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-violet-700 hover:bg-violet-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm shrink-0">
            <i class="fas fa-plus text-xs"></i>
            Nueva Empresa
        </a>
    </div>

    {{-- Buscador --}}
    <form method="GET" action="{{ route('admin.companies.index') }}"
          class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm flex gap-3 shrink-0">
        <div class="flex-1 relative">
            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, correo o RUC..."
                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-violet-500">
        </div>
        <button type="submit" class="px-4 py-2 bg-violet-700 hover:bg-violet-800 text-white text-sm font-medium rounded-lg flex items-center gap-2">
            <i class="fas fa-search text-xs"></i> Buscar
        </button>
    </form>

    {{-- Tabla (ocupa el alto restante; el scroll va adentro) --}}
    <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($companies->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center py-16">
            <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mb-3">
                <i class="fas fa-building text-slate-300 text-xl"></i>
            </div>
            <p class="text-sm font-medium text-slate-500">Sin empresas registradas</p>
            <p class="text-xs text-slate-400 mt-1">Registra la primera botica cliente</p>
        </div>
        @else
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Empresa</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Usuario (correo)</th>
                        <th class="text-left px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">RUC</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Sedes</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Empleados</th>
                        <th class="text-center px-4 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Productos</th>                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-400 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($companies as $company)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="fas fa-building text-violet-700 text-xs"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800">{{ $company->name }}</p>
                                    <p class="text-xs text-slate-400">Desde {{ $company->created_at?->format('d/m/Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">{{ $company->email }}</td>
                        <td class="px-4 py-3 text-slate-500 font-mono text-xs">{{ $company->ruc ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $company->branches_count }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $company->employees_count }}</td>
                        <td class="px-4 py-3 text-center text-slate-700">{{ $company->products_count }}</td>
                        <td class="px-5 py-3 text-center whitespace-nowrap">
                            <a href="{{ route('admin.companies.edit', $company) }}"
                               class="inline-flex w-8 h-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-violet-50 hover:border-violet-200 transition-colors"
                               title="Editar">
                                <i class="fas fa-pen text-xs"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($companies->hasPages())
        <div class="shrink-0 px-5 py-3 border-t border-slate-100">
            {{ $companies->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
