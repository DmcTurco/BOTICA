<?php

namespace App\Services;

use App\Models\BatchAllocation;
use App\Models\BranchStock;
use App\Models\StockBatch;
use Illuminate\Support\Carbon;

/**
 * Lotes y vencimientos (FEFO: primero en vencer, primero en salir).
 *
 * branch_stock es el total oficial del producto en la sede; los lotes son su desglose.
 * Puede haber unidades "sin lote" (stock anterior al control por lotes o ajustes): se venden
 * después de los lotes. Las unidades de lotes vencidos no se venden ni se usan como insumo.
 *
 * Todos los métodos deben llamarse DENTRO de una transacción (bloquean filas con FOR UPDATE).
 */
class BatchService
{
    /**
     * Registra el ingreso de un lote. No hace nada si la cantidad es 0 o negativa.
     */
    public function receive(
        int $companyId,
        int $branchId,
        string $productCode,
        float $quantity,
        ?string $batch,
        Carbon|string|null $expiration,
        float $unitCost,
        string $sourceType,
        ?int $sourceId,
    ): ?StockBatch {
        if ($quantity <= 0) {
            return null;
        }

        return StockBatch::create([
            'company_id'         => $companyId,
            'branch_id'          => $branchId,
            'product_code'       => $productCode,
            'batch'              => $batch ?: null,
            'expiration_date'    => $expiration ?: null,
            'quantity_initial'   => round($quantity, 2),
            'quantity_remaining' => round($quantity, 2),
            'unit_cost'          => $unitCost,
            'source_type'        => $sourceType,
            'source_id'          => $sourceId,
        ]);
    }

    /**
     * Descuenta unidades por FEFO. Lo que no alcanzan a cubrir los lotes sale de las unidades sin lote
     * (no se registra). Devuelve las líneas tomadas: [['batch' => StockBatch, 'quantity' => float], ...].
     *
     * @param  bool  $skipExpired  true = no toca lotes vencidos (ventas, insumos); false = los incluye
     * @param  bool  $record       true = guarda la asignación para poder revertirla con restore()
     */
    public function consume(
        int $branchId,
        string $productCode,
        float $quantity,
        ?string $referenceType = null,
        ?int $referenceId = null,
        bool $skipExpired = true,
        bool $record = true,
    ): array {
        $remaining = round($quantity, 2);
        $lines     = [];

        $batches = StockBatch::where('branch_id', $branchId)
            ->where('product_code', $productCode)
            ->where('quantity_remaining', '>', 0)
            ->when($skipExpired, fn ($q) => $q->where(fn ($w) => $w->whereNull('expiration_date')->orWhereDate('expiration_date', '>=', today()->toDateString())))
            ->orderByRaw('expiration_date IS NULL')
            ->orderBy('expiration_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = round(min($remaining, $batch->quantity_remaining), 2);
            $batch->update(['quantity_remaining' => round($batch->quantity_remaining - $take, 2)]);
            $remaining = round($remaining - $take, 2);

            if ($record && $referenceType && $referenceId) {
                BatchAllocation::create([
                    'stock_batch_id' => $batch->id,
                    'reference_type' => $referenceType,
                    'reference_id'   => $referenceId,
                    'quantity'       => $take,
                ]);
            }

            $lines[] = ['batch' => $batch, 'quantity' => $take];
        }

        return $lines;
    }

    /**
     * Devuelve a sus lotes de origen todo lo que consumió un documento (venta anulada o editada,
     * preparado anulado, traspaso anulado). Las asignaciones se eliminan.
     */
    public function restore(string $referenceType, int $referenceId): void
    {
        $allocations = BatchAllocation::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $allocation) {
            $batch = StockBatch::lockForUpdate()->find($allocation->stock_batch_id);

            if ($batch) {
                $batch->update(['quantity_remaining' => round(min($batch->quantity_initial, $batch->quantity_remaining + $allocation->quantity), 2)]);
            }

            $allocation->delete();
        }
    }

    /**
     * Unidades de lotes vencidos que hay en la sede para un producto (no vendibles).
     */
    public function expiredQuantity(int $branchId, string $productCode): float
    {
        return (float) StockBatch::where('branch_id', $branchId)
            ->where('product_code', $productCode)
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', today()->toDateString())
            ->sum('quantity_remaining');
    }

    /**
     * Unidades vencidas por producto: [código => cantidad]. Una sola consulta para varios productos.
     */
    public function expiredMap(int $branchId, array $productCodes): array
    {
        return StockBatch::where('branch_id', $branchId)
            ->whereIn('product_code', array_unique($productCodes))
            ->whereNotNull('expiration_date')
            ->whereDate('expiration_date', '<', today()->toDateString())
            ->selectRaw('product_code, SUM(quantity_remaining) as expired')
            ->groupBy('product_code')
            ->pluck('expired', 'product_code')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    /**
     * Unidades que realmente se pueden vender: stock total menos lo vencido.
     */
    public function sellable(int $branchId, string $productCode): float
    {
        $stock = (float) BranchStock::where('branch_id', $branchId)->where('product_code', $productCode)->value('stock_actual');

        return max(0, round($stock - $this->expiredQuantity($branchId, $productCode), 2));
    }

    /**
     * Mantiene la regla "los lotes nunca suman más que el stock": si algún movimiento bajó el total
     * sin pasar por los lotes (ajuste, anulación), recorta el exceso empezando por los que vencen más tarde.
     */
    public function reconcile(int $branchId, string $productCode): void
    {
        $stock = (float) BranchStock::where('branch_id', $branchId)->where('product_code', $productCode)->value('stock_actual');

        $batches = StockBatch::where('branch_id', $branchId)
            ->where('product_code', $productCode)
            ->where('quantity_remaining', '>', 0)
            ->orderByRaw('expiration_date IS NOT NULL')       // sin vencimiento primero (se recortan antes)
            ->orderByDesc('expiration_date')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->get();

        $excess = round($batches->sum('quantity_remaining') - $stock, 2);

        foreach ($batches as $batch) {
            if ($excess <= 0) {
                break;
            }

            $cut = round(min($excess, $batch->quantity_remaining), 2);
            $batch->update(['quantity_remaining' => round($batch->quantity_remaining - $cut, 2)]);
            $excess = round($excess - $cut, 2);
        }
    }

    /**
     * ¿Siguen intactas todas las unidades que entraron con un documento? (nada vendido ni movido)
     * Se usa antes de anular una compra, un preparado o un traspaso.
     */
    public function isIntact(string $sourceType, int $sourceId, ?int $branchId = null): bool
    {
        return !StockBatch::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereColumn('quantity_remaining', '<', 'quantity_initial')
            ->exists();
    }

    /**
     * Retira del control de lotes los que originó un documento anulado.
     */
    public function discardSource(string $sourceType, int $sourceId, ?int $branchId = null): void
    {
        StockBatch::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->update(['quantity_remaining' => 0]);
    }
}
