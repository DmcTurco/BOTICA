<?php

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Employee;
use App\Models\Formula;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Services\BatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Ventas: FEFO ──────────────────────────────────────────────

it('vende primero el lote que vence antes', function () {
    $employee = loginEmployee();
    posSetup($employee);
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => 0]);
    addBatch('P1', 5, today()->addDays(200)->toDateString(), 'TARDE');
    addBatch('P1', 5, today()->addDays(20)->toDateString(), 'PRONTO');
    addBatch('P1', 5, null, 'SINFECHA');

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 7]], 'total' => 82.6])->assertOk();

    expect(remaining('PRONTO'))->toBe(0.0)     // el que vence antes se agota primero
        ->and(remaining('TARDE'))->toBe(3.0)   // luego el siguiente en vencer
        ->and(remaining('SINFECHA'))->toBe(5.0) // lo que no vence, al final
        ->and(stockOf('P1'))->toBe(8.0);
});

it('no vende unidades de lotes vencidos', function () {
    $employee = loginEmployee();
    posSetup($employee);
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => 0]);
    addBatch('P1', 4, today()->subDays(2)->toDateString(), 'VENCIDO');
    addBatch('P1', 2, today()->addDays(30)->toDateString(), 'VIGENTE');

    // Hay 6 en stock pero solo 2 vigentes
    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 3]], 'total' => 35.4])->assertStatus(422);
    expect(stockOf('P1'))->toBe(6.0);

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 2]], 'total' => 23.6])->assertOk();
    expect(remaining('VIGENTE'))->toBe(0.0)->and(remaining('VENCIDO'))->toBe(4.0)->and(stockOf('P1'))->toBe(4.0);
});

it('un lote que vence hoy todavía se puede vender', function () {
    $employee = loginEmployee();
    posSetup($employee);
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => 0]);
    addBatch('P1', 3, today()->toDateString(), 'HOY');

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 3]], 'total' => 35.4])->assertOk();

    expect(remaining('HOY'))->toBe(0.0);
});

it('las unidades sin lote se venden después de los lotes', function () {
    $employee = loginEmployee();
    posSetup($employee);                       // 10 unidades sin lote
    addBatch('P1', 2, today()->addDays(10)->toDateString(), 'L1');

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 5]], 'total' => 59])->assertOk();

    expect(remaining('L1'))->toBe(0.0)->and(stockOf('P1'))->toBe(7.0);
});

it('anular la venta con nota de crédito devuelve las unidades a su lote', function () {
    $employee = loginEmployee();
    posSetup($employee);
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => 0]);
    addBatch('P1', 5, today()->addDays(10)->toDateString(), 'L1');
    addBatch('P1', 5, today()->addDays(90)->toDateString(), 'L2');

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 7]], 'total' => 82.6])->assertOk();
    expect(remaining('L1'))->toBe(0.0)->and(remaining('L2'))->toBe(3.0);

    $order = App\Models\Order::firstOrFail();
    DB::transaction(fn () => app(BatchService::class)->restore('order', $order->id));

    expect(remaining('L1'))->toBe(5.0)->and(remaining('L2'))->toBe(5.0);
});

// ── Compras ───────────────────────────────────────────────────

it('una compra crea su lote y no se anula si ya se vendió parte', function () {
    $employee = loginEmployee();
    posSetup($employee);
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => 0]);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 10, 'unit_cost' => 2, 'expiration_date' => today()->addDays(60)->toDateString(), 'batch' => 'C-1']],
    ])->assertSessionHasNoErrors();
    expect(remaining('C-1'))->toBe(10.0);

    posSale(['items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 4]], 'total' => 47.2])->assertOk();

    $this->post(route('employee.purchases.void', Purchase::firstOrFail()), ['void_reason' => 'x'])->assertSessionHas('error');
    expect(Purchase::first()->status)->toBe(1)->and(stockOf('P1'))->toBe(6.0);
});

