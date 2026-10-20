@extends('admin/layouts/base')

@section('title', 'Importar datos de cliente')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="shrink-0">
        <h1 class="text-xl font-bold text-slate-800">Importar datos de cliente</h1>
        <p class="text-sm text-slate-500 mt-0.5">Productos y stock de una botica que migra de otro sistema. Primero subes el archivo y lo revisas completo; recién después eliges la compañía y registras.</p>
    </div>

    <div class="flex-1 min-h-0 overflow-auto space-y-3">

        {{-- Paso 1: modelo --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Paso 1</p>
                    <h2 class="text-base font-bold text-slate-800">Descarga el modelo y llénalo</h2>
                    <p class="text-sm text-slate-500 mt-1">
                        Úsalo tal cual: los encabezados no se cambian. Borra la fila de ejemplo (si la dejas, se ignora) y pega los productos del cliente.
                        Puedes guardarlo como Excel (.xlsx) o CSV.
                    </p>
                </div>
                <a href="{{ route('admin.client-imports.template') }}"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-violet-300 text-violet-700 hover:bg-violet-50 text-sm font-semibold rounded-xl transition-colors shrink-0">
                    <i class="fas fa-download text-xs"></i> Descargar modelo
                </a>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="text-left text-slate-400 uppercase tracking-wider">
                            <th class="py-2 pr-4 font-semibold">Columna</th>
                            <th class="py-2 pr-4 font-semibold">¿Obligatoria?</th>
                            <th class="py-2 font-semibold">Qué poner</th>
                        </tr>
                    </thead>
                    <tbody class="text-slate-600 divide-y divide-slate-100">
                        <tr><td class="py-1.5 pr-4 font-mono">producto</td><td class="pr-4">Sí</td><td>Nombre del producto (hasta 150 caracteres).</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">stock</td><td class="pr-4">Sí</td><td>Cantidad en la <b>unidad más pequeña</b> que se vende (tabletas, no cajas). Pon 0 si no hay.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">precio_compra · precio_venta</td><td class="pr-4">Sí</td><td>Por unidad. Sin precio de venta el producto entra, pero el punto de venta no deja venderlo.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">codigo_barras</td><td class="pr-4">No</td><td>Letras, números y guiones. No se repite. Formatea la columna como <b>Texto</b> en Excel para no perder ceros ni ver 7.75E+12.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">categoria · laboratorio · principio_activo</td><td class="pr-4">No</td><td>Se crean si no existen; si ya existen (aunque cambien mayúsculas o tildes) se reutilizan.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">stock_minimo · stock_maximo</td><td class="pr-4">No</td><td>Para las alertas de reposición.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">vencimiento</td><td class="pr-4">No</td><td>dd/mm/aaaa. Con fecha y stock se crea un lote que vence ese día.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">requiere_receta</td><td class="pr-4">No</td><td>Sí / No.</td></tr>
                        <tr><td class="py-1.5 pr-4 font-mono">cantidad_caja · precio_caja<br>cantidad_blister · precio_blister</td><td class="pr-4">No</td><td>Presentaciones: cuántas unidades trae y a qué precio se vende. Cantidad y precio van juntos.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paso 2: subir el archivo --}}
        <form method="POST" action="{{ route('admin.client-imports.preview') }}" enctype="multipart/form-data"
              class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            @csrf
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Paso 2</p>
            <h2 class="text-base font-bold text-slate-800">Sube el archivo para revisarlo</h2>
            <p class="text-sm text-slate-500 mt-1 mb-4">Se mostrará la lista completa de lo que trae. <b>No se guarda nada</b> y todavía no eliges compañía: eso es al final.</p>

            <div class="max-w-xl">
                <label for="file" class="block text-sm font-semibold text-slate-700 mb-1.5">Archivo (.xlsx o .csv) <span class="text-red-500">*</span></label>
                <input type="file" name="file" id="file" accept=".xlsx,.csv,.txt"
                       class="w-full text-sm text-slate-600 border rounded-lg file:mr-3 file:px-3 file:py-2.5 file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium @error('file') border-red-400 @else border-slate-200 @enderror">
                <p class="text-xs text-slate-400 mt-1">Hasta 10 MB y 20 000 productos.</p>
            </div>

            <div class="mt-5">
                <button type="submit"
                        class="px-5 py-2.5 bg-violet-700 hover:bg-violet-800 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    <i class="fas fa-magnifying-glass text-xs mr-1"></i> Subir y revisar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
