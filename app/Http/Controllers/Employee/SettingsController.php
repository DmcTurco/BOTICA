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
            'rounding_favor'  => 'customer',   // a favor de quién se redondea: customer (hacia abajo) o business (hacia arriba)
            'amount_discount' => false,
        ], $branch->getSettingGroup('sales'));

        $templates = PrintController::TEMPLATES;

        return view('employee.pages.settings.index', compact('branch', 'printConfig', 'salesConfig', 'templates'));
    }

    /**
     * Pantalla para elegir el formato de impresión con vista previa.
     */
    public function format()
    {
        $employee = auth()->guard('employee')->user();
        $branch   = $employee->branch()->with('config')->first();

        $currentTemplate = $branch->getSettingGroup('printing')['default_template'] ?? 'ticket_80mm';

        $templateLabels = [
            'ticket_80mm' => ['icon' => 'fa-receipt',    'title' => 'Ticket 80mm',         'desc' => 'Impresora térmica estándar — papel de 80mm'],
            'ticket_58mm' => ['icon' => 'fa-receipt',    'title' => 'Ticket 58mm',         'desc' => 'Impresora térmica compacta — papel de 58mm'],
            'boleta_a4'   => ['icon' => 'fa-file-lines', 'title' => 'Boleta / Factura A4', 'desc' => 'Hoja completa A4 — diseño formal'],
            'nota_venta'  => ['icon' => 'fa-file-alt',   'title' => 'Nota de Venta',       'desc' => 'Comprobante interno — tamaño A5'],
        ];

        return view('employee.pages.settings.print-format', compact('branch', 'currentTemplate', 'templateLabels'));
    }

    /**
     * Guarda solo el formato de impresión, conservando el resto de la config de impresión.
     */
    public function updateFormat(Request $request)
    {
        $request->validate([
            'default_template' => 'required|in:' . implode(',', array_keys(PrintController::TEMPLATES)),
        ]);

        $employee = auth()->guard('employee')->user();
        $branch   = $employee->branch()->with('config')->first();

        $branch->setSettingGroup('printing', array_merge(
            $branch->getSettingGroup('printing'),
            ['default_template' => $request->default_template]
        ));

        return redirect()->route('employee.settings.index')->with('success', 'Formato de impresión actualizado.');
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
            'rounding_favor'   => 'nullable|in:customer,business',
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
            'rounding_favor'  => $request->input('rounding_favor') === 'business' ? 'business' : 'customer',
            'amount_discount' => $request->boolean('amount_discount'),
        ]);

        return back()->with('success', 'Configuración guardada correctamente.');
    }
}
