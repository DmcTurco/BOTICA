<?php

namespace App\Services;

use App\Models\BranchStock;
use App\Models\StockMovement;
use RuntimeException;

/**
 * Mueve el stock de una sede y deja el rastro en el kardex.
 * Debe llamarse DENTRO de una transacción DB: bloquea la fila de stock (FOR UPDATE).
 */
class StockService
{
    /**
     * Suma o resta unidades al stock de un producto en una sede.
     *
     * @param  float   $delta      positivo = ingreso, negativo = salida
     * @param  string  $reference  origen del movimiento: transfer, production, manual, purchase_void...
     * @param  ?string $type       fuerza el tipo del kardex ('ajuste'); por defecto entrada/salida según el signo
     * @return float   stock resultante
     *
     * @throws RuntimeException si la salida deja el stock en negativo
     */
    public function move(
        int $companyId,
        int $branchId,
        string $productCode,
        float $delta,
        string $reference,
        ?int $referenceId = null,
        float $unitCost = 0,
        ?string $notes = null,
        ?string $type = null,
    ): float {
        $stock = BranchStock::where('branch_id', $branchId)
            ->where('product_code', $productCode)
            ->lockForUpdate()
            ->first();

        $current = $stock ? (float) $stock->stock_actual : 0.0;
        $balance = round($current + $delta, 2);

        if ($balance < 0) {
            throw new RuntimeException("Stock insuficiente de {$productCode}: hay {$current} y se necesitan " . abs($delta) . '.');
        }

        if ($stock) {
            $stock->update(['stock_actual' => $balance]);
        } else {
            BranchStock::create([
                'branch_id'    => $branchId,
                'product_code' => $productCode,
                'stock_actual' => $balance,
            ]);
        }

        StockMovement::create([
            'company_id'     => $companyId,
            'branch_id'      => $branchId,
            'product_code'   => $productCode,
            'type'           => $type ?? ($delta >= 0 ? 'entrada' : 'salida'),
            'reference_type' => $reference,
            'reference_id'   => $referenceId,
            'quantity'       => $type === 'ajuste' ? $delta : abs($delta),
            'unit_cost'      => $unitCost,
            'balance'        => $balance,
            'notes'          => $notes,
        ]);

        return $balance;
    }
}
