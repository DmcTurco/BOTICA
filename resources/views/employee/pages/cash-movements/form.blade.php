@extends('employee/layouts/base')

@section('title', 'Nuevo Movimiento de Caja')
@section('main-padding', 'p-2 md:p-3')

@php
    $emp         = auth()->guard('employee')->user();
    $canExpense  = $emp->hasPrivilege(\App\Models\Employee::PRIV_GASTOS_DIA);
    $canIncome   = $emp->hasPrivilege(\App\Models\Employee::PRIV_OTROS_INGRESOS);
    $defaultType = old('type', $canExpense ? 'expense' : 'income');
    $money       = fn ($n) => 'S/ ' . number_format($n, 2);
    $inputClass  = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent';
@endphp

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('employee.cash-movements.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">Nuevo Movimiento de Caja</h1>
            <p class="text-sm text-slate-500 mt-0.5">Un gasto o ingreso de efectivo que no es una venta. Se refleja en el efectivo esperado al cerrar la caja.</p>
        </div>
    </div>

    <form action="{{ route('employee.cash-movements.store') }}" method="POST" class="flex-1 flex flex-col min-h-0">
        @csrf

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-money-bill-transfer text-sky-600 text-xs"></i>
                    <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos del movimiento</span>
                </div>
                {{-- Un gasto no puede superar este monto --}}
                <span class="text-xs text-slate-500">
                    Efectivo en caja: <strong class="text-slate-800">{{ $money($caja->expectedCash()) }}</strong>
                </span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                {{-- Tipo: una tarjeta por opción (solo las que el empleado tiene permitidas) --}}
                <div>
                    <p class="block text-sm font-medium text-slate-700 mb-1.5">Tipo <span class="text-red-500">*</span></p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @if($canExpense)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="expense" class="peer sr-only" {{ $defaultType === 'expense' ? 'checked' : '' }}>
                            <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-red-400 peer-checked:bg-red-50 hover:border-slate-300 transition-colors">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="fas fa-circle-arrow-up text-red-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">Gasto</p>
                                    <p class="text-xs text-slate-500">Sale dinero de la caja (flete, taxi, útiles...)</p>
                                </div>
                            </div>
                        </label>
                        @endif
                        @if($canIncome)
                        <label class="cursor-pointer">
                            <input type="radio" name="type" value="income" class="peer sr-only" {{ $defaultType === 'income' ? 'checked' : '' }}>
                            <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-slate-200 peer-checked:border-emerald-400 peer-checked:bg-emerald-50 hover:border-slate-300 transition-colors">
                                <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="fas fa-circle-arrow-down text-emerald-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">Otro ingreso</p>
                                    <p class="text-xs text-slate-500">Entra dinero que no es venta (sencillo, reposición...)</p>
                                </div>
                            </div>
                        </label>
                        @endif
                    </div>
                    @error('type')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label for="concept" class="block text-sm font-medium text-slate-700 mb-1.5">Concepto <span class="text-red-500">*</span></label>
                        <input type="text" id="concept" name="concept" maxlength="150" value="{{ old('concept') }}"
                               placeholder="Ej: Pago de flete, sencillo recibido" class="{{ $inputClass }} @error('concept') border-red-400 @enderror">
                        @error('concept')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="amount" class="block text-sm font-medium text-slate-700 mb-1.5">Monto (S/) <span class="text-red-500">*</span></label>
                        <input type="number" id="amount" name="amount" min="0.01" step="0.01" value="{{ old('amount') }}"
                               placeholder="0.00" class="{{ $inputClass }} @error('amount') border-red-400 @enderror">
                        @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-1.5">Nota <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="Detalle adicional, N° de recibo, a quién se pagó..."
                              class="{{ $inputClass }} resize-none">{{ old('notes') }}</textarea>
                </div>

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ \App\Support\ListUrl::to('employee.cash-movements.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    Registrar movimiento
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
