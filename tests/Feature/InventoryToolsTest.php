<?php

use App\Models\Branch;
use App\Models\Employee;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Ajuste de inventario ──────────────────────────────────────

it('fija el stock contado y deja el ajuste en el kardex', function () {
    loginEmployee();
    stockProduct('P1', 50, 2);

    $this->post(route('employee.adjustments.store'), [
        'product_code' => 'P1', 'mode' => 'set', 'quantity' => 42, 'reason' => 'conteo',
    ])->assertSessionHasNoErrors();

    $movement = StockMovement::firstOrFail();
    expect(stockOf('P1'))->toBe(42.0)
        ->and($movement->type)->toBe('ajuste')
        ->and($movement->quantity)->toBe(-8.0)
        ->and($movement->balance)->toBe(42.0)
        ->and($movement->reference_type)->toBe('manual');
});

it('suma y resta unidades, y no deja el stock en negativo', function () {
    loginEmployee();
    stockProduct('P1', 10);

    $this->post(route('employee.adjustments.store'), ['product_code' => 'P1', 'mode' => 'add', 'quantity' => 5, 'reason' => 'donacion']);
    expect(stockOf('P1'))->toBe(15.0);

    $this->post(route('employee.adjustments.store'), ['product_code' => 'P1', 'mode' => 'subtract', 'quantity' => 100, 'reason' => 'merma'])
        ->assertSessionHas('error');
    expect(stockOf('P1'))->toBe(15.0);
});

it('rechaza un ajuste que no cambia nada y exige nota en "otro"', function () {
    loginEmployee();
    stockProduct('P1', 10);

    $this->post(route('employee.adjustments.store'), ['product_code' => 'P1', 'mode' => 'set', 'quantity' => 10, 'reason' => 'conteo'])
        ->assertSessionHas('error');
    $this->post(route('employee.adjustments.store'), ['product_code' => 'P1', 'mode' => 'add', 'quantity' => 1, 'reason' => 'otro'])
        ->assertSessionHasErrors('notes');
});

// ── Reposición ────────────────────────────────────────────────

it('lista solo los productos en o bajo el mínimo con la cantidad sugerida', function () {
    loginEmployee();
    stockProduct('BAJO', 3, 1, 10);
    stockProduct('OK', 50, 1, 10);
    stockProduct('SINMIN', 0, 1, null);

    $this->get(route('employee.replenishment.index'))
        ->assertOk()
        ->assertSee('Producto BAJO')
        ->assertSee('+17')            // sugerido: 2 × mínimo (20) − stock (3)
        ->assertDontSee('Producto OK')
        ->assertDontSee('Producto SINMIN');
});

// ── Traspasos ─────────────────────────────────────────────────

function secondBranch(): Branch
{
    return Branch::create(['company_id' => 1, 'name' => 'Sede Norte', 'status' => 1]);
}

it('traspasa mercadería entre sedes y la anula devolviendo el stock', function () {
    loginEmployee();
    $norte = secondBranch();
    stockProduct('P1', 30, 1.5);

    $this->post(route('employee.transfers.store'), [
        'to_branch_id' => $norte->id, 'items' => [['product_code' => 'P1', 'quantity' => 12]],
    ])->assertSessionHasNoErrors();

    $transfer = StockTransfer::firstOrFail();
    expect(stockOf('P1', 1))->toBe(18.0)
        ->and(stockOf('P1', $norte->id))->toBe(12.0)
        ->and(StockMovement::where('reference_type', 'transfer')->count())->toBe(2);

    $this->post(route('employee.transfers.void', $transfer), ['void_reason' => 'Error de envío'])->assertSessionHasNoErrors();

    expect(stockOf('P1', 1))->toBe(30.0)
        ->and(stockOf('P1', $norte->id))->toBe(0.0)
        ->and($transfer->fresh()->status)->toBe(0);
});

it('no traspasa más de lo que hay ni a la misma sede', function () {
    loginEmployee();
    $norte = secondBranch();
    stockProduct('P1', 5);

    $this->post(route('employee.transfers.store'), ['to_branch_id' => $norte->id, 'items' => [['product_code' => 'P1', 'quantity' => 6]]])
        ->assertSessionHas('error');
    $this->post(route('employee.transfers.store'), ['to_branch_id' => 1, 'items' => [['product_code' => 'P1', 'quantity' => 1]]])
        ->assertSessionHasErrors('to_branch_id');

    expect(StockTransfer::count())->toBe(0)->and(stockOf('P1'))->toBe(5.0);
});

it('no anula un traspaso si el destino ya vendió la mercadería', function () {
    loginEmployee();
    $norte = secondBranch();
    stockProduct('P1', 10);

    $this->post(route('employee.transfers.store'), ['to_branch_id' => $norte->id, 'items' => [['product_code' => 'P1', 'quantity' => 10]]]);
    App\Models\BranchStock::where('branch_id', $norte->id)->update(['stock_actual' => 4]); // el destino vendió 6

    $this->post(route('employee.transfers.void', StockTransfer::first()), ['void_reason' => 'x'])->assertSessionHas('error');

    expect(StockTransfer::first()->status)->toBe(1)->and(stockOf('P1', 1))->toBe(0.0);
});

