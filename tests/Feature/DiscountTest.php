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
