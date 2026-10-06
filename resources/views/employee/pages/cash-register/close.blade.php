@extends('employee/layouts/base', ['elementActive' => 'dashboard'])

@section('title', 'Cierre de Caja')
@section('main-padding', 'p-0')

@section('content-area')
<div class="flex-1 overflow-auto p-4 md:p-6">

    {{-- ── Encabezado ──────────────────────────────────── --}}
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Cierre de Caja</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                Abierta el {{ $caja->opened_at->locale('es')->isoFormat('dddd D [de] MMMM · HH:mm') }}
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-semibold text-emerald-700">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Caja abierta
        </span>
    </div>

    {{-- ── Mensajes ─────────────────────────────────────── --}}
    @if(session('error'))
    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700">
        <i class="fas fa-circle-exclamation mr-1.5"></i>{{ session('error') }}
    </div>
    @endif
    @if($errors->any())
    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 space-y-1">
        @foreach($errors->all() as $error)
            <p><i class="fas fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form id="formCierre" action="{{ route('employee.cash-register.close') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            {{-- ── VENTAS POR FORMA DE PAGO ─────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-slate-100 bg-slate-50">
                    <div class="w-7 h-7 bg-slate-200 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-receipt text-slate-600 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Ventas por forma de pago</h3>
                </div>
                <div class="p-4 space-y-2 text-sm">
                    @foreach($paymentLabels as $type => $label)
                    <div class="flex justify-between {{ $type === \App\Models\CashRegister::PAYMENT_CASH ? 'text-emerald-700 font-semibold' : 'text-slate-600' }}">
                        <span>{{ $label }}</span>
                        <span>S/ {{ number_format($paymentTotals[$type], 2) }}</span>
                    </div>
                    @endforeach
                    <div class="flex justify-between text-slate-800 font-bold border-t border-slate-200 pt-3">
                        <span>Total ventas</span>
                        <span>S/ {{ number_format($totalOrders, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- ── EFECTIVO ESPERADO + CONTADO ──────────────── --}}
            <div class="space-y-4">

                <div class="bg-emerald-600 rounded-2xl p-5 text-white">
                    <p class="text-xs font-semibold text-emerald-300 uppercase tracking-wider mb-1">Efectivo esperado</p>
                    <p class="text-4xl font-black tracking-tight leading-none">
                        S/ {{ number_format($expectedCash, 2) }}
                    </p>
                    <div class="mt-4 pt-4 border-t border-emerald-500 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs text-emerald-200">
                        <span>Apertura</span>
                        <span class="text-right font-semibold">S/ {{ number_format($caja->opening_amount, 2) }}</span>
                        <span>+ Ventas en efectivo</span>
                        <span class="text-right font-semibold">S/ {{ number_format($cashTotal, 2) }}</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-4">
                    <div>
                        <label for="closing_amount" class="block text-xs font-semibold text-slate-600 mb-2">
                            <i class="fas fa-coins mr-1.5 text-slate-400"></i>
                            Efectivo contado (S/)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-medium text-sm">S/</span>
                            <input type="number" name="closing_amount" id="closing_amount" step="0.01" min="0" required
                                   value="{{ old('closing_amount', number_format($expectedCash, 2, '.', '')) }}" placeholder="0.00"
                                   class="w-full pl-9 pr-4 py-3 text-lg font-semibold border border-slate-300 rounded-xl
                                          focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                        </div>
                        <p id="diferencia" class="text-xs mt-2 text-slate-400">Diferencia: —</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-2">
                            <i class="fas fa-note-sticky mr-1.5 text-slate-400"></i>
                            Observación (opcional)
                        </label>
                        <textarea name="notes" rows="3"
                                  class="w-full text-sm text-slate-700 border border-slate-200 rounded-lg px-3 py-2 resize-none
                                         focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                  placeholder="Ej: faltante por vuelto, etc.">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="space-y-2">
                    <button type="button" onclick="abrirModalConfirmar()"
                            class="w-full py-3.5 bg-red-500 hover:bg-red-600 active:scale-95 text-white font-bold rounded-xl
                                   transition-all flex items-center justify-center gap-2 shadow-sm text-sm">
                        <i class="fas fa-lock"></i>
                        Cerrar Caja
                    </button>
                    <a href="{{ route('employee.orders.index') }}"
                       class="block w-full py-2.5 text-center text-sm text-slate-400 hover:text-slate-600 transition-colors">
                        Volver a Ventas
                    </a>
                </div>
            </div>

        </div>
    </form>
</div>

{{-- Modal confirmar cierre --}}
<div id="modalConfirmarCierre" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-lock text-red-600 text-sm"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-800">Confirmar cierre de caja</h3>
        </div>
        <p class="text-slate-600 text-sm mb-3">
            ¿Seguro que deseas cerrar la caja?
        </p>
        <div class="bg-slate-50 rounded-lg p-3 space-y-1.5 text-sm mb-3">
            <div class="flex justify-between text-slate-600">
                <span>Efectivo esperado</span>
                <span class="font-medium">S/ {{ number_format($expectedCash, 2) }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Efectivo contado</span>
                <span id="confirmar-contado" class="font-medium">—</span>
            </div>
            <div class="flex justify-between text-slate-800 font-semibold border-t border-slate-200 pt-1.5">
                <span>Diferencia</span>
                <span id="confirmar-diferencia">—</span>
            </div>
        </div>
        <p class="text-red-600 text-xs mb-6">Ya no podrás registrar ventas hasta abrir una nueva caja.</p>
        <div class="flex gap-3 justify-end">
            <button type="button" onclick="cerrarModalConfirmar()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cancelar
            </button>
            <button type="button" id="btnConfirmarCierre" onclick="confirmarCierre()"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                Cerrar Caja
            </button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const esperadoCierre = {{ json_encode((float) $expectedCash) }};

// Abre el modal de confirmación (primero valida que el monto contado sea válido)
function abrirModalConfirmar() {
    const form  = document.getElementById('formCierre');
    const campo = document.getElementById('closing_amount');

    if (!form.reportValidity()) return;

    const contado = parseFloat(campo.value) || 0;
    const dif     = Math.round((contado - esperadoCierre) * 100) / 100;

    document.getElementById('confirmar-contado').textContent    = 'S/ ' + contado.toFixed(2);
    document.getElementById('confirmar-diferencia').textContent = (dif > 0 ? '+' : '') + 'S/ ' + dif.toFixed(2);
    document.getElementById('confirmar-diferencia').className   =
        dif === 0 ? 'text-emerald-600' : (dif > 0 ? 'text-amber-600' : 'text-red-600');

    document.getElementById('modalConfirmarCierre').style.setProperty('display', 'flex', 'important');
}

function cerrarModalConfirmar() {
    document.getElementById('modalConfirmarCierre').style.setProperty('display', 'none', 'important');
}

// Envía el cierre (se deshabilita el botón para evitar doble envío)
function confirmarCierre() {
    const btn = document.getElementById('btnConfirmarCierre');
    btn.disabled = true;
    btn.classList.add('opacity-60', 'cursor-not-allowed');
    document.getElementById('formCierre').submit();
}

document.addEventListener('DOMContentLoaded', function () {
    const esperado = {{ json_encode((float) $expectedCash) }};
    const campo    = document.getElementById('closing_amount');
    const texto    = document.getElementById('diferencia');

    // Muestra la diferencia entre lo contado y lo esperado mientras se escribe
    function actualizar() {
        if (campo.value === '') {
            texto.textContent = 'Diferencia: —';
            texto.className   = 'text-xs mt-2 text-slate-400';
            return;
        }
        const dif = Math.round((parseFloat(campo.value) - esperado) * 100) / 100;
        texto.textContent = 'Diferencia: ' + (dif > 0 ? '+' : '') + 'S/ ' + dif.toFixed(2);
        texto.className   = 'text-xs mt-2 font-semibold ' +
            (dif === 0 ? 'text-emerald-600' : (dif > 0 ? 'text-amber-600' : 'text-red-600'));
    }

    campo.addEventListener('input', actualizar);
    actualizar();

    // Enter no envía el formulario: el cierre solo se confirma desde el modal
    document.getElementById('formCierre').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            abrirModalConfirmar();
        }
    });
});
</script>
@endsection