it('anular una compra intacta retira su lote', function () {
    loginEmployee();
    stockProduct('P1', 0);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 10, 'unit_cost' => 2, 'expiration_date' => today()->addDays(60)->toDateString(), 'batch' => 'C-1']],
    ]);
    $this->post(route('employee.purchases.void', Purchase::firstOrFail()), ['void_reason' => 'duplicada'])->assertSessionHasNoErrors();

    expect(remaining('C-1'))->toBe(0.0)->and(stockOf('P1'))->toBe(0.0);
});

// ── Traspasos ─────────────────────────────────────────────────

it('el lote viaja con el traspaso y vuelve al anularlo', function () {
    loginEmployee();
    $norte = Branch::create(['company_id' => 1, 'name' => 'Norte', 'status' => 1]);
    stockProduct('P1', 0);
    addBatch('P1', 10, today()->addDays(45)->toDateString(), 'L1');

    $this->post(route('employee.transfers.store'), ['to_branch_id' => $norte->id, 'items' => [['product_code' => 'P1', 'quantity' => 6]]])
        ->assertSessionHasNoErrors();

    $dest = StockBatch::where('branch_id', $norte->id)->firstOrFail();
    expect($dest->batch)->toBe('L1')->and($dest->quantity_remaining)->toBe(6.0)->and($dest->expiration_date->toDateString())->toBe(today()->addDays(45)->toDateString())
        ->and(StockBatch::where('branch_id', 1)->value('quantity_remaining'))->toBe(4.0);

    $this->post(route('employee.transfers.void', StockTransfer::firstOrFail()), ['void_reason' => 'error'])->assertSessionHasNoErrors();

    expect(StockBatch::where('branch_id', 1)->value('quantity_remaining'))->toBe(10.0)
        ->and(StockBatch::where('branch_id', $norte->id)->value('quantity_remaining'))->toBe(0.0);
});

it('no traspasa unidades vencidas', function () {
    loginEmployee();
    $norte = Branch::create(['company_id' => 1, 'name' => 'Norte', 'status' => 1]);
    stockProduct('P1', 0);
    addBatch('P1', 5, today()->subDay()->toDateString(), 'VENC');

    $this->post(route('employee.transfers.store'), ['to_branch_id' => $norte->id, 'items' => [['product_code' => 'P1', 'quantity' => 2]]])
        ->assertSessionHas('error');

    expect(StockTransfer::count())->toBe(0)->and(stockOf('P1'))->toBe(5.0);
});

// ── Ajustes y bajas ───────────────────────────────────────────

it('un ajuste de resta descuenta de los lotes y uno de suma puede crear lote', function () {
    loginEmployee();
    stockProduct('P1', 0);
    addBatch('P1', 5, today()->addDays(10)->toDateString(), 'L1');

    $this->post(route('employee.adjustments.store'), ['product_code' => 'P1', 'mode' => 'subtract', 'quantity' => 2, 'reason' => 'merma'])->assertSessionHasNoErrors();
    expect(remaining('L1'))->toBe(3.0);

    $this->post(route('employee.adjustments.store'), [
        'product_code' => 'P1', 'mode' => 'add', 'quantity' => 4, 'reason' => 'conteo', 'batch' => 'NUEVO', 'expiration_date' => today()->addDays(99)->toDateString(),
    ])->assertSessionHasNoErrors();
    expect(remaining('NUEVO'))->toBe(4.0)->and(stockOf('P1'))->toBe(7.0);
});

it('da de baja un lote vencido y deja el ajuste en el kardex', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $batch = addBatch('P1', 6, today()->subDays(5)->toDateString(), 'VENC');

    $this->post(route('employee.batches.write-off', $batch))->assertSessionHasNoErrors();

    expect(stockOf('P1'))->toBe(0.0)->and(remaining('VENC'))->toBe(0.0)
        ->and(StockMovement::where('type', 'ajuste')->value('quantity'))->toBe(-6.0);
});

it('no da de baja un lote que aún no vence', function () {
    loginEmployee();
    stockProduct('P1', 0);
    $batch = addBatch('P1', 6, today()->addDays(5)->toDateString(), 'OK');

    $this->post(route('employee.batches.write-off', $batch))->assertSessionHas('error');

    expect(stockOf('P1'))->toBe(6.0);
});

