<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\FormulaRequest;
use App\Models\Formula;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FormulaController extends Controller
{
    /**
     * Lista las fórmulas magistrales de la compañía.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Formula::with(['product:code,name', 'yieldUnit'])
            ->withCount('ingredients')
            ->where('company_id', $employee->company_id);

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%' . $request->buscar . '%')
                  ->orWhere('name', 'like', '%' . $request->buscar . '%')
                  ->orWhere('pharmaceutical_form', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $formulas = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('employee.pages.formulas.index', compact('formulas'));
    }

    /**
     * Formulario de nueva fórmula.
     */
    public function create()
    {
        $employee = auth()->guard('employee')->user();

        return view('employee.pages.formulas.form', [
            'code'      => Formula::nextCode($employee->company_id),
            'products'  => $this->catalog($employee->company_id),
            'units'     => Unit::where('status', 1)->orderBy('name')->get(),
            'currentIngredients' => [],
        ]);
    }

    /**
     * Guarda la fórmula con sus insumos.
     */
    public function store(FormulaRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        try {
            $formula = Formula::create([
                'company_id'  => $employee->company_id,
                'employee_id' => $employee->id,
                'code'        => Formula::nextCode($employee->company_id),
            ] + $this->formulaData($request));

            $this->syncIngredients($formula, $request->ingredients);

            DB::commit();

            return redirect()->route('employee.formulas.show', $formula)
                ->with('success', 'Fórmula registrada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar fórmula: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al registrar la fórmula. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Detalle de la fórmula: insumos, resultado final, costo estimado y últimos lotes.
     */
    public function show(Formula $formula)
    {
        $this->authorizeFormula($formula);

        $formula->load(['ingredients.product.unit', 'product', 'yieldUnit', 'employee']);
        $lotes = $formula->productions()->orderByDesc('produced_at')->orderByDesc('id')->limit(10)->get();

        return view('employee.pages.formulas.show', compact('formula', 'lotes'));
    }

    /**
     * Formulario de edición.
     */
    public function edit(Formula $formula)
    {
        $this->authorizeFormula($formula);

        $employee = auth()->guard('employee')->user();
        $formula->load('ingredients');

        return view('employee.pages.formulas.form', [
            'formula'   => $formula,
            'code'      => $formula->code,
            'products'  => $this->catalog($employee->company_id),
            'units'     => Unit::where('status', 1)->orderBy('name')->get(),
            'currentIngredients' => $formula->ingredients
                ->map(fn ($i) => ['product_code' => $i->product_code, 'quantity' => $i->quantity, 'notes' => $i->notes])
                ->all(),
        ]);
    }

    /**
     * Actualiza la fórmula. Los lotes ya preparados conservan su propio detalle de insumos.
     */
    public function update(FormulaRequest $request, Formula $formula)
    {
        $this->authorizeFormula($formula);

        DB::beginTransaction();

        try {
            $formula->update($this->formulaData($request));
            $this->syncIngredients($formula, $request->ingredients);

            DB::commit();

            return redirect()->route('employee.formulas.show', $formula)
                ->with('success', 'Fórmula actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar fórmula: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al actualizar la fórmula. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Elimina la fórmula (soft delete); el historial de lotes se conserva.
     */
    public function destroy(Formula $formula)
    {
        $this->authorizeFormula($formula);

        $formula->delete();

        return redirect()->route('employee.formulas.index')
            ->with('success', 'Fórmula eliminada correctamente.');
    }

    // ── Helpers privados ────────────────────────────────────────

    /** Impide acceder a fórmulas de otra compañía */
    private function authorizeFormula(Formula $formula): void
    {
        abort_if($formula->company_id !== auth()->guard('employee')->user()->company_id, 403);
    }

    /** Campos de la cabecera de la fórmula tomados del request validado */
    private function formulaData(FormulaRequest $request): array
    {
        return $request->safe()->only([
            'name', 'pharmaceutical_form', 'description', 'yield_quantity', 'yield_unit_id',
            'procedure', 'storage', 'shelf_life_days', 'sell_in_pos', 'product_code', 'status',
        ]);
    }

    /** Reemplaza los insumos de la fórmula por los enviados en el formulario */
    private function syncIngredients(Formula $formula, array $ingredients): void
    {
        $formula->ingredients()->delete();

        foreach ($ingredients as $item) {
            $formula->ingredients()->create([
                'product_code' => $item['product_code'],
                'quantity'     => $item['quantity'],
                'notes'        => $item['notes'] ?? null,
            ]);
        }
    }

    /** Catálogo de productos activos de la compañía para elegir insumos o producto final */
    private function catalog(int $companyId)
    {
        return Product::with('unit:id,abbreviation')
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['code', 'name', 'purchase_price', 'unit_id']);
    }
}
