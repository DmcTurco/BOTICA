@extends('employee/layouts/base')

@section('title', 'Registro de Recetas')
@section('main-padding', 'p-2 md:p-3')

@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0'; @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Registro de Recetas Médicas</h1>
        <p class="text-sm text-slate-500 mt-0.5">Recetas despachadas en tu sede: paciente, médico y productos entregados</p>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.prescriptions.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-end flex-wrap">
            <div class="flex-1 relative min-w-48">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Paciente, médico, CMP o N° de receta..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <input type="date" name="fecha_desde" value="{{ $desde }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Paciente</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Médico</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Productos con receta</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Venta</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($prescriptions as $p)
                    <tr class="hover:bg-slate-50 align-top">
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $p->created_at->format('d/m/Y H:i') }}<p class="text-[10px] text-slate-400">receta del {{ $p->prescription_date->format('d/m/Y') }}</p></td>
                        <td class="px-5 py-3"><p class="text-xs font-medium text-slate-800">{{ $p->patient_name }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $p->patient_document ?: 'sin documento' }}</p></td>
                        <td class="px-5 py-3 hidden md:table-cell"><p class="text-xs text-slate-700">{{ $p->doctor_name }}</p><p class="text-[10px] text-slate-400">CMP {{ $p->doctor_license }}@if($p->prescription_number) · N° {{ $p->prescription_number }}@endif</p></td>
                        <td class="px-5 py-3">
                            @foreach($p->recipe_items as $i)
                            <p class="text-xs text-slate-700">{{ $fmt($i['qty']) }} × {{ $i['name'] }} @if($i['controlled'])<span class="inline-flex px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-red-50 text-red-600">controlado</span>@endif</p>
                            @endforeach
                        </td>
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono hidden lg:table-cell">{{ $p->order?->voucher_number }}<p class="font-sans text-[10px] text-slate-400">{{ $p->employee?->name }}</p></td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-slate-400">No hay recetas registradas en el rango</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($prescriptions->hasPages())<div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $prescriptions->links() }}</div>@endif
    </div>
</div>
@endsection
