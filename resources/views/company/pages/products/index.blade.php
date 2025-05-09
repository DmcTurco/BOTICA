@extends('company/layouts/base')

@section('title', 'Ventana de Productos')

@section('content-area')
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <!-- Cabecera de la tarjeta -->
                    <div class="card-header p-0 position-relative mt-n4 mx-0 z-index-2">
                        <div
                            class="bg-gradient-primary shadow-primary border-radius-lg px-3 py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 text-white">Gestión de Productos</h6>
                                <p class="mb-0 text-white text-sm opacity-8">Administra el inventario de productos de la
                                    farmacia</p>
                            </div>
                            <div>
                                <a href="{{ route('company.products.create') }}" class="btn btn-sm btn-white" ><i class="fas fa-plus me-2"></i> Nuevo Producto</a>
                                {{-- <button type="button" class="btn btn-sm btn-white" data-toggle="modal"
                                    data-target="#productoModal">
                                    <i class="fas fa-plus me-2"></i> Nuevo Producto
                                </button> --}}
                            </div>
                        </div>
                    </div>

                    <!-- Cuerpo de la tarjeta -->
                    <div class="card-body px-0 pt-0 pb-2">
                        <!-- Filtros de búsqueda -->
                        <div class="p-3">
                            <form action="" method="GET" id="filtrosForm">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="buscar" class="text-sm text-secondary mb-2">Buscar:</label>
                                            <div class="input-group input-group-outline">
                                                <input type="text" class="form-control" id="buscar" name="buscar"
                                                    placeholder="Código, nombre o principio activo" value="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="categoria" class="text-sm text-secondary mb-2">Categoría:</label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="categoria" name="categoria">
                                                    <option value="">Todas las categorías</option>
                                                    @foreach ($categorias as $categoria)
                                                        <option value="{{ $categoria->id }}" 
                                                            {{ request('categoria') == $categoria->id ? 'selected' : '' }}>
                                                            {{ $categoria->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="laboratorio"
                                                class="text-sm text-secondary mb-2">Laboratorio:</label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="laboratorio" name="laboratorio">
                                                    <option value="">Todos los laboratorios</option>
                                                    {{-- @foreach ($laboratorios as $laboratorio)
                                                        <option value="{{ $laboratorio->id }}" 
                                                            {{ request('laboratorio') == $laboratorio->id ? 'selected' : '' }}>
                                                            {{ $laboratorio->nombre }}
                                                        </option>
                                                    @endforeach --}}
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <div class="form-group w-100">
                                            <button type="submit" class="btn bg-gradient-primary w-100 mb-0">
                                                <i class="fas fa-search me-2"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tabla de Productos -->
                        <div class="table-responsive p-3">
                            <div class="d-flex justify-content-end mb-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        id="exportarMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-download me-2"></i> Exportar
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportarMenu">
                                        <a class="dropdown-item" href="#"><i class="fas fa-file-excel me-2"></i>
                                            Excel</a>
                                        <a class="dropdown-item" href="#"><i class="fas fa-file-pdf me-2"></i> PDF</a>
                                    </div>
                                </div>
                            </div>

                            <table class="table align-items-center mb-0" id="productosTable">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Código</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Nombre</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Categoría</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Stock</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Precio Venta</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Laboratorio</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Vencimiento</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Estado</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- @forelse($productos as $producto)
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div>
                                                    @if ($producto->foto)
                                                        <img src="{{ asset($producto->foto) }}" class="avatar avatar-sm me-3" alt="{{ $producto->nombre }}">
                                                    @else
                                                        <div class="icon-shape icon-sm bg-gradient-primary shadow text-center border-radius-sm">
                                                            <i class="fas fa-capsules text-white opacity-10"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $producto->codigo }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $producto->nombre }}</p>
                                            <p class="text-xs text-secondary mb-0">{{ $producto->principio_activo }}</p>
                                        </td>
                                        <td>
                                            <p class="text-xs font-weight-bold mb-0">{{ $producto->categoria->nombre }}</p>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge bg-gradient-{{ $producto->stock_actual <= $producto->stock_minimo ? 'warning' : 'success' }}">
                                                {{ $producto->stock_actual }}
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">S/. {{ number_format($producto->precio_venta_unidad, 2) }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold">{{ $producto->laboratorio ? $producto->laboratorio->nombre : '-' }}</span>
                                        </td>
                                        <td class="align-middle text-center">
                                            @if ($producto->fecha_vencimiento)
                                                <span class="badge bg-gradient-{{ now()->diffInMonths($producto->fecha_vencimiento, false) < 3 ? 'danger' : 'info' }}">
                                                    {{ $producto->fecha_vencimiento->format('d/m/Y') }}
                                                </span>
                                            @else
                                                <span class="text-secondary text-xs">-</span>
                                            @endif
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="badge bg-gradient-{{ $producto->activo ? 'success' : 'secondary' }}">
                                                {{ $producto->activo ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="ms-auto text-end">
                                                <a href="javascript:;" class="btn btn-link text-dark px-3 mb-0" data-toggle="modal" data-target="#productoModal" data-producto="{{ $producto->codigo }}">
                                                    <i class="fas fa-pencil-alt text-dark me-2"></i>Editar
                                                </a>
                                                <a href="javascript:;" class="btn btn-link text-danger text-gradient px-3 mb-0 btn-eliminar" data-codigo="{{ $producto->codigo }}" data-nombre="{{ $producto->nombre }}">
                                                    <i class="far fa-trash-alt me-2"></i>Eliminar
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <p class="text-sm mb-0">No hay productos registrados</p>
                                        </td>
                                    </tr>
                                    @endforelse --}}
                                </tbody>
                            </table>

                            <!-- Paginación -->
                            <div class="d-flex justify-content-end mt-3">
                                {{-- {{ $productos->appends(request()->except('page'))->links() }} --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
