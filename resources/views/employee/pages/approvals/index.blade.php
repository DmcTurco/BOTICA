@extends('employee/layouts/base', ['elementActive' => 'approvals'])

@section('title', 'Aprobación de Cajas Históricas')
@section('main-padding', 'p-2 md:p-4')

@section('content-area')
<div class="flex-1 flex flex-col gap-4 min-h-0">

    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <i class="fas fa-vault text-emerald-600 text-lg"></i>
            Aprobación de Cajas Históricas
        </h1>
        <p class="text-sm text-slate-500 mt-0.5">
            Revisa y valida las cajas registradas en fechas pasadas por los empleados.
        </p>
    </div>

    {{-- ── Alertas ──────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-sm text-emerald-700 flex items-center gap-2 shrink-0">
        <i class="fas fa-circle-check text-emerald-500"></i>
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700 flex items-center gap-2 shrink-0">
        <i class="fas fa-circle-exclamation text-red-400"></i>
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-sm text-red-700 space-y-1 shrink-0">
        @foreach($errors->all() as $error)
            <p><i class="fas fa-circle-exclamation mr-1.5 text-red-400"></i>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    {{-- ── CAJAS PENDIENTES ─────────────────────────────────────── --}}
    <div class="shrink-0">
        <h2 class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-amber-500 inline-block"></span>
            Pendientes de revisión
            @if($pending->isNotEmpty())
            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">{{ $pending->count() }}</span>
            @endif
        </h2>

        @if($pending->isEmpty())
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center text-slate-400">
            <i class="fas fa-circle-check text-5xl mb-3 text-emerald-400 opacity-60"></i>
            <p class="text-sm font-medium text-slate-500">No hay cajas pendientes de aprobación</p>
            <p class="text-xs mt-1">¡Todo al día!</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($pending as $caja)
            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm overflow-hidden">

                {{-- Cabecera de la caja --}}
                <div class="flex items-center justify-between px-4 py-3 bg-amber-50 border-b border-amber-100">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center shrink-0">
                            <i class="fas fa-clock text-amber-600 text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">
                                {{ $caja->register_date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                            </p>
                            <p class="text-xs text-slate-500">
                                Empleado: <span class="font-semibold text-slate-700">{{ $caja->employee->name }}</span>
                                &nbsp;·&nbsp; Apertura: S/ {{ number_format($caja->opening_amount, 2) }}
                                &nbsp;·&nbsp; Cerrada: {{ $caja->closed_at?->format('d/m/Y H:i') ?? '—' }}
                            </p>
                        </div>
                    </div>

                    {{-- Total ventas --}}
                    <div class="text-right shrink-0">
                        <p class="text-xs text-slate-400">Total ventas</p>
                        <p class="text-lg font-black text-slate-800">
                            S/ {{ number_format($caja->totalOrders(), 2) }}
                        </p>
                    </div>
                </div>

                {{-- Tabla de órdenes --}}
                @php $ordenes = $caja->orders->where('status', 1); @endphp
                @if($ordenes->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50">
                                <th class="px-4 py-2 text-left font-semibold">Comprobante</th>
                                <th class="px-4 py-2 text-left font-semibold">Cliente</th>
                                <th class="px-4 py-2 text-left font-semibold">Hora</th>
                                <th class="px-4 py-2 text-left font-semibold">Ítems</th>
                                <th class="px-4 py-2 text-right font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach($ordenes->sortByDesc('created_at') as $orden)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <span class="font-semibold text-slate-700">{{ $orden->voucher_number ?? '—' }}</span>
                                    <span class="ml-1 text-slate-400">
                                        {{ match($orden->voucher_type ?? 0) {
                                            1 => 'Boleta',
                                            2 => 'Factura',
                                            default => 'Nota'
                                        } }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-600">{{ $orden->client?->name ?? 'Sin cliente' }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $orden->created_at->format('H:i') }}</td>
                                <td class="px-4 py-2.5 text-slate-500">{{ $orden->items->count() }} ítem(s)</td>
                                <td class="px-4 py-2.5 text-right font-bold text-slate-800">S/ {{ number_format($orden->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-50 border-t border-slate-200">
                                <td colspan="4" class="px-4 py-2 text-xs font-bold text-slate-500 text-right uppercase tracking-wider">Total caja</td>
                                <td class="px-4 py-2 text-right font-black text-slate-800">
                                    S/ {{ number_format($caja->totalOrders(), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @else
                <div class="px-4 py-4 text-xs text-slate-400 italic">Sin ventas registradas en esta caja.</div>
                @endif

                {{-- Botones de acción --}}
                <div class="flex items-center justify-end gap-2 px-4 py-3 bg-slate-50 border-t border-slate-100">
                    <button type="button"
                            data-action="{{ route('employee.approvals.reject', $caja) }}"
                            data-fecha="{{ $caja->register_date->format('d/m/Y') }}"
                            data-empleado="{{ $caja->employee->name }}"
                            class="btn-rechazar inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white hover:bg-red-50 text-red-700 text-xs font-bold transition-colors border border-red-200">
                        <i class="fas fa-xmark"></i> Rechazar
                    </button>
                    <button type="button"
                            data-action="{{ route('employee.approvals.approve', $caja) }}"
                            data-fecha="{{ $caja->register_date->format('d/m/Y') }}"
                            data-empleado="{{ $caja->employee->name }}"
                            class="btn-aprobar inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors shadow-sm">
                        <i class="fas fa-circle-check"></i> Aprobar caja
                    </button>
                </div>

            </div>{{-- /caja pendiente --}}
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── HISTORIAL RECIENTE ───────────────────────────────────── --}}
    @if($recentHistory->isNotEmpty())
    <div class="shrink-0">
        <h2 class="text-sm font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-slate-400 inline-block"></span>
            Historial reciente
        </h2>

        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-slate-500 uppercase tracking-wider border-b border-slate-100 bg-slate-50">
                        <th class="px-4 py-2.5 text-left font-semibold">Fecha</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Empleado</th>
                        <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                        <th class="px-4 py-2.5 text-center font-semibold">Estado</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Revisada por</th>
                        <th class="px-4 py-2.5 text-left font-semibold">Motivo rechazo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($recentHistory as $caja)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 text-xs text-slate-700 font-semibold">
                            {{ $caja->register_date->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $caja->employee->name }}</td>
                        <td class="px-4 py-3 text-xs text-right font-bold text-slate-800">
                            S/ {{ number_format($caja->expected_amount ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($caja->isApproved())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                <i class="fas fa-circle-check text-[10px]"></i> Aprobada
                            </span>
                            @elseif($caja->isRejected())
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                <i class="fas fa-xmark text-[10px]"></i> Rechazada
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $caja->approvedBy?->name ?? '—' }}
                            @if($caja->approved_at)
                            <span class="block text-slate-400 text-[10px]">{{ $caja->approved_at->format('d/m/Y H:i') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-red-600 max-w-xs">
                            {{ $caja->rejection_reason ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif


{{-- Modal aprobar caja --}}
<div id="modalAprobar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-emerald-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-circle-check text-emerald-600 text-sm"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-800">Aprobar caja</h3>
        </div>
        <p class="text-slate-600 text-sm mb-1">
            ¿Aprobar la caja del <strong id="aprobar-fecha" class="text-slate-800"></strong>
            de <strong id="aprobar-empleado" class="text-slate-800"></strong>?
        </p>
        <p class="text-slate-400 text-xs mb-6">Las ventas de esta caja quedarán validadas.</p>
        <form id="formAprobar" action="" method="POST" class="flex gap-3 justify-end">
            @csrf
            <button type="button" onclick="cerrarModal('modalAprobar')"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cancelar
            </button>
            <button type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors">
                Aprobar
            </button>
        </form>
    </div>
</div>

{{-- Modal rechazar caja --}}
<div id="modalRechazar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-xmark text-red-600 text-sm"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-800">Rechazar caja</h3>
        </div>
        <p class="text-slate-600 text-sm mb-3">
            Vas a rechazar la caja del <strong id="rechazar-fecha" class="text-slate-800"></strong>
            de <strong id="rechazar-empleado" class="text-slate-800"></strong>.
        </p>
        <form id="formRechazar" action="" method="POST">
            @csrf
            <label for="rejection_reason" class="block text-xs font-semibold text-slate-600 mb-1.5">
                Motivo del rechazo <span class="text-red-500">*</span>
            </label>
            <textarea name="rejection_reason" id="rejection_reason" rows="3" required maxlength="500"
                      placeholder="Explica por qué se rechaza esta caja..."
                      class="w-full text-sm text-slate-700 border border-slate-300 rounded-lg px-3 py-2 resize-none
                             focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent"></textarea>
            <p class="text-red-600 text-xs mt-2 mb-5">Se revertirá el stock de las ventas de esta caja.</p>
            <div class="flex gap-3 justify-end">
                <button type="button" onclick="cerrarModal('modalRechazar')"
                        class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Rechazar caja
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function abrirModal(id) {
    document.getElementById(id).style.setProperty('display', 'flex', 'important');
}

function cerrarModal(id) {
    document.getElementById(id).style.setProperty('display', 'none', 'important');
}

// Aprobar: llena el modal con los datos de la caja y apunta el formulario a su ruta
document.querySelectorAll('.btn-aprobar').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('formAprobar').action = btn.dataset.action;
        document.getElementById('aprobar-fecha').textContent    = btn.dataset.fecha;
        document.getElementById('aprobar-empleado').textContent = btn.dataset.empleado;
        abrirModal('modalAprobar');
    });
});

// Rechazar: igual, y deja vacío el motivo
document.querySelectorAll('.btn-rechazar').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.getElementById('formRechazar').action = btn.dataset.action;
        document.getElementById('rechazar-fecha').textContent    = btn.dataset.fecha;
        document.getElementById('rechazar-empleado').textContent = btn.dataset.empleado;
        document.getElementById('rejection_reason').value = '';
        abrirModal('modalRechazar');
        document.getElementById('rejection_reason').focus();
    });
});
</script>
@endsection
