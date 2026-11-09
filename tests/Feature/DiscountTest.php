<?php

use App\Models\DocumentSeries;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Sunat\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Cálculo ───────────────────────────────────────────────────

it('aplica el descuento al importe de cada línea, que ya incluye el IGV', function () {
    $tax = (new TaxCalculator())->calculate(
        [
            ['product_id' => 'A', 'price' => 10, 'qty' => 2],  // gravado: 20 − 10% = 18 con IGV → base 15.25 + IGV 2.75
            ['product_id' => 'B', 'price' => 5,  'qty' => 1],  // exonerado: 5 − 10% = 4.50
        ],
        ['A' => '10', 'B' => '20'],
        10
    );

    expect($tax['lines'][0]['discount'])->toBe(2.0)
        ->and($tax['lines'][0]['base'])->toBe(15.25)
        ->and($tax['taxable'])->toBe(15.25)
        ->and($tax['exonerated'])->toBe(4.5)
        ->and($tax['discount'])->toBe(2.5)
        ->and($tax['igv'])->toBe(2.75)
        ->and($tax['total'])->toBe(22.5);
});

it('sin descuento el cálculo no cambia y rechaza porcentajes fuera de rango', function () {
    $tax = (new TaxCalculator())->calculate([['product_id' => 'A', 'price' => 10, 'qty' => 1]], ['A' => '10']);

    expect($tax['discount'])->toBe(0.0)->and($tax['total'])->toBe(10.0);
    expect(fn () => (new TaxCalculator())->calculate([['product_id' => 'A', 'price' => 1, 'qty' => 1]], ['A' => '10'], 101))
        ->toThrow(InvalidArgumentException::class);
});

// ── Venta en el POS ───────────────────────────────────────────

it('registra la venta con descuento y guarda los montos en la orden y sus líneas', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // 2 × 10 = 20 − 10% = 18 (ya con IGV: base 15.25 + IGV 2.75)
    posSale(['discount_percent' => 10, 'total' => 18])->assertOk()->assertJson(['success' => true]);

    $order = Order::firstOrFail();
    expect((float) $order->total)->toBe(18.0)
        ->and((float) $order->discount_amount)->toBe(2.0)
        ->and((float) $order->discount_percent)->toBe(10.0)
        ->and((float) $order->subtotal)->toBe(15.25)
        ->and((float) $order->igv)->toBe(2.75)
        ->and((float) OrderItem::first()->discount_amount)->toBe(2.0)
        ->and(stockOf('P1'))->toBe(8.0);
});

it('rechaza si el total del navegador no coincide con el descuento aplicado', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // El navegador dice 20 (sin descuento) pero pidió 10%: el servidor calcula 18
    posSale(['discount_percent' => 10, 'total' => 20])->assertStatus(422);

    expect(Order::count())->toBe(0)->and(stockOf('P1'))->toBe(10.0);
});

it('un empleado sin el privilegio no puede dar descuentos', function () {
    $employee = loginEmployee([Employee::PRIV_VER_VENTAS]);
    posSetup($employee);

    posSale(['discount_percent' => 10, 'total' => 18])->assertStatus(422)->assertJsonFragment(['message' => 'No tienes permiso para aplicar descuentos.']);

    expect(Order::count())->toBe(0);
});

it('no permite descuentos mayores al tope', function () {
    $employee = loginEmployee();
    posSetup($employee);

    posSale(['discount_percent' => 60, 'total' => 8])->assertStatus(422);

    expect(Order::count())->toBe(0);
});

it('el descuento aparece en el comprobante impreso', function () {
    $employee = loginEmployee();
    posSetup($employee);
    posSale(['discount_percent' => 10, 'total' => 18])->assertOk();

    foreach (['ticket_80mm', 'ticket_58mm', 'boleta_a4', 'nota_venta'] as $template) {
        $this->get(route('employee.orders.print', [Order::first(), $template]))->assertOk()->assertSee('10%', false);
    }
});

it('el porcentaje de descuento debe ser un número entero', function () {
    $employee = loginEmployee();
    posSetup($employee);

    posSale(['discount_percent' => 10.5, 'total' => 21.24])->assertStatus(422);

    expect(Order::count())->toBe(0);
});

// ── Descuento por monto y redondeo en efectivo (configurables por sede) ───────

/** Activa o apaga las opciones de venta de la sede del empleado */
function salesSettings(Employee $employee, bool $rounding, bool $amount): void
{
    $employee->branch->setSettingGroup('sales', ['cash_rounding' => $rounding, 'amount_discount' => $amount]);
}

