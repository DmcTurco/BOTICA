@extends('employee/layouts/base')

@section('title', 'Productos')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    {{-- Header --}}
    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Productos</h1>
            <p class="text-sm text-slate-500 mt-0.5">Administra el inventario de la farmacia</p>
        </div>
        <a href="{{ route('employee.products.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nuevo Producto
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.products.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                           placeholder="Código, nombre o principio activo...">
                </div>
                <select name="categoria"
                        class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $categoria)
                        <option value="{{ $categoria->id }}" {{ request('categoria') == $categoria->id ? 'selected' : '' }}>
                            {{ $categoria->name }}
                        </option>
                    @endforeach
                </select>
                <select name="laboratorio"
                        class="sm:w-48 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">Todos los laboratorios</option>
                    @foreach($laboratories as $lab)
                        <option value="{{ $lab->id }}" {{ request('laboratorio') == $lab->id ? 'selected' : '' }}>
                            {{ $lab->name }}
                        </option>
                    @endforeach
                </select>
                <button type="submit"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap">
                    <i class="fas fa-search text-xs"></i> Filtrar
                </button>
                @if(request()->hasAny(['buscar','categoria','laboratorio','estado']))
                <a href="{{ route('employee.products.index') }}"
                   class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                    <i class="fas fa-xmark text-xs"></i> Limpiar
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

        {{-- Cabecera del card --}}
        <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200 shrink-0">
            <p class="text-sm font-semibold text-slate-800">
                Lista de productos
                <span class="ml-2 text-xs font-normal text-slate-400">{{ $products->total() }} registros</span>
            </p>
            <div class="relative">
                <button id="exportBtn"
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs border border-slate-200 rounded-lg text-slate-600 hover:bg-slate-50 transition-colors">
                    <i class="fas fa-download text-xs"></i> Exportar
                </button>
                <div id="exportMenu" class="absolute right-0 mt-1 w-36 bg-white border border-slate-200 rounded-lg shadow-lg z-10" style="display:none">
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-t-lg">
                        <i class="fas fa-file-excel text-emerald-600 text-xs"></i> Excel
                    </a>
                    <a href="#" class="flex items-center gap-2 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 rounded-b-lg">
                        <i class="fas fa-file-pdf text-red-600 text-xs"></i> PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Área scrollable --}}
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Código</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Categoría</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Stock</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">P. Venta</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden xl:table-cell">Laboratorio</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $producto)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-3 text-slate-500 text-xs font-mono">{{ $producto->code }}</td>
                        <td class="px-5 py-3">
                            <div>
                                <p class="font-medium text-slate-800">{{ $producto->name }}</p>
                                @if($producto->active_ingredient)
                                    <p class="text-xs text-slate-400">{{ $producto->active_ingredient }}</p>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-slate-600 text-xs hidden md:table-cell">
                            {{ $producto->category->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ ($producto->branchStocks->first()?->stock_minimum) && ($producto->branchStocks->first()?->stock_actual ?? 0) <= $producto->branchStocks->first()?->stock_minimum
                                    ? 'bg-amber-50 text-amber-700'
                                    : 'bg-emerald-50 text-emerald-700' }}">
                                {{ $producto->branchStocks->first()?->stock_actual ?? 0 }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-center text-slate-600 text-xs hidden lg:table-cell">
                            S/. {{ number_format($producto->unit_sale_price, 2) }}
                        </td>
                        <td class="px-5 py-3 text-center text-slate-500 text-xs hidden xl:table-cell">
                            {{ $producto->laboratory->name ?? '—' }}
                        </td>
                        <td class="px-5 py-3 text-center">
                            @if($producto->status)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Activo</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-500">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('employee.products.edit', $producto->code) }}"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 transition-colors" title="Editar">
                                    <i class="fas fa-pencil text-xs"></i>
                                </a>
                                <button type="button"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600 transition-colors btn-eliminar"
                                        data-id="{{ $producto->code }}" data-nombre="{{ $producto->name }}" title="Eliminar">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-5 py-14 text-center">
                            <div class="flex flex-col items-center gap-2 text-slate-400">
                                <i class="fas fa-pills text-3xl"></i>
                                <p class="text-sm">No hay productos registrados</p>
                                <a href="{{ route('employee.products.create') }}" class="text-emerald-600 text-sm hover:underline">Agregar primer producto</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginador (footer estático) --}}
        @if($products->hasPages())
        <div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">
            {{ $products->withQueryString()->links() }}
        </div>
        @endif

    </div>
</div>

{{-- Modal eliminar --}}
<div id="modalEliminar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0">
                <i class="fas fa-trash text-red-600 text-sm"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-800">Confirmar eliminación</h3>
        </div>
        <p class="text-slate-600 text-sm mb-1">
            ¿Seguro que deseas eliminar el producto <strong id="nombre-eliminar" class="text-slate-800"></strong>?
        </p>
        <p class="text-red-600 text-xs mb-6">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="cerrarModal()"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                Cancelar
            </button>
            <form id="form-eliminar" action="" method="POST" class="inline">
                @csrf @method('DELETE')
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                    Eliminar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function cerrarModal() {
    document.getElementById('modalEliminar').style.setProperty('display', 'none', 'important');
}
$(document).ready(function() {
    $('#exportBtn').on('click', function(e) {
        e.stopPropagation();
        const m = document.getElementById('exportMenu');
        m.style.display = m.style.display === 'none' ? 'block' : 'none';
    });
    $(document).on('click', () => document.getElementById('exportMenu').style.display = 'none');

    $('.btn-eliminar').on('click', function() {
        $('#nombre-eliminar').text($(this).data('nombre'));
        const url = "{{ route('employee.products.destroy', ':id') }}";
        $('#form-eliminar').attr('action', url.replace(':id', $(this).data('id')));
        document.getElementById('modalEliminar').style.setProperty('display', 'flex', 'important');
    });
});
</script>
@endsection
