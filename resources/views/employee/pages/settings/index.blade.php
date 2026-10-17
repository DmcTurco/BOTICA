@extends('employee/layouts/base', ['elementActive' => 'settings'])

@section('title', 'Configuración')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-4 min-h-0 overflow-auto">

    {{-- Header --}}
    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Configuración</h1>
        <p class="text-sm text-slate-500 mt-0.5">Ajustes de la sede: <strong>{{ $branch->name }}</strong></p>
    </div>

    <form method="POST" action="{{ route('employee.settings.update') }}">
        @csrf

        {{-- ── Sección: Ventas ── --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-4">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
                <div class="w-9 h-9 bg-sky-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-cash-register text-sky-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Ventas y cobro</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Opciones del punto de venta para esta sede</p>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Redondeo en pagos en efectivo</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            El total se cobra hacia abajo al múltiplo de S/ 0.10 (ej: S/ 44.37 → S/ 44.30). Solo con pago en efectivo;
                            el comprobante conserva su total y el redondeo se muestra aparte.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                        <input type="checkbox" name="cash_rounding" value="1" class="sr-only peer" {{ $salesConfig['cash_rounding'] ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-sky-500 rounded-full peer
                                    peer-checked:bg-sky-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:after:translate-x-full"></div>
                    </label>
                </div>

                <div class="border-t border-slate-100"></div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Descuento por monto (S/)</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Además del porcentaje, permite descontar un monto fijo en soles (ej: S/ 1.00 menos). Mismo permiso y mismo tope del 50% que el descuento por porcentaje.
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                        <input type="checkbox" name="amount_discount" value="1" class="sr-only peer" {{ $salesConfig['amount_discount'] ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-sky-500 rounded-full peer
                                    peer-checked:bg-sky-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:after:translate-x-full"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- ── Sección: Impresión ── --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Card header --}}
            <div class="flex items-center gap-3 px-6 py-4 border-b border-slate-100">
                <div class="w-9 h-9 bg-sky-50 rounded-lg flex items-center justify-center shrink-0">
                    <i class="fas fa-print text-sky-600 text-sm"></i>
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">Impresión de comprobantes</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Define el formato y comportamiento al imprimir ventas</p>
                </div>
            </div>

            <div class="p-6 space-y-6">

                {{-- Plantilla por defecto: solo se muestra la seleccionada; se cambia en su propia pantalla --}}
                @php
                    $templateLabels = [
                        'ticket_80mm' => ['icon' => 'fa-receipt',    'title' => 'Ticket 80mm',          'desc' => 'Impresora térmica estándar — papel de 80mm'],
                        'ticket_58mm' => ['icon' => 'fa-receipt',    'title' => 'Ticket 58mm',          'desc' => 'Impresora térmica compacta — papel de 58mm'],
                        'boleta_a4'   => ['icon' => 'fa-file-lines', 'title' => 'Boleta / Factura A4',  'desc' => 'Hoja completa A4 — diseño formal'],
                        'nota_venta'  => ['icon' => 'fa-file-alt',   'title' => 'Nota de Venta',        'desc' => 'Comprobante interno — tamaño A5'],
                    ];
                    $current = $templateLabels[$printConfig['default_template']] ?? $templateLabels['ticket_80mm'];
                @endphp
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-3">
                        Formato de impresión por defecto
                    </label>
                    <input type="hidden" name="default_template" value="{{ $printConfig['default_template'] }}">
                    <div class="flex items-center justify-between gap-4 p-4 border-2 border-sky-500 bg-sky-50 rounded-xl">
                        <div>
                            <div class="flex items-center gap-2">
                                <i class="fas {{ $current['icon'] }} text-sky-500 text-xs"></i>
                                <span class="text-sm font-semibold text-slate-700">{{ $current['title'] }}</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $current['desc'] }}</p>
                        </div>
                        <a href="{{ route('employee.settings.format') }}"
                           class="shrink-0 px-4 py-2 bg-white border border-slate-200 text-slate-700 text-sm font-medium rounded-lg hover:bg-slate-50 transition-colors">
                            <i class="fas fa-eye text-sky-500 mr-1"></i> Ver y cambiar formato
                        </a>
                    </div>
                    @error('default_template')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-100"></div>

                {{-- Auto print --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Impresión automática al guardar venta</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Si está activo, al completar una venta se abre el comprobante listo para imprimir automáticamente
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer shrink-0 mt-0.5">
                        <input type="checkbox" name="auto_print" value="1" class="sr-only peer"
                               {{ $printConfig['auto_print'] ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-sky-500 rounded-full peer
                                    peer-checked:bg-sky-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                    peer-checked:after:translate-x-full"></div>
                    </label>
                </div>

                <div class="border-t border-slate-100"></div>

                {{-- Copies + printer name --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Número de copias
                        </label>
                        <select name="copies"
                                class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 bg-white">
                            @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}" {{ $printConfig['copies'] == $i ? 'selected' : '' }}>{{ $i }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Nombre de impresora
                            <span class="text-xs font-normal text-slate-400">(opcional — para impresión directa futura)</span>
                        </label>
                        <input type="text" name="printer_name"
                               value="{{ old('printer_name', $printConfig['printer_name']) }}"
                               placeholder="Ej: EPSON TM-T20"
                               class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>
                </div>

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center gap-3">
                <button type="submit"
                        class="px-5 py-2.5 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Guardar configuración
                </button>
                <a href="{{ route('employee.home') }}"
                   class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-100 transition-colors">
                    Cancelar
                </a>
            </div>

        </div>

    </form>

</div>
@endsection

