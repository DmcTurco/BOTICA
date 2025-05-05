@extends('company/layouts/base')

@section('title', 'Ventana de Ventas')

@section('content-area')

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header p-0 position-relative mt-n4 mx-0 z-index-2">
                            <div class="bg-gradient-warning shadow-primary border-radius-lg px-3 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 text-white">Ventana de Atención al cliente</h6>
                                    <p class="mb-0 text-white text-sm opacity-8">Facturación - Boletas y Notas de Venta</p>
                                </div>
                                <div class="d-flex align-items-center">
                                    <button class="btn btn-icon btn-outline-white btn-sm ms-2">
                                        <i class="fas fa-user"></i>
                                    </button>
                                    <button class="btn btn-icon btn-outline-white btn-sm ms-2">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <button class="btn btn-icon btn-outline-white btn-sm ms-2">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <div class="input-group ms-2" style="width: 220px;">
                                        <span class="input-group-text text-body">
                                            <i class="fas fa-receipt pe-2"></i>Nro Comprobante
                                        </span>
                                        <input type="text" class="form-control">
                                        <button class="btn btn-white mb-0">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    <button class="btn btn-icon btn-sm btn-white ms-2">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card-body px-0 pt-0 pb-0">
                            <div class="row m-0">
                                <!-- Tabla de productos -->
                                <div class="col-md-9 p-3">
                                    <div class="table-responsive">
                                        <table class="table align-items-center mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                        Id Prod.</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                                        Descripción del Producto</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                        Cant.</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                        Pre Unit.</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                        Importe</th>
                                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                        Operación</th>
                                                </tr>
                                            </thead>
                                            <tbody id="productos-seleccionados">
                                                <!-- Aquí se mostrarán los productos seleccionados -->
                                                <tr id="carrito-vacio">
                                                    <td colspan="6" class="text-center py-5">
                                                        <div class="mb-3 d-flex justify-content-center">
                                                            <div style="max-width: 80px;">
                                                                <svg viewBox="0 0 160 160" xmlns="http://www.w3.org/2000/svg">
                                                                    <g fill="none" stroke="#fd7e14" stroke-width="4">
                                                                        <!-- Papelera -->
                                                                        <path d="M40 40 L120 40 L110 140 L50 140 Z" stroke="#fd7e14" fill="#ffb57d" stroke-linejoin="round"/>
                                                                        <path d="M30 40 L130 40" stroke-linecap="round"/>
                                                                        <path d="M65 30 L95 30" stroke-linecap="round"/>
                                                                        <path d="M65 60 L65 120" stroke-linecap="round" stroke-dasharray="5,5"/>
                                                                        <path d="M80 60 L80 120" stroke-linecap="round" stroke-dasharray="5,5"/>
                                                                        <path d="M95 60 L95 120" stroke-linecap="round" stroke-dasharray="5,5"/>
                                                                    </g>
                                                                </svg>
                                                            </div>
                                                            {{-- <div class="ms-4 d-flex flex-column justify-content-center">
                                                                <h6 class="text-gradient text-warning mb-1">00</h6>
                                                            </div> --}}
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    
                                    <!-- Sección de pago y totales -->
                                    <div class="row mt-4">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label text-secondary text-xs font-weight-bold mb-2">Tipo Pago</label>
                                                <div class="input-group input-group-static mb-4">
                                                    <select class="form-control" id="tipoPago">
                                                        <option value="" disabled selected>Seleccionar</option>
                                                        <option value="1">Efectivo</option>
                                                        <option value="2">Tarjeta</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label text-secondary text-xs font-weight-bold mb-2">Nro Operación</label>
                                                <div class="input-group input-group-outline mb-4">
                                                    <input type="text" class="form-control" placeholder="Nro Operación">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-control-label text-secondary text-xs font-weight-bold mb-2">Tipo de Comprobante</label>
                                                <div class="input-group input-group-static mb-4">
                                                    <select class="form-control" id="tipoComprobante">
                                                        <option value="" disabled selected>Seleccionar</option>
                                                        <option value="1">Boleta</option>
                                                        <option value="2">Factura</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-control-label text-secondary text-xs font-weight-bold mb-2">Nombre del Cliente</label>
                                                <div class="input-group input-group-outline mb-3">
                                                    <input type="text" class="form-control" placeholder="Nombre del Cliente">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label class="form-control-label text-secondary text-xs font-weight-bold mb-2">Ruc - Dni</label>
                                                <div class="input-group input-group-outline mb-3">
                                                    <input type="text" class="form-control" placeholder="Ruc - Dni">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 d-flex justify-content-end align-items-end">
                                            <button class="btn bg-gradient-primary w-80 mb-3">Terminar Venta (F5)</button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Panel lateral para búsqueda de productos -->
                                <div class="col-md-3 border-start p-3">
                                    <h6 class="text-sm mb-3">Escanee o Ingrese código Producto</h6>
                                    <div class="input-group input-group-outline mb-3">
                                        <input type="text" class="form-control" placeholder="Código Producto">
                                        <button class="btn btn-outline-primary mb-0">
                                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;">
                                                <circle cx="10" cy="10" r="7" fill="none" stroke="#fd7e14" stroke-width="2"/>
                                                <line x1="15.5" y1="15.5" x2="22" y2="22" stroke="#fd7e14" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" id="checkTodo">
                                        <label class="form-check-label text-sm text-body" for="checkTodo">
                                            Todo
                                        </label>
                                    </div>
                                    
                                    <div class="card border shadow-xs mt-4">
                                        <div class="card-body p-3">
                                            <!-- Aquí iría la lista de productos -->
                                            <div class="d-flex justify-content-center align-items-center py-5">
                                                <p class="text-sm text-secondary mb-0">Sin resultados</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Footer con totales -->
                        <div class="card-footer p-3 border-top d-flex justify-content-end">
                            <div class="col-md-4">
                                <div class="row align-items-center">
                                    <div class="col-md-6 text-end">
                                        <p class="mb-1 text-secondary text-sm">Sub Total S/.</p>
                                        <p class="mb-1 text-secondary text-sm">Total Igv S/.</p>
                                        <p class="mb-0 text-warning text-lg font-weight-bold">Total Cobrar S/.</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1 text-dark text-sm">00.00</p>
                                        <p class="mb-1 text-dark text-sm">00.00</p>
                                        <p class="mb-0 text-lg font-weight-bold bg-gradient-warning text-white px-3 py-1 border-radius-lg text-center">00.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

            </div>
        </div>
    </div>

@endsection