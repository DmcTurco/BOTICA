@extends('employee/layouts/base')

@section('title', isset($products) ? 'Editar Producto' : 'Nuevo Producto')

@section('main-padding', 'p-2 md:p-3')
@section('main-class', 'overflow-hidden')

@section('content-area')
    <div class="flex-1 flex flex-col gap-3 min-h-0">

        {{-- Header --}}
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('employee.products.index') }}"
                class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 transition-colors">
                <i class="fas fa-arrow-left text-sm"></i>
            </a>
            <div>
                <h1 class="text-xl font-bold text-slate-800">
                    {{ isset($products) ? 'Editar Producto' : 'Nuevo Producto' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ isset($products) ? 'Modifica los datos del producto' : 'Completa los datos para registrar un producto' }}
                </p>
            </div>
        </div>

        {{-- Errores --}}
        @if ($errors->any())
            <div class="shrink-0 bg-red-50 border border-red-200 text-red-700 rounded-lg p-4 text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2"><i class="fas fa-circle-exclamation text-xs"></i> {{ $error }}
                    </p>
                @endforeach
            </div>
        @endif

        <form id="productoForm"
            action="{{ isset($products) ? route('employee.products.update', $products->code) : route('employee.products.store') }}"
            method="POST" class="flex-1 flex flex-col">
            @csrf
            @if (isset($products))
                @method('PUT')
            @endif

            {{-- Tabs --}}
            <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                {{-- Tab Nav --}}
                <div class="flex border-b border-slate-200 bg-slate-50 px-4 pt-4 gap-1 shrink-0">
                    <button type="button"
                        class="tab-btn active-tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
                        data-target="tab-general">
                        <i class="fas fa-info-circle mr-1.5"></i> Información General
                    </button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
                        data-target="tab-precios">
                        <i class="fas fa-tag mr-1.5"></i> Precios y Stock
                    </button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg transition-colors"
                        data-target="tab-presentaciones">
                        <i class="fas fa-box mr-1.5"></i> Presentaciones
                    </button>
                </div>

                <div class="flex-1 overflow-auto p-6">

                    {{-- Tab: Información General --}}
                    <div id="tab-general" class="tab-panel space-y-5">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="codigo" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Código <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="codigo" name="codigo" required maxlength="20"
                                    value="{{ old('codigo', $products->code ?? '') }}"
                                    {{ isset($products) ? 'readonly' : '' }}
                                    class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent {{ isset($products) ? 'bg-slate-50 text-slate-500' : '' }}"
                                    placeholder="Ej: MED-001">
                                @error('codigo')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="categoria_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Categoría <span class="text-red-500">*</span>
                                </label>
                                <select id="categoria_id" name="categoria_id" required
                                    class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="">Seleccionar categoría</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('categoria_id', $products->category_id ?? '') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('categoria_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="nombre" class="block text-sm font-medium text-slate-700 mb-1.5">
                                Nombre del Producto <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nombre" name="nombre" required maxlength="150"
                                value="{{ old('nombre', $products->name ?? '') }}"
                                class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                placeholder="Nombre completo del producto">
                            @error('nombre')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="descripcion"
                                class="block text-sm font-medium text-slate-700 mb-1.5">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="2" maxlength="255"
                                class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent resize-none"
                                placeholder="Descripción opcional...">{{ old('descripcion', $products->description ?? '') }}</textarea>
                            @error('descripcion')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="laboratorio_id"
                                    class="block text-sm font-medium text-slate-700 mb-1.5">Laboratorio</label>
                                <select id="laboratorio_id" name="laboratorio_id"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="">Seleccionar laboratorio</option>
                                    @foreach ($laboratories as $laboratory)
                                        <option value="{{ $laboratory->id }}"
                                            {{ old('laboratorio_id', $products->laboratory_id ?? '') == $laboratory->id ? 'selected' : '' }}>
                                            {{ $laboratory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('laboratorio_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="principio_activo"
                                    class="block text-sm font-medium text-slate-700 mb-1.5">Principio Activo</label>
                                <input type="text" id="principio_activo" name="principio_activo" maxlength="100"
                                    value="{{ old('principio_activo', $products->active_ingredient ?? '') }}"
                                    class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                    placeholder="Ej: Ibuprofeno">
                                @error('principio_activo')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label for="unidad_medida_id" class="block text-sm font-medium text-slate-700 mb-1.5">
                                    Unidad de Medida <span class="text-red-500">*</span>
                                </label>
                                <select id="unidad_medida_id" name="unidad_medida_id" required
                                    class="select2 w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                                    <option value="">Seleccionar unidad</option>
                                    @foreach ($units as $unit)
                                        <option value="{{ $unit->id }}"
                                            {{ old('unidad_medida_id', $products->unit_id ?? '') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('unidad_medida_id')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex items-end">
                                <p class="text-xs text-slate-400 pb-2">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    La fecha de vencimiento se registra por lote al registrar una compra.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="producto_gravado" value="1"
                                    {{ old('producto_gravado', $products->taxed_product ?? 0) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-slate-700">Producto gravado (IGV)</span>
                            </label>
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="requiere_receta" value="1"
                                    {{ old('requiere_receta', $products->requires_recipe ?? 0) ? 'checked' : '' }}
                                    class="w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <span class="text-sm text-slate-700">Requiere receta médica</span>
                            </label>
                        </div>
                    </div>

                    {{-- Tab: Precios y Stock --}}
                    <div id="tab-precios" class="tab-panel hidden space-y-5">

                        {{-- Precios Unitarios --}}
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Precio Unitario
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="precio_compra" class="block text-sm font-medium text-slate-700 mb-1.5">
                                        Precio Compra <span class="text-red-500">*</span>
                                    </label>
                                    <div
                                        class="flex rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent">
                                        <span
                                            class="flex items-center px-3 bg-slate-50 text-slate-500 text-sm border-r border-slate-300">S/.</span>
                                        <input type="number" id="precio_compra" name="precio_compra" required
                                            min="0" step="0.01"
                                            value="{{ old('precio_compra', $products->purchase_price ?? '') }}"
                                            class="flex-1 px-3 py-2.5 text-sm bg-white focus:outline-none"
                                            placeholder="0.00">
                                    </div>
                                    @error('precio_compra')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="precio_venta_unidad"
                                        class="block text-sm font-medium text-slate-700 mb-1.5">
                                        Precio Venta <span class="text-red-500">*</span>
                                    </label>
                                    <div
                                        class="flex rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent">
                                        <span
                                            class="flex items-center px-3 bg-slate-50 text-slate-500 text-sm border-r border-slate-300">S/.</span>
                                        <input type="number" id="precio_venta_unidad" name="precio_venta_unidad"
                                            required min="0" step="0.01"
                                            value="{{ old('precio_venta_unidad', $products->unit_sale_price ?? '') }}"
                                            class="flex-1 px-3 py-2.5 text-sm bg-white focus:outline-none"
                                            placeholder="0.00">
                                    </div>
                                    @error('precio_venta_unidad')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-center">
                                    <p class="text-xs text-slate-500 mb-1">Utilidad Unitaria</p>
                                    <p id="utilidad_unitaria" class="text-base font-bold text-emerald-700">S/. 0.00</p>
                                </div>
                                <div class="bg-sky-50 border border-sky-200 rounded-lg p-3 text-center">
                                    <p class="text-xs text-slate-500 mb-1">Margen</p>
                                    <p id="margen_utilidad" class="text-base font-bold text-sky-700">0.00%</p>
                                </div>
                            </div>
                        </div>

                        {{-- Precios por Paquete --}}
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Precio por
                                Paquete <span class="font-normal text-slate-400 normal-case">(opcional)</span></p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <div>
                                    <label for="precio_compra_paquete"
                                        class="block text-sm font-medium text-slate-700 mb-1.5">Precio Compra</label>
                                    <div
                                        class="flex rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent">
                                        <span
                                            class="flex items-center px-3 bg-slate-50 text-slate-500 text-sm border-r border-slate-300">S/.</span>
                                        <input type="number" id="precio_compra_paquete" name="precio_compra_paquete"
                                            min="0" step="0.01"
                                            value="{{ old('precio_compra_paquete', $products->package_purchase_price ?? '') }}"
                                            class="flex-1 px-3 py-2.5 text-sm bg-white focus:outline-none"
                                            placeholder="0.00">
                                    </div>
                                    @error('precio_compra_paquete')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="precio_venta_paquete"
                                        class="block text-sm font-medium text-slate-700 mb-1.5">Precio Venta</label>
                                    <div
                                        class="flex rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent">
                                        <span
                                            class="flex items-center px-3 bg-slate-50 text-slate-500 text-sm border-r border-slate-300">S/.</span>
                                        <input type="number" id="precio_venta_paquete" name="precio_venta_paquete"
                                            min="0" step="0.01"
                                            value="{{ old('precio_venta_paquete', $products->package_sale_price ?? '') }}"
                                            class="flex-1 px-3 py-2.5 text-sm bg-white focus:outline-none"
                                            placeholder="0.00">
                                    </div>
                                    @error('precio_venta_paquete')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="unidades_por_paquete"
                                        class="block text-sm font-medium text-slate-700 mb-1.5">Unidades por
                                        Paquete</label>
                                    <input type="number" id="unidades_por_paquete" name="unidades_por_paquete"
                                        min="0" step="1"
                                        value="{{ old('unidades_por_paquete', $products->units_per_package ?? '') }}"
                                        class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                        placeholder="Ej: 12">
                                    @error('unidades_por_paquete')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="mt-3">
                                <div
                                    class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 text-center inline-block min-w-40">
                                    <p class="text-xs text-slate-500 mb-1">Utilidad por Paquete</p>
                                    <p id="utilidad_paquete" class="text-base font-bold text-emerald-700">S/. 0.00</p>
                                </div>
                            </div>
                        </div>

                        {{-- Stock --}}
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Control de Stock
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

                                {{-- Stock actual: solo visible en modo editar (en crear aún no hay stock) --}}
                                @isset($products)
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Stock en esta
                                            sede</label>
                                        <div
                                            class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg bg-slate-50 flex items-center justify-between">
                                            <span class="font-semibold text-slate-800">
                                                {{ $products->branchStocks->first()?->stock_actual ?? 0 }}
                                            </span>
                                            <a href="{{ route('employee.purchases.create', ['product' => $products->code]) }}"
                                                class="text-xs text-emerald-600 hover:text-emerald-700 font-medium flex items-center gap-1">
                                                <i class="fas fa-plus-circle text-[10px]"></i> Registrar compra
                                            </a>
                                        </div>
                                        <p class="text-xs text-slate-400 mt-1">El stock se actualiza mediante compras
                                            registradas.</p>
                                    </div>
                                @endisset

                                <div>
                                    <label for="stock_minimo"
                                        class="block text-sm font-medium text-slate-700 mb-1.5">Stock Mínimo</label>
                                    <input type="number" id="stock_minimo" name="stock_minimo" min="0"
                                        step="1"
                                        value="{{ old('stock_minimo', isset($products) ? $products->branchStocks->first()?->stock_minimum ?? '' : '') }}"
                                        class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                        placeholder="0">
                                    @error('stock_minimo')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="stock_maximo"
                                        class="block text-sm font-medium text-slate-700 mb-1.5">Stock Máximo</label>
                                    <input type="number" id="stock_maximo" name="stock_maximo" min="0"
                                        step="1"
                                        value="{{ old('stock_maximo', isset($products) ? $products->branchStocks->first()?->stock_maximum ?? '' : '') }}"
                                        class="w-full px-3 py-2.5 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                        placeholder="0">
                                    @error('stock_maximo')
                                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab: Presentaciones --}}
                    <div id="tab-presentaciones" class="tab-panel hidden space-y-4">

                        <div
                            class="bg-sky-50 border border-sky-200 text-sky-800 rounded-lg p-3 text-sm flex items-start gap-2">
                            <i class="fas fa-info-circle mt-0.5 text-sky-500 shrink-0"></i>
                            <span>Las presentaciones permiten manejar distintas formas de venta del mismo producto (unidad,
                                blister, caja, etc.)</span>
                        </div>

                        <div id="presentaciones-container" class="space-y-3">
                            {{-- Presentaciones dinámicas aquí --}}
                        </div>

                        <div class="text-center pt-1">
                            <button type="button" id="btn-agregar-presentacion"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-emerald-600 border border-emerald-300 rounded-lg hover:bg-emerald-50 transition-colors">
                                <i class="fas fa-plus text-xs"></i> Agregar Presentación
                            </button>
                        </div>

                        {{-- Template --}}
                        <template id="presentacion-template">
                            <div class="presentacion-item border border-slate-200 rounded-lg p-4 bg-white">
                                <div class="flex items-center justify-between mb-4">
                                    <p class="text-sm font-semibold text-slate-700 presentacion-titulo">Presentación #</p>
                                    <button type="button"
                                        class="btn-eliminar-presentacion w-7 h-7 flex items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600 transition-colors">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                            Unidad de Medida <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            class="select2 unidad-medida-select w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                            required>
                                            <option value="">Seleccione</option>
                                            @foreach ($units as $unit)
                                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                            Cantidad Equivalente <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number"
                                            class="cantidad-equivalente w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                            required min="1" step="0.01" placeholder="Ej: 10">
                                        <p class="text-xs text-slate-400 mt-1">¿Cuántas unidades base contiene?</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1.5">
                                            Precio de Venta <span class="text-red-500">*</span>
                                        </label>
                                        <div
                                            class="flex rounded-lg border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-transparent">
                                            <span
                                                class="flex items-center px-3 bg-slate-50 text-slate-500 text-xs border-r border-slate-300">S/.</span>
                                            <input type="number"
                                                class="precio-venta flex-1 px-3 py-2 text-sm bg-white focus:outline-none"
                                                required min="0" step="0.01" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="flex items-center gap-2.5 cursor-pointer">
                                        <input type="checkbox"
                                            class="es-presentacion-principal w-4 h-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
                                            value="1">
                                        <span class="es-presentacion-principal-label text-sm text-slate-700">Es la
                                            presentación principal</span>
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>{{-- /p-6 --}}

                {{-- Footer de acciones --}}
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                    <a href="{{ route('employee.products.index') }}"
                        class="px-4 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                        <i class="fas fa-save text-xs"></i>
                        {{ isset($products) ? 'Actualizar Producto' : 'Guardar Producto' }}
                    </button>
                </div>

            </div>{{-- /card --}}
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        let contadorPresentaciones = 0;

        $(document).ready(function() {
            inicializarTabs();
            inicializarSelect2();
            inicializarEventos();

            @if (isset($products) && $products->presentations->count())
                @foreach ($products->presentations as $presentacion)
                    agregarPresentacion({
                        unidad_medida_id: '{{ $presentacion->unit_id }}',
                        cantidad_equivalente: '{{ $presentacion->equivalent_amount }}',
                        precio_venta: '{{ $presentacion->sale_price }}',
                        es_presentacion_principal: {{ $presentacion->main_presentation ? 'true' : 'false' }}
                    });
                @endforeach
            @endif

            calcularUtilidades();
        });

        function inicializarTabs() {
            const btnActive = 'bg-white text-emerald-600 border border-slate-200 border-b-white shadow-sm -mb-px';
            const btnInactive = 'text-slate-500 hover:text-slate-700';

            document.querySelectorAll('.tab-btn').forEach(btn => {
                if (btn.classList.contains('active-tab')) {
                    btn.className =
                        'tab-btn active-tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ' +
                        btnActive;
                } else {
                    btn.className = 'tab-btn px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ' +
                        btnInactive;
                }

                btn.addEventListener('click', function() {
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.classList.remove('active-tab');
                        b.className =
                            'tab-btn px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ' +
                            btnInactive;
                    });
                    this.classList.add('active-tab');
                    this.className =
                        'tab-btn active-tab px-4 py-2 text-sm font-medium rounded-t-lg transition-colors ' +
                        btnActive;

                    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                    document.getElementById(this.dataset.target).classList.remove('hidden');
                });
            });
        }

        function inicializarSelect2() {
            $('.select2').select2({
                placeholder: "Seleccione una opción",
                allowClear: true,
                width: '100%'
            });
        }

        function agregarPresentacion(datos = null) {
            contadorPresentaciones++;

            const template = document.querySelector('#presentacion-template');
            const clone = document.importNode(template.content, true);

            const item = clone.querySelector('.presentacion-item');
            item.setAttribute('data-index', contadorPresentaciones);

            clone.querySelector('.presentacion-titulo').textContent = 'Presentación #' + contadorPresentaciones;

            const unidadSelect = clone.querySelector('.unidad-medida-select');
            unidadSelect.setAttribute('name', `presentaciones[${contadorPresentaciones}][unidad_medida_id]`);

            const cantidadInput = clone.querySelector('.cantidad-equivalente');
            cantidadInput.setAttribute('name', `presentaciones[${contadorPresentaciones}][cantidad_equivalente]`);

            const precioInput = clone.querySelector('.precio-venta');
            precioInput.setAttribute('name', `presentaciones[${contadorPresentaciones}][precio_venta]`);

            const esPrincipalCheck = clone.querySelector('.es-presentacion-principal');
            esPrincipalCheck.setAttribute('name', `presentaciones[${contadorPresentaciones}][es_presentacion_principal]`);
            esPrincipalCheck.setAttribute('id', `es_principal_${contadorPresentaciones}`);

            const esPrincipalLabel = clone.querySelector('.es-presentacion-principal-label');
            esPrincipalLabel.setAttribute('for', `es_principal_${contadorPresentaciones}`);

            if (datos) {
                unidadSelect.value = datos.unidad_medida_id;
                cantidadInput.value = datos.cantidad_equivalente;
                precioInput.value = datos.precio_venta;
                esPrincipalCheck.checked = datos.es_presentacion_principal;
            }

            document.querySelector('#presentaciones-container').appendChild(clone);

            $(item).find('.select2').select2({
                placeholder: "Seleccione una opción",
                allowClear: true,
                width: '100%'
            });
        }

        function inicializarEventos() {
            $('#precio_compra, #precio_venta_unidad, #precio_compra_paquete, #precio_venta_paquete').on('input',
                calcularUtilidades);

            $('#btn-agregar-presentacion').on('click', function() {
                agregarPresentacion();
            });

            $('#presentaciones-container').on('click', '.btn-eliminar-presentacion', function() {
                $(this).closest('.presentacion-item').remove();
            });
        }

        function inicializarEventos() {
            $('#precio_compra, #precio_venta_unidad, #precio_compra_paquete, #precio_venta_paquete').on('input',
                calcularUtilidades);

            $('#btn-agregar-presentacion').on('click', function() {
                agregarPresentacion();
            });

            $('#presentaciones-container').on('click', '.btn-eliminar-presentacion', function() {
                $(this).closest('.presentacion-item').remove();
            });
        }

        function calcularUtilidades() {
            const pc = parseFloat($('#precio_compra').val()) || 0;
            const pv = parseFloat($('#precio_venta_unidad').val()) || 0;
            const pcp = parseFloat($('#precio_compra_paquete').val()) || 0;
            const pvp = parseFloat($('#precio_venta_paquete').val()) || 0;

            const utilUnit = pv - pc;
            $('#utilidad_unitaria').text('S/. ' + utilUnit.toFixed(2));

            const margen = pc > 0 ? (utilUnit / pc) * 100 : 0;
            $('#margen_utilidad').text(margen.toFixed(2) + '%');

            $('#utilidad_paquete').text('S/. ' + (pvp - pcp).toFixed(2));
        }
    </script>
@endsection
