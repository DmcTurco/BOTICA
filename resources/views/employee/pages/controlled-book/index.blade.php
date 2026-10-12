@extends('employee/layouts/base')

@section('title', 'Libro de Controlados')
@section('main-padding', 'p-2 md:p-3')

@php $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0'; @endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Libro de Controlados</h1>
            <p class="text-sm text-slate-500 mt-0.5">Psicotrópicos y estupefacientes · {{ $month->translatedFormat('F Y') }}</p>
        </div>
        <form action="{{ route('employee.controlled-book.index') }}" method="GET" class="flex items-center gap-2 flex-wrap">
            <input type="month" name="mes" value="{{ $month->format('Y-m') }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg">
            <select name="producto" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                <option value="">Todos los controlados</option>
                @foreach($controlled as $c)<option value="{{ $c->code }}" {{ $selected === $c->code ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
            <a href="{{ route('employee.controlled-book.print', request()->query()) }}" target="_blank" class="px-4 py-2 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-medium rounded-lg"><i class="fas fa-print text-xs mr-1"></i> Imprimir</a>
        </form>
    </div>

    @include('employee.partials.alerts')

    @if($controlled->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800">Aún no hay productos marcados como controlados. Márcalos en la ficha del producto (campo "Producto controlado").</div>
    @endif

    @foreach($sections as $s)
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-3 bg-slate-50 border-b border-slate-200 flex items-center justify-between flex-wrap gap-2">
            <p class="text-sm font-semibold text-slate-800">{{ $s['product']->name }} <span class="ml-1 inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-50 text-red-600">{{ \App\Models\Product::CONTROLLED_LABELS[$s['product']->controlled_type] }}</span></p>
            <p class="text-xs text-slate-500">Saldo inicial <strong class="text-slate-800">{{ $fmt($s['opening']) }}</strong> · Saldo final <strong class="text-slate-800">{{ $fmt($s['closing']) }}</strong></p>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-slate-200">
                <th class="text-left px-5 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                <th class="text-left px-5 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Documento</th>
                <th class="text-left px-5 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Paciente / médico / receta</th>
                <th class="text-center px-5 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Entrada</th>
                <th class="text-center px-5 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Salida</th>
                <th class="text-center px-5 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">Saldo</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($s['rows'] as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2 text-xs text-slate-600">{{ $r['date']->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-2 text-xs text-slate-700">{{ $r['label'] }}</td>
                    <td class="px-5 py-2 text-xs text-slate-600 hidden md:table-cell">
                        @if($r['rx']){{ $r['rx']->patient_name }} ({{ $r['rx']->patient_document }}) · Dr(a). {{ $r['rx']->doctor_name }} CMP {{ $r['rx']->doctor_license }} · Receta {{ $r['rx']->prescription_number }}@else —@endif
                    </td>
                    <td class="px-5 py-2 text-center text-xs font-semibold text-emerald-700">{{ $r['in'] ? '+' . $fmt($r['in']) : '' }}</td>
                    <td class="px-5 py-2 text-center text-xs font-semibold text-red-600">{{ $r['out'] ? '−' . $fmt($r['out']) : '' }}</td>
                    <td class="px-5 py-2 text-center text-xs font-bold text-slate-800">{{ $fmt($r['balance']) }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">Sin movimientos en el mes</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
    @endforeach
</div>
@endsection
