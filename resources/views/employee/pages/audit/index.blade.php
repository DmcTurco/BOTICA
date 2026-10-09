@extends('employee/layouts/base')

@section('title', 'Bitácora')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Bitácora de Actividad</h1>
        <p class="text-sm text-slate-500 mt-0.5">Quién anuló, ajustó, cambió precios, dio descuentos o modificó permisos</p>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.audit.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-end flex-wrap">
            <div class="flex-1 relative min-w-48">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar en la descripción..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <select name="grupo" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todas las acciones</option>
                @foreach($groups as $g)<option value="{{ $g }}" {{ request('grupo') === $g ? 'selected' : '' }}>{{ $g }}</option>@endforeach
            </select>
            <select name="empleado" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los empleados</option>
                @foreach($employees as $e)<option value="{{ $e->id }}" {{ request('empleado') == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>@endforeach
            </select>
            <input type="date" name="fecha_desde" value="{{ $desde }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cuándo</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Quién</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Qué hizo</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Detalle</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-5 py-2.5 text-xs text-slate-600 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}<p class="text-[10px] text-slate-400">{{ $log->ip }}</p></td>
                        <td class="px-5 py-2.5 text-xs font-medium text-slate-800">{{ $log->employee_name ?: '—' }}</td>
                        <td class="px-5 py-2.5"><p class="text-xs text-slate-800">{{ $log->description }}</p>@if($log->subject)<p class="text-[10px] text-slate-400">{{ $log->subject }}</p>@endif</td>
                        <td class="px-5 py-2.5 text-[11px] text-slate-500 hidden lg:table-cell">
                            @foreach(($log->details ?? []) as $key => $value)<span class="inline-block mr-2"><span class="text-slate-400">{{ $key }}:</span> {{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value }}</span>@endforeach
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-5 py-14 text-center text-sm text-slate-400">No hay actividad registrada con esos filtros</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $logs->links() }}</div>@endif
    </div>
</div>
@endsection
