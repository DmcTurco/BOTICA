@extends('company/layouts/base')

@section('title', isset($categoria) ? 'Editar Categoría' : 'Nueva Categoría')

@section('content-area')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-primary shadow-primary border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">
                            <i class="fas fa-tags me-2"></i> {{ isset($categoria) ? 'Editar Categoría' : 'Nueva Categoría' }}
                        </h6>
                    </div>
                </div>
                <div class="card-body">
                    <form id="categoriaForm" action="{{ isset($categoria) ? route('company.categories.update', $categoria->id) : route('company.categories.store') }}" method="POST">
                        @csrf
                        @if(isset($categoria))
                            @method('PUT')
                        @endif
                        
                        <!-- Mensajes de error -->
                        @if ($errors->any())
                        <div class="alert alert-danger text-white">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre" class="form-control-label text-sm">Nombre <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-outline">
                                        <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                            id="nombre" name="nombre" required maxlength="100" 
                                            value="{{ old('nombre', $categoria->name ?? '') }}">
                                    </div>
                                    @error('nombre')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="icono" class="form-control-label text-sm">Icono</label>
                                    <div class="input-group input-group-outline">
                                        <input type="text" class="form-control @error('icono') is-invalid @enderror" 
                                            id="icono" name="icono" placeholder="fa-tag" 
                                            value="{{ old('icono', $categoria->icon ?? '') }}">
                                    </div>
                                    <small class="form-text text-muted">Usa clases de Font Awesome (ej: fa-tag, fa-pills)</small>
                                    @error('icono')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <label for="descripcion" class="form-control-label text-sm">Descripción</label>
                            <div class="input-group input-group-outline">
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                    id="descripcion" name="descripcion" rows="3" maxlength="255">{{ old('descripcion', $categoria->description ?? '') }}</textarea>
                            </div>
                            @error('descripcion')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                        
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" 
                                {{ old('activo', isset($categoria) ? $categoria->status : '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="activo">Categoría Activa</label>
                        </div>
                        
                        @if(isset($categoria))
                        <!-- Información adicional solo en modo edición -->
                        <div class="card mt-4 bg-gradient-light shadow-sm">
                            <div class="card-body">
                                <h6 class="text-dark mb-3">Información adicional</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="text-sm mb-1">Productos asociados:</p>
                                        <h5 class="font-weight-bold">{{ $productosCount ?? 0 }}</h5>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-sm mb-1">Fecha creación:</p>
                                        <h5 class="font-weight-bold">{{ $categoria->created_at->format('d/m/Y') }}</h5>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="text-sm mb-1">Última actualización:</p>
                                        <h5 class="font-weight-bold">{{ $categoria->updated_at->format('d/m/Y H:i') }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Vista previa del icono -->
                        <div class="mt-4 mb-3">
                            <label class="form-control-label text-sm">Vista previa del icono:</label>
                            <div class="d-flex align-items-center mt-2">
                                <div class="icon-shape icon-lg bg-gradient-primary shadow text-center border-radius-lg me-2">
                                    <i id="icono-preview" class="fas {{ isset($categoria) ? ($categoria->icon ?: 'fa-tag') : 'fa-tag' }} text-white opacity-10"></i>
                                </div>
                                <p class="text-sm mb-0">El icono se mostrará en la lista de categorías y en los productos asociados.</p>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-md-6 text-start">
                                <a href="{{ route('company.categories.index') }}" class="btn btn-light">
                                    <i class="fas fa-arrow-left me-2"></i> Volver
                                </a>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" class="btn bg-gradient-primary">
                                    <i class="fas fa-save me-2"></i> {{ isset($categoria) ? 'Actualizar' : 'Guardar' }} Categoría
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
    $(document).ready(function() {
        // Actualizar vista previa del icono
        $('#icono').on('input', function() {
            const iconoClass = $(this).val() || 'fa-tag';
            $('#icono-preview').attr('class', 'fas ' + iconoClass + ' text-white opacity-10');
        });
        
        // Inicializar con el valor actual
        const iconoInicial = $('#icono').val() || 'fa-tag';
        $('#icono-preview').attr('class', 'fas ' + iconoInicial + ' text-white opacity-10');
    });
</script>
@endsection