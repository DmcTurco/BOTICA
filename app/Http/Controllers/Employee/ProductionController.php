<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\ProductionRequest;
use App\Models\BranchStock;
use App\Models\Formula;
use App\Models\Product;
use App\Models\Production;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionController extends Controller
{
    /**
     * Historial de preparaciones (lotes) de la sede del empleado.
     */
    public function index(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $query = Production::with('formula:id,code,name')
            ->where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id);

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('batch', 'like', '%' . $request->buscar . '%')
                  ->orWhereHas('formula', fn ($f) => $f->withTrashed()->where('name', 'like', '%' . $request->buscar . '%'));
            });
        }

        // Filtro por estado de vencimiento
        if ($request->input('vencimiento') === 'vencido') {
            $query->where('status', 1)->whereDate('expiration_date', '<', now());
        } elseif ($request->input('vencimiento') === 'por_vencer') {
            $query->where('status', 1)->whereDate('expiration_date', '>=', now())
                  ->whereDate('expiration_date', '<=', now()->addDays(30));
        }

        $producciones = $query->orderByDesc('produced_at')->orderByDesc('id')->paginate(15)->withQueryString();

        return view('employee.pages.productions.index', compact('producciones'));
    }

    /**
     * Formulario para preparar un lote. Entrega las fórmulas activas con sus insumos
     * y el stock disponible en la sede para calcular en pantalla lo que se necesita.
     */
    public function create(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $formulas = Formula::with(['ingredients.product.unit', 'product:code,name', 'yieldUnit'])
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        $codes  = $formulas->flatMap(fn ($f) => $f->ingredients->pluck('product_code'))->unique()->values();
        $stocks = BranchStock::where('branch_id', $employee->branch_id)
            ->whereIn('product_code', $codes)
            ->pluck('stock_actual', 'product_code');

        // Datos que consume el JS del formulario
        $formulasData = $formulas->map(fn ($f) => [
            'id'           => $f->id,
            'code'         => $f->code,
            'name'         => $f->name,
            'yield'        => $f->yield_quantity,
            'unit'         => $f->yieldUnit?->abbreviation,
            'shelf_life'   => $f->shelf_life_days,
            'sell_in_pos'  => $f->sell_in_pos,
            'product_name' => $f->product?->name,
            'ingredients'  => $f->ingredients->map(fn ($i) => [
                'code'  => $i->product_code,
                'name'  => $i->product?->name,
                'unit'  => $i->product?->unit?->abbreviation,
                'qty'   => $i->quantity,
                'cost'  => (float) ($i->product?->purchase_price ?? 0),
                'stock' => (float) ($stocks[$i->product_code] ?? 0),
            ])->values(),
        ])->values();

        return view('employee.pages.productions.form', [
            'formulasData'    => $formulasData,
            'selectedFormula' => (int) $request->query('formula'),
        ]);
    }

    /**
     * Registra la preparación: descuenta los insumos del stock de la sede, guarda el lote
     * y, si la fórmula se vende en el POS, da ingreso al producto final. Todo en una transacción.
     */
    public function store(ProductionRequest $request)
    {
        $employee = auth()->guard('employee')->user();

        DB::beginTransaction();

        try {
            $formula = Formula::with('ingredients')
                ->where('company_id', $employee->company_id)
                ->where('status', 1)
                ->lockForUpdate()
                ->findOrFail($request->formula_id);

            $quantity   = round((float) $request->quantity_produced, 2);
            $multiplier = $quantity / $formula->yield_quantity;

            // Cantidad real a consumir de cada insumo (a 2 decimales, igual que el stock)
            $needs = [];
            foreach ($formula->ingredients as $ingredient) {
                $needed = round($ingredient->quantity * $multiplier, 2);
                if ($needed <= 0) {
                    DB::rollBack();
                    return redirect()->back()->withInput()
                        ->with('error', 'La cantidad a preparar es muy pequeña: algún insumo se redondea a 0. Aumenta la cantidad.');
                }
                $needs[$ingredient->product_code] = $needed;
            }

            // Bloquear el stock de los insumos en orden por código (evita bloqueos cruzados)
            ksort($needs);
            $stocks = BranchStock::where('branch_id', $employee->branch_id)
                ->whereIn('product_code', array_keys($needs))
                ->orderBy('product_code')
                ->lockForUpdate()
                ->get()
                ->keyBy('product_code');

            $prices = Product::whereIn('code', array_keys($needs))->pluck('purchase_price', 'code');

            // Verificar que alcance el stock de todos los insumos
            foreach ($needs as $code => $needed) {
                $available = (float) ($stocks[$code]->stock_actual ?? 0);
                if ($available < $needed) {
                    DB::rollBack();
                    $name = $formula->ingredients->firstWhere('product_code', $code)?->product?->name ?? $code;
                    return redirect()->back()->withInput()
                        ->with('error', "Stock insuficiente de «{$name}»: se necesitan {$needed} y hay {$available}.");
                }
            }

            // Número de lote y vencimiento (por defecto según la vida útil de la fórmula)
            $producedAt = $request->date('produced_at');
            $expiration = $request->filled('expiration_date')
                ? $request->date('expiration_date')
                : ($formula->shelf_life_days ? $producedAt->copy()->addDays($formula->shelf_life_days) : null);

            $totalCost = 0;
            foreach ($needs as $code => $needed) {
                $totalCost += round($needed * (float) ($prices[$code] ?? 0), 2);
            }
            $unitCost = $quantity > 0 ? round($totalCost / $quantity, 2) : 0;

            $production = Production::create([
                'company_id'        => $employee->company_id,
                'branch_id'         => $employee->branch_id,
                'formula_id'        => $formula->id,
                'employee_id'       => $employee->id,
                'batch'             => $request->filled('batch') ? $request->batch : $this->nextBatch($formula, $producedAt),
                'multiplier'        => round($multiplier, 4),
                'quantity_produced' => $quantity,
                'produced_at'       => $producedAt,
                'expiration_date'   => $expiration,
                'total_cost'        => $totalCost,
                'unit_cost'         => $unitCost,
                'product_code'      => $formula->sell_in_pos ? $formula->product_code : null,
                'notes'             => $request->notes,
            ]);

            // Descontar cada insumo, guardar el detalle y registrar la salida en el kardex
            foreach ($needs as $code => $needed) {
                $price      = (float) ($prices[$code] ?? 0);
                $newBalance = (float) $stocks[$code]->stock_actual - $needed;
                $stocks[$code]->update(['stock_actual' => $newBalance]);

                $production->ingredients()->create([
                    'product_code' => $code,
                    'quantity'     => $needed,
                    'unit_cost'    => $price,
                    'subtotal'     => round($needed * $price, 2),
                ]);

                StockMovement::create([
                    'company_id'     => $employee->company_id,
                    'branch_id'      => $employee->branch_id,
                    'product_code'   => $code,
                    'type'           => 'salida',
                    'reference_type' => 'production',
                    'reference_id'   => $production->id,
                    'quantity'       => $needed,
                    'unit_cost'      => $price,
                    'balance'        => $newBalance,
                    'notes'          => 'Insumo del lote ' . $production->batch,
                ]);
            }

            // Si se vende en el POS, el producto final ingresa al stock de la sede
            if ($formula->sell_in_pos && $formula->product_code) {
                $finalStock = BranchStock::where('branch_id', $employee->branch_id)
                    ->where('product_code', $formula->product_code)
                    ->lockForUpdate()
                    ->first();

                if ($finalStock) {
                    $finalBalance = (float) $finalStock->stock_actual + $quantity;
                    $finalStock->update(['stock_actual' => $finalBalance]);
                } else {
                    $finalBalance = $quantity;
                    BranchStock::create([
                        'branch_id'    => $employee->branch_id,
                        'product_code' => $formula->product_code,
                        'stock_actual' => $finalBalance,
                    ]);
                }

                StockMovement::create([
                    'company_id'     => $employee->company_id,
                    'branch_id'      => $employee->branch_id,
                    'product_code'   => $formula->product_code,
                    'type'           => 'entrada',
                    'reference_type' => 'production',
                    'reference_id'   => $production->id,
                    'quantity'       => $quantity,
                    'unit_cost'      => $unitCost,
                    'balance'        => $finalBalance,
                    'notes'          => 'Lote ' . $production->batch . ' · ' . $formula->name,
                ]);
            }

            DB::commit();

            return redirect()->route('employee.productions.show', $production)
                ->with('success', 'Preparación registrada correctamente. Los insumos fueron descontados del stock.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar preparación: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Error al registrar la preparación. Por favor, inténtelo de nuevo.');
        }
    }

    /**
     * Detalle de una preparación: lote, vencimiento, insumos consumidos y costo.
     */
    public function show(Production $production)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($production->company_id !== $employee->company_id, 403);

        $production->load(['formula.yieldUnit', 'ingredients.product.unit', 'product', 'employee', 'branch']);

        return view('employee.pages.productions.show', compact('production'));
    }

    /**
     * Etiqueta imprimible del lote (lote, contenido, vencimiento, conservación).
     */
    public function label(Production $production)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($production->company_id !== $employee->company_id, 403);

        $production->load(['formula.yieldUnit', 'employee', 'branch.company']);

        return view('employee.pages.productions.label', compact('production'));
    }

    /**
     * Anula una preparación: devuelve los insumos al stock y, si el preparado se vendía en el POS,
     * retira del stock el producto final. Falla si ese producto ya se vendió (no hay unidades suficientes).
     */
    public function void(Request $request, Production $production, StockService $stock)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($production->company_id !== $employee->company_id || $production->branch_id !== $employee->branch_id, 403);

        $request->validate(['void_reason' => 'required|string|max:255'], [
            'void_reason.required' => 'Indica el motivo de la anulación.',
        ]);

        if ((int) $production->status === 0) {
            return back()->with('error', 'Esta preparación ya está anulada.');
        }

        DB::beginTransaction();

        try {
            // Primero se retira el producto final: si ya se vendió, se corta antes de tocar nada
            if ($production->product_code) {
                $stock->move(
                    $employee->company_id, $employee->branch_id, $production->product_code, -(float) $production->quantity_produced,
                    'production_void', $production->id, (float) $production->unit_cost, 'Anulación del lote ' . $production->batch
                );
            }

            foreach ($production->ingredients()->orderBy('product_code')->get() as $ingredient) {
                $stock->move(
                    $employee->company_id, $employee->branch_id, $ingredient->product_code, (float) $ingredient->quantity,
                    'production_void', $production->id, (float) $ingredient->unit_cost, 'Anulación del lote ' . $production->batch
                );
            }

            $production->update(['status' => 0, 'voided_at' => now(), 'void_reason' => $request->void_reason]);

            DB::commit();

            return redirect()->route('employee.productions.show', $production)
                ->with('success', 'Preparación anulada. Los insumos volvieron al stock.');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', 'No se puede anular: el producto final ya se vendió en parte o por completo. ' . $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular preparación: ' . $e->getMessage());
            return back()->with('error', 'Error al anular la preparación. Inténtelo de nuevo.');
        }
    }

    /**
     * Genera el siguiente número de lote: CÓDIGO-AAMMDD-NN (NN = correlativo del día).
     */
    private function nextBatch(Formula $formula, $producedAt): string
    {
        $prefix = $formula->code . '-' . $producedAt->format('ymd') . '-';
        $count  = Production::where('company_id', $formula->company_id)
            ->where('batch', 'like', $prefix . '%')
            ->count();

        return $prefix . str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);
    }
}
