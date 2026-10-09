@extends('employee/layouts/base')

@section('title', 'Datos del Local')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0 overflow-auto max-w-2xl w-full mx-auto">

    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Datos del Local</h1>
        <p class="text-sm text-slate-500 mt-0.5">Información de contacto de tu sede. Aparece en los comprobantes impresos.</p>
    </div>

    @include('employee.partials.alerts')

    <form action="{{ route('employee.local.update') }}" method="POST" class="bg-white rounded-xl border border-slate-200 shadow-sm">
        @csrf @method('PUT')
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nombre de la sede <span class="text-red-500">*</span></label>
                <input type="text" name="name" required maxlength="100" value="{{ old('name', $branch->name) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Dirección</label>
                <input type="text" name="address" maxlength="200" value="{{ old('address', $branch->address) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Teléfono</label>
                    <input type="text" name="phone" maxlength="30" value="{{ old('phone', $branch->phone) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Correo</label>
                    <input type="email" name="email" maxlength="100" value="{{ old('email', $branch->email) }}" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
            <p class="text-xs text-slate-400"><i class="fas fa-circle-info mr-1"></i> El código de establecimiento SUNAT y las series los administra la empresa.</p>
        </div>
        <div class="px-6 py-3 border-t border-slate-200 bg-slate-50 flex justify-end rounded-b-xl">
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm flex items-center gap-2"><i class="fas fa-save text-xs"></i> Guardar</button>
        </div>
    </form>
</div>
@endsection
