<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\PrintController;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Muestra la pantalla de configuración de la sede.
     */
    public function index()
    {
        $employee = auth()->guard('employee')->user();
        $branch   = $employee->branch()->with('config')->first();

        // Configuración de impresión actual (con valores por defecto)
        $printConfig = array_merge([
            'default_template' => 'ticket_80mm',
            'auto_print'       => false,
            'copies'           => 1,
            'printer_name'     => '',
        ], $branch->getSettingGroup('printing'));

        // Ajustes de venta (apagados por defecto)
        $salesConfig = array_merge([
            'cash_rounding'   => false,
            'amount_discount' => false,
        ], $branch->getSettingGroup('sales'));

        $templates = PrintController::TEMPLATES;

        return view('employee.pages.settings.index', compact('branch', 'printConfig', 'salesConfig', 'templates'));
    }

    /**
     * Guarda la configuración de impresión de la sede.
     */
    public function update(Request $request)
    {
        $request->validate([
            'default_template' => 'required|in:' . implode(',', array_keys(PrintController::TEMPLATES)),
            'auto_print'       => 'boolean',
            'copies'           => 'required|integer|min:1|max:5',
            'printer_name'     => 'nullable|string|max:100',
            'cash_rounding'    => 'nullable|boolean',
            'amount_discount'  => 'nullable|boolean',
        ]);

        $employee = auth()->guard('employee')->user();
        $branch   = $employee->branch()->with('config')->first();

        $branch->setSettingGroup('printing', [
            'default_template' => $request->default_template,
            'auto_print'       => $request->boolean('auto_print'),
            'copies'           => (int) $request->copies,
            'printer_name'     => $request->printer_name ?? '',
        ]);

        $branch->setSettingGroup('sales', [
            'cash_rounding'   => $request->boolean('cash_rounding'),
            'amount_discount' => $request->boolean('amount_discount'),
        ]);

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
