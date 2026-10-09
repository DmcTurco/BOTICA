@extends('employee/layouts/base')

@section('title', 'Lotes y Vencimientos')
@section('main-padding', 'p-2 md:p-3')

@php
    $fmt = fn ($n) => rtrim(rtrim(number_format($n, 2), '0'), '.') ?: '0';
    $canWriteOff = auth()->guard('employee')->user()->hasPrivilege(\App\Models\Employee::PRIV_AJUSTE_INVENTARIO);
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Lotes y Vencimientos</h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ $all ? 'Todos los lotes con stock en tu sede' : 'Lotes vencidos o que vencen en los próximos ' . $days . ' días' }}
            </p>
        </div>
        <form action="{{ route('employee.reports.expiring') }}" method="GET" class="flex items-center gap-2">
            <select name="dias" class="px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white">
                @foreach([30, 60, 90, 180, 365] as $n)<option value="{{ $n }}" {{ !$all && $days === $n ? 'selected' : '' }}>Vencen en {{ $n }} días</option>@endforeach
                <option value="todos" {{ $all ? 'selected' : '' }}>Todos los lotes</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg"><i class="fas fa-filter text-xs mr-1"></i> Ver</button>
        </form>
    </div>

    @include('employee.partials.alerts')
    @include('employee.partials.export-button')

    <div class="grid grid-cols-2 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Lotes listados</p><p class="text-lg font-bold text-slate-800">{{ $rows->count() }}</p></div>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4"><p class="text-xs text-red-600">Ya vencidos</p><p class="text-lg font-bold text-red-700">{{ $expired }}</p></div>
    </div>

    <p class="text-xs text-slate-400 shrink-0"><i class="fas fa-circle-info mr-1"></i> Las ventas salen primero del lote que vence antes y nunca de un lote vencido. Las unidades vencidas siguen contando en el stock hasta que las des de baja.</p>

    <div class="flex-1 min-h-0 overflow-auto bg-white rounded-xl border border-slate-200 shadow-sm">
        <table class="w-full text-sm">
            <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Producto</th>
                <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Lote / origen</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Quedan</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Vence</th>
                <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                <th class="px-5 py-3"></th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-2.5"><p class="text-xs font-medium text-slate-800">{{ $r['name'] }}</p><p class="text-[10px] text-slate-400">Stock total: {{ $fmt($r['stock']) }}</p></td>
                    <td class="px-5 py-2.5 text-xs text-slate-600 hidden sm:table-cell">
                        <span class="font-mono">{{ $r['batch'] ?: 'sin lote' }}</span>
                        <p class="text-[10px] text-slate-400">@if($r['link'])<a href="{{ $r['link'] }}" class="hover:text-emerald-600">{{ $r['origin'] }}</a>@else{{ $r['origin'] }}@endif</p>
                    </td>
                    <td class="px-5 py-2.5 text-center text-xs font-semibold text-slate-700">{{ $fmt($r['qty']) }}</td>
                    <td class="px-5 py-2.5 text-center text-xs text-slate-700">{{ $r['expires'] ? $r['expires']->format('d/m/Y') : '—' }}</td>
                    <td class="px-5 py-2.5 text-center">
                        @if($r['days'] === null)<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">No vence</span>
                        @elseif($r['days'] < 0)<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Vencido hace {{ abs($r['days']) }} d</span>
                        @elseif($r['days'] <= 30)<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">Vence en {{ $r['days'] }} d</span>
                        @else<span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Vence en {{ $r['days'] }} d</span>@endif
                    </td>
                    <td class="px-5 py-2.5 text-right">
                        @if($canWriteOff && $r['days'] !== null && $r['days'] < 0)
                        <button type="button" class="btn-baja px-3 py-1.5 text-xs font-medium text-red-600 bg-red-50 hover:bg-red-100 rounded-lg"
                                data-action="{{ route('employee.batches.write-off', $r['id']) }}" data-name="{{ $r['name'] }}" data-batch="{{ $r['batch'] ?: 'sin lote' }}" data-qty="{{ $fmt($r['qty']) }}">
                            Dar de baja
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400"><i class="fas fa-circle-check text-3xl text-emerald-400 mb-2"></i><p>No hay lotes en el período</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Confirmación de baja --}}
<div id="modalBaja" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form id="formBaja" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        @csrf
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-trash text-red-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Dar de baja lote vencido</h3>
        </div>
        <p class="text-sm text-slate-600" id="textoBaja"></p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('modalBaja').style.setProperty('display','none','important')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Dar de baja</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-baja').forEach(btn => btn.addEventListener('click', () => {
    document.getElementById('formBaja').action = btn.dataset.action;
    document.getElementById('textoBaja').innerHTML =
        `Se retirarán <strong>${btn.dataset.qty}</strong> unidades vencidas de <strong>${btn.dataset.name}</strong> (lote ${btn.dataset.batch}). Quedará un ajuste de salida en el Kardex.`;
    document.getElementById('modalBaja').style.setProperty('display', 'flex', 'important');
}));
</script>
@endsection
