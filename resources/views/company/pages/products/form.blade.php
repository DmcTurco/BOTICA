<!-- Modal para crear/editar producto -->

<div class="modal fade" id="productoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
    aria-labelledby="myModalLabel" aria-hidden="true">

        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary">
                    <h5 class="modal-title text-white" id="productoModalLabel">Nuevo Producto</h5>
                    <button type="button" class="btn-close text-info" data-bs-dismiss="modal" aria-label="Close" style=" font-size:30px">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
                <form id="productoForm" action="" method="POST">
                    @csrf
                    <div class="modal-body">
                        <ul class="nav nav-pills nav-fill mb-3" id="productTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general"
                                    role="tab" aria-controls="general" aria-selected="true">
                                    <i class="fas fa-info-circle me-2"></i> Información General
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="precios-tab" data-toggle="tab" href="#precios" role="tab"
                                    aria-controls="precios" aria-selected="false">
                                    <i class="fas fa-dollar-sign me-2"></i> Precios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="presentaciones-tab" data-toggle="tab" href="#presentaciones"
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
                                                    required maxlength="20">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="categoria_id" class="form-control-label text-sm">Categoría <span
                                                    class="text-danger">*</span></label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="categoria_id" name="categoria_id"
                                                    required>
                                                    <option value="">Seleccionar categoría</option>
                                                    {{-- @foreach ($categorias as $categoria)
                                                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}
                                                        </option>
                                                    @endforeach --}}
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="nombre" class="form-control-label text-sm">Nombre del Producto <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group input-group-outline">
                                        <input type="text" class="form-control" id="nombre" name="nombre"
                                            required maxlength="150">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion" class="form-control-label text-sm">Descripción</label>
                                    <div class="input-group input-group-outline">
                                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="laboratorio_id"
                                                class="form-control-label text-sm">Laboratorio</label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="laboratorio_id"
                                                    name="laboratorio_id">
                                                    <option value="">Seleccionar laboratorio</option>
                                                    {{-- @foreach ($laboratorios as $laboratorio)
                                                        <option value="{{ $laboratorio->id }}">{{ $laboratorio->nombre }}
                                                        </option>
                                                    @endforeach --}}
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
                                                    name="principio_activo" maxlength="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="unidad_medida_id" class="form-control-label text-sm">Unidad de
                                                Medida <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="unidad_medida_id"
                                                    name="unidad_medida_id" required>
                                                    <option value="">Seleccionar unidad</option>
                                                    {{-- @foreach ($unidades as $unidad)
                                                        <option value="{{ $unidad->id }}">{{ $unidad->nombre }}</option>
                                                    @endforeach --}}
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="unidades_por_paquete" class="form-control-label text-sm">Cant.
                                                por
                                                Paquete</label>
                                            <div class="input-group input-group-outline">
                                                <input type="number" class="form-control" id="unidades_por_paquete"
                                                    name="unidades_por_paquete" min="0" step="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="fecha_vencimiento" class="form-control-label text-sm">Fecha
                                                Vencimiento</label>
                                            <div class="input-group input-group-outline">
                                                <input type="date" class="form-control" id="fecha_vencimiento"
                                                    name="fecha_vencimiento">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="stock_actual" class="form-control-label text-sm">Stock Actual
                                                <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-outline">
                                                <input type="number" class="form-control" id="stock_actual"
                                                    name="stock_actual" required min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="stock_minimo" class="form-control-label text-sm">Stock
                                                Mínimo</label>
                                            <div class="input-group input-group-outline">
                                                <input type="number" class="form-control" id="stock_minimo"
                                                    name="stock_minimo" min="0" step="1">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="stock_maximo" class="form-control-label text-sm">Stock
                                                Máximo</label>
                                            <div class="input-group input-group-outline">
                                                <input type="number" class="form-control" id="stock_maximo"
                                                    name="stock_maximo" min="0" step="1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" id="producto_gravado"
                                                    name="producto_gravado" value="1">
                                                <label class="form-check-label" for="producto_gravado">Producto
                                                    Gravado
                                                    (IGV)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input" id="requiere_receta"
                                                    name="requiere_receta" value="1">
                                                <label class="form-check-label" for="requiere_receta">Requiere Receta
                                                    Médica</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña de precios -->
                            <div class="tab-pane fade" id="precios" role="tabpanel" aria-labelledby="precios-tab">
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
                                                    name="precio_compra" required min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_compra_paquete"
                                                class="form-control-label text-sm">Precio
                                                Compra por Paquete</label>
                                            <div class="input-group input-group-outline">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">S/.</span>
                                                </div>
                                                <input type="number" class="form-control" id="precio_compra_paquete"
                                                    name="precio_compra_paquete" min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_venta_unidad" class="form-control-label text-sm">Precio
                                                Venta Unitario <span class="text-danger">*</span></label>
                                            <div class="input-group input-group-outline">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">S/.</span>
                                                </div>
                                                <input type="number" class="form-control" id="precio_venta_unidad"
                                                    name="precio_venta_unidad" required min="0"
                                                    step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="precio_venta_paquete"
                                                class="form-control-label text-sm">Precio
                                                Venta por Paquete</label>
                                            <div class="input-group input-group-outline">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">S/.</span>
                                                </div>
                                                <input type="number" class="form-control" id="precio_venta_paquete"
                                                    name="precio_venta_paquete" min="0" step="0.01">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bg-gradient-light shadow-sm mt-3">
                                    <div class="card-body">
                                        <h6 class="card-title">Cálculo de Utilidad</h6>
                                        <div class="row">
                                            <div class="col-md-4">
                                                <p class="mb-1 text-sm">Utilidad Unitaria:</p>
                                                <h5 class="text-success text-gradient" id="utilidad_unitaria">S/. 0.00
                                                </h5>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1 text-sm">Utilidad por Paquete:</p>
                                                <h5 class="text-success text-gradient" id="utilidad_paquete">S/. 0.00
                                                </h5>
                                            </div>
                                            <div class="col-md-4">
                                                <p class="mb-1 text-sm">Margen de Utilidad:</p>
                                                <h5 class="text-primary text-gradient" id="margen_utilidad">0.00%</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pestaña de presentaciones adicionales -->
                            <div class="tab-pane fade" id="presentaciones" role="tabpanel"
                                aria-labelledby="presentaciones-tab">
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
                                                            <select class="form-control unidad-medida-select" required>
                                                                <option value="">Seleccione</option>
                                                                {{-- @foreach ($unidades as $unidad)
                                                                    <option value="{{ $unidad->id }}">
                                                                        {{ $unidad->nombre }}</option>
                                                                @endforeach --}}
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
                                                        class="form-check-input es-presentacion-principal"
                                                        id="">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn bg-gradient-primary">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
