@extends('company/layouts/base', ['elementActive' => 'dashboard'])

@section('title', 'Editar Apertura de Caja')
@section('main-padding', 'p-0')

@section('content-area')
<div class="flex-1 overflow-auto p-4 md:p-6">

    {{-- ── Encabezado ──────────────────────────────────── --}}
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-lg font-bold text-slate-800">Editar Apertura de Caja</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                Abierta el {{ $caja->opened_at->locale('es')->isoFormat('dddd D [de] MMMM · HH:mm') }}
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-semibold text-emerald-700">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Caja abierta
        </span>
    </div>

    {{-- ── Flash messages ───────────────────────────────── --}}
    @if(session('success'))
    <div class="mb-5 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-sm text-emerald-700">
        <i class="fas fa-circle-check mr-1.5"></i>{{ session('success') }}
    </div>
    @endif
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

    @php
        // Indexar las denominaciones guardadas para precargar los inputs
        $denGuardadas = collect($caja->opening_denominations ?? [])
            ->keyBy(fn($d) => 'den_' . str_replace('.', '_', $d['valor']));
    @endphp

    <form id="formEditar" action="{{ route('company.cash-register.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

            {{-- ── BILLETES ─────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-slate-100 bg-amber-50">
                    <div class="w-7 h-7 bg-amber-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-money-bill-wave text-amber-600 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Billetes</h3>
                    <span id="resumen-billetes" class="ml-auto text-xs font-semibold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">
                        S/ 0.00
                    </span>
                </div>
                <div class="p-3 space-y-2">
                    @foreach([200, 100, 50, 20, 10] as $valor)
                    @php
                        $key      = 'den_' . str_replace('.', '_', $valor);
                        $cantidad = $denGuardadas->get($key)['cantidad'] ?? 0;
                    @endphp
                    <div class="fila-denominacion flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 transition-all {{ $cantidad > 0 ? 'border-emerald-300 bg-emerald-50' : '' }}"
                         data-valor="{{ $valor }}" data-grupo="billete">
                        <span class="text-xs font-bold text-amber-700 bg-amber-100 px-2 py-1 rounded-lg shrink-0 w-14 text-center">
                            S/&nbsp;{{ number_format($valor, 0) }}
                        </span>
                        <div class="flex items-center gap-1 mx-auto">
                            <button type="button" class="btn-restar w-8 h-8 flex items-center justify-center rounded-lg bg-slate-200 hover:bg-red-100 hover:text-red-600 text-slate-600 font-bold text-base transition-colors shrink-0 select-none">−</button>
                            <input type="number" name="{{ $key }}" id="{{ $key }}" min="0" value="{{ $cantidad }}"
                                   class="campo-cantidad w-12 text-center text-sm font-bold text-slate-800 bg-white border border-slate-300 rounded-lg py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <button type="button" class="btn-sumar w-8 h-8 flex items-center justify-center rounded-lg bg-slate-200 hover:bg-emerald-100 hover:text-emerald-700 text-slate-600 font-bold text-base transition-colors shrink-0 select-none">+</button>
                        </div>
                        <span class="campo-subtotal text-xs font-semibold text-slate-600 shrink-0 w-16 text-right">
                            S/ {{ number_format($cantidad * $valor, 2) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── MONEDAS ──────────────────────────────────────── --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2.5 px-4 py-3.5 border-b border-slate-100 bg-sky-50">
                    <div class="w-7 h-7 bg-sky-100 rounded-lg flex items-center justify-center shrink-0">
                        <i class="fas fa-coins text-sky-600 text-xs"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Monedas</h3>
                    <span id="resumen-monedas" class="ml-auto text-xs font-semibold text-sky-700 bg-sky-100 px-2 py-0.5 rounded-full">
                        S/ 0.00
                    </span>
                </div>
                <div class="p-3 space-y-2">
                    @foreach([5, 2, 1, 0.50, 0.20, 0.10] as $valor)
                    @php
                        $key      = 'den_' . str_replace('.', '_', $valor);
                        $cantidad = $denGuardadas->get($key)['cantidad'] ?? 0;
                    @endphp
                    <div class="fila-denominacion flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-xl border border-slate-200 transition-all {{ $cantidad > 0 ? 'border-emerald-300 bg-emerald-50' : '' }}"
                         data-valor="{{ $valor }}" data-grupo="moneda">
                        <span class="text-xs font-bold text-sky-700 bg-sky-100 px-2 py-1 rounded-lg shrink-0 w-14 text-center">
                            S/&nbsp;{{ number_format($valor, 2) }}
                        </span>
                        <div class="flex items-center gap-1 mx-auto">
                            <button type="button" class="btn-restar w-8 h-8 flex items-center justify-center rounded-lg bg-slate-200 hover:bg-red-100 hover:text-red-600 text-slate-600 font-bold text-base transition-colors shrink-0 select-none">−</button>
                            <input type="number" name="{{ $key }}" id="{{ $key }}" min="0" value="{{ $cantidad }}"
                                   class="campo-cantidad w-12 text-center text-sm font-bold text-slate-800 bg-white border border-slate-300 rounded-lg py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                            <button type="button" class="btn-sumar w-8 h-8 flex items-center justify-center rounded-lg bg-slate-200 hover:bg-emerald-100 hover:text-emerald-700 text-slate-600 font-bold text-base transition-colors shrink-0 select-none">+</button>
                        </div>
                        <span class="campo-subtotal text-xs font-semibold text-slate-600 shrink-0 w-16 text-right">
                            S/ {{ number_format($cantidad * $valor, 2) }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ── RESUMEN + ACCIÓN ─────────────────────────────── --}}
            <div class="md:col-span-2 xl:col-span-1 space-y-4">

                {{-- Total --}}
                <div class="bg-emerald-600 rounded-2xl p-5 text-white">
                    <p class="text-xs font-semibold text-emerald-300 uppercase tracking-wider mb-1">Total en caja</p>
                    <p id="total-display" class="text-4xl font-black tracking-tight leading-none">
                        S/ {{ number_format($caja->opening_amount, 2) }}
                    </p>
                    <div class="mt-4 pt-4 border-t border-emerald-500 grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs text-emerald-200">
                        <span>Subtotal billetes</span>
                        <span id="total-billetes" class="text-right font-semibold">S/ 0.00</span>
                        <span>Subtotal monedas</span>
                        <span id="total-monedas" class="text-right font-semibold">S/ 0.00</span>
                    </div>
                </div>

                <div class="md:grid md:grid-cols-2 xl:block gap-4 space-y-4 md:space-y-0 xl:space-y-4">

                    {{-- Observación --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                        <label class="block text-xs font-semibold text-slate-600 mb-2">
                            <i class="fas fa-note-sticky mr-1.5 text-slate-400"></i>
                            Observación (opcional)
                        </label>
                        <textarea name="notes" rows="3"
                                  class="w-full text-sm text-slate-700 border border-slate-200 rounded-lg px-3 py-2 resize-none
                                         focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                  placeholder="Ej: se corrigió el conteo inicial...">{{ old('notes', $caja->notes) }}</textarea>
                    </div>

                    {{-- Botones --}}
                    <div class="space-y-2">
                        <button type="submit"
                                class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold rounded-xl
                                       transition-all flex items-center justify-center gap-2 shadow-sm text-sm">
                            <i class="fas fa-floppy-disk"></i>
                            Guardar Cambios
                        </button>
                        <a href="{{ route('company.orders.index') }}"
                           class="block w-full py-2.5 text-center text-sm text-slate-400 hover:text-slate-600 transition-colors">
                            Volver a Ventas
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Bloquear Enter ────────────────────────────────────────
    document.getElementById('formEditar').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') e.preventDefault();
    });

    // ── Recalcular totales ────────────────────────────────────
    function recalcular() {
        let totalBilletes = 0;
        let totalMonedas  = 0;

        document.querySelectorAll('.fila-denominacion').forEach(function (fila) {
            const valor    = parseFloat(fila.dataset.valor);
            const grupo    = fila.dataset.grupo;
            const input    = fila.querySelector('.campo-cantidad');
            const cantidad = Math.max(0, parseInt(input.value) || 0);

            if (parseInt(input.value) < 0 || isNaN(parseInt(input.value))) input.value = 0;

            const subtotal = cantidad * valor;
            fila.querySelector('.campo-subtotal').textContent = 'S/ ' + subtotal.toFixed(2);

            const activa = cantidad > 0;
            fila.classList.toggle('border-emerald-300', activa);
            fila.classList.toggle('bg-emerald-50',      activa);
            fila.classList.toggle('border-slate-200',   !activa);
            fila.classList.toggle('bg-slate-50',        !activa);

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

    // ── Botones + / − ────────────────────────────────────────
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

    // Calcular totales iniciales con los valores precargados
    recalcular();
});
</script>
@endsection

@endsection
