<?php

use App\Models\CashRegister;
use App\Models\CreditNote;
use App\Models\DocumentSeries;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Deja una factura F001-00000001 aceptada por SUNAT con el nombre mal puesto
 * (2 × P1 de S/ 10 en efectivo = S/ 20) y las series necesarias para emitir otra.
 */
function acceptedInvoice(Employee $employee): Order
{
    posSetup($employee);

    foreach ([
        [DocumentSeries::BOLETA, 'Boleta', 'B001'],
        [DocumentSeries::FACTURA, 'Factura', 'F001'],
        [DocumentSeries::NOTA_CREDITO_BOLETA, 'NC boleta', 'BC01'],
        [DocumentSeries::NOTA_CREDITO_FACTURA, 'NC factura', 'FC01'],
    ] as [$type, $name, $series]) {
        DocumentSeries::create([
            'company_id' => 1, 'branch_id' => $employee->branch_id, 'type_code' => $type,
            'name' => $name, 'series' => $series, 'current_number' => 0, 'digits' => 8, 'active' => true,
        ]);
    }

    posSale(['total' => 20])->assertOk();

    $order = Order::firstOrFail();
    $order->update([
        'voucher_type' => 2, 'voucher_number' => 'F001-90000001', 'document_type_id' => 3,
        'customer_document' => '20123456789', 'customer_name' => 'CLIENTE PÚBLICO',
        'sunat_status' => Order::SUNAT_ACCEPTED, 'sunat_code' => '0',
    ]);

    return $order->fresh();
}

it('corrige una factura aceptada: nota de crédito, comprobante nuevo con los datos bien y la misma caja', function () {
    $employee = loginEmployee();
    $old      = acceptedInvoice($employee);
    $register = CashRegister::firstOrFail();

    expect(stockOf('P1'))->toBe(8.0)->and($register->totalCash())->toBe(20.0);

    // El cliente no tenía RUC: se pasa a boleta con su DNI y el nombre correcto (en minúsculas: se guarda en mayúsculas)
    $this->postJson(route('employee.orders.correct', $old), [
        'voucher_type' => 1, 'document_type_id' => 1, 'customer_document' => '47539215', 'customer_name' => 'david mamani chino',
        'reason_code' => CreditNote::REASON_CANCELLATION, 'reason_text' => 'Datos del cliente mal puestos',
    ])->assertOk()->assertJson(['success' => true]);

    $new = Order::where('id', '!=', $old->id)->firstOrFail();

    expect($old->fresh()->status)->toBe(0)
        ->and(CreditNote::where('order_id', $old->id)->count())->toBe(1)
        ->and((int) $new->voucher_type)->toBe(1)
        ->and($new->customer_name)->toBe('DAVID MAMANI CHINO')
        ->and($new->customer_document)->toBe('47539215')
        ->and((int) $new->document_type_id)->toBe(1)
        ->and((float) $new->total)->toBe(20.0)
        ->and($new->status)->toBe(1)
        // El stock quedó igual (volvió y salió de nuevo) y el dinero no se contó dos veces
        ->and(stockOf('P1'))->toBe(8.0)
        ->and($new->cash_register_id)->toBe($old->cash_register_id)
        ->and($register->fresh()->totalCash())->toBe(20.0);
});

it('corrige una factura rechazada sin nota de crédito: se anula y se emite una nueva con RUC', function () {
    $employee = loginEmployee();
    $old      = acceptedInvoice($employee);
    $old->update(['document_type_id' => 1, 'customer_document' => '47539215', 'sunat_status' => Order::SUNAT_REJECTED, 'sunat_code' => '2800']);

    $this->postJson(route('employee.orders.correct', $old), [
        'voucher_type' => 2, 'document_type_id' => 3, 'customer_document' => '20123456789', 'customer_name' => 'Mi Empresa SAC',
    ])->assertOk()->assertJson(['success' => true]);

    $new = Order::where('id', '!=', $old->id)->firstOrFail();

    expect($old->fresh()->status)->toBe(0)
        ->and(CreditNote::count())->toBe(0)                       // un rechazado no tiene nota de crédito
        ->and((int) $new->voucher_type)->toBe(2)
        ->and((int) $new->document_type_id)->toBe(3)
        ->and($new->customer_name)->toBe('MI EMPRESA SAC')
        ->and(stockOf('P1'))->toBe(8.0)
        ->and(StockMovement::where('reference_type', 'order_void')->where('reference_id', $old->id)->exists())->toBeTrue();
});

