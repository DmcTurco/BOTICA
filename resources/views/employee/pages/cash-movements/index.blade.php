@extends('employee/layouts/base')

@section('title', 'Gastos e Ingresos de Caja')
@section('main-padding', 'p-2 md:p-3')

@php
    $money = fn ($n) => 'S/ ' . number_format($n, 2);
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header: el registro se hace en su propio formulario --}}
    <div class="flex items-center justify-between gap-3 shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Gastos e Ingresos de Caja</h1>
            <p class="text-sm text-slate-500 mt-0.5">Movimientos de efectivo que no son ventas. Se reflejan en el efectivo esperado al cerrar la caja.</p>
        </div>
        @if($caja)
        <a href="{{ route('employee.cash-movements.create') }}"
           class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nuevo movimiento
        </a>
        @endif
    </div>

    @include('employee.partials.alerts')

    @if($caja)
    {{-- Resumen de la caja abierta del empleado --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 shrink-0">
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Apertura</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->opening_amount) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Ventas en efectivo</p><p class="text-lg font-bold text-slate-800">{{ $money($caja->totalCash()) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Otros ingresos</p><p class="text-lg font-bold text-emerald-700">+ {{ $money($caja->otherIncome()) }}</p></div>
        <div class="bg-white rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-400">Gastos</p><p class="text-lg font-bold text-red-600">− {{ $money($caja->expenses()) }}</p></div>
        <div class="bg-sky-600 rounded-xl p-4 text-white"><p class="text-xs text-sky-200">Efectivo en caja</p><p class="text-lg font-bold">{{ $money($caja->expectedCash()) }}</p></div>
    </div>
    @else
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 flex items-center gap-2 shrink-0">
        <i class="fas fa-lock"></i> No tienes una caja abierta. <a href="{{ route('employee.cash-register.show-open') }}" class="underline font-medium">Abre tu caja</a> para registrar movimientos.
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.cash-movements.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div><label class="block text-xs text-slate-500 mb-1">Desde</label><input type="date" name="fecha_desde" value="{{ $desde }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <div><label class="block text-xs text-slate-500 mb-1">Hasta</label><input type="date" name="fecha_hasta" value="{{ $hasta }}" class="px-3 py-2 text-sm border border-slate-300 rounded-lg"></div>
            <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-filter text-xs"></i> Filtrar</button>
        </form>
    </div>

    {{-- Tabla: ocupa el alto disponible y hace scroll por dentro --}}
    <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Fecha</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Concepto</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Tipo</th>
                        <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Monto</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Registró</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $m)
                    <tr class="hover:bg-slate-50 {{ $m->status ? '' : 'opacity-50' }}">
                        <td class="px-5 py-3 text-xs text-slate-600">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3"><p class="text-xs font-medium text-slate-800 {{ $m->status ? '' : 'line-through' }}">{{ $m->concept }}</p>@if($m->notes)<p class="text-[10px] text-slate-400">{{ $m->notes }}</p>@endif</td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $m->type === 'income' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600' }}">{{ \App\Models\CashMovement::TYPE_LABELS[$m->type] }}</span>
                            @unless($m->status)<span class="block text-[10px] text-slate-400">anulado{{ $m->void_reason ? ': ' . $m->void_reason : '' }}</span>@endunless
                        </td>
                        <td class="px-5 py-3 text-right text-xs font-semibold {{ $m->type === 'income' ? 'text-emerald-700' : 'text-red-600' }}">{{ $m->type === 'income' ? '+' : '−' }} {{ $money($m->amount) }}</td>
                        <td class="px-5 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $m->employee?->name }}</td>
                        <td class="px-5 py-3 text-right">
                            @if($m->status && $myRegister && $m->cash_register_id === $myRegister)
                            <button type="button" class="btn-anular w-8 h-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" title="Anular"
                                    data-action="{{ route('employee.cash-movements.void', $m) }}" data-concepto="{{ $m->concept }}" data-monto="{{ $money($m->amount) }}">
                                <i class="fas fa-ban text-xs pointer-events-none"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-slate-400">No hay movimientos en el rango seleccionado</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())<div class="shrink-0 px-5 py-3 border-t border-slate-200 bg-slate-50">{{ $movements->links() }}</div>@endif
    </div>
</div>

{{-- Anular un movimiento: pide el motivo --}}
<div id="modalAnularMov" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form id="formAnularMov" method="POST" class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
        @csrf
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-ban text-red-600 text-sm"></i></div>
            <h3 class="text-base font-semibold text-slate-800">Anular movimiento</h3>
        </div>
        <p class="text-sm text-slate-600">Se anulará <strong id="anularConcepto" class="text-slate-800"></strong> por <strong id="anularMonto" class="text-slate-800"></strong> y dejará de contar en el cuadre de la caja.</p>
        <input type="text" name="void_reason" maxlength="255" placeholder="Motivo de la anulación"
               class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-400">
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="document.getElementById('modalAnularMov').style.setProperty('display','none','important')" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Anular</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-anular').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var form = document.getElementById('formAnularMov');
        form.action = btn.dataset.action;
        form.querySelector('[name=void_reason]').value = '';
        document.getElementById('anularConcepto').textContent = '«' + btn.dataset.concepto + '»';
        document.getElementById('anularMonto').textContent = btn.dataset.monto;
        document.getElementById('modalAnularMov').style.setProperty('display', 'flex', 'important');
    });
});
</script>
@endsection
