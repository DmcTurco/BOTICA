<?php

use App\Models\BranchStock;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Presentation;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Venta por presentaciones: el stock se lleva en unidades base (tabletas) y cada presentación
 * trae un factor (blíster x10). P1 (de posSetup) vale S/ 10 la unidad, gravado con IGV.
 */
function blisterOfP1(float $stock = 20): Presentation
{
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => $stock]);
    $unit = Unit::create(['company_id' => 1, 'name' => 'Blíster', 'abbreviation' => 'BL', 'sunat_code' => 'BX', 'status' => 1]);

    return Presentation::create(['product_code' => 'P1', 'unit_id' => $unit->id, 'equivalent_amount' => 10, 'sale_price' => 90, 'status' => 1]);
}

it('vende unidad y blíster del mismo producto en líneas separadas y descuenta las unidades reales', function () {
    posSetup(loginEmployee());
    $blister = blisterOfP1(20);

    // 3 unidades × S/ 10 + 1 blíster × S/ 90 = S/ 120 + IGV 18% = S/ 141.60
    posSale(['items' => [
        ['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 3],
        ['code' => 'P1', 'name' => 'x', 'price' => 90, 'qty' => 1, 'presentation_id' => $blister->id],
    ], 'total' => 141.60])->assertOk();

    $items = OrderItem::orderBy('id')->get();
    expect($items)->toHaveCount(2)
        ->and((float) $items[0]->quantity)->toBe(3.0)
        ->and((float) $items[0]->units_per_item)->toBe(1.0)
        ->and($items[0]->presentation_id)->toBeNull()
        ->and((float) $items[1]->quantity)->toBe(1.0)
        ->and((float) $items[1]->units_per_item)->toBe(10.0)
        ->and((float) $items[1]->unit_price)->toBe(90.0)
        ->and($items[1]->presentation_id)->toBe($blister->id)
        ->and($items[1]->unit_code)->toBe('BX')                 // unidad SUNAT de la presentación
        ->and($items[1]->product_name)->toContain('Blíster x10')
        ->and(stockOf('P1'))->toBe(7.0)                         // 20 − (3 + 10)
        ->and((int) StockMovement::where('reference_type', 'order')->sum('quantity'))->toBe(13);
});

it('suma todas las presentaciones del producto al validar el stock', function () {
    posSetup(loginEmployee());
    $blister = blisterOfP1(12);

    // 3 unidades + 1 blíster = 13 tabletas y solo hay 12
    posSale(['items' => [
        ['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 3],
        ['code' => 'P1', 'name' => 'x', 'price' => 90, 'qty' => 1, 'presentation_id' => $blister->id],
    ], 'total' => 141.60])->assertStatus(422)->assertJsonFragment(['success' => false]);

    expect(stockOf('P1'))->toBe(12.0)->and(Order::count())->toBe(0);
});

it('el precio de la presentación lo pone el servidor, no el navegador', function () {
    posSetup(loginEmployee());
    $blister = blisterOfP1(20);

    // Se envía el blíster con precio de unidad: el servidor usa S/ 90 y el total no cuadra
    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 1, 'presentation_id' => $blister->id]], 'total' => 11.80])
        ->assertStatus(422);

    // Presentación de otro producto
    stockProduct('P2', 5);
    $ajena = Presentation::create(['product_code' => 'P2', 'unit_id' => $blister->unit_id, 'equivalent_amount' => 5, 'sale_price' => 40, 'status' => 1]);
    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 40, 'qty' => 1, 'presentation_id' => $ajena->id]], 'total' => 47.20])
        ->assertStatus(422);

    expect(stockOf('P1'))->toBe(20.0);
});

it('un POS abierto antes del cambio (sin presentation_id) igual descuenta el blíster completo', function () {
    posSetup(loginEmployee());
    blisterOfP1(20);

    // Solo precio de blíster, sin presentation_id: se identifica por su precio único
    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 90, 'qty' => 1]], 'total' => 106.20])->assertOk();

    expect(stockOf('P1'))->toBe(10.0)
        ->and((float) OrderItem::first()->units_per_item)->toBe(10.0);
});

it('al editar una venta devuelve las unidades reales del blíster y descuenta las nuevas', function () {
    posSetup(loginEmployee());
    $blister = blisterOfP1(20);

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 90, 'qty' => 1, 'presentation_id' => $blister->id]], 'total' => 106.20])->assertOk();
    expect(stockOf('P1'))->toBe(10.0);

    // Caja pendiente de aprobación: la venta se puede editar
    CashRegister::query()->update(['approval_status' => CashRegister::APPROVAL_PENDING]);

    $this->putJson(route('employee.orders.update-historical', Order::firstOrFail()), [
        'items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 2]], 'payment_type' => 1, 'total' => 23.60,
    ])->assertOk();

    // Se devuelven 10 (el blíster) y salen 2 unidades
    expect(stockOf('P1'))->toBe(18.0)
        ->and(OrderItem::count())->toBe(1)
        ->and((float) OrderItem::first()->units_per_item)->toBe(1.0);
});

it('rechazar la caja devuelve las unidades reales del blíster', function () {
    $admin = loginEmployee();
    posSetup($admin);
    $blister = blisterOfP1(20);

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 90, 'qty' => 2, 'presentation_id' => $blister->id]], 'total' => 212.40])->assertOk();
    expect(stockOf('P1'))->toBe(0.0);

    $register = CashRegister::firstOrFail();
    $register->update(['approval_status' => CashRegister::APPROVAL_PENDING]);

    $this->post(route('employee.approvals.reject', $register), ['rejection_reason' => 'Prueba'])->assertRedirect();

    expect(stockOf('P1'))->toBe(20.0);
});

it('los reportes cuentan unidades base para que la ganancia use el costo correcto', function () {
    posSetup(loginEmployee());
    $blister = blisterOfP1(20);

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 90, 'qty' => 1, 'presentation_id' => $blister->id]], 'total' => 106.20])->assertOk();

    // Costo: 10 tabletas × S/ 4 (precio de compra de P1) = S/ 40; venta neta S/ 90
    $row = $this->get(route('employee.reports.profit'))->assertOk()->viewData('rows')->firstWhere('code', 'P1');

    expect((float) $row->qty)->toBe(10.0)
        ->and((float) $row->cost)->toBe(40.0)
        ->and((float) $row->profit)->toBe(50.0);
});