it('no corrige a factura con DNI y deja todo como estaba', function () {
    $employee = loginEmployee();
    $old      = acceptedInvoice($employee);

    $this->postJson(route('employee.orders.correct', $old), [
        'voucher_type' => 2, 'document_type_id' => 1, 'customer_document' => '47539215', 'customer_name' => 'JUAN PEREZ',
        'reason_code' => CreditNote::REASON_CANCELLATION, 'reason_text' => 'x',
    ])->assertStatus(422)->assertJsonValidationErrors('document_type_id');

    expect($old->fresh()->status)->toBe(1)->and(Order::count())->toBe(1)->and(CreditNote::count())->toBe(0)->and(stockOf('P1'))->toBe(8.0);
});

it('si el comprobante nuevo no se puede emitir, se deshace todo (no queda la nota de crédito)', function () {
    $employee = loginEmployee();
    $old      = acceptedInvoice($employee);

    // El precio cambió después de la venta: el total ya no coincidiría y no se corrige solo
    Product::where('code', 'P1')->update(['unit_sale_price' => 12]);

    $this->postJson(route('employee.orders.correct', $old), [
        'voucher_type' => 1, 'document_type_id' => 1, 'customer_document' => '47539215', 'customer_name' => 'JUAN PEREZ',
        'reason_code' => CreditNote::REASON_CANCELLATION, 'reason_text' => 'Datos mal puestos',
    ])->assertStatus(422)->assertJson(['success' => false]);

    expect($old->fresh()->status)->toBe(1)
        ->and(Order::count())->toBe(1)
        ->and(CreditNote::count())->toBe(0)
        ->and(stockOf('P1'))->toBe(8.0);
});

it('solo se corrigen comprobantes aceptados o rechazados, y no las ventas a crédito', function () {
    $employee = loginEmployee();
    $old      = acceptedInvoice($employee);
    $data     = ['voucher_type' => 1, 'document_type_id' => 1, 'customer_document' => '47539215', 'customer_name' => 'JUAN PEREZ', 'reason_code' => '01', 'reason_text' => 'x'];

    $old->update(['sunat_status' => Order::SUNAT_PENDING]);
    $this->postJson(route('employee.orders.correct', $old), $data)->assertStatus(422)->assertJson(['success' => false]);

    $old->update(['sunat_status' => Order::SUNAT_ACCEPTED, 'payment_type' => CashRegister::PAYMENT_CREDIT]);
    $this->postJson(route('employee.orders.correct', $old), $data)->assertStatus(422)->assertJson(['success' => false]);

    expect(Order::count())->toBe(1)->and($old->fresh()->status)->toBe(1);
});

it('el historial muestra el botón Corregir solo en boletas y facturas aceptadas o rechazadas', function () {
    $employee = loginEmployee();
    $old      = acceptedInvoice($employee);

    $this->get(route('employee.orders.historial'))->assertOk()
        ->assertSee('Corregir cliente o tipo de comprobante')->assertSee('modalCorregir', false);

    // Pendiente de enviar a SUNAT: aún no se puede corregir
    $old->update(['sunat_status' => Order::SUNAT_PENDING]);
    $this->get(route('employee.orders.historial'))->assertOk()->assertDontSee('Corregir cliente o tipo de comprobante');
});
