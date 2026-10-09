<?php

use App\Models\Employee;
use App\Models\Purchase;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeSupplier(array $data = []): Supplier
{
    static $n = 0;
    $n++;

    return Supplier::create(array_merge(['company_id' => 1, 'name' => "Droguería {$n}", 'status' => 1], $data));
}

/** Compra a crédito de S/ 100 (10 × 10) */
function creditPurchase(Supplier $supplier, string $due = null): Purchase
{
    test()->post(route('employee.purchases.store'), [
        'document_type' => 2, 'document_number' => 'F001-1', 'purchased_at' => today()->toDateString(),
        'supplier_id' => $supplier->id, 'payment_condition' => 'credit', 'due_date' => $due ?? today()->addDays(30)->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 10, 'unit_cost' => 10]],
    ])->assertSessionHasNoErrors();

    return Purchase::latest('id')->firstOrFail();
}

// ── Proveedores ───────────────────────────────────────────────

it('crea, edita y elimina proveedores; el RUC no se repite', function () {
    loginEmployee();

    $this->post(route('employee.suppliers.store'), ['name' => 'Droguería Sur', 'ruc' => '20123456789', 'status' => 1])->assertSessionHasNoErrors();
    $supplier = Supplier::firstOrFail();

    $this->post(route('employee.suppliers.store'), ['name' => 'Otra', 'ruc' => '20123456789', 'status' => 1])->assertSessionHasErrors('ruc');
    $this->post(route('employee.suppliers.store'), ['name' => 'Corto', 'ruc' => '123', 'status' => 1])->assertSessionHasErrors('ruc');

    $this->put(route('employee.suppliers.update', $supplier), ['name' => 'Droguería Sur SAC', 'ruc' => '20123456789', 'status' => 0])->assertSessionHasNoErrors();
    expect($supplier->fresh()->name)->toBe('Droguería Sur SAC')->and($supplier->fresh()->status)->toBe(0);

    $this->get(route('employee.suppliers.index'))->assertOk()->assertSee('Droguería Sur SAC');
    $this->delete(route('employee.suppliers.destroy', $supplier))->assertSessionHas('success');
    expect(Supplier::count())->toBe(0);
});

it('no elimina un proveedor con deuda pendiente', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $supplier = makeSupplier();
    creditPurchase($supplier);

    $this->delete(route('employee.suppliers.destroy', $supplier))->assertSessionHas('error');

    expect(Supplier::count())->toBe(1);
});

// ── Compras a crédito y pagos ─────────────────────────────────

it('una compra a crédito queda con saldo y una al contado queda pagada', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $supplier = makeSupplier();

    $credit = creditPurchase($supplier);
    expect($credit->payment_condition)->toBe('credit')->and($credit->balance)->toBe(100.0)->and($credit->supplier)->toBe($supplier->name);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => 5]],
    ])->assertSessionHasNoErrors();
    $cash = Purchase::latest('id')->first();
    expect($cash->payment_condition)->toBe('cash')->and($cash->balance)->toBe(0.0)->and((float) $cash->paid_amount)->toBe(5.0);
});

it('exige fecha de vencimiento en una compra a crédito', function () {
    loginEmployee();
    stockProduct('P1', 0);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(), 'payment_condition' => 'credit',
        'items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => 5]],
    ])->assertSessionHasErrors('due_date');

    expect(Purchase::count())->toBe(0);
});

it('registra pagos parciales hasta saldar y no deja pagar de más', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $purchase = creditPurchase(makeSupplier());

    $this->post(route('employee.payables.pay', $purchase), ['amount' => 40, 'method' => 3, 'paid_at' => today()->toDateString(), 'reference' => 'OP-1'])->assertSessionHasNoErrors();
    expect($purchase->fresh()->balance)->toBe(60.0);

    $this->post(route('employee.payables.pay', $purchase), ['amount' => 60.01, 'method' => 1, 'paid_at' => today()->toDateString()])->assertSessionHas('error');
    expect($purchase->fresh()->balance)->toBe(60.0)->and(SupplierPayment::count())->toBe(1);

    $this->post(route('employee.payables.pay', $purchase), ['amount' => 60, 'method' => 1, 'paid_at' => today()->toDateString()])->assertSessionHas('success');
    expect($purchase->fresh()->balance)->toBe(0.0);

    $this->get(route('employee.purchases.show', $purchase))->assertOk()->assertSee('Pagada');
});

it('cuentas por pagar resume la deuda y marca lo vencido', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $supplier = makeSupplier();
    creditPurchase($supplier, today()->addDays(10)->toDateString());
    $late = creditPurchase($supplier, today()->addDays(5)->toDateString());
    $late->update(['due_date' => today()->subDays(3)]);

    $view = $this->get(route('employee.payables.index'))->assertOk();

    expect($view->viewData('totalDebt'))->toBe(200.0)->and($view->viewData('overdue'))->toBe(100.0);
    expect($this->get(route('employee.payables.index', ['estado' => 'vencidas']))->viewData('purchases'))->toHaveCount(1);
});

it('anular una compra a crédito la saca de la deuda', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $purchase = creditPurchase(makeSupplier());

    $this->post(route('employee.purchases.void', $purchase), ['void_reason' => 'error'])->assertSessionHasNoErrors();

    expect($purchase->fresh()->balance)->toBe(0.0);
    expect($this->get(route('employee.payables.index'))->viewData('totalDebt'))->toBe(0.0);
});

// ── Órdenes de compra ─────────────────────────────────────────

it('emite una orden de compra y la recibe como compra', function () {
    loginEmployee();
    stockProduct('P1', 0, 3);
    $supplier = makeSupplier();

    $this->post(route('employee.purchase-orders.store'), [
        'supplier_id' => $supplier->id, 'items' => [['product_code' => 'P1', 'quantity' => 12, 'unit_cost' => 2.5]],
    ])->assertSessionHasNoErrors();

    $order = PurchaseOrder::firstOrFail();
    expect($order->number)->toBe('OC-0001')->and((float) $order->total)->toBe(30.0)->and($order->status)->toBe('pending');
    $this->get(route('employee.purchase-orders.show', $order))->assertOk()->assertSee('OC-0001');

    // Al abrir la recepción se precargan los productos de la orden
    $form = $this->get(route('employee.purchases.create', ['order' => $order->id]))->assertOk();
    expect($form->viewData('preload'))->toBe([['code' => 'P1', 'qty' => 12.0, 'cost' => 2.5]]);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(), 'supplier_id' => $supplier->id, 'purchase_order_id' => $order->id,
        'items' => [['product_code' => 'P1', 'quantity' => 12, 'unit_cost' => 2.5]],
    ])->assertSessionHasNoErrors();

    expect($order->fresh()->status)->toBe('received')->and(stockOf('P1'))->toBe(12.0);
    // Una orden ya recibida no se puede volver a usar
    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(), 'purchase_order_id' => $order->id,
        'items' => [['product_code' => 'P1', 'quantity' => 1, 'unit_cost' => 2.5]],
    ])->assertSessionHasErrors('purchase_order_id');
});

it('numera las órdenes en orden y permite anular las pendientes', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $supplier = makeSupplier();

    foreach ([1, 2] as $_) {
        $this->post(route('employee.purchase-orders.store'), ['supplier_id' => $supplier->id, 'items' => [['product_code' => 'P1', 'quantity' => 1]]])->assertSessionHasNoErrors();
    }
    expect(PurchaseOrder::orderBy('id')->pluck('number')->all())->toBe(['OC-0001', 'OC-0002']);

    $this->post(route('employee.purchase-orders.cancel', PurchaseOrder::first()))->assertSessionHas('success');
    expect(PurchaseOrder::first()->status)->toBe('cancelled');
    $this->post(route('employee.purchase-orders.cancel', PurchaseOrder::first()))->assertSessionHas('error');
});

it('reposición arma una orden de compra con las cantidades sugeridas', function () {
    loginEmployee();
    stockProduct('BAJO', 2, 1, 10);   // sugerido: 2 × 10 − 2 = 18

    $this->get(route('employee.replenishment.index'))->assertOk()->assertSee('Orden de compra con los marcados');

    $form = $this->get(route('employee.purchase-orders.create', ['products' => ['BAJO' => 18]]))->assertOk();
    expect($form->viewData('initial'))->toBe([['product_code' => 'BAJO', 'quantity' => 18.0]]);
});

it('proveedores y cuentas por pagar exigen su privilegio', function () {
    loginEmployee([Employee::PRIV_VER_COMPRAS]);

    $this->get(route('employee.purchase-orders.index'))->assertOk();
    $this->get(route('employee.suppliers.index'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.payables.index'))->assertRedirect(route('employee.home'));
});
