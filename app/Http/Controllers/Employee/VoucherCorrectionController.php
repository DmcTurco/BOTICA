<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\VoucherCorrectionRequest;
use App\Jobs\SendCreditNoteToSunat;
use App\Models\AuditLog;
use App\Models\Order;
use App\Services\Sunat\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class VoucherCorrectionController extends Controller
{
    /**
     * Corrige un comprobante (nombre, documento o tipo boleta/factura) en un solo paso:
     *  1. Anula el original: con nota de crédito si SUNAT lo aceptó, o sin ella si lo rechazó
     *     (un rechazado no tiene validez y su número queda inutilizado). El stock vuelve a la sede.
     *  2. Emite un comprobante nuevo con los mismos productos, precios y pago, y los datos corregidos.
     *  3. El comprobante nuevo queda en la misma caja que el original: el cliente ya pagó, así que
     *     no entra dinero nuevo (anulado −X y nuevo +X en esa caja).
     * Todo ocurre en una transacción: si algo falla, no cambia nada.
     */
    public function store(VoucherCorrectionRequest $request, Order $order, CreditNoteService $service)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($order->company_id !== $employee->company_id || $order->branch_id !== $employee->branch_id, 403);

        $order->load(['items', 'prescription', 'creditNote']);

        if (!$order->canCorrect()) {
            return response()->json([
                'success' => false,
                'message' => 'Este comprobante no se puede corregir: debe estar aceptado o rechazado por SUNAT, no estar anulado y no ser una venta a crédito.',
            ], 422);
        }

        // La receta se registra en la venta original: se corrige a mano para no perder ese dato
        if ($order->prescription) {
            return response()->json([
                'success' => false,
                'message' => 'Esta venta tiene una receta registrada. Anúlala con una nota de crédito y regístrala de nuevo.',
            ], 422);
        }

        $wasAccepted = $order->sunat_status === Order::SUNAT_ACCEPTED;
        $oldNumber   = $order->voucher_number;
        $note        = null;

        DB::beginTransaction();

        try {
            if ($wasAccepted) {
                $note = $service->issue($order, $employee, $request->reason_code, $request->reason_text);
            } else {
                $service->voidRejected($order, $employee);
            }

            // El comprobante nuevo se registra con el mismo flujo de una venta (stock, series, SUNAT)
            $response = app(OrderController::class)->store($this->saleRequest($request, $order));
            $payload  = $response->getData(true);

            if (!($payload['success'] ?? false)) {
                throw new RuntimeException($payload['message'] ?? 'No se pudo emitir el comprobante nuevo.');
            }

            $new = Order::findOrFail($payload['order_id']);

            // El cliente ya pagó: el total debe ser el mismo. Si un precio cambió desde la venta, no se corrige solo
            if (abs((float) $new->total - (float) $order->total) > 0.005) {
                throw new RuntimeException('Los precios de los productos cambiaron desde esta venta y el total ya no coincide (S/ ' . number_format($order->total, 2) . '). Anúlala con una nota de crédito y regístrala de nuevo.');
            }

            // Mismo dinero, misma caja
            $new->update(['cash_register_id' => $order->cash_register_id]);

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (RuntimeException $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al corregir el comprobante {$oldNumber}: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'No se pudo corregir el comprobante.'], 500);
        }

        AuditLog::record(
            'voucher.correct',
            "Corrigió {$oldNumber}: nuevo comprobante {$new->voucher_number}" . ($note ? " (NC {$note->voucher_number})" : ' (el original estaba rechazado)'),
            $oldNumber,
            ['nuevo' => $new->voucher_number, 'nota_credito' => $note?->voucher_number]
        );

        // La nota de crédito se informa a SUNAT después de responder al cajero (el comprobante nuevo ya lo hizo la venta)
        if ($note) {
            SendCreditNoteToSunat::dispatchAfterResponse($note->id);
        }

        return response()->json([
            'success'        => true,
            'message'        => "Comprobante corregido: {$oldNumber} se anuló y se emitió {$new->voucher_number}.",
            'voucher_number' => $new->voucher_number,
            'order_id'       => $new->id,
        ]);
    }

    /**
     * Arma la solicitud de la venta nueva con los productos, precios, pago y descuento de la original
     * y los datos corregidos del cliente. Los precios los vuelve a tomar la venta de la base de datos.
     */
    private function saleRequest(VoucherCorrectionRequest $request, Order $order): Request
    {
        $data = [
            'items' => $order->items->map(fn ($item) => [
                'product_id'      => $item->product_id,
                'name'            => $item->product_name,
                'qty'             => (float) $item->quantity,
                'price'           => (float) $item->unit_price,
                'presentation_id' => $item->presentation_id,
            ])->all(),
            'payment_type'      => $order->payment_type,
            'operation_number'  => $order->operation_number,
            'amount_received'   => $order->amount_received,
            'voucher_type'      => (int) $request->voucher_type,
            'document_type_id'  => $request->document_type_id,
            'customer_document' => $request->customer_document,
            'customer_name'     => $request->customer_name,
        ];

        // Descuento de la venta original: porcentaje entero si lo fue; si no, el monto
        if ((float) $order->discount_amount > 0) {
            $percent = (float) $order->discount_percent;

            if ($percent > 0 && floor($percent) === $percent) {
                $data['discount_percent'] = (int) $percent;
            } else {
                $data['discount_value'] = (float) $order->discount_amount;
            }
        }

        return Request::create('/', 'POST', $data);
    }
}
