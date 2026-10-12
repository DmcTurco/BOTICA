@extends('employee/layouts/base')

@section('title', 'Proveedores')
@section('main-padding', 'p-2 md:p-3')

@section('content-area')
<div class="flex-1 flex flex-col gap-3 min-h-0">

    <div class="flex items-center justify-between shrink-0">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Proveedores</h1>
            <p class="text-sm text-slate-500 mt-0.5">Droguerías y distribuidores a los que les compras</p>
        </div>
        <button type="button" id="btnNuevo" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <i class="fas fa-plus text-xs"></i> Nuevo Proveedor
        </button>
    </div>

    @include('employee.partials.alerts')

    <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm shrink-0">
        <form action="{{ route('employee.suppliers.index') }}" method="GET" class="flex gap-3">
            <div class="flex-1 relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, RUC o contacto..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg flex items-center gap-2"><i class="fas fa-search text-xs"></i> Filtrar</button>
        </form>
    </div>

    <div class="flex-1 flex flex-col bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="flex-1 min-h-0 overflow-auto">
            <table class="w-full text-sm">
                <thead class="sticky top-0 z-10"><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Proveedor</th>
                    <th class="text-left px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Contacto</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Compras</th>
                    <th class="text-right px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Deuda</th>
                    <th class="text-center px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">Estado</th>
                    <th class="px-5 py-3"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($suppliers as $s)
                    @php $debt = (float) ($debts[$s->id] ?? 0); @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3"><p class="text-xs font-medium text-slate-800">{{ $s->name }}</p><p class="text-[10px] text-slate-400 font-mono">{{ $s->ruc ?: 'sin RUC' }}</p></td>
                        <td class="px-5 py-3 text-xs text-slate-600 hidden md:table-cell">{{ $s->contact ?: '—' }}<p class="text-[10px] text-slate-400">{{ $s->phone }}</p></td>
                        <td class="px-5 py-3 text-center text-xs text-slate-600 hidden sm:table-cell">{{ $s->purchases_count }}</td>
                        <td class="px-5 py-3 text-right text-xs font-semibold {{ $debt > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $debt > 0 ? 'S/ ' . number_format($debt, 2) : '—' }}</td>
                        <td class="px-5 py-3 text-center"><span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $s->status ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $s->status ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" class="btn-editar w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-amber-50 hover:text-amber-600" title="Editar"
                                        data-id="{{ $s->id }}" data-ruc="{{ $s->ruc }}" data-name="{{ $s->name }}" data-contact="{{ $s->contact }}" data-phone="{{ $s->phone }}"
                                        data-email="{{ $s->email }}" data-address="{{ $s->address }}" data-notes="{{ $s->notes }}" data-status="{{ $s->status }}"><i class="fas fa-pen text-xs pointer-events-none"></i></button>
                                <button type="button" class="btn-eliminar w-8 h-8 flex items-center justify-center rounded-lg text-slate-500 hover:bg-red-50 hover:text-red-600" title="Eliminar"
                                        data-id="{{ $s->id }}" data-nombre="{{ $s->name }}"><i class="fas fa-trash text-xs pointer-events-none"></i></button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-14 text-center text-sm text-slate-400">Aún no hay proveedores registrados</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($suppliers->hasPages())<div class="px-5 py-3 border-t border-slate-200 shrink-0 bg-slate-50">{{ $suppliers->links() }}</div>@endif
    </div>
</div>

<div id="modalProveedor" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <form id="formProveedor" method="POST" action="{{ route('employee.suppliers.store') }}" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-auto">
        @csrf
        <input type="hidden" name="_method" id="metodo" value="POST">
        <h3 class="text-base font-semibold text-slate-800" id="tituloModal">Nuevo proveedor</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2"><label class="block text-xs font-medium text-slate-600 mb-1">Nombre / razón social <span class="text-red-500">*</span></label><input type="text" name="name" id="fName" required maxlength="150" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">RUC</label><input type="text" name="ruc" id="fRuc" maxlength="11" inputmode="numeric" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Contacto</label><input type="text" name="contact" id="fContact" maxlength="100" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Teléfono</label><input type="text" name="phone" id="fPhone" maxlength="30" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Correo</label><input type="email" name="email" id="fEmail" maxlength="100" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div class="sm:col-span-3"><label class="block text-xs font-medium text-slate-600 mb-1">Dirección</label><input type="text" name="address" id="fAddress" maxlength="200" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div class="sm:col-span-2"><label class="block text-xs font-medium text-slate-600 mb-1">Notas</label><input type="text" name="notes" id="fNotes" maxlength="500" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Estado</label><select name="status" id="fStatus" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"><option value="1">Activo</option><option value="0">Inactivo</option></select></div>
        </div>
        <div class="flex gap-3 justify-end pt-2">
            <button type="button" onclick="cerrarModales()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg">Guardar</button>
        </div>
    </form>
</div>

<div id="modalEliminar" class="fixed inset-0 bg-black/50 z-50 items-center justify-center p-4" style="display:none!important">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <div class="flex items-center gap-3 mb-4"><div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center shrink-0"><i class="fas fa-trash text-red-600 text-sm"></i></div><h3 class="text-base font-semibold text-slate-800">Confirmar eliminación</h3></div>
        <p class="text-slate-600 text-sm mb-6">¿Seguro que deseas eliminar al proveedor <strong id="nombre-eliminar" class="text-slate-800"></strong>?</p>
        <div class="flex gap-3 justify-end">
            <button onclick="cerrarModales()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancelar</button>
            <form id="form-eliminar" action="" method="POST" class="inline">@csrf @method('DELETE')<button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg">Eliminar</button></form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
const abrir = id => document.getElementById(id).style.setProperty('display', 'flex', 'important');
function cerrarModales() { ['modalProveedor', 'modalEliminar'].forEach(id => document.getElementById(id).style.setProperty('display', 'none', 'important')); }
$(function () {
    const campos = { fName: 'name', fRuc: 'ruc', fContact: 'contact', fPhone: 'phone', fEmail: 'email', fAddress: 'address', fNotes: 'notes' };
    $('#btnNuevo').on('click', function () {
        $('#formProveedor').attr('action', "{{ route('employee.suppliers.store') }}"); $('#metodo').val('POST'); $('#tituloModal').text('Nuevo proveedor');
        Object.keys(campos).forEach(id => $('#' + id).val('')); $('#fStatus').val('1'); abrir('modalProveedor');
    });
    $('.btn-editar').on('click', function () {
        const d = $(this).data();
        $('#formProveedor').attr('action', "{{ route('employee.suppliers.update', ':id') }}".replace(':id', d.id)); $('#metodo').val('PUT'); $('#tituloModal').text('Editar proveedor');
        Object.entries(campos).forEach(([id, key]) => $('#' + id).val(d[key] ?? '')); $('#fStatus').val(String(d.status)); abrir('modalProveedor');
    });
    $('.btn-eliminar').on('click', function () {
        $('#nombre-eliminar').text($(this).data('nombre'));
        $('#form-eliminar').attr('action', "{{ route('employee.suppliers.destroy', ':id') }}".replace(':id', $(this).data('id'))); abrir('modalEliminar');
    });
});
</script>
@endsection
