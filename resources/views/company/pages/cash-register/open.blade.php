@extends('company/layouts/base', ['elementActive' => 'dashboard'])

@section('title', 'Apertura de Caja')
@section('main-padding', 'p-0')

@section('content-area')
<div class="flex-1 overflow-auto p-4 md:p-6">

    {{-- ── Encabezado ──────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-lg font-bold text-slate-800">Apertura de Caja</h1>
        <p class="text-sm text-slate-400 mt-0.5">
            {{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY · HH:mm') }}
        </p>
    </div>

    {{-- ── Errores ──────────────────────────────────────── --}}
    @if($errors->any())
    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700 space-y-1">
        @foreach($errors->all() as $error)
            <p><i class="fas fa-circle-exclamation mr-1.5"></i>{{ $error }}</p>
        @endforeach
    </div>
    @endif

    <form id="formApertura" action="{{ route('company.cash-register.open') }}" method="POST">
        @csrf

        {{-- Layout: denominaciones a la izquierda, panel de acción a la derecha --}}
        <div class="flex flex-col xl:flex-row gap-5">

            {{-- ── DENOMINACIONES (billetes + monedas en 2 col) ──── --}}
            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">

                {{-- BILLETES --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-slate-100 bg-amber-50">
                        <div class="w-6 h-6 bg-amber-100 rounded-md flex items-center justify-center shrink-0">
                            <i class="fas fa-money-bill-wave text-amber-600 text-xs"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Billetes</h3>
                        <span id="resumen-billetes" class="ml-auto text-xs font-bold text-amber-700">S/ 0.00</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach([200, 100, 50, 20, 10] as $valor)
                        @php $key = 'den_' . str_replace('.', '_', $valor) @endphp
                        <div class="fila-denominacion flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                             data-valor="{{ $valor }}" data-grupo="billete">
                            <span class="text-sm font-bold text-slate-700 w-14 shrink-0">S/ {{ number_format($valor, 0) }}</span>
                            <div class="flex items-center gap-1.5 mx-auto">
                                <button type="button" class="btn-restar w-7 h-7 flex items-center justify-center rounded-md bg-slate-100 hover:bg-red-100 hover:text-red-600 text-slate-500 font-bold transition-colors shrink-0 select-none text-sm">−</button>
                                <input type="number" name="{{ $key }}" min="0" value="0"
                                       class="campo-cantidad w-11 text-center text-sm font-bold text-slate-800 bg-white border border-slate-200 rounded-md py-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <button type="button" class="btn-sumar w-7 h-7 flex items-center justify-center rounded-md bg-slate-100 hover:bg-emerald-100 hover:text-emerald-700 text-slate-500 font-bold transition-colors shrink-0 select-none text-sm">+</button>
                            </div>
                            <span class="campo-subtotal text-xs font-semibold text-slate-400 w-16 text-right shrink-0">S/ 0.00</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- MONEDAS --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2.5 px-4 py-3 border-b border-slate-100 bg-sky-50">
                        <div class="w-6 h-6 bg-sky-100 rounded-md flex items-center justify-center shrink-0">
                            <i class="fas fa-coins text-sky-600 text-xs"></i>
                        </div>
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Monedas</h3>
                        <span id="resumen-monedas" class="ml-auto text-xs font-bold text-sky-700">S/ 0.00</span>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach([5, 2, 1, 0.50, 0.20, 0.10] as $valor)
                        @php $key = 'den_' . str_replace('.', '_', $valor) @endphp
                        <div class="fila-denominacion flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors"
                             data-valor="{{ $valor }}" data-grupo="moneda">
                            <span class="text-sm font-bold text-slate-700 w-14 shrink-0">S/ {{ number_format($valor, 2) }}</span>
                            <div class="flex items-center gap-1.5 mx-auto">
                                <button type="button" class="btn-restar w-7 h-7 flex items-center justify-center rounded-md bg-slate-100 hover:bg-red-100 hover:text-red-600 text-slate-500 font-bold transition-colors shrink-0 select-none text-sm">−</button>
                                <input type="number" name="{{ $key }}" min="0" value="0"
                                       class="campo-cantidad w-11 text-center text-sm font-bold text-slate-800 bg-white border border-slate-200 rounded-md py-1 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                <button type="button" class="btn-sumar w-7 h-7 flex items-center justify-center rounded-md bg-slate-100 hover:bg-emerald-100 hover:text-emerald-700 text-slate-500 font-bold transition-colors shrink-0 select-none text-sm">+</button>
                            </div>
                            <span class="campo-subtotal text-xs font-semibold text-slate-400 w-16 text-right shrink-0">S/ 0.00</span>
                        </div>
                        @endforeach
                    </div>
                </div>

            </div>{{-- /denominaciones --}}

            {{-- ── PANEL DERECHO: total + acción ───────────────── --}}
            <div class="xl:w-72 shrink-0 flex flex-col gap-4">

                {{-- Total --}}
                <div class="bg-emerald-600 rounded-2xl p-5 text-white">
                    <p class="text-xs font-semibold text-emerald-300 uppercase tracking-widest mb-2">Total en caja</p>
                    <p id="total-display" class="text-5xl font-black tracking-tight">S/ 0.00</p>
                    <div class="mt-4 pt-4 border-t border-emerald-500 space-y-1.5 text-xs text-emerald-200">
                        <div class="flex justify-between">
                            <span>Billetes</span>
                            <span id="total-billetes" class="font-semibold text-white">S/ 0.00</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Monedas</span>
                            <span id="total-monedas" class="font-semibold text-white">S/ 0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Observación --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex flex-col gap-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Observación <span class="font-normal normal-case">(opcional)</span>
                    </label>
                    <textarea name="notes" rows="4"
                              class="w-full text-sm text-slate-700 border border-slate-200 rounded-xl px-3 py-2.5 resize-none
                                     focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                              placeholder="Ej: efectivo del turno anterior..."></textarea>
                </div>

                {{-- Botón --}}
                <button type="submit"
                        class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[.98] text-white font-bold rounded-2xl
                               transition-all flex items-center justify-center gap-2 shadow-md text-sm tracking-wide">
                    <i class="fas fa-lock-open"></i>
                    Abrir Caja y Comenzar
                </button>

                <a href="{{ route('company.home') }}"
                   class="text-center text-sm text-slate-400 hover:text-slate-600 transition-colors py-1">
                    Volver al dashboard
                </a>

            </div>{{-- /panel derecho --}}

        </div>
    </form>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Bloquear Enter
    document.getElementById('formApertura').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });

    function recalcular() {
        let totalBilletes = 0;
        let totalMonedas  = 0;

        document.querySelectorAll('.fila-denominacion').forEach(function (fila) {
            const valor    = parseFloat(fila.dataset.valor);
            const grupo    = fila.dataset.grupo;
            const input    = fila.querySelector('.campo-cantidad');
            const cantidad = Math.max(0, parseInt(input.value) || 0);

            if (cantidad < 0 || isNaN(cantidad)) input.value = 0;

            const subtotal = cantidad * valor;
            fila.querySelector('.campo-subtotal').textContent = subtotal > 0
                ? 'S/ ' + subtotal.toFixed(2)
                : '—';

            // Resaltar fila con cantidad > 0
            fila.classList.toggle('bg-emerald-50', cantidad > 0);

            if (grupo === 'billete') totalBilletes += subtotal;
            else                     totalMonedas  += subtotal;
        });

        const total = totalBilletes + totalMonedas;

        document.getElementById('total-billetes').textContent  = 'S/ ' + totalBilletes.toFixed(2);
        document.getElementById('total-monedas').textContent   = 'S/ ' + totalMonedas.toFixed(2);
        document.getElementById('total-display').textContent   = 'S/ ' + total.toFixed(2);
        document.getElementById('resumen-billetes').textContent = 'S/ ' + totalBilletes.toFixed(2);
        document.getElementById('resumen-monedas').textContent  = 'S/ ' + totalMonedas.toFixed(2);
    }

    document.querySelectorAll('.fila-denominacion').forEach(function (fila) {
        const input    = fila.querySelector('.campo-cantidad');
        const btnMas   = fila.querySelector('.btn-sumar');
        const btnMenos = fila.querySelector('.btn-restar');

        btnMas.addEventListener('click', function () {
            input.value = (parseInt(input.value) || 0) + 1;
            recalcular();
        });
        btnMenos.addEventListener('click', function () {
            const v = parseInt(input.value) || 0;
            if (v > 0) { input.value = v - 1; recalcular(); }
        });
        input.addEventListener('input', recalcular);
        input.addEventListener('focus', function () { this.select(); });
    });

});
</script>
@endsection

@endsection
