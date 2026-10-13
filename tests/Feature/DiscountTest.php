<?php

use App\Models\DocumentSeries;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Sunat\TaxCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Cálculo ───────────────────────────────────────────────────

it('aplica el descuento a la base de cada línea antes del IGV', function () {
    $tax = (new TaxCalculator())->calculate(
        [
            ['code' => 'A', 'price' => 10, 'qty' => 2],  // gravado: 20 − 10% = 18 → IGV 3.24
            ['code' => 'B', 'price' => 5,  'qty' => 1],  // exonerado: 5 − 10% = 4.50
        ],
        ['A' => '10', 'B' => '20'],
        10
    );

    expect($tax['lines'][0]['discount'])->toBe(2.0)
        ->and($tax['lines'][0]['base'])->toBe(18.0)
        ->and($tax['taxable'])->toBe(18.0)
        ->and($tax['exonerated'])->toBe(4.5)
        ->and($tax['discount'])->toBe(2.5)
        ->and($tax['igv'])->toBe(3.24)
        ->and($tax['total'])->toBe(25.74);
});

it('sin descuento el cálculo no cambia y rechaza porcentajes fuera de rango', function () {
    $tax = (new TaxCalculator())->calculate([['code' => 'A', 'price' => 10, 'qty' => 1]], ['A' => '10']);

    expect($tax['discount'])->toBe(0.0)->and($tax['total'])->toBe(11.8);
    expect(fn () => (new TaxCalculator())->calculate([['code' => 'A', 'price' => 1, 'qty' => 1]], ['A' => '10'], 101))
        ->toThrow(InvalidArgumentException::class);
});

// ── Venta en el POS ───────────────────────────────────────────

it('registra la venta con descuento y guarda los montos en la orden y sus líneas', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // 2 × 10 = 20 − 10% = 18 + IGV 3.24 = 21.24
    posSale(['discount_percent' => 10, 'total' => 21.24])->assertOk()->assertJson(['success' => true]);

    $order = Order::firstOrFail();
    expect((float) $order->total)->toBe(21.24)
        ->and((float) $order->discount_amount)->toBe(2.0)
        ->and((float) $order->discount_percent)->toBe(10.0)
        ->and((float) $order->subtotal)->toBe(18.0)
        ->and((float) OrderItem::first()->discount_amount)->toBe(2.0)
        ->and(stockOf('P1'))->toBe(8.0);
});

it('rechaza si el total del navegador no coincide con el descuento aplicado', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // El navegador dice 23.60 (sin descuento) pero pidió 10%: el servidor calcula 21.24
    posSale(['discount_percent' => 10, 'total' => 23.60])->assertStatus(422);

    expect(Order::count())->toBe(0)->and(stockOf('P1'))->toBe(10.0);
});

it('un empleado sin el privilegio no puede dar descuentos', function () {
    $employee = loginEmployee([Employee::PRIV_VER_VENTAS]);
    posSetup($employee);

    posSale(['discount_percent' => 10, 'total' => 21.24])->assertStatus(422)->assertJsonFragment(['message' => 'No tienes permiso para aplicar descuentos.']);

    expect(Order::count())->toBe(0);
});

it('no permite descuentos mayores al tope', function () {
    $employee = loginEmployee();
    posSetup($employee);

    posSale(['discount_percent' => 60, 'total' => 9.44])->assertStatus(422);

    expect(Order::count())->toBe(0);
});

it('el descuento aparece en el comprobante impreso', function () {
    $employee = loginEmployee();
    posSetup($employee);
    posSale(['discount_percent' => 10, 'total' => 21.24])->assertOk();

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
            ['code' => 'A', 'price' => 3.33, 'qty' => 1],
            ['code' => 'B', 'price' => 3.33, 'qty' => 1],
            ['code' => 'C', 'price' => 3.34, 'qty' => 1],
        ],
        ['A' => '10', 'B' => '10', 'C' => '20'],
        0,
        1.0
    );

    expect($tax['discount'])->toBe(1.0)
        ->and(round(array_sum(array_column($tax['lines'], 'discount')), 2))->toBe(1.0)
        ->and($tax['subtotal'])->toBe(9.0);
});

it('el descuento por monto exige activarlo en la configuración de la sede', function () {
    $employee = loginEmployee();
    posSetup($employee);

    posSale(['discount_value' => 1, 'total' => 22.42])->assertStatus(422)
        ->assertJsonFragment(['message' => 'El descuento por monto no está activado en la configuración de la sede.']);

    salesSettings($employee, false, true);

    // 20 − 1.00 = 19.00 + IGV 3.42 = 22.42 (5% equivalente)
    posSale(['discount_value' => 1, 'total' => 22.42])->assertOk();

    $order = Order::firstOrFail();
    expect((float) $order->discount_amount)->toBe(1.0)
        ->and((float) $order->discount_percent)->toBe(5.0)
        ->and((float) $order->total)->toBe(22.42);
});

it('no mezcla porcentaje con monto ni pasa del tope', function () {
    $employee = loginEmployee();
    posSetup($employee);
    salesSettings($employee, false, true);

    posSale(['discount_percent' => 10, 'discount_value' => 1, 'total' => 22.42])->assertStatus(422);
    posSale(['discount_value' => 11, 'total' => 10.62])->assertStatus(422);   // más del 50% de S/ 20

    expect(Order::count())->toBe(0);
});

it('redondea hacia abajo a S/ 0.10 solo en efectivo y si la sede lo activó', function () {
    $employee = loginEmployee();
    posSetup($employee);

    // 20 − 0.55 = 19.45 + IGV 3.50 = 22.95
    $sale = ['discount_value' => 0.55, 'total' => 22.95];

    // Sede sin redondeo
    salesSettings($employee, false, true);
    posSale($sale)->assertOk();
    expect((float) Order::latest('id')->first()->rounding_amount)->toBe(0.0);

    // Redondeo activo: en efectivo se perdonan S/ 0.05
    salesSettings($employee, true, true);
    posSale($sale)->assertOk();
    $cash = Order::latest('id')->first();
    expect((float) $cash->rounding_amount)->toBe(0.05)->and((float) $cash->total)->toBe(22.95);

    // Con tarjeta no se redondea
    posSale($sale + ['payment_type' => 2, 'operation_number' => '123'])->assertOk();
    expect((float) Order::latest('id')->first()->rounding_amount)->toBe(0.0);

    // El efectivo de la caja cuenta lo cobrado (22.95 + 22.90 de la venta redondeada no, solo efectivo: 22.95 + 22.90)
    expect(App\Models\CashRegister::first()->totalCash())->toBe(45.85);
});

it('el comprobante impreso muestra el redondeo y el monto a cobrar', function () {
    $employee = loginEmployee();
    posSetup($employee);
    salesSettings($employee, true, true);
    posSale(['discount_value' => 0.55, 'total' => 22.95])->assertOk();

    foreach (['ticket_80mm', 'ticket_58mm', 'boleta_a4', 'nota_venta'] as $template) {
        $this->get(route('employee.orders.print', [Order::first(), $template]))->assertOk()->assertSee('Redondeo')->assertSee('22.90');
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
