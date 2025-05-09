@extends('company/layouts/base')

@section('title', 'Registrar Producto')

@section('content-area')

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            <i class="fas fa-box me-2"></i> {{ isset($producto) ? 'Editar Producto' : 'Nuevo Producto' }}
                        </h6>
                    </div>
                </div>
                <div class="card-body">
                    <form id="productoForm" action="{{ isset($producto) ? route('company.products.update', $producto->codigo) : route('company.products.store') }}" method="POST">
                        @csrf
                        @if(isset($producto))
                            @method('PUT')
                        @endif

                        <ul class="nav nav-pills nav-fill mb-3" id="productTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="general-tab" data-bs-toggle="tab" href="#general" role="tab"
                                    aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle me-2"></i> Información General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="precios-tab" data-bs-toggle="tab" href="#precios" role="tab"
                                    aria-controls="precios" aria-selected="false">
                                    <i class="fas fa-dollar-sign me-2"></i> Precios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="presentaciones-tab" data-bs-toggle="tab" href="#presentaciones"
                                    role="tab" aria-controls="presentaciones" aria-selected="false">
                                    <i class="fas fa-box me-2"></i> Presentaciones
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content p-2" id="productTabContent">
                            <!-- Pestaña de información general -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel"
                                aria-labelledby="general-tab">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="codigo" class="form-control-label text-sm">Código <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-outline">
                                                <input type="text" class="form-control" id="codigo" name="codigo"
                                                    required maxlength="20" value="{{ $producto->codigo ?? '' }}" {{ isset($producto) ? 'readonly' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="categoria_id" class="form-control-label text-sm">Categoría <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="categoria_id" name="categoria_id" required>
                                                    <option value="">Seleccionar categoría</option>
                                                    @foreach ($categorias as $categoria)
                                                        <option value="{{ $categoria->id }}" {{ isset($producto) && $producto->categoria_id == $categoria->id ? 'selected' : '' }}>
                                                            {{ $categoria->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="nombre" class="form-control-label text-sm">Nombre del Producto <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-outline">
                                        <input type="text" class="form-control" id="nombre" name="nombre" required
                                            maxlength="150" value="{{ $producto->nombre ?? '' }}">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion" class="form-control-label text-sm">Descripción</label>
                                    <div class="input-group input-group-outline">
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2">{{ $producto->description ?? '' }}</textarea>
                                    </div>
                                </div>

                                <!-- Resto de campos de información general aquí... -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="laboratorio_id"
                                                class="form-control-label text-sm">Laboratorio</label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="laboratorio_id" name="laboratorio_id">
                                                    <option value="">Seleccionar laboratorio</option>
                                                    @foreach ($laboratorios as $laboratorio)
                                                        <option value="{{ $laboratorio->id }}" {{ isset($producto) && $producto->laboratorio_id == $laboratorio->id ? 'selected' : '' }}>
                                                            {{ $laboratorio->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="principio_activo" class="form-control-label text-sm">Principio
                                                Activo</label>
                                            <div class="input-group input-group-outline">
                                                <input type="text" class="form-control" id="principio_activo"
                                                    name="principio_activo" maxlength="100" value="{{ $producto->principio_activo ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Más campos... -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="unidad_medida_id" class="form-control-label text-sm">Unidad de
                                                Medida <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control select2" id="unidad_medida_id"
                                                    name="unidad_medida_id" required>
                                                    <option value="">Seleccionar unidad</option>
                                                    @foreach ($unidades as $unidad)
                                                        <option value="{{ $unidad->id }}" {{ isset($producto) && $producto->unidad_medida_id == $unidad->id ? 'selected' : '' }}>
                                                            {{ $unidad->nombre }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Resto de campos del formulario -->
                                </div>
                            </div>

                            <!-- Pestaña de precios -->
                            <div class="tab-pane fade" id="precios" role="tabpanel" aria-labelledby="precios-tab">
                                <!-- Contenido de la pestaña precios -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_compra" class="form-control-label text-sm">Precio
                                                Compra
                                                Unitario <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-outline">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">S/.</span>
                                                </div>
                                                <input type="number" class="form-control" id="precio_compra"
                                                    name="precio_compra" required min="0" step="0.01" value="{{ $producto->precio_compra ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_compra_paquete" class="form-control-label text-sm">Precio
                                                Compra por Paquete</label>
                                            <div class="input-group input-group-outline">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">S/.</span>
                                                </div>
                                                <input type="number" class="form-control" id="precio_compra_paquete"
                                                    name="precio_compra_paquete" min="0" step="0.01" value="{{ $producto->precio_compra_paquete ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Resto de campos de precios -->
                            </div>

                            <!-- Pestaña de presentaciones adicionales -->
                            <div class="tab-pane fade" id="presentaciones" role="tabpanel"
                                aria-labelledby="presentaciones-tab">
                                <!-- Contenido de la pestaña presentaciones -->
                                <div class="alert alert-info text-white"
                                    style="background-image: linear-gradient(195deg, #49a3f1 0%, #1A73E8 100%);">
                                    <i class="fas fa-info-circle me-2"></i> Las presentaciones permiten manejar
                                    distintas
                                    formas de venta del mismo producto (unidad, blister, caja, etc.)
                                </div>

                                <div id="presentaciones-container">
                                    <!-- Aquí se cargarán dinámicamente las presentaciones -->
                                </div>

                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        id="btn-agregar-presentacion">
                                        <i class="fas fa-plus me-2"></i> Agregar Presentación
                                    </button>
                                </div>

                                <!-- Template para nuevas presentaciones -->
                                <template id="presentacion-template">
                                    <div class="card mb-3 presentacion-item">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0 text-sm">Presentación #</h6>
                                                <button type="button"
                                                    class="btn btn-sm text-danger btn-eliminar-presentacion">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="form-control-label text-sm">Unidad de Medida
                                                            <span class="text-danger">*</span></label>
                                                        <div class="input-group input-group-static">
                                                            <select class="form-control select2 unidad-medida-select" required>
                                                                <option value="">Seleccione</option>
                                                                @foreach ($unidades as $unidad)
                                                                    <option value="{{ $unidad->id }}">
                                                                        {{ $unidad->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="form-control-label text-sm">Cantidad Equivalente
                                                            <span class="text-danger">*</span></label>
                                                        <div class="input-group input-group-outline">
                                                            <input type="number"
                                                                class="form-control cantidad-equivalente" required
                                                                min="1" step="0.01">
                                                        </div>
                                                        <small class="form-text text-muted">¿Cuántas unidades base
                                                            contiene?</small>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label class="form-control-label text-sm">Precio de Venta <span
                                                                class="text-danger">*</span></label>
                                                        <div class="input-group input-group-outline">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text">S/.</span>
                                                            </div>
                                                            <input type="number" class="form-control precio-venta"
                                                                required min="0" step="0.01">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox"
                                                        class="form-check-input es-presentacion-principal" id="">
                                                    <label class="form-check-label es-presentacion-principal-label"
                                                        for="">
                                                        Es la presentación principal
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-6 text-start">
                                <a href="{{ route('company.products.index') }}" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i> Volver
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn bg-gradient-primary">
                                    <i class="fas fa-save me-2"></i> Guardar Producto
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Mantener el mismo JavaScript que ya tienes
    
    /**
     * Funcionalidad JavaScript para la gestión de productos
     */

    // Variables globales
    let contadorPresentaciones = 0;
    let datosProductos = [];

    /**
     * Inicialización cuando el documento está listo
     */
    $(document).ready(function() {
        // Inicializar componentes
        inicializarSelect2();
        inicializarFormulario();
        inicializarValidaciones();
        inicializarEventos();
        
        // Si estamos en edición, cargar las presentaciones
        @if(isset($producto) && $producto->presentaciones)
            @foreach($producto->presentaciones as $presentacion)
                agregarPresentacion({
                    unidad_medida_id: '{{ $presentacion->unidad_medida_id }}',
                    cantidad_equivalente: '{{ $presentacion->cantidad_equivalente }}',
                    precio_venta: '{{ $presentacion->precio_venta }}',
                    es_presentacion_principal: {{ $presentacion->es_presentacion_principal ? 'true' : 'false' }}
                });
            @endforeach
        @endif
        
        // Calcular utilidades iniciales
        calcularUtilidades();
    });

    /**
     * Inicializa los selectores con búsqueda mejorada
     */
    function inicializarSelect2() {
        $('.select2').select2({
            placeholder: "Seleccione una opción",
            allowClear: true,
            width: '100%'
        });
    }

    /**
     * Inicializa el formulario de productos
     */
    function inicializarFormulario() {
        // Inicializaciones específicas del formulario
    }

    /**
     * Agrega una presentación al formulario
     */
    function agregarPresentacion(datos = null) {
        contadorPresentaciones++;
        
        // Clonar el template
        const template = document.querySelector('#presentacion-template');
        const clone = document.importNode(template.content, true);
        
        // Actualizar IDs y nombres
        const presentacionItem = clone.querySelector('.presentacion-item');
        presentacionItem.setAttribute('data-index', contadorPresentaciones);
        
        // Actualizar el número de presentación
        const titulo = presentacionItem.querySelector('h6');
        titulo.textContent = 'Presentación #' + contadorPresentaciones;
        
        // Actualizar los nombres de los campos
        const unidadSelect = presentacionItem.querySelector('.unidad-medida-select');
        unidadSelect.setAttribute('name', `presentaciones[${contadorPresentaciones}][unidad_medida_id]`);
        
        const cantidadInput = presentacionItem.querySelector('.cantidad-equivalente');
        cantidadInput.setAttribute('name', `presentaciones[${contadorPresentaciones}][cantidad_equivalente]`);
        
        const precioInput = presentacionItem.querySelector('.precio-venta');
        precioInput.setAttribute('name', `presentaciones[${contadorPresentaciones}][precio_venta]`);
        
        const esPrincipalCheck = presentacionItem.querySelector('.es-presentacion-principal');
        esPrincipalCheck.setAttribute('name', `presentaciones[${contadorPresentaciones}][es_presentacion_principal]`);
        esPrincipalCheck.setAttribute('id', `es_principal_${contadorPresentaciones}`);
        esPrincipalCheck.setAttribute('value', '1');
        
        const esPrincipalLabel = presentacionItem.querySelector('.es-presentacion-principal-label');
        esPrincipalLabel.setAttribute('for', `es_principal_${contadorPresentaciones}`);
        
        // Si se proporcionaron datos, llenar los campos
        if (datos) {
            unidadSelect.value = datos.unidad_medida_id;
            cantidadInput.value = datos.cantidad_equivalente;
            precioInput.value = datos.precio_venta;
            esPrincipalCheck.checked = datos.es_presentacion_principal;
        }
        
        // Agregar al contenedor
        document.querySelector('#presentaciones-container').appendChild(clone);
        
        // Inicializar select2 si existe
        if ($('.select2').length) {
            $(presentacionItem).find('.select2').select2({
                placeholder: "Seleccione una opción",
                allowClear: true,
                width: '100%'
            });
        }
    }

    /**
     * Inicializa los eventos
     */
    function inicializarEventos() {
        // Calcular utilidades al cambiar los precios
        $('#precio_compra, #precio_venta_unidad, #precio_compra_paquete, #precio_venta_paquete').on('input', calcularUtilidades);
        
        // Agregar nueva presentación
        $('#btn-agregar-presentacion').on('click', function() {
            agregarPresentacion();
        });
        
        // Eliminar presentación (delegación de eventos)
        $('#presentaciones-container').on('click', '.btn-eliminar-presentacion', function() {
            $(this).closest('.presentacion-item').remove();
        });
        
        // Inicializar los tabs de Bootstrap 5
        document.querySelectorAll('#productTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('href');
                
                // Desactivar todos los tabs
                document.querySelectorAll('#productTabs .nav-link').forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                
                // Desactivar todos los contenidos
                document.querySelectorAll('.tab-pane').forEach(p => {
                    p.classList.remove('show', 'active');
                });
                
                // Activar el tab seleccionado
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                
                // Activar el contenido seleccionado
                document.querySelector(tabId).classList.add('show', 'active');
            });
        });
    }

    /**
     * Calcula las utilidades basadas en los precios
     */
    function calcularUtilidades() {
        const precioCompra = parseFloat($('#precio_compra').val()) || 0;
        const precioVenta = parseFloat($('#precio_venta_unidad').val()) || 0;
        const precioCompraPaquete = parseFloat($('#precio_compra_paquete').val()) || 0;
        const precioVentaPaquete = parseFloat($('#precio_venta_paquete').val()) || 0;
        
        // Calcular utilidad unitaria
        const utilidadUnitaria = precioVenta - precioCompra;
        $('#utilidad_unitaria').text('S/. ' + utilidadUnitaria.toFixed(2));
        
        // Calcular utilidad por paquete
        const utilidadPaquete = precioVentaPaquete - precioCompraPaquete;
        $('#utilidad_paquete').text('S/. ' + utilidadPaquete.toFixed(2));
        
        // Calcular margen de utilidad
        let margenUtilidad = 0;
        if (precioCompra > 0) {
            margenUtilidad = (utilidadUnitaria / precioCompra) * 100;
        }
        $('#margen_utilidad').text(margenUtilidad.toFixed(2) + '%');
    }

    /**
     * Inicializa validaciones del formulario
     */
    function inicializarValidaciones() {
        // Validación del lado del cliente
        const formulario = document.getElementById('productoForm');
        if (formulario) {
            formulario.addEventListener('submit', function(event) {
                if (!formulario.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                formulario.classList.add('was-validated');
            }, false);
        }
    }

    /**
     * Muestra una alerta en la página
     */
    function mostrarAlerta(mensaje, tipo = 'info') {
        const alertaHTML = `
            <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                ${mensaje}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        `;
        
        $('.container-fluid').first().prepend(alertaHTML);
        
        // Auto-ocultar después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
</script>


@endsection