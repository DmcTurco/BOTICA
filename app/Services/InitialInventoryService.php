<?php

namespace App\Services;

use App\Http\Requests\Company\InventoryAdjustmentRequest;
use App\Models\AuditLog;
use App\Models\BranchStock;
use App\Models\Employee;
use App\Models\PriceChange;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Inventario inicial (toma de inventario): fija el stock contado de muchos productos a la vez en la
 * sede del empleado, con su costo, precio de venta, lote y vencimiento. Lo usan la pantalla de
 * inventario inicial y la importación desde Excel.
 *
 * Cada fila se registra como un ajuste "Inventario inicial" (kardex de ajuste, referencia manual),
 * igual que en Ajuste de inventario, así queda un solo historial. La cantidad FIJA el stock (no suma).
 */
class InitialInventoryService
{
    public function __construct(private StockService $stock, private BatchService $batches)
    {
    }

    /**
     * @param  array<int, array{code: string, quantity?: mixed, cost?: mixed, price?: mixed, batch?: mixed, expiration?: mixed, line?: int}>  $rows
     * @return array{counted: int, prices: int, errors: array<int, string>}
     */
    public function apply(Employee $employee, array $rows): array
    {
        $counted = 0;
        $prices  = 0;
        $errors  = [];
        $reason  = InventoryAdjustmentRequest::REASONS['inicial'];

        $products = Product::where('company_id', $employee->company_id)
            ->whereIn('code', array_column($rows, 'code'))
            ->get()
            ->keyBy('code');

        foreach ($rows as $row) {
            $label   = isset($row['line']) ? 'Fila ' . $row['line'] : ($row['code'] ?? '');
            $product = $products->get($row['code'] ?? '');

            if (!$product) {
                $errors[] = "{$label}: el producto «" . ($row['code'] ?? '') . '» no existe en tu catálogo.';
                continue;
            }

            $hasQuantity = isset($row['quantity']) && $row['quantity'] !== '';

            // Cada fila en su propio punto de control: si una falla, las demás se guardan
            DB::beginTransaction();
            try {
                // Costo y precio (con su historial de cambios)
                $prices += PriceChange::apply($product, [
                    'purchase_price'  => $row['cost'] ?? null,
                    'unit_sale_price' => $row['price'] ?? null,
                ], $employee->id);

                if ($hasQuantity) {
                    $counted += $this->count($employee, $product, round((float) $row['quantity'], 2), $row, $reason) ? 1 : 0;
                }

                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                $errors[] = "{$label} ({$product->name}): " . $e->getMessage();
            }
        }

        if ($counted || $prices) {
            AuditLog::record('stock.initial', "Inventario inicial: {$counted} producto(s) contado(s)" . ($prices ? ", {$prices} precio(s) actualizado(s)" : ''), null, ['productos' => $counted, 'precios' => $prices]);
        }

        return ['counted' => $counted, 'prices' => $prices, 'errors' => $errors];
    }

    /**
     * Fija el stock de un producto en la cantidad contada. Devuelve true si el stock cambió.
     */
    private function count(Employee $employee, Product $product, float $quantity, array $row, string $reason): bool
    {
        $current = (float) BranchStock::where('branch_id', $employee->branch_id)
            ->where('product_code', $product->code)
            ->lockForUpdate()
            ->value('stock_actual');

        $delta = round($quantity - $current, 2);

        if ($delta == 0) {
            return false;
        }

        $this->stock->move(
            $employee->company_id, $employee->branch_id, $product->code, $delta,
            'manual', null, (float) $product->purchase_price, $reason, 'ajuste'
        );

        // Lotes: si entran unidades se registran con su lote/vencimiento (si se indicaron);
        // si bajan, salen por FEFO como en un ajuste
        if ($delta > 0) {
            if (filled($row['batch'] ?? null) || filled($row['expiration'] ?? null)) {
                $this->batches->receive(
                    $employee->company_id, $employee->branch_id, $product->code, $delta,
                    filled($row['batch'] ?? null) ? mb_substr(trim((string) $row['batch']), 0, 30) : null,
                    filled($row['expiration'] ?? null) ? $row['expiration'] : null,
                    (float) $product->purchase_price, 'adjustment', null
                );
            }
        } else {
            $this->batches->consume($employee->branch_id, $product->code, abs($delta), null, null, false, false);
        }

        $this->batches->reconcile($employee->branch_id, $product->code);

        return true;
    }
}