it('reparte un descuento por monto entre las líneas y suma exactamente el monto', function () {
    $tax = (new TaxCalculator())->calculate(
        [
            ['product_id' => 'A', 'price' => 3.33, 'qty' => 1],
            ['product_id' => 'B', 'price' => 3.33, 'qty' => 1],
            ['product_id' => 'C', 'price' => 3.34, 'qty' => 1],
        ],
        ['A' => '10', 'B' => '10', 'C' => '20'],
        0,
        1.0
    );

    expect($tax['discount'])->toBe(1.0)
        ->and(round(array_sum(array_column($tax['lines'], 'discount')), 2))->toBe(1.0)
        ->and($tax['total'])->toBe(9.0);
});

it('el descuento por monto exige activarlo en la configuración de la sede', function () {
    $employee = loginEmployee();
    posSetup($employee);

    posSale(['discount_value' => 1, 'total' => 19])->assertStatus(422)
        ->assertJsonFragment(['message' => 'El descuento por monto no está activado en la configuración de la sede.']);

    salesSettings($employee, false, true);

    // 20 − 1.00 = 19.00 (5% equivalente; el precio ya incluye el IGV)
    posSale(['discount_value' => 1, 'total' => 19])->assertOk();

    $order = Order::firstOrFail();
    expect((float) $order->discount_amount)->toBe(1.0)
        ->and((float) $order->discount_percent)->toBe(5.0)
        ->and((float) $order->total)->toBe(19.0);
});

it('no mezcla porcentaje con monto ni pasa del tope', function () {
    $employee = loginEmployee();
    posSetup($employee);
    salesSettings($employee, false, true);

    posSale(['discount_percent' => 10, 'discount_value' => 1, 'total' => 19])->assertStatus(422);
    posSale(['discount_value' => 11, 'total' => 9])->assertStatus(422);   // más del 50% de S/ 20

    expect(Order::count())->toBe(0);
});

it('redondea hacia abajo a S/ 0.10 solo en efectivo y si la sede lo activó', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // 20 − 0.55 = 19.45 (el precio ya incluye el IGV)
    $sale = ['discount_value' => 0.55, 'total' => 19.45];

    // Sede sin redondeo
    salesSettings($employee, false, true);
    posSale($sale)->assertOk();
    expect((float) Order::latest('id')->first()->rounding_amount)->toBe(0.0);

    // Redondeo activo: en efectivo se perdonan S/ 0.05
    salesSettings($employee, true, true);
    posSale($sale)->assertOk();
    $cash = Order::latest('id')->first();
    expect((float) $cash->rounding_amount)->toBe(0.05)->and((float) $cash->total)->toBe(19.45);

    // Con tarjeta no se redondea
    posSale($sale + ['payment_type' => 2, 'operation_number' => '123'])->assertOk();
    expect((float) Order::latest('id')->first()->rounding_amount)->toBe(0.0);

    // El efectivo de la caja cuenta lo cobrado en efectivo: 19.45 (sin redondeo) + 19.40 (redondeada); la tarjeta no suma
    expect(App\Models\CashRegister::first()->totalCash())->toBe(38.85);
});

it('redondea hacia arriba si la sede eligió a favor del negocio', function () {
    $employee = loginEmployee();
    posSetup($employee);
    $employee->branch->setSettingGroup('sales', ['cash_rounding' => true, 'rounding_favor' => 'business', 'amount_discount' => true]);

    // 19.45 en efectivo → se cobra 19.50: se guarda −0.05 (cobrado de más)
    $sale = ['discount_value' => 0.55, 'total' => 19.45];

    posSale($sale + ['amount_received' => 19.45])->assertStatus(422);   // no alcanza para los 19.50
    posSale($sale + ['amount_received' => 25])->assertOk();

    $order = Order::latest('id')->first();
    expect((float) $order->rounding_amount)->toBe(-0.05)
        ->and((float) $order->total)->toBe(19.45)                        // el comprobante conserva su total
        ->and(App\Models\CashRegister::first()->totalCash())->toBe(19.5);

    $this->get(route('employee.orders.print', [$order, 'ticket_80mm']))->assertOk()
        ->assertSee('+ S/ 0.05', false)->assertSee('19.50')->assertSee('5.50');   // vuelto de 25

    // Con tarjeta no se redondea
    posSale($sale + ['payment_type' => 2, 'operation_number' => '123'])->assertOk();
    expect((float) Order::latest('id')->first()->rounding_amount)->toBe(0.0);
});

