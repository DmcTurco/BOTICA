<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\DocumentSeries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentSeriesController extends Controller
{
    /**
     * Series y correlativos de comprobantes de la sede del empleado.
     */
    public function index()
    {
        $employee = auth()->guard('employee')->user();

        $series = DocumentSeries::where('company_id', $employee->company_id)
            ->where('branch_id', $employee->branch_id)
            ->orderBy('type_code')->orderBy('series')
            ->get();

        return view('employee.pages.series.index', compact('series'));
    }

    /**
     * Ajusta una serie: puede avanzar el último correlativo usado (nunca retroceder,
     * para no repetir números ya emitidos) y activar o desactivar la serie.
     * Siempre debe quedar una serie activa por tipo de comprobante.
     */
    public function update(Request $request, DocumentSeries $series)
    {
        $employee = auth()->guard('employee')->user();

        abort_if($series->company_id !== $employee->company_id || $series->branch_id !== $employee->branch_id, 403);

        $limit = $series->digits === 8 ? DocumentSeries::LIMITE_CORRELATIVO : (10 ** $series->digits) - 1;

        $data = $request->validate([
            'current_number' => "required|integer|min:{$series->current_number}|max:{$limit}",
            'active'         => 'required|boolean',
        ], [
            'current_number.min' => "El correlativo no puede retroceder: ya se usó hasta el {$series->current_number}.",
            'current_number.max' => "El correlativo no puede superar {$limit}.",
        ]);

        $sameType = fn () => DocumentSeries::where('company_id', $employee->company_id)
            ->where('branch_id', $series->branch_id)
            ->where('type_code', $series->type_code)
            ->where('id', '!=', $series->id);

        $error = null;

        DB::transaction(function () use ($series, $data, $sameType, &$error) {
            if (!$data['active']) {
                if (!$sameType()->where('active', true)->exists()) {
                    $error = 'Debe quedar al menos una serie activa de este tipo de comprobante.';
                    return;
                }
            } else {
                // Una sola serie activa por tipo y sede: la anterior pasa a inactiva
                $sameType()->update(['active' => false]);
            }

            $series->update(['current_number' => $data['current_number'], 'active' => $data['active']]);
        });

        if ($error) {
            return back()->with('error', $error);
        }

        \App\Models\AuditLog::record('series.update', 'Actualizó la serie ' . $series->series, $series->series, ['ultimo_numero' => $data['current_number'], 'activa' => (bool) $data['active']]);

        return redirect()->route('employee.series.index')->with('success', "Serie {$series->series} actualizada.");
    }
}
