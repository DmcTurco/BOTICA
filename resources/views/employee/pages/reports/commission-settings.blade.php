@extends('employee/layouts/base')

@section('title', 'Administrar Comisiones')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto max-w-3xl w-full mx-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Administrar Comisiones</h1>
        <p class="text-sm text-slate-500 mt-0.5">Porcentaje que gana cada vendedor sobre su venta neta. Déjalo en 0 si no recibe comisión.</p>
    </div>

    @include('employee.partials.alerts')

    <form action="{{ route('employee.commissions.update') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @csrf @method('PUT')
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Empleado</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Correo</th>
                <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider w-40">Comisión (%)</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($employees as $e)
                <tr>
                    <td class="px-5 py-2.5 text-xs font-medium text-slate-800">{{ $e->name }}</td>
                    <td class="px-5 py-2.5 text-xs text-slate-500 hidden sm:table-cell">{{ $e->email }}</td>
                    <td class="px-5 py-2.5 text-right"><input type="number" name="rates[{{ $e->id }}]" min="0" max="100" step="0.01" value="{{ old('rates.' . $e->id, (float) $e->commission_rate) }}" class="w-28 px-2 py-1.5 text-sm text-right border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex justify-end">
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center gap-2"><i class="fas fa-save text-xs"></i> Guardar</button>
        </div>
    </form>
</div>
@endsection
