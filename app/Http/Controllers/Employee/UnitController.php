<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Lista las unidades de medida de la compañía con la cantidad de productos que las usan.
     */
    public function index(Request $request)
    {
        $query = Unit::deCompania($this->companyId())->withCount(['productos', 'presentaciones'])
            ->search($request->buscar, ['name', 'abbreviation']);

        $units = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('employee.pages.units.index', [
            'units'      => $units,
            'sunatCodes' => UnitRequest::SUNAT_CODES,
        ]);
    }

    /**
     * Muestra el formulario para crear una unidad de medida.
     */
    public function create()
    {
        return view('employee.pages.units.form', ['sunatCodes' => UnitRequest::SUNAT_CODES]);
    }

    /**
     * Muestra el formulario para editar una unidad, con cuántos productos y presentaciones la usan.
     */
    public function edit(Unit $unit)
    {
        $this->authorizeUnit($unit);

        $unit->loadCount(['productos', 'presentaciones']);

        return view('employee.pages.units.form', ['unit' => $unit, 'sunatCodes' => UnitRequest::SUNAT_CODES]);
    }

    /**
     * Crea una unidad de medida de la compañía.
     */
    public function store(UnitRequest $request)
    {
        Unit::create($request->validated() + ['company_id' => $this->companyId()]);

        return redirect()->route('employee.units.index')->with('success', 'Unidad creada correctamente.');
    }

    /**
     * Actualiza una unidad de medida.
     */
    public function update(UnitRequest $request, Unit $unit)
    {
        $this->authorizeUnit($unit);

        $unit->update($request->validated());

        return redirect()->route('employee.units.index')->with('success', 'Unidad actualizada correctamente.');
    }

    /**
     * Elimina una unidad, siempre que ningún producto ni presentación la use.
     */
    public function destroy(Unit $unit)
    {
        $this->authorizeUnit($unit);

        if ($unit->productos()->exists() || $unit->presentaciones()->exists()) {
            return redirect()->route('employee.units.index')
                ->with('error', 'No se puede eliminar «' . $unit->name . '»: hay productos o presentaciones que la usan. Puedes desactivarla.');
        }

        $unit->delete();

        return redirect()->route('employee.units.index')->with('success', 'Unidad eliminada correctamente.');
    }

    /**
     * Compañía del empleado autenticado.
     */
    private function companyId(): int
    {
        return auth()->guard('employee')->user()->company_id;
    }

    /**
     * Solo se gestionan las unidades de la propia compañía.
     */
    private function authorizeUnit(Unit $unit): void
    {
        abort_if($unit->company_id !== $this->companyId(), 403);
    }
}
