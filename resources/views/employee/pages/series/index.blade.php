@extends('employee/layouts/base')

@section('title', 'Correlativos')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto max-w-5xl w-full mx-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Series y Correlativos</h1>
        <p class="text-sm text-slate-500 mt-0.5">Series de comprobantes de tu sede. El correlativo solo puede avanzar, nunca retroceder.</p>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-800 shrink-0">
        <i class="fas fa-triangle-exclamation mr-1"></i> Avanzar el correlativo deja números sin usar. Hazlo solo si migras desde otro sistema o necesitas empezar desde un número determinado. SUNAT valida la serie y el número de cada comprobante.
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Comprobante</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Serie</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Último usado</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Siguiente</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Activa</th>
                <th class="px-5 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($series as $s)
                <tr>
                    <td class="px-5 py-3 text-xs font-medium text-slate-800">
                        {{ $s->name }}
                        {{-- El formulario va fuera de la fila; los campos lo referencian con el atributo form --}}
                        <form id="fs{{ $s->id }}" action="{{ route('employee.series.update', $s) }}" method="POST">@csrf @method('PUT')</form>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs text-slate-700">{{ $s->series }}</td>
                    <td class="px-5 py-3"><input form="fs{{ $s->id }}" type="number" name="current_number" value="{{ $s->current_number }}" min="{{ $s->current_number }}" class="w-32 px-2 py-1.5 text-xs border border-slate-300 rounded-lg font-mono"></td>
                    <td class="px-5 py-3 font-mono text-xs text-slate-500 hidden md:table-cell">{{ $s->series }}-{{ str_pad($s->current_number + 1, $s->digits, '0', STR_PAD_LEFT) }}</td>
                    <td class="px-5 py-3 text-center">
                        <select form="fs{{ $s->id }}" name="active" class="px-2 py-1.5 text-xs border border-slate-300 rounded-lg bg-white">
                            <option value="1" {{ $s->active ? 'selected' : '' }}>Sí</option>
                            <option value="0" {{ !$s->active ? 'selected' : '' }}>No</option>
                        </select>
                    </td>
                    <td class="px-5 py-3 text-right"><button form="fs{{ $s->id }}" type="submit" class="px-3 py-1.5 text-xs font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg">Guardar</button></td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-slate-400">Tu sede aún no tiene series configuradas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
