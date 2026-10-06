<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CompanySunatSetting;
use App\Models\SunatSummary;
use App\Services\Sunat\DailySummaryService;

class SunatSummaryController extends Controller
{
    /**
     * Envía a SUNAT el resumen diario de las boletas pendientes de la compañía
     * y consulta los resúmenes que SUNAT aún estaba procesando.
     * Se ejecuta en el momento para mostrar el resultado al usuario.
     */
    public function store(DailySummaryService $service)
    {
        $employee = auth()->guard('employee')->user();

        $setting = CompanySunatSetting::where('company_id', $employee->company_id)->first();

        if (!$setting || !$setting->enabled || !$setting->isReady() || !$setting->usesBoletaSummary()) {
            return response()->json([
                'success' => false,
                'message' => 'La compañía no usa resumen diario de boletas.',
            ], 422);
        }

        $resolved  = $service->refreshProcessing($employee->company_id);
        $summaries = $service->sendPending($employee->company_id);

        if ($summaries->isEmpty() && $resolved === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No hay boletas pendientes de informar a SUNAT.',
            ]);
        }

        $lines = $summaries->map(fn (SunatSummary $s) => "{$s->identifier} ({$s->documents_count} boletas): "
            . match ($s->status) {
                SunatSummary::STATUS_ACCEPTED   => 'aceptado',
                SunatSummary::STATUS_REJECTED   => 'rechazado — ' . $s->message,
                SunatSummary::STATUS_PROCESSING => 'SUNAT aún lo procesa, se consultará después',
                default                         => 'error de envío — ' . $s->message,
            });

        return response()->json([
            'success' => $summaries->every(fn (SunatSummary $s) => $s->status !== SunatSummary::STATUS_REJECTED
                && $s->status !== SunatSummary::STATUS_ERROR),
            'message' => $lines->isNotEmpty()
                ? $lines->implode("\n")
                : "Se actualizaron {$resolved} resúmenes que estaban en proceso.",
        ]);
    }
}
