@extends('employee/layouts/base', ['elementActive' => 'dashboard'])

@section('title', 'Caja Histórica — ' . $cashRegister->register_date->format('d/m/Y'))
@section('main-padding', 'p-2 md:p-4')

@section('content-area')
<div class="flex-1 flex flex-col gap-4 min-h-0">

    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="flex items-start justify-between shrink-0 gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('employee.home') }}"
                   class="text-slate-400 hover:text-slate-600 transition-colors text-sm">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-xl font-bold text-slate-800">
                    Caja Histórica — {{ $cashRegister->register_date->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                </h1>
            </div>
            <p class="text-sm text-slate-500 ml-6">
                Empleado: <span class="font-semibold text-slate-700">{{ $cashRegister->employee->name }}</span>
                &nbsp;·&nbsp; Apertura: S/ {{ number_format($cashRegister->opening_amount, 2) }}
            </p>
        </div>

        {{-- Badge de estado --}}
        @if($cashRegister->isPending() && $cashRegister->status === 1)
            <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                <i class="fas fa-clock"></i> Abierta · Pendiente
            </span>
        @elseif($cashRegister->isPending() && $cashRegister->status === 0)
            <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                <i class="fas fa-hourglass-half"></i> Enviada a validación
            </span>
        @endif
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

    {{-- ── Aviso informativo ────────────────────────────────────── --}}
    @if($cashRegister->status === 1)
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-800 shrink-0">
        <p class="font-semibold flex items-center gap-1.5 mb-1">
            <i class="fas fa-triangle-exclamation text-amber-500"></i>
            Caja en modo histórico
        </p>
        <p class="text-xs leading-relaxed">
            Las ventas que registres aquí quedarán <strong>pendientes de validación</strong> por el administrador.
            El stock se descuenta al registrar cada venta, pero el administrador puede revertirlo si rechaza la caja.
            Cuando termines, cierra la caja para enviarla a revisión.
        </p>
    </div>
    @endif

    {{-- ── Contenido principal: órdenes + panel lateral ──────── --}}
    <div class="flex flex-col xl:flex-row gap-4 flex-1 min-h-0">

        {{-- TABLA DE ÓRDENES --}}
        <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col min-h-0">

            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50 shrink-0">
                <h2 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-receipt text-amber-500 text-xs"></i>
                    Ventas registradas
                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                        {{ $cashRegister->orders->where('status', 1)->count() }}
                    </span>
                </h2>

                {{-- Botón nueva venta (solo si la caja está abierta) --}}
                @if($cashRegister->isEditable())
                <a href="{{ route('employee.orders.index') }}?historical={{ $cashRegister->id }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition-colors">
                    <i class="fas fa-plus"></i> Nueva venta
                </a>
                @endif
            </div>

            <div class="overflow-auto flex-1">
                @php
                    $ordenes = $cashRegister->orders->where('status', 1)->sortByDesc('created_at');
                @endphp

                @if($ordenes->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                    <i class="fas fa-receipt text-4xl mb-3 opacity-30"></i>
                    <p class="text-sm font-medium">Aún no hay ventas en esta caja</p>
                    @if($cashRegister->isEditable())
                    <p class="text-xs mt-1">Usa el botón "Nueva venta" para registrar</p>
                    @endif
                </div>
                @else
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white border-b border-slate-100 z-10">
                        <tr class="text-xs text-slate-500 uppercase tracking-wider">
                            <th class="px-4 py-2.5 text-left font-semibold">N° / Comprobante</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Cliente</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Hora</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Total</th>
                            <th class="px-4 py-2.5 text-center font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($ordenes as $orden)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-800">{{ $orden->voucher_number ?? '—' }}</span>
                                <span class="ml-1.5 text-xs text-slate-400">
                                    {{ match($orden->voucher_type ?? 0) {
                                        1 => 'Boleta',
                                        2 => 'Factura',
                                        default => 'Nota'
                                    } }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-xs">
                                {{ $orden->client?->name ?? 'Sin cliente' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">
                                {{ $orden->created_at->format('H:i') }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-slate-800">
                                S/ {{ number_format($orden->total, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if($cashRegister->isEditable())
                                    <a href="{{ route('employee.orders.edit', $orden) }}"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-sky-50 hover:bg-sky-100 text-sky-700 text-xs font-medium transition-colors"
                                       title="Editar venta">
                                        <i class="fas fa-pen text-[10px]"></i> Editar
                                    </a>
                                    @endif
                                    <a href="{{ route('employee.orders.print', $orden) }}" target="_blank"
                                       class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-medium transition-colors"
                                       title="Imprimir">
                                        <i class="fas fa-print text-[10px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

        </div>{{-- /tabla --}}

        {{-- PANEL LATERAL --}}
        <div class="xl:w-64 shrink-0 flex flex-col gap-4">

            {{-- Resumen de caja --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 space-y-3">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Resumen</h3>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Apertura</span>
                        <span class="font-semibold text-slate-700">S/ {{ number_format($cashRegister->opening_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total ventas</span>
                        <span class="font-bold text-emerald-700">S/ {{ number_format($cashRegister->totalOrders(), 2) }}</span>
                    </div>
                    @if($cashRegister->status === 0)
                    <div class="flex justify-between pt-2 border-t border-slate-100">
                        <span class="text-slate-500">Esperado</span>
                        <span class="font-bold text-slate-800">S/ {{ number_format($cashRegister->expected_amount ?? 0, 2) }}</span>
                    </div>
                    @endif
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <p class="text-xs text-slate-400">
                        <i class="fas fa-clock mr-1"></i>
                        Abierta {{ $cashRegister->opened_at->format('d/m/Y H:i') }}
                    </p>
                    @if($cashRegister->closed_at)
                    <p class="text-xs text-slate-400 mt-1">
                        <i class="fas fa-lock mr-1"></i>
                        Cerrada {{ $cashRegister->closed_at->format('d/m/Y H:i') }}
                    </p>
                    @endif
                </div>
            </div>

            {{-- Botón cerrar caja (solo si está abierta) --}}
            @if($cashRegister->isEditable())
            <form action="{{ route('employee.cash-register.close-historical', $cashRegister) }}" method="POST"
                  onsubmit="return confirm('¿Cerrar esta caja y enviarla a validación del administrador?')">
                @csrf
                <button type="submit"
                        class="w-full py-3.5 bg-slate-700 hover:bg-slate-800 active:scale-[.98] text-white font-bold rounded-2xl
                               transition-all flex items-center justify-center gap-2 shadow-sm text-sm">
                    <i class="fas fa-lock"></i>
                    Cerrar y Enviar a Validación
                </button>
            </form>

            <p class="text-xs text-slate-400 text-center -mt-2 leading-relaxed">
                Una vez cerrada, el administrador revisará y aprobará o rechazará esta caja.
            </p>
            @elseif($cashRegister->status === 0)
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-center text-xs text-blue-700">
                <i class="fas fa-hourglass-half text-blue-400 text-xl mb-2 block"></i>
                Caja cerrada.<br>Esperando revisión del administrador.
            </div>
            @endif

        </div>{{-- /panel lateral --}}

    </div>

</div>
@endsection
