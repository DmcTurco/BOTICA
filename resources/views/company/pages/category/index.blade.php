@extends('company/layouts/base')

@section('title', 'Gestión de Categorías')

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
                                <h6 class="mb-0 text-white">Gestión de Categorías</h6>
                                <p class="mb-0 text-white text-sm opacity-8">Administra las categorías de productos para la
                                    farmacia</p>
                            </div>
                            <div>
                                <a href="{{ route('company.categories.create') }}" class="btn btn-sm btn-white">
                                    <i class="fas fa-plus me-2"></i> Nueva Categoría
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Cuerpo de la tarjeta -->
                    <div class="card-body px-0 pt-0 pb-2">
                        <!-- Filtros de búsqueda -->
                        <div class="p-3">
                            <form action="{{ route('company.categories.index') }}" method="GET" id="filtrosForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="buscar" class="text-sm text-secondary mb-2">Buscar:</label>
                                            <div class="input-group input-group-outline">
                                                <input type="text" class="form-control" id="buscar" name="buscar"
                                                    placeholder="Nombre o descripción de categoría"
                                                    value="{{ request('buscar') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="estado" class="text-sm text-secondary mb-2">Estado:</label>
                                            <div class="input-group input-group-static">
                                                <select class="form-control" id="estado" name="estado">
                                                    <option value="">Todos</option>
                                                    <option value="1" {{ request('estado') == '1' ? 'selected' : '' }}>
                                                        Activo</option>
                                                    <option value="0" {{ request('estado') == '0' ? 'selected' : '' }}>
                                                        Inactivo</option>
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

                        <!-- Tabla de Categorías -->
                        <div class="table-responsive p-3">
                            <div class="d-flex justify-content-end mb-3">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                        id="exportarMenu" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        <i class="fas fa-download me-2"></i> Exportar
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="exportarMenu">
                                        <a class="dropdown-item" href="">
                                            <i class="fas fa-file-excel me-2"></i> Excel
                                        </a>
                                        <a class="dropdown-item" href="">
                                            <i class="fas fa-file-pdf me-2"></i> PDF
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID
                                        </th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Nombre</th>
                                        <th
                                            class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                                            Descripción</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Productos</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Estado</th>
                                        <th
                                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                            Fecha Creación</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categorias as $categoria)
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $categoria->id }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div>
                                                        <div
                                                            class="icon-shape icon-sm bg-gradient-primary shadow text-center border-radius-sm me-2">
                                                            <i
                                                                class="fas {{ $categoria->icono ?? 'fa-tag' }} text-white opacity-10"></i>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $categoria->name }}</h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs text-secondary mb-0">
                                                    {{ Str::limit($categoria->description, 50) }}</p>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="badge bg-gradient-info">
                                                    {{ $categoria->productos_count ?? 0 }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span
                                                    class="badge bg-gradient-{{ $categoria->status ? 'success' : 'secondary' }}">
                                                    {{ $categoria->status ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    {{ $categoria->created_at->format('d/m/Y') }}
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="ms-auto text-end">
                                                    <a href="{{ route('company.categories.edit', $categoria->id) }}"
                                                        class="btn btn-link text-dark px-3 mb-0">
                                                        <i class="fas fa-pencil-alt text-dark me-2"></i>Editar
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-link text-danger text-gradient px-3 mb-0 btn-eliminar-categoria"
                                                        data-id="{{ $categoria->id }}"
                                                        data-nombre="{{ $categoria->name }}">
                                                        <i class="far fa-trash-alt me-2"></i>Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4">
                                                <p class="text-sm mb-0">No hay categorías registradas</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            <!-- Paginación -->
                            <div class="d-flex justify-content-end mt-3">
                                @if (isset($categorias) && method_exists($categorias, 'links'))
                                    {{ $categorias->withQueryString()->links() }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar categoría -->
    <div class="modal fade" id="confirmarEliminarModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title text-white">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar la categoría <strong id="nombre-categoria-eliminar"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer y podría afectar a los productos asociados.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <form id="form-eliminar" action="" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <script>
        $(document).ready(function() {
            // Configurar modal de eliminación
            $('.btn-eliminar-categoria').on('click', function() {
                const id = $(this).data('id');
                const nombre = $(this).data('nombre');

                $('#nombre-categoria-eliminar').text(nombre);
                // Usar la función route de Laravel con un placeholder
                const url = "{{ route('company.categories.destroy', ':id') }}";
                $('#form-eliminar').attr('action', url.replace(':id', id));
                $('#confirmarEliminarModal').modal('show');
            });
        });
    </script>

@endsection
