<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Lista las unidades de medida con la cantidad de productos que las usan.
     */
    public function index(Request $request)
    {
        $query = Unit::withCount(['productos', 'presentaciones']);

        if ($request->filled('buscar')) {
            $query->where(fn ($q) => $q->where('name', 'like', '%' . $request->buscar . '%')
                ->orWhere('abbreviation', 'like', '%' . $request->buscar . '%'));
        }

        $units = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('employee.pages.units.index', [
            'units'      => $units,
            'sunatCodes' => UnitRequest::SUNAT_CODES,
        ]);
    }

    /**
     * Crea una unidad de medida.
     */
    public function store(UnitRequest $request)
    {
        Unit::create($request->validated());

        return redirect()->route('employee.units.index')->with('success', 'Unidad creada correctamente.');
    }

    /**
     * Actualiza una unidad de medida.
     */
    public function update(UnitRequest $request, Unit $unit)
    {
        $unit->update($request->validated());

        return redirect()->route('employee.units.index')->with('success', 'Unidad actualizada correctamente.');
    }

    /**
     * Elimina una unidad, siempre que ningún producto ni presentación la use.
     */
    public function destroy(Unit $unit)
    {
        if ($unit->productos()->exists() || $unit->presentaciones()->exists()) {
            return redirect()->route('employee.units.index')
                ->with('error', 'No se puede eliminar «' . $unit->name . '»: hay productos o presentaciones que la usan. Puedes desactivarla.');
        }

        $unit->delete();

        return redirect()->route('employee.units.index')->with('success', 'Unidad eliminada correctamente.');
    }
}