it('la configuración guarda a favor de quién se redondea (cliente por defecto)', function () {
    $employee = loginEmployee();
    $base = ['default_template' => 'ticket_80mm', 'copies' => 1, 'cash_rounding' => 1];

    $this->get(route('employee.settings.index'))->assertOk()->assertSee('Redondear a favor de');

    $this->post(route('employee.settings.update'), $base + ['rounding_favor' => 'business'])->assertSessionHasNoErrors();
    expect($employee->branch->fresh()->getSettingGroup('sales')['rounding_favor'])->toBe('business');

    $this->post(route('employee.settings.update'), $base)->assertSessionHasNoErrors();
    expect($employee->branch->fresh()->getSettingGroup('sales')['rounding_favor'])->toBe('customer');

    $this->post(route('employee.settings.update'), $base + ['rounding_favor' => 'otro'])->assertSessionHasErrors('rounding_favor');
});

it('el comprobante impreso muestra el redondeo y el monto a cobrar', function () {
    $employee = loginEmployee();
    posSetup($employee);
    salesSettings($employee, true, true);
    posSale(['discount_value' => 0.55, 'total' => 19.45])->assertOk();

    foreach (['ticket_80mm', 'ticket_58mm', 'boleta_a4', 'nota_venta'] as $template) {
        $this->get(route('employee.orders.print', [Order::first(), $template]))->assertOk()->assertSee('Redondeo')->assertSee('19.40');
    }
});

it('guarda con cuánto pagó el cliente en efectivo, rechaza si no alcanza y lo imprime con el vuelto', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // Efectivo insuficiente: no se registra la venta
    posSale(['amount_received' => 10])->assertStatus(422);
    expect(Order::count())->toBe(0);

    // Efectivo suficiente: queda guardado y el comprobante muestra "Paga con" y el vuelto
    posSale(['amount_received' => 30])->assertOk();
    $order = Order::first();
    expect((float) $order->amount_received)->toBe(30.0);

    foreach (['ticket_80mm', 'ticket_58mm', 'boleta_a4', 'nota_venta'] as $template) {
        $this->get(route('employee.orders.print', [$order, $template]))
            ->assertOk()->assertSee('Paga con', false)->assertSee('VUELTO', false)
            ->assertSee(number_format(30 - (float) $order->total, 2), false);
    }

    // Con tarjeta el monto recibido se ignora
    posSale(['payment_type' => 2, 'operation_number' => '123', 'amount_received' => 30])->assertOk();
    expect(Order::latest('id')->first()->amount_received)->toBeNull();
});

it('el total de cada línea impresa es cantidad × precio (con IGV), no la base (que va solo en el pie)', function () {
    posSetup(loginEmployee());
    posSale(['total' => 20])->assertOk();   // 2 × S/ 10 gravado: base 16.95 + IGV 3.05

    foreach (['ticket_80mm', 'ticket_58mm', 'boleta_a4', 'nota_venta'] as $template) {
        $this->get(route('employee.orders.print', [Order::first(), $template]))
            ->assertOk()->assertSee('20.00')->assertSee('16.95')->assertSee('3.05');   // línea y total 20.00; base e IGV en el pie
    }
});

it('la factura exige un cliente con RUC: con DNI se rechaza antes de emitir (SUNAT da el error 2800)', function () {
    posSetup(loginEmployee());

    posSale(['voucher_type' => 2, 'document_type_id' => 1, 'customer_document' => '47539215', 'customer_name' => 'JUAN PEREZ', 'total' => 20])
        ->assertStatus(422)->assertJsonFragment(['message' => 'La factura requiere un cliente con RUC. Para un cliente con DNI emite una boleta.']);

    expect(Order::count())->toBe(0)->and(stockOf('P1'))->toBe(10.0);
});

it('el detalle del historial incluye la respuesta de SUNAT, con el motivo si la rechazó', function () {
    posSetup(loginEmployee());
    posSale(['total' => 20])->assertOk();
    $order = Order::firstOrFail();

    // Nota de venta: no va a SUNAT, no hay bloque
    $this->getJson(route('employee.orders.detalle', $order))->assertOk()->assertJsonPath('sunat', null);

    // Factura rechazada: se ve el código y el mensaje de SUNAT
    $order->update(['voucher_type' => 2, 'sunat_status' => Order::SUNAT_REJECTED, 'sunat_code' => '2800', 'sunat_message' => 'El dato ingresado en el tipo de documento de identidad del receptor no esta permitido.']);
    $this->getJson(route('employee.orders.detalle', $order))->assertOk()
        ->assertJsonPath('sunat.status', 'rejected')->assertJsonPath('sunat.label', 'Rechazado')
        ->assertJsonPath('sunat.code', '2800')->assertJsonPath('sunat.message', 'El dato ingresado en el tipo de documento de identidad del receptor no esta permitido.');
});
