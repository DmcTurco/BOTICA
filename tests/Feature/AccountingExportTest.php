<?php

use App\Models\CreditNote;
use App\Models\Employee;
use App\Models\Order;
use App\Support\CsvExport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Contenido de una descarga CSV como matriz de filas */
function csvRows($response): array
{
    $text = ltrim($response->streamedContent(), "\xEF\xBB\xBF");

    return array_map('str_getcsv', array_filter(explode("\n", $text)));
}

it('el registro de ventas separa tipos, anulados y notas de crédito, y excluye notas de venta de los totales', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);

    $boleta = reportSale($employee, 'A', 1, 100, ['voucher_type' => 1, 'voucher_number' => 'B001-00000001', 'taxable_amount' => 100, 'igv' => 18, 'total' => 118, 'customer_name' => 'JUAN PEREZ', 'customer_document' => '12345678']);
    reportSale($employee, 'A', 1, 50, ['voucher_type' => 2, 'voucher_number' => 'F001-00000001', 'taxable_amount' => 50, 'igv' => 9, 'total' => 59]);
    reportSale($employee, 'A', 1, 30, ['voucher_type' => 3, 'voucher_number' => 'NV01-00000001', 'total' => 30]);
    reportSale($employee, 'A', 1, 77, ['voucher_type' => 1, 'voucher_number' => 'B001-00000002', 'taxable_amount' => 77, 'igv' => 13.86, 'total' => 90.86, 'status' => 0]);   // anulada

    CreditNote::create(['company_id' => 1, 'branch_id' => 1, 'order_id' => $boleta->id, 'employee_id' => $employee->id, 'voucher_number' => 'BC01-00000001',
        'reason_code' => '01', 'reason_text' => 'Anulación', 'taxable_amount' => 100, 'subtotal' => 100, 'igv' => 18, 'total' => 118, 'sunat_status' => 'pending']);

    $view = $this->get(route('employee.accounting.sales'))->assertOk()->assertSee('B001-00000001');
    $totals = $view->viewData('totals');

    // Boleta 118 + factura 59 − nota de crédito 118 = 59 (la NV de 30 y la anulada no cuentan)
    expect($totals['total'])->toBe(59.0)->and($totals['igv'])->toBe(9.0)->and($totals['taxable'])->toBe(50.0)
        ->and($view->viewData('rows'))->toHaveCount(5);
});

it('exporta el registro de ventas en CSV con tipos SUNAT y serie/número separados', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);
    reportSale($employee, 'A', 1, 100, ['voucher_type' => 1, 'voucher_number' => 'B001-00000007', 'taxable_amount' => 100, 'igv' => 18, 'total' => 118, 'customer_name' => 'Ñandú Pérez']);

    $response = $this->get(route('employee.accounting.sales', ['export' => 'csv']))->assertOk();
    $rows     = csvRows($response);

    expect($response->headers->get('content-disposition'))->toContain('registro_ventas_' . now()->format('Y-m') . '.csv')
        ->and($rows[0])->toContain('Tipo comprobante', 'Serie', 'Número', 'IGV')
        ->and($rows[1][1])->toBe('03')       // boleta
        ->and($rows[1][2])->toBe('B001')
        ->and($rows[1][3])->toBe('00000007')
        ->and($rows[1][6])->toBe('Ñandú Pérez')
        ->and($rows[1][11])->toBe('118.00');
});

it('el registro de compras suma lo vigente y exporta con RUC del proveedor', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $supplier = App\Models\Supplier::create(['company_id' => 1, 'name' => 'Droguería Sur', 'ruc' => '20123456789', 'status' => 1]);

    foreach ([100, 40] as $i => $cost) {
        $this->post(route('employee.purchases.store'), [
            'document_type' => 2, 'document_number' => 'F001-' . ($i + 1), 'purchased_at' => today()->toDateString(), 'supplier_id' => $supplier->id, 'tax' => $cost * 0.18,
            'items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => $cost]],
        ])->assertSessionHasNoErrors();
    }
    $this->post(route('employee.purchases.void', App\Models\Purchase::first()), ['void_reason' => 'x'])->assertSessionHasNoErrors();

    $totals = $this->get(route('employee.accounting.purchases'))->assertOk()->viewData('totals');
    expect($totals['subtotal'])->toBe(40.0)->and($totals['tax'])->toBe(7.2);

    $rows = csvRows($this->get(route('employee.accounting.purchases', ['export' => 'csv'])));
    expect($rows)->toHaveCount(3)->and($rows[1][3])->toBe('20123456789')->and($rows[1][9])->toBe('Anulada')->and($rows[2][9])->toBe('Vigente');
});

it('los reportes se descargan como CSV con sus filtros', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 4);
    reportSale($employee, 'A', 5, 50);

    $best   = csvRows($this->get(route('employee.reports.best-sellers', ['export' => 'csv'])));
    $profit = csvRows($this->get(route('employee.reports.profit', ['export' => 'csv'])));
    $inv    = csvRows($this->get(route('employee.reports.inventory.valued', ['export' => 'csv'])));
    $stale  = $this->get(route('employee.reports.no-rotation', ['export' => 'csv']))->assertOk();

    expect($best[0])->toContain('Unidades vendidas')->and($best[1][0])->toBe('A')->and($best[1][2])->toBe('5.00')
        ->and($profit[1][5])->toBe('30.00')                 // 50 de venta − 20 de costo
        ->and($inv[0])->toContain('Valor a costo');
    expect($stale->headers->get('content-type'))->toContain('text/csv');
});

it('el CSV neutraliza fórmulas de Excel en el texto', function () {
    $response = CsvExport::download('prueba', ['Texto', 'Monto'], [['=HYPERLINK("http://malo")', 12.5], ['-123', 3.0], ['@suma', 1.0]]);

    ob_start();
    $response->sendContent();
    $text = ltrim(ob_get_clean(), "\xEF\xBB\xBF");

    expect($text)->toContain("\"'=HYPERLINK")->toContain('-123,3.00')->toContain("'@suma");
});

it('los registros contables exigen su privilegio', function () {
    loginEmployee([Employee::PRIV_REPORTE_UTILIDAD]);

    $this->get(route('employee.accounting.sales'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.accounting.purchases', ['export' => 'csv']))->assertRedirect(route('employee.home'));
});
