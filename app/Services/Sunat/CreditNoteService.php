<?php

namespace App\Services\Sunat;

use App\Models\BranchStock;
use App\Models\CreditNote;
use App\Models\DocumentSeries;
use App\Models\Employee;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Emite la nota de crédito que anula por completo una boleta o factura aceptada por SUNAT.
 * En una sola transacción: crea la nota con su correlativo, marca la venta como anulada
 * y devuelve el stock a la sede (con su movimiento de kardex).
 * El envío a SUNAT lo hace después SendCreditNoteToSunat.
 */
class CreditNoteService
{
    /**
     * @throws RuntimeException si la venta no se puede anular con una nota de crédito
     */
    public function issue(Order $order, Employee $employee, string $reasonCode, string $reasonText): CreditNote
    {
        return DB::transaction(function () use ($order, $employee, $reasonCode, $reasonText) {
            // Se bloquea la venta para que dos usuarios no emitan la nota a la vez
            $order = Order::with('items')->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->company_id !== $employee->company_id || $order->branch_id !== $employee->branch_id) {
                throw new RuntimeException('Solo puedes anular comprobantes de tu sede.');
            }

            if (!$order->isSunatVoucher() || $order->sunat_status !== Order::SUNAT_ACCEPTED) {
                throw new RuntimeException('Solo se puede emitir una nota de crédito sobre una boleta o factura aceptada por SUNAT.');
            }

            if (!$order->status) {
                throw new RuntimeException('Este comprobante ya está anulado.');
            }

            if (CreditNote::where('order_id', $order->id)->exists()) {
                throw new RuntimeException('Este comprobante ya tiene una nota de crédito.');
            }

            // Las series de nota de crédito empiezan con B (boletas) o F (facturas)
            $typeCode = (int) $order->voucher_type === 2
                ? DocumentSeries::NOTA_CREDITO_FACTURA
                : DocumentSeries::NOTA_CREDITO_BOLETA;

            $note = CreditNote::create([
                'company_id'        => $order->company_id,
                'branch_id'         => $order->branch_id,
                'order_id'          => $order->id,
                'employee_id'       => $employee->id,
                'voucher_number'    => DocumentSeries::siguiente($typeCode, $order->branch_id),
                'reason_code'       => $reasonCode,
                'reason_text'       => $reasonText,
                'taxable_amount'    => $order->taxable_amount,
                'exonerated_amount' => $order->exonerated_amount,
                'unaffected_amount' => $order->unaffected_amount,
                'subtotal'          => $order->subtotal,
                'igv'               => $order->igv,
                'total'             => $order->total,
                'sunat_status'      => Order::SUNAT_PENDING,
            ]);

            $this->restoreStock($order, $note, $employee);

            // La venta queda anulada: ya no suma en el total de la caja
            $order->update(['status' => 0, 'credit_balance' => 0]);

            return $note;
        });
    }

    /**
     * Devuelve a la sede el stock de cada ítem anulado y registra la entrada en el kardex.
     * Se bloquean las filas de stock en orden por código (evita bloqueos cruzados).
     */
    private function restoreStock(Order $order, CreditNote $note, Employee $employee): void
    {
        // Las unidades vuelven a los lotes de los que salieron
        app(\App\Services\BatchService::class)->restore('order', $order->id);

        $stocks = BranchStock::where('branch_id', $order->branch_id)
            ->whereIn('product_code', $order->items->pluck('product_code')->unique())
            ->orderBy('product_code')
            ->lockForUpdate()
            ->get()
            ->keyBy('product_code');

        foreach ($order->items as $item) {
            $stock = $stocks->get($item->product_code);

            if (!$stock) {
                continue; // el producto ya no existe en la sede: no hay stock que devolver
            }

            $stock->update(['stock_actual' => $stock->stock_actual + $item->quantity]);

            StockMovement::create([
                'company_id'     => $order->company_id,
                'branch_id'      => $order->branch_id,
                'product_code'   => $item->product_code,
                'type'           => 'entrada',
                'reference_type' => 'credit_note',
                'reference_id'   => $note->id,
                'quantity'       => (int) $item->quantity,
                'unit_cost'      => $item->unit_price,
                'balance'        => (int) $stock->stock_actual,
                'notes'          => "Anulación {$order->voucher_number} (NC {$note->voucher_number})",
            ]);
        }
    }
}
