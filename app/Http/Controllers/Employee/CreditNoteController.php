<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\CreditNoteRequest;
use App\Jobs\SendCreditNoteToSunat;
use App\Models\CreditNote;
use App\Models\Order;
use App\Services\Sunat\Contracts\SunatGateway;
use App\Services\Sunat\CreditNoteService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CreditNoteController extends Controller
{
    /**
     * Emite la nota de crédito que anula una boleta o factura aceptada por SUNAT.
     * La venta queda anulada y el stock vuelve a la sede; el envío a SUNAT ocurre
     * después de responder al usuario.
     */
    public function store(CreditNoteRequest $request, Order $order, CreditNoteService $service)
    {
        $employee = auth()->guard('employee')->user();

        try {
            $note = $service->issue($order, $employee, $request->reason_code, $request->reason_text);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::error("Error al emitir la nota de crédito de la orden {$order->id}: " . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'No se pudo emitir la nota de crédito.'], 500);
        }

        \App\Models\AuditLog::record('credit_note.issue', 'Emitió la nota de crédito ' . $note->voucher_number . ' (S/ ' . number_format($note->total, 2) . ')', $order->voucher_number, ['motivo' => $request->reason_text]);

        SendCreditNoteToSunat::dispatchAfterResponse($note->id);

        return response()->json([
            'success'        => true,
            'message'        => "Nota de crédito {$note->voucher_number} emitida. La venta quedó anulada.",
            'voucher_number' => $note->voucher_number,
        ]);
    }

    /**
     * Reenvía a SUNAT una nota de crédito pendiente o con error de envío.
     * Se ejecuta en el momento para mostrar el resultado.
     */
    public function resend(CreditNote $creditNote, SunatGateway $gateway)
    {
        $employee = auth()->guard('employee')->user();

        abort_if(
            $creditNote->company_id !== $employee->company_id ||
            $creditNote->branch_id  !== $employee->branch_id,
            403
        );

        if (!$creditNote->canResendToSunat()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta nota de crédito no se puede reenviar a SUNAT.',
            ], 422);
        }

        $updated = (new SendCreditNoteToSunat($creditNote->id))->handle($gateway);

        return response()->json([
            'success' => $updated?->sunat_status === Order::SUNAT_ACCEPTED,
            'status'  => $updated?->sunat_status,
            'label'   => $updated?->sunatLabel(),
            'message' => $updated?->sunat_message ?? 'La nota de crédito ya se está enviando.',
        ]);
    }
}
