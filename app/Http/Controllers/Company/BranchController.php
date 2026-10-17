<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\BranchRequest;
use App\Models\Branch;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    /**
     * Lista todas las sedes de la compañía.
     */
    public function index()
    {
        $company = auth()->guard('company')->user();

        $branches = Branch::with('documentSeries')
            ->where('company_id', $company->id)
            ->orderBy('name')
            ->get();

        // Sedes que comparten código de establecimiento: sus comprobantes declaran el mismo local
        $duplicateCodes = Branch::duplicateEstablishmentCodes($company->id);

        return view('company.pages.branches.index', compact('branches', 'duplicateCodes'));
    }

    /**
     * Muestra el formulario para crear una sede, con el siguiente código de establecimiento libre.
     */
    public function create()
    {
        $suggestedCode = Branch::nextEstablishmentCode(auth()->guard('company')->id());

        return view('company.pages.branches.form', compact('suggestedCode'));
    }

    /**
     * Guarda una nueva sede.
     */
    public function store(BranchRequest $request)
    {
        $company = auth()->guard('company')->user();

        $data = $request->safe()->except('series');
        $data['company_id'] = $company->id;

        // La sede nace con sus series de comprobantes (B00X, F00X, NV0X)
        DB::transaction(function () use ($data) {
            $branch = Branch::create($data);
            DocumentSeries::crearSeriesParaSede($branch);
        });

        return redirect()->route('company.branches.index')
            ->with('success', 'Sede creada correctamente con sus series de comprobantes.');
    }

    /**
     * Muestra el formulario para editar una sede.
     */
    public function edit(Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        $series = $branch->documentSeries()
            ->whereIn('type_code', array_keys(DocumentSeries::PER_BRANCH))
            ->where('active', true)
            ->orderByRaw("CASE type_code WHEN 'BOLETA' THEN 1 WHEN 'FACTURA' THEN 2 WHEN 'NOTA_VENTA' THEN 3 WHEN 'NOTA_CREDITO_BOLETA' THEN 4 ELSE 5 END")
            ->get();

        return view('company.pages.branches.form', compact('branch', 'series'));
    }

    /**
     * Actualiza una sede existente.
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        DB::transaction(function () use ($request, $branch) {
            $branch->update($request->safe()->except('series'));

            // Solo cambian las series editadas que aún no emitieron comprobantes (ya validadas)
            foreach ($request->input('series', []) as $id => $value) {
                if ($value === '') {
                    continue;
                }

                DocumentSeries::where('id', (int) $id)
                    ->where('branch_id', $branch->id)
                    ->where('current_number', 0)
                    ->whereIn('type_code', array_keys(DocumentSeries::PER_BRANCH))
                    ->update(['series' => $value]);
            }
        });

        // Se queda en la edición para que se vea lo que se guardó
        return redirect()->route('company.branches.edit', $branch)
            ->with('success', 'Sede actualizada correctamente.');
    }

    /**
     * Genera las series de comprobantes que le falten a la sede (B00X, F00X, NV0X, BC0X, FC0X)
     * y, si no existen, los correlativos internos globales y los tipos de documento (DNI, RUC...).
     * Útil cuando en el servidor no se corrieron los seeders.
     * No toca las series que ya existen.
     */
    public function generateSeries(Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        $missing = DocumentSeries::tiposFaltantesDeSede($branch);

        DB::transaction(function () use ($branch) {
            DocumentType::crearTiposBase();
            DocumentSeries::crearSeriesGlobales();
            DocumentSeries::crearSeriesParaSede($branch);
        });

        $message = empty($missing)
            ? "La sede {$branch->name} ya tenía todas sus series."
            : 'Se generaron ' . count($missing) . " series para la sede {$branch->name}.";

        return redirect()->route('company.branches.index')->with('success', $message);
    }

    /**
     * Elimina una sede (solo si no tiene empleados ni transacciones).
     */
    public function destroy(Branch $branch)
    {
        $company = auth()->guard('company')->user();
        abort_if($branch->company_id !== $company->id, 403);

        if ($branch->employees()->exists()) {
            return back()->with('error', 'No se puede eliminar una sede con empleados asignados.');
        }

        if ($branch->orders()->exists() || $branch->purchases()->exists()) {
            return back()->with('error', 'No se puede eliminar una sede con transacciones registradas.');
        }

        $branch->delete();

        return redirect()->route('company.branches.index')
            ->with('success', 'Sede eliminada correctamente.');
    }
}
