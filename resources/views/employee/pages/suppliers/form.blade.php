@extends('employee/layouts/base')

@section('title', isset($supplier) ? 'Editar Proveedor' : 'Nuevo Proveedor')
@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
@php
    $inputClass = 'w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent';
@endphp
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center gap-3 shrink-0">
        <a href="{{ \App\Support\ListUrl::to('employee.suppliers.index') }}"
           class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <div>
            <h1 class="text-xl font-bold text-slate-800">
                {{ isset($supplier) ? 'Editar Proveedor' : 'Nuevo Proveedor' }}
                @if(isset($supplier))
                    <span class="text-slate-400 font-normal">—</span>
                    <span class="text-emerald-700">{{ $supplier->name }}</span>
                @endif
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                {{ isset($supplier) ? 'Modifica los datos del proveedor' : 'Completa los datos para registrar un proveedor' }}
            </p>
        </div>
    </div>

    <form action="{{ isset($supplier) ? route('employee.suppliers.update', $supplier) : route('employee.suppliers.store') }}"
          method="POST" class="flex-1 flex flex-col min-h-0">
        @csrf
        @if(isset($supplier)) @method('PUT') @endif

        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0 flex items-center gap-2">
                <i class="fas fa-truck text-emerald-600 text-xs"></i>
                <span class="text-xs font-semibold text-slate-600 uppercase tracking-wider">Datos del Proveedor</span>
            </div>

            <div class="flex-1 overflow-auto p-6 space-y-5">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">
                            Nombre / razón social <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" maxlength="150"
                               value="{{ old('name', $supplier->name ?? '') }}"
                               class="{{ $inputClass }} {{ $errors->has('name') ? 'border-red-400' : '' }}"
                               placeholder="Ej: Droguería Sur S.A.C.">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="ruc" class="block text-sm font-medium text-slate-700 mb-1.5">RUC</label>
                        <input type="text" id="ruc" name="ruc" maxlength="11" inputmode="numeric"
                               value="{{ old('ruc', $supplier->ruc ?? '') }}"
                               class="{{ $inputClass }} {{ $errors->has('ruc') ? 'border-red-400' : '' }}"
                               placeholder="11 dígitos">
                        @error('ruc') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label for="contact" class="block text-sm font-medium text-slate-700 mb-1.5">Contacto</label>
                        <input type="text" id="contact" name="contact" maxlength="100"
                               value="{{ old('contact', $supplier->contact ?? '') }}"
                               class="{{ $inputClass }}" placeholder="Persona de contacto">
                        @error('contact') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-slate-700 mb-1.5">Teléfono</label>
                        <input type="text" id="phone" name="phone" maxlength="30"
                               value="{{ old('phone', $supplier->phone ?? '') }}"
                               class="{{ $inputClass }}" placeholder="Ej: 987654321">
                        @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Correo</label>
                        <input type="email" id="email" name="email" maxlength="100"
                               value="{{ old('email', $supplier->email ?? '') }}"
                               class="{{ $inputClass }} {{ $errors->has('email') ? 'border-red-400' : '' }}"
                               placeholder="correo@proveedor.com">
                        @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-sm font-medium text-slate-700 mb-1.5">Dirección</label>
                    <input type="text" id="address" name="address" maxlength="200"
                           value="{{ old('address', $supplier->address ?? '') }}"
                           class="{{ $inputClass }}" placeholder="Dirección fiscal o de despacho">
                    @error('address') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-slate-700 mb-1.5">Notas</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500"
                              class="{{ $inputClass }} resize-none"
                              placeholder="Condiciones de pago, días de visita, etc.">{{ old('notes', $supplier->notes ?? '') }}</textarea>
                    @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3 py-1">
                    <input type="hidden" name="status" value="0">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="status" name="status" value="1" class="sr-only peer"
                               {{ old('status', isset($supplier) ? $supplier->status : 1) == 1 ? 'checked' : '' }}>
                        <div class="w-10 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-emerald-500 rounded-full peer
                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                    after:content-[''] after:absolute after:top-0.5 after:left-0.5
                                    after:bg-white after:border-slate-300 after:border after:rounded-full
                                    after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                    </label>
                    <label for="status" class="text-sm font-medium text-slate-700 cursor-pointer">Proveedor activo</label>
                </div>

                @if(isset($supplier))
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Información adicional</p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Compras registradas</p>
                            <p class="text-lg font-bold text-slate-800">{{ $purchasesCount }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Deuda pendiente</p>
                            <p class="text-lg font-bold {{ $debt > 0 ? 'text-red-600' : 'text-slate-800' }}">
                                {{ $debt > 0 ? 'S/ ' . number_format($debt, 2) : '—' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Fecha creación</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $supplier->created_at->format('d/m/Y') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 mb-1">Última actualización</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $supplier->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                <a href="{{ \App\Support\ListUrl::to('employee.suppliers.index') }}"
                   class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <i class="fas fa-save text-xs"></i>
                    {{ isset($supplier) ? 'Actualizar Proveedor' : 'Guardar Proveedor' }}
                </button>
            </div>

        </div>
    </form>
</div>
@endsection
