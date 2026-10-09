@extends('employee/layouts/base')

@section('title', 'Cierres de Caja')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
    $canPrint = auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_IMPRIMIR_MOV_CAJA);
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Cierres de Caja</h1>
            <p class="text-sm text-slate-500 mt-0.5">Cajas de tu sede con su efectivo esperado, contado y diferencia</p>
        </div>
        @if($canPrint)
        <form action="{{ route('employee.cash-closures.print') }}" method="GET" target="_blank" class="flex items-center gap-2">
            <input type="date" name="fecha" value="{{ today()->toDateString() }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg">
                <i class="fas fa-print text-xs"></i> Imprimir movimiento del día
            </button>
        </form>
        @endif
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.cash-closures.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-end flex-wrap">
            <div><label class="block text-xs text-slate-500 mb-1">Desde</label><input type="date" name="fecha_desde" value="{{ $desde }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="block text-xs text-slate-500 mb-1">Hasta</label><input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Cajero</label>
                <select name="empleado" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                    <option value="">Todos</option>
                    @foreach($employees as $e)<option value="{{ $e->id }}" {{ request('empleado') == $e->id ? 'selected' : '' }}>{{ $e->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 mb-1">Estado</label>
                <select name="estado" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                    <option value="">Todas</option>
                    <option value="cerradas" {{ request('estado') === 'cerradas' ? 'selected' : '' }}>Cerradas</option>
                    <option value="abiertas" {{ request('estado') === 'abiertas' ? 'selected' : '' }}>Abiertas</option>
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600 pb-2"><input type="checkbox" name="con_diferencia" value="1" {{ request()->boolean('con_diferencia') ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600"> Solo con diferencia</label>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Cajero</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Apertura</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Ventas</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Esperado</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Contado</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Diferencia</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($registers as $r)
                    @php $diff = (float) ($r->difference ?? 0); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-700">{{ $r->register_date->format('d/m/Y') }}<p class="text-[10px] text-slate-400">#{{ $r->id }}</p></td>
                        <td class="px-5 py-3 text-xs text-slate-800">{{ $r->employee?->name }}</td>
                        <td class="px-5 py-3 text-right text-xs text-slate-600 hidden md:table-cell">{{ $money($r->opening_amount) }}</td>
                        <td class="px-5 py-3 text-right text-xs font-medium text-slate-800">{{ $money($r->sales_total) }}</td>
                        <td class="px-5 py-3 text-right text-xs text-slate-600 hidden lg:table-cell">{{ $r->status ? '—' : $money($r->expected_amount ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-xs text-slate-600 hidden lg:table-cell">{{ $r->closing_amount !== null ? $money($r->closing_amount) : '—' }}</td>
                        <td class="px-5 py-3 text-right text-xs font-semibold {{ $r->status ? 'text-slate-300' : ($diff < 0 ? 'text-red-600' : ($diff > 0 ? 'text-amber-600' : 'text-emerald-700')) }}">
                            {{ $r->status ? '—' : (($diff > 0 ? '+' : '') . number_format($diff, 2)) }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $r->status ? 'bg-sky-50 text-sky-700' : 'bg-slate-100 text-slate-600' }}">{{ $r->status ? 'Abierta' : 'Cerrada' }}</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('employee.cash-closures.show', $r) }}" class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600" title="Ver detalle"><i class="fas fa-eye text-xs"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="px-5 py-14 text-center text-sm text-slate-400">No hay cajas en el rango seleccionado</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($registers->hasPages())<div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $registers->links() }}</div>@endif
    </div>
</div>
@endsection
