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
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-4 py-3 bg-slate-50 border-t border-slate-100">

                    {{-- Formulario de rechazo (desplegable) --}}
                    <div class="w-full sm:flex-1" x-data="{ rechazando: false }">
                        <div x-show="!rechazando" class="flex items-center gap-2">
                            <button @click="rechazando = true"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold transition-colors border border-red-200">
                                <i class="fas fa-xmark"></i> Rechazar
                            </button>
                        </div>

                        <div x-show="rechazando" x-cloak class="flex flex-col sm:flex-row gap-2 w-full">
                            <form action="{{ route('employee.approvals.reject', $caja) }}" method="POST"
                                  class="flex flex-col sm:flex-row gap-2 w-full">
                                @csrf
                                <input type="text" name="rejection_reason"
                                       placeholder="Motivo del rechazo (obligatorio)..."
                                       required maxlength="500"
                                       class="flex-1 text-xs border border-red-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-transparent">
                                <div class="flex gap-2 shrink-0">
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-bold transition-colors">
                                        <i class="fas fa-check"></i> Confirmar rechazo
                                    </button>
                                    <button type="button" @click="rechazando = false"
                                            class="inline-flex items-center px-3 py-2 rounded-lg bg-white hover:bg-slate-100 text-slate-600 text-xs font-medium transition-colors border border-slate-200">
                                        Cancelar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Botón aprobar --}}
                    <form action="{{ route('employee.approvals.approve', $caja) }}" method="POST"
                          onsubmit="return confirm('¿Aprobar la caja del {{ $caja->register_date->format('d/m/Y') }} de {{ $caja->employee->name }}?')">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors shadow-sm">
                            <i class="fas fa-circle-check"></i> Aprobar caja
                        </button>
                    </form>

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

</div>
@endsection
