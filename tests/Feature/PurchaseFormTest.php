<?php

use App\Models\BranchStock;
use App\Models\Presentation;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Compra mínima válida; $override pisa cualquier campo */
function purchasePayload(array $override = []): array
{
    return array_merge([
        'document_type' => 1, 'document_number' => 'B001-' . random_int(100000, 99999999), 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => 2]],
    ], $override);
}

it('convierte la compra por presentación a unidades base', function () {
    loginEmployee();
    stockProduct('P1', 0, 0.15);
    $blister = Unit::create(['name' => 'Blíster', 'abbreviation' => 'BL', 'status' => 1]);
    $presentation = Presentation::create(['product_code' => 'P1', 'unit_id' => $blister->id, 'equivalent_amount' => 10, 'sale_price' => 3.5, 'status' => 1]);

    // 5 blísteres de 10 unidades a S/ 1.50 cada uno
    $this->post(route('employee.purchases.store'), purchasePayload([
        'items' => [['product_code' => 'P1', 'presentation_id' => $presentation->id, 'quantity' => 5, 'unit_cost' => 1.5]],
    ]))->assertSessionHasNoErrors();

    $detail = PurchaseDetail::firstOrFail();
    expect((float) BranchStock::where('product_code', 'P1')->first()->stock_actual)->toBe(50.0)
        ->and($detail->quantity)->toBe(50)
        ->and((float) $detail->unit_cost)->toBe(0.15)
        ->and((float) $detail->subtotal)->toBe(7.5)
        ->and((float) Purchase::first()->total)->toBe(7.5);
});

it('rechaza una presentación que no es del producto', function () {
    loginEmployee();
    stockProduct('P1');
    stockProduct('P2');
    $box = Unit::create(['name' => 'Caja', 'abbreviation' => 'CJ', 'status' => 1]);
    $other = Presentation::create(['product_code' => 'P2', 'unit_id' => $box->id, 'equivalent_amount' => 10, 'sale_price' => 3.5, 'status' => 1]);

    $this->post(route('employee.purchases.store'), purchasePayload([
        'items' => [['product_code' => 'P1', 'presentation_id' => $other->id, 'quantity' => 1, 'unit_cost' => 2]],
    ]))->assertSessionHasErrors('items.0.presentation_id');
});

it('registra como proveedor nuevo el nombre escrito y lo reutiliza', function () {
    loginEmployee();
    stockProduct('P1');

    $this->post(route('employee.purchases.store'), purchasePayload(['supplier' => 'Droguería Nueva']))->assertSessionHasNoErrors();
    $this->post(route('employee.purchases.store'), purchasePayload(['supplier' => 'droguería nueva']))->assertSessionHasNoErrors();

    expect(Supplier::count())->toBe(1)
        ->and(Purchase::pluck('supplier_id')->unique()->count())->toBe(1)
        ->and(Purchase::first()->supplier_id)->toBe(Supplier::first()->id);
});

it('valida documento, fecha, costo, vencimiento y crédito', function () {
    loginEmployee();
    stockProduct('P1');

    $this->post(route('employee.purchases.store'), purchasePayload(['document_number' => null]))->assertSessionHasErrors('document_number');
    $this->post(route('employee.purchases.store'), purchasePayload(['document_number' => null, 'document_type' => 3]))->assertSessionHasNoErrors();
    $this->post(route('employee.purchases.store'), purchasePayload(['purchased_at' => today()->addDay()->toDateString()]))->assertSessionHasErrors('purchased_at');
    $this->post(route('employee.purchases.store'), purchasePayload(['items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => 0]]]))->assertSessionHasErrors('items.0.unit_cost');
    $this->post(route('employee.purchases.store'), purchasePayload(['items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => 2, 'expiration_date' => today()->subDay()->toDateString()]]]))->assertSessionHasErrors('items.0.expiration_date');
    $this->post(route('employee.purchases.store'), purchasePayload(['payment_condition' => 'credit', 'due_date' => today()->addDays(30)->toDateString()]))->assertSessionHasErrors('supplier');
});

it('no deja registrar dos veces el mismo comprobante del mismo proveedor', function () {
    loginEmployee();
    stockProduct('P1');

    $payload = purchasePayload(['document_number' => 'F001-77', 'supplier' => 'Proveedor Uno']);
    $this->post(route('employee.purchases.store'), $payload)->assertSessionHasNoErrors();
    $this->post(route('employee.purchases.store'), $payload)->assertSessionHasErrors('document_number');
});
