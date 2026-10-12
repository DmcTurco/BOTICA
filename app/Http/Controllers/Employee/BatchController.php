<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\StockBatch;
use App\Services\BatchService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchController extends Controller
{
    /**
     * Da de baja las unidades que quedan de un lote vencido: sale del stock y del control de lotes,
     * y queda un ajuste en el Kardex.
     */
    public function writeOff(StockBatch $batch, StockService $stock, BatchService $batches)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($batch->company_id !== $employee->company_id || $batch->branch_id !== $employee->branch_id, 403);

        if (!$batch->isExpired()) {
            return back()->with('error', 'Solo se pueden dar de baja lotes ya vencidos.');
        }

        if ($batch->quantity_remaining <= 0) {
            return back()->with('error', 'Este lote ya no tiene unidades.');
        }

        DB::beginTransaction();

        try {
            $batch = StockBatch::lockForUpdate()->find($batch->id);
            $qty   = $batch->quantity_remaining;

            $stock->move(
                $employee->company_id, $employee->branch_id, $batch->product_code, -$qty, 'manual', null,
                $batch->unit_cost, 'Baja por vencimiento · lote ' . ($batch->batch ?: 'sin lote'), 'ajuste'
            );
            $batch->update(['quantity_remaining' => 0]);

            DB::commit();

            \App\Models\AuditLog::record('batch.write_off', 'Dio de baja el lote vencido ' . ($batch->batch ?: 'sin lote') . ' de ' . $batch->product_code, $batch->product_code, ['unidades' => $qty]);

            return back()->with('success', 'Se dieron de baja ' . rtrim(rtrim(number_format($qty, 2), '0'), '.') . ' unidades vencidas.');
        } catch (\RuntimeException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al dar de baja un lote: ' . $e->getMessage());
            return back()->with('error', 'Error al dar de baja el lote. Inténtelo de nuevo.');
        }
    }
}
