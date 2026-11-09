<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\InventoryAdjustmentRequest;
use App\Models\BranchStock;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\BatchService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryAdjustmentController extends Controller
{
    /**
     * Listado de los ajustes de inventario de la sede (más recientes primero).
     */
    public function index()
    {
        $employee = auth()->guard('employee')->user();

        $adjustments = StockMovement::with('product:id,code,name')
            ->where('branch_id', $employee->branch_id)
            ->where('reference_type', 'manual')
            ->orderByDesc('id')
            ->paginate(15);

        return view('employee.pages.adjustments.index', compact('adjustments'));
    }

    /**
     * Formulario para registrar un ajuste, con el stock actual de cada producto en la sede.
     */
    public function create(Request $request)
    {
        $employee = auth()->guard('employee')->user();

        $stocks = BranchStock::where('branch_id', $employee->branch_id)->pluck('stock_actual', 'product_id');

        $products = Product::with('unit:id,abbreviation')
            ->where('company_id', $employee->company_id)
            ->where('status', 1)
            ->orderBy('name')
            ->get(['id', 'code', 'barcode', 'name', 'unit_id', 'purchase_price'])
            ->map(fn ($p) => [
                'id'      => $p->id,
                'code'    => $p->code,
                'barcode' => $p->barcode,
                'name'    => $p->name,
                'unit'  => $p->unit?->abbreviation,
                'stock' => (float) ($stocks[$p->id] ?? 0),
            ])->values()->all();

        // Producto precargado (viene del botón "Ajustar stock" de la lista de productos);
        // solo se acepta si pertenece a la compañía
        $selectedId = collect($products)->firstWhere('id', (int) $request->query('producto'))['id'] ?? null;

        return view('employee.pages.adjustments.form', [
            'products'   => $products,
            'reasons'    => InventoryAdjustmentRequest::REASONS,
            'selectedId' => $selectedId,
        ]);
    }

    /**
     * Aplica el ajuste. En modo "fijar", la diferencia se calcula con el stock bloqueado
     * dentro de la transacción, para que una venta simultánea no la descuadre.
     */
    public function store(InventoryAdjustmentRequest $request, StockService $stock)
    {
        $employee = auth()->guard('employee')->user();
        $product  = Product::where('company_id', $employee->company_id)->findOrFail($request->product_id);
        $quantity = round((float) $request->quantity, 2);

        DB::beginTransaction();

        try {
            $current = (float) BranchStock::where('branch_id', $employee->branch_id)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->value('stock_actual');

            $delta = match ($request->mode) {
                'set'      => round($quantity - $current, 2),
                'add'      => $quantity,
                'subtract' => -$quantity,
            };

            if ($delta == 0) {
                DB::rollBack();
                return back()->withInput()->with('error', 'El ajuste no cambia el stock actual (' . $current . ').');
            }

            $reason = InventoryAdjustmentRequest::REASONS[$request->reason];
            $notes  = $reason . ($request->filled('notes') ? ': ' . $request->notes : '');

            $stock->move(
                $employee->company_id, $employee->branch_id, $product->id, $delta,
                'manual', null, (float) $product->purchase_price, mb_substr($notes, 0, 255), 'ajuste'
            );

            // Lotes: al restar se descuenta por FEFO (incluye vencidos); al sumar se puede indicar lote y vencimiento
            $batches = app(BatchService::class);
            if ($delta < 0) {
                $batches->consume($employee->branch_id, $product->id, abs($delta), null, null, false, false);
            } elseif ($request->filled('batch') || $request->filled('expiration_date')) {
                $batches->receive(
                    $employee->company_id, $employee->branch_id, $product->id, $delta,
                    $request->batch, $request->expiration_date, (float) $product->purchase_price, 'adjustment', null
                );
            }
            $batches->reconcile($employee->branch_id, $product->id);

            DB::commit();

            \App\Models\AuditLog::record('stock.adjust', 'Ajustó el stock de «' . $product->name . '» (' . ($delta > 0 ? '+' : '') . $delta . ')', $product->code, ['motivo' => $notes, 'antes' => $current, 'despues' => round($current + $delta, 2)]);

            return redirect()->route('employee.adjustments.index')
                ->with('success', 'Ajuste registrado: «' . $product->name . '» ' . ($delta > 0 ? '+' : '') . $delta . '. Stock actual: ' . round($current + $delta, 2) . '.');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al ajustar inventario: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al registrar el ajuste. Inténtelo de nuevo.');
        }
    }
}