it('el reporte lista vencidos y próximos a vencer con la opción de baja', function () {
    loginEmployee();
    stockProduct('P1', 0);
    addBatch('P1', 6, today()->subDays(5)->toDateString(), 'VENC');
    addBatch('P1', 3, today()->addDays(10)->toDateString(), 'PRONTO');
    addBatch('P1', 9, today()->addDays(400)->toDateString(), 'LEJOS');

    $view = $this->get(route('employee.reports.expiring', ['dias' => 30]))->assertOk()->assertSee('Dar de baja');

    expect($view->viewData('rows')->pluck('batch')->all())->toBe(['VENC', 'PRONTO'])->and($view->viewData('expired'))->toBe(1);
    expect($this->get(route('employee.reports.expiring', ['dias' => 'todos']))->viewData('rows'))->toHaveCount(3);
});

// ── Preparados ────────────────────────────────────────────────

it('el preparado entra como lote con su vencimiento y los insumos salen por FEFO', function () {
    loginEmployee();
    stockProduct('UREA', 0, 0.5);
    stockProduct('CREMA', 0, 0);
    addBatch('UREA', 3, today()->subDays(1)->toDateString(), 'UREA-VENC');   // vencido: no se usa
    addBatch('UREA', 20, today()->addDays(90)->toDateString(), 'UREA-OK');

    $this->post(route('employee.formulas.store'), [
        'name' => 'Crema', 'yield_quantity' => 10, 'status' => 1, 'shelf_life_days' => 30, 'sell_in_pos' => 1, 'product_code' => 'CREMA',
        'ingredients' => [['product_code' => 'UREA', 'quantity' => 4]],
    ])->assertSessionHasNoErrors();
    $formula = Formula::firstOrFail();

    $this->post(route('employee.productions.store'), ['formula_id' => $formula->id, 'quantity_produced' => 10, 'produced_at' => today()->toDateString()])
        ->assertSessionHasNoErrors();

    $production = Production::firstOrFail();
    expect(remaining('UREA-OK'))->toBe(16.0)->and(remaining('UREA-VENC'))->toBe(3.0);

    $final = StockBatch::where('source_type', 'production')->firstOrFail();
    expect($final->batch)->toBe($production->batch)->and($final->quantity_remaining)->toBe(10.0)
        ->and($final->expiration_date->toDateString())->toBe(today()->addDays(30)->toDateString());

    // Anular devuelve los insumos a su lote y retira el preparado
    $this->post(route('employee.productions.void', $production), ['void_reason' => 'x'])->assertSessionHasNoErrors();
    expect(remaining('UREA-OK'))->toBe(20.0)->and($final->fresh()->quantity_remaining)->toBe(0.0)->and(stockOf('CREMA'))->toBe(0.0);
});

it('no prepara con insumos vencidos aunque haya stock total', function () {
    loginEmployee();
    stockProduct('UREA', 0, 0.5);
    addBatch('UREA', 50, today()->subDays(1)->toDateString(), 'UREA-VENC');

    $this->post(route('employee.formulas.store'), [
        'name' => 'Crema', 'yield_quantity' => 10, 'status' => 1,
        'ingredients' => [['product_code' => 'UREA', 'quantity' => 4]],
    ]);

    $this->post(route('employee.productions.store'), ['formula_id' => Formula::firstOrFail()->id, 'quantity_produced' => 10, 'produced_at' => today()->toDateString()])
        ->assertSessionHas('error');

    expect(Production::count())->toBe(0)->and(stockOf('UREA'))->toBe(50.0);
});

// ── Consistencia ──────────────────────────────────────────────

it('los lotes nunca suman más que el stock total', function () {
    loginEmployee();
    stockProduct('P1', 0);
    addBatch('P1', 5, today()->addDays(10)->toDateString(), 'A');
    addBatch('P1', 5, today()->addDays(20)->toDateString(), 'B');
    BranchStock::where('product_code', 'P1')->update(['stock_actual' => 7]);   // se perdieron 3 sin pasar por los lotes

    DB::transaction(fn () => app(BatchService::class)->reconcile(1, 'P1'));

    // El exceso se recorta del lote que vence más tarde
    expect(remaining('A'))->toBe(5.0)->and(remaining('B'))->toBe(2.0);
});
