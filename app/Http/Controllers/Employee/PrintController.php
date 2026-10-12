<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\CompanySunatSetting;
use App\Models\Order;
use App\Services\Sunat\VoucherQr;

class PrintController extends Controller
{
    /**
     * Plantillas de impresión disponibles.
     * Cada clave mapea al archivo en resources/views/employee/print/
     */
    const TEMPLATES = [
        'ticket_80mm' => 'employee.print.ticket_80mm',
        'ticket_58mm' => 'employee.print.ticket_58mm',
        'boleta_a4'   => 'employee.print.boleta_a4',
        'nota_venta'  => 'employee.print.nota_venta',
    ];

    /**
     * Renderiza el comprobante de impresión de una orden.
     *
     * Si no se pasa template en la URL, usa el configurado en la sede.
     * Si la sede tampoco tiene config, usa ticket_80mm por defecto.
     */
    public function show(Order $order, string $template = null)
    {
        $employee = auth()->guard('employee')->user();

        // Verificar que la orden pertenece a la misma compañía
        abort_if($order->company_id !== $employee->company_id, 403);

        // Determinar plantilla: parámetro URL → config de sede → fallback
        $template = $this->resolveTemplate($template, $employee);

        // Cargar relaciones necesarias para el comprobante
        $order->load([
            'items',
            'branch',
            'company',
            'employee',
            'client',
            'documentType',
        ]);

        $paymentLabels  = [1 => 'Efectivo', 2 => 'Tarjeta', 3 => 'Transferencia', 4 => 'Yape', 5 => 'Crédito'];
        $voucherLabels  = [1 => 'Boleta de Venta', 2 => 'Factura', 3 => 'Nota de Venta'];

        // Boleta o factura electrónica: razón social y dirección fiscal + QR de SUNAT.
        // La nota de venta (y las empresas sin facturación electrónica) usan los datos de siempre.
        $isSunat = $order->sunat_status !== Order::SUNAT_NOT_APPLICABLE;
        $setting = $isSunat ? CompanySunatSetting::where('company_id', $order->company_id)->first() : null;

        return view(self::TEMPLATES[$template], [
            'order'         => $order,
            'paymentLabel'  => $paymentLabels[$order->payment_type] ?? 'Otro',
            'voucherLabel'  => $voucherLabels[$order->voucher_type] ?? 'Comprobante',
            'issuerName'    => $setting?->legal_name ?: $order->company->name,
            'issuerAddress' => $setting?->fiscal_address ?: ($order->company->address ?: $order->branch->address),
            'qrSvg'         => $isSunat ? app(VoucherQr::class)->svg($order) : null,
        ]);
    }

    /**
     * Resuelve qué plantilla usar según prioridad:
     *   1. Parámetro de URL (si es válido)
     *   2. Config de la sede del empleado
     *   3. Fallback: ticket_80mm
     */
    private function resolveTemplate(?string $template, $employee): string
    {
        // Parámetro de URL válido
        if ($template && array_key_exists($template, self::TEMPLATES)) {
            return $template;
        }

        // Config de la sede
        $branch           = $employee->branch()->with('config')->first();
        $configTemplate   = $branch?->getSetting('printing', 'default_template');

        if ($configTemplate && array_key_exists($configTemplate, self::TEMPLATES)) {
            return $configTemplate;
        }

        return 'ticket_80mm';
    }
}