// ── Anular compra ─────────────────────────────────────────────

it('anula una compra descontando lo que ingresó', function () {
    loginEmployee();
    stockProduct('P1', 0);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 20, 'unit_cost' => 1]],
    ])->assertSessionHasNoErrors();
    expect(stockOf('P1'))->toBe(20.0);

    $purchase = Purchase::firstOrFail();
    $this->post(route('employee.purchases.void', $purchase), ['void_reason' => 'Se duplicó'])->assertSessionHasNoErrors();

    expect(stockOf('P1'))->toBe(0.0)->and($purchase->fresh()->status)->toBe(0);
});

it('no anula una compra cuya mercadería ya se vendió', function () {
    loginEmployee();
    stockProduct('P1', 0);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 20, 'unit_cost' => 1]],
    ]);
    App\Models\BranchStock::where('product_code', 'P1')->update(['stock_actual' => 5]); // se vendieron 15

    $this->post(route('employee.purchases.void', Purchase::first()), ['void_reason' => 'x'])->assertSessionHas('error');

    expect(stockOf('P1'))->toBe(5.0)->and(Purchase::first()->status)->toBe(1);
});

it('exige el privilegio de eliminar guía de ingreso para anular compras', function () {
    loginEmployee([Employee::PRIV_VER_COMPRAS]);
    stockProduct('P1', 0);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 5, 'unit_cost' => 1]],
    ]);

    $this->post(route('employee.purchases.void', Purchase::first()), ['void_reason' => 'x'])->assertRedirect(route('employee.home'));
    expect(Purchase::first()->status)->toBe(1);
});

// ── Unidades de medida ────────────────────────────────────────

it('crea, edita y elimina unidades; no elimina una que está en uso', function () {
    loginEmployee();

    $this->post(route('employee.units.store'), ['name' => 'Sachet', 'abbreviation' => 'SCH', 'sunat_code' => 'NIU', 'status' => 1])
        ->assertSessionHasNoErrors();
    $unit = Unit::where('name', 'Sachet')->firstOrFail();

    $this->put(route('employee.units.update', $unit), ['name' => 'Sachet 5g', 'abbreviation' => 'SCH', 'sunat_code' => 'NIU', 'status' => 0])
        ->assertSessionHasNoErrors();
    expect($unit->fresh()->name)->toBe('Sachet 5g')->and($unit->fresh()->status)->toBe(0);

    $this->post(route('employee.units.store'), ['name' => 'Sachet 5g', 'abbreviation' => 'X', 'sunat_code' => 'NIU', 'status' => 1])
        ->assertSessionHasErrors('name');

    $product = stockProduct('P1', 1);
    $product->update(['unit_id' => $unit->id]);
    $this->delete(route('employee.units.destroy', $unit))->assertSessionHas('error');
    expect(Unit::find($unit->id))->not->toBeNull();

    $product->update(['unit_id' => null]);
    $this->delete(route('employee.units.destroy', $unit))->assertSessionHas('success');
    expect(Unit::find($unit->id))->toBeNull();
});

// ── Preparaciones: anular y etiqueta ──────────────────────────

it('anula una preparación devolviendo insumos y retirando el producto final', function () {
    loginEmployee();
    stockProduct('UREA', 100, 0.5);
    stockProduct('CREMA', 0, 0);

    $this->post(route('employee.formulas.store'), [
        'name' => 'Crema', 'yield_quantity' => 10, 'status' => 1, 'sell_in_pos' => 1, 'product_code' => 'CREMA',
        'ingredients' => [['product_code' => 'UREA', 'quantity' => 4]],
    ])->assertSessionHasNoErrors();
    $formula = App\Models\Formula::firstOrFail();

    $this->post(route('employee.productions.store'), ['formula_id' => $formula->id, 'quantity_produced' => 10, 'produced_at' => today()->toDateString()])
        ->assertSessionHasNoErrors();
    $production = App\Models\Production::firstOrFail();
    expect(stockOf('UREA'))->toBe(96.0)->and(stockOf('CREMA'))->toBe(10.0);

    $this->get(route('employee.productions.label', $production))->assertOk()->assertSee($production->batch);

    // Si ya se vendió parte del preparado, no se puede anular
    App\Models\BranchStock::where('product_code', 'CREMA')->update(['stock_actual' => 3]);
    $this->post(route('employee.productions.void', $production), ['void_reason' => 'x'])->assertSessionHas('error');
    expect(stockOf('UREA'))->toBe(96.0)->and($production->fresh()->status)->toBe(1);

    App\Models\BranchStock::where('product_code', 'CREMA')->update(['stock_actual' => 10]);
    $this->post(route('employee.productions.void', $production), ['void_reason' => 'Error de pesada'])->assertSessionHasNoErrors();
    expect(stockOf('UREA'))->toBe(100.0)->and(stockOf('CREMA'))->toBe(0.0)->and($production->fresh()->status)->toBe(0);
});
