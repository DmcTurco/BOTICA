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
    inicializarDataTable();
    inicializarFormulario();
    inicializarValidaciones();
    inicializarEventos();
    inicializarSelect2();
});

/**
 * Inicializa la tabla de datos
 */
function inicializarDataTable() {
    // Si existe el elemento en la página
    if ($('#productosTable').length) {
        $('#productosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            },
            responsive: true,
            stateSave: true,
            pageLength: 10,
            columnDefs: [
                { orderable: false, targets: [-1] } // Columna de acciones no ordenable
            ]
        });
    }
}

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
    // Limpiar el formulario al abrir el modal para un nuevo producto
    $('#productoModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const codigoProducto = button.data('producto');
        
        if (!codigoProducto) {
            // Nuevo producto
            $(this).find('.modal-title').text('Nuevo Producto');
            $('#productoForm').attr('action', '/productos').find("input[name='_method']").remove();
            $('#productoForm')[0].reset();
            $('#codigo').prop('readonly', false);
            $('#presentaciones-container').empty();
            contadorPresentaciones = 0;
            calcularUtilidades();
        } else {
            cargarDatosProducto(codigoProducto);
        }
    });

    // Resetear al cerrar el modal
    $('#productoModal').on('hidden.bs.modal', function() {
        $('#productoForm').trigger("reset");
        $('#presentaciones-container').empty();
        $('#productoForm').removeClass('was-validated');
        $('.is-invalid').removeClass('is-invalid');
    });
}

/**
 * Carga los datos de un producto para editar
 */
function cargarDatosProducto(codigo) {
    $('#productoModal').find('.modal-title').text('Editar Producto');
    
    // Limpiar presentaciones existentes
    $('#presentaciones-container').empty();
    contadorPresentaciones = 0;
    
    // Cargar datos del producto
    $.ajax({
        url: `/productos/${codigo}/edit`,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            const form = $('#productoForm');
            form.attr('action', `/productos/${codigo}`);
            form.append('<input type="hidden" name="_method" value="PUT">');
            
            // Llenar campos del formulario
            $('#codigo').val(data.codigo).prop('readonly', true);
            $('#nombre').val(data.nombre);
            $('#descripcion').val(data.descripcion);
            $('#categoria_id').val(data.categoria_id);
            $('#laboratorio_id').val(data.laboratorio_id);
            $('#principio_activo').val(data.principio_activo);
            $('#precio_compra').val(data.precio_compra);
            $('#precio_compra_paquete').val(data.precio_compra_paquete);
            $('#unidades_por_paquete').val(data.unidades_por_paquete);
            $('#precio_venta_unidad').val(data.precio_venta_unidad);
            $('#precio_venta_paquete').val(data.precio_venta_paquete);
            $('#unidad_medida_id').val(data.unidad_medida_id);
            $('#stock_actual').val(data.stock_actual);
            $('#stock_minimo').val(data.stock_minimo);
            $('#stock_maximo').val(data.stock_maximo);
            $('#fecha_vencimiento').val(data.fecha_vencimiento ? data.fecha_vencimiento.split('T')[0] : '');
            $('#producto_gravado').prop('checked', data.producto_gravado);
            $('#requiere_receta').prop('checked', data.requiere_receta);
            
            // Actualizar selects mejorados
            if ($('.select2').length) {
                $('.select2').trigger('change');
            }
            
            // Calcular utilidades
            calcularUtilidades();
            
            // Cargar presentaciones
            if (data.presentaciones && data.presentaciones.length > 0) {
                data.presentaciones.forEach(function(presentacion) {
                    agregarPresentacion(presentacion);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error al cargar el producto:", error);
            mostrarAlerta('Error al cargar el producto. Inténtelo nuevamente.', 'danger');
        }
    });
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
    
    // Eliminar producto
    $('.btn-eliminar').on('click', function() {
        const codigo = $(this).data('codigo');
        const nombre = $(this).data('nombre');
        
        $('#nombre-producto-eliminar').text(codigo + ' - ' + nombre);
        $('#form-eliminar').attr('action', `/productos/${codigo}`);
        $('#confirmarEliminarModal').modal('show');
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