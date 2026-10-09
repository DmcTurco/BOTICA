<?php

use App\Models\BranchStock;
use App\Models\Employee;
use App\Models\Formula;
use App\Models\Product;
use App\Models\Production;
use App\Models\Role;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Crea un empleado administrador de sede (accede a todo) y lo autentica en el guard employee */
function fmLogin(): Employee
{
    $employee = Employee::create([
        'company_id' => 1, 'branch_id' => 1, 'role_id' => Role::BRANCH_ADMIN,
        'name' => 'Químico', 'email' => 'quimico@example.com', 'password' => 'secret',
    ]);
    test()->actingAs($employee, 'employee');

    return $employee;
}

/** Crea un producto de la compañía 1 con su stock en la sede 1 */
function fmProduct(string $code, float $purchasePrice, float $stock): Product
{
    $product = new Product([
        'code' => $code, 'company_id' => 1, 'name' => "Producto {$code}",
        'purchase_price' => $purchasePrice, 'unit_sale_price' => 10, 'igv_affectation' => '20',
        'employee_id' => 1, 'status' => 1,
    ]);
    $product->save();
    BranchStock::create(['branch_id' => 1, 'product_code' => $code, 'stock_actual' => $stock]);

    return $product;
}

/** Datos válidos de una fórmula: 100 g de crema con urea (2 insumos) */
function fmFormulaPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Crema de urea 10%', 'yield_quantity' => 100, 'status' => 1, 'shelf_life_days' => 30,
        'ingredients' => [
            ['product_code' => 'UREA', 'quantity' => 10],
            ['product_code' => 'BASE', 'quantity' => 90],
        ],
    ], $overrides);
}

// ── Fórmulas ──────────────────────────────────────────────────

it('registra una fórmula con sus insumos y código correlativo', function () {
    fmLogin();
    fmProduct('UREA', 0.50, 1000);
    fmProduct('BASE', 0.10, 1000);

    $this->post(route('employee.formulas.store'), fmFormulaPayload())->assertRedirect();

    $formula = Formula::first();
    expect($formula->code)->toBe('FM-0001')
        ->and($formula->sell_in_pos)->toBeFalse()
        ->and($formula->product_code)->toBeNull()
        ->and($formula->ingredients)->toHaveCount(2)
        ->and($formula->estimatedCost())->toBe(14.0); // 10×0.50 + 90×0.10
});

it('exige un producto cuando la fórmula se vende en el POS', function () {
    fmLogin();
    fmProduct('UREA', 0.50, 1000);
    fmProduct('BASE', 0.10, 1000);

    $this->post(route('employee.formulas.store'), fmFormulaPayload(['sell_in_pos' => 1]))
        ->assertSessionHasErrors('product_code');

    expect(Formula::count())->toBe(0);
});

it('ignora el producto vinculado si la venta en POS está apagada', function () {
    fmLogin();
    fmProduct('UREA', 0.50, 1000);
    fmProduct('BASE', 0.10, 1000);
    fmProduct('CREMA', 0, 0);

    $this->post(route('employee.formulas.store'), fmFormulaPayload(['product_code' => 'CREMA']))->assertRedirect();

    expect(Formula::first()->product_code)->toBeNull();
});

it('rechaza insumos repetidos y el producto final como insumo', function () {
    fmLogin();
    fmProduct('UREA', 0.50, 1000);

    $this->post(route('employee.formulas.store'), fmFormulaPayload([
        'ingredients' => [['product_code' => 'UREA', 'quantity' => 1], ['product_code' => 'UREA', 'quantity' => 2]],
    ]))->assertSessionHasErrors('ingredients.0.product_code');

    $this->post(route('employee.formulas.store'), fmFormulaPayload([
        'sell_in_pos' => 1, 'product_code' => 'UREA',
        'ingredients' => [['product_code' => 'UREA', 'quantity' => 1]],
    ]))->assertSessionHasErrors('product_code');
});

// ── Preparaciones ─────────────────────────────────────────────

/** Fórmula guardada lista para preparar, con los insumos creados */
function fmReadyFormula(array $overrides = []): Formula
{
    fmProduct('UREA', 0.50, 100);
    fmProduct('BASE', 0.10, 1000);

    test()->post(route('employee.formulas.store'), fmFormulaPayload($overrides))->assertSessionHasNoErrors();

    return Formula::firstOrFail();
}

it('descuenta los insumos, calcula costos y genera lote sin tocar el POS', function () {
    fmLogin();
    $formula = fmReadyFormula();

    // 200 g = el doble de la fórmula base → 20 de urea y 180 de base
    $this->post(route('employee.productions.store'), [
        'formula_id' => $formula->id, 'quantity_produced' => 200, 'produced_at' => today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $production = Production::firstOrFail();
    expect($production->batch)->toBe('FM-0001-' . today()->format('ymd') . '-01')
        ->and($production->multiplier)->toBe(2.0)
        ->and((float) $production->total_cost)->toBe(28.0)   // 20×0.50 + 180×0.10
        ->and((float) $production->unit_cost)->toBe(0.14)
        ->and($production->expiration_date->toDateString())->toBe(today()->addDays(30)->toDateString())
        ->and($production->product_code)->toBeNull()
        ->and($production->ingredients)->toHaveCount(2);

    expect((float) BranchStock::where('product_code', 'UREA')->value('stock_actual'))->toBe(80.0)
        ->and((float) BranchStock::where('product_code', 'BASE')->value('stock_actual'))->toBe(820.0)
        ->and(StockMovement::where('reference_type', 'production')->where('type', 'salida')->count())->toBe(2)
        ->and(StockMovement::where('type', 'entrada')->count())->toBe(0);
});

it('da ingreso al producto final cuando la fórmula se vende en el POS', function () {
    fmLogin();
    fmProduct('CREMA', 0, 0);
    $formula = fmReadyFormula(['sell_in_pos' => 1, 'product_code' => 'CREMA', 'yield_quantity' => 10]);

    $this->post(route('employee.productions.store'), [
        'formula_id' => $formula->id, 'quantity_produced' => 10, 'produced_at' => today()->toDateString(),
    ])->assertSessionHasNoErrors();

    $movement = StockMovement::where('product_code', 'CREMA')->firstOrFail();
    expect((float) BranchStock::where('product_code', 'CREMA')->value('stock_actual'))->toBe(10.0)
        ->and($movement->type)->toBe('entrada')
        ->and($movement->reference_type)->toBe('production')
        ->and($movement->balance)->toBe(10.0)
        ->and(Production::first()->product_code)->toBe('CREMA');
});

it('no prepara si falta stock de un insumo y no deja cambios a medias', function () {
    fmLogin();
    $formula = fmReadyFormula();

    // 1000 g necesitan 100 de urea y solo hay 100 → alcanza; 1100 g necesitan 110 → falta
    $this->post(route('employee.productions.store'), [
        'formula_id' => $formula->id, 'quantity_produced' => 1100, 'produced_at' => today()->toDateString(),
    ])->assertSessionHas('error');

    expect(Production::count())->toBe(0)
        ->and((float) BranchStock::where('product_code', 'UREA')->value('stock_actual'))->toBe(100.0)
        ->and((float) BranchStock::where('product_code', 'BASE')->value('stock_actual'))->toBe(1000.0)
        ->and(StockMovement::count())->toBe(0);
});

it('numera los lotes del mismo día de forma correlativa', function () {
    fmLogin();
    $formula = fmReadyFormula();

    foreach ([1, 2] as $_) {
        $this->post(route('employee.productions.store'), [
            'formula_id' => $formula->id, 'quantity_produced' => 100, 'produced_at' => today()->toDateString(),
        ])->assertSessionHasNoErrors();
    }

    expect(Production::orderBy('id')->pluck('batch')->all())->toBe([
        'FM-0001-' . today()->format('ymd') . '-01',
        'FM-0001-' . today()->format('ymd') . '-02',
    ]);
});

it('no permite preparar una fórmula inactiva', function () {
    fmLogin();
    $formula = fmReadyFormula(['status' => 0]);

    $this->post(route('employee.productions.store'), [
        'formula_id' => $formula->id, 'quantity_produced' => 100, 'produced_at' => today()->toDateString(),
    ])->assertSessionHasErrors('formula_id');

    expect(Production::count())->toBe(0);
});

it('muestra las pantallas del módulo', function () {
    fmLogin();
    $formula = fmReadyFormula();

    $this->post(route('employee.productions.store'), [
        'formula_id' => $formula->id, 'quantity_produced' => 100, 'produced_at' => today()->toDateString(),
    ]);
    $production = Production::firstOrFail();

    $this->get(route('employee.formulas.index'))->assertOk()->assertSee('Crema de urea 10%');
    $this->get(route('employee.formulas.create'))->assertOk();
    $this->get(route('employee.formulas.show', $formula))->assertOk()->assertSee($production->batch);
    $this->get(route('employee.formulas.edit', $formula))->assertOk();
    $this->get(route('employee.productions.index'))->assertOk()->assertSee($production->batch);
    $this->get(route('employee.productions.create', ['formula' => $formula->id]))->assertOk();
    $this->get(route('employee.productions.show', $production))->assertOk()->assertSee('Insumos consumidos');
});

it('exige el privilegio correspondiente a empleados sin acceso', function () {
    $employee = Employee::create([
        'company_id' => 1, 'branch_id' => 1, 'role_id' => Role::EMPLOYEE,
        'name' => 'Cajero', 'email' => 'cajero@example.com', 'password' => 'secret',
        'privileges' => [Employee::PRIV_VER_FORMULAS],
    ]);
    $this->actingAs($employee, 'employee');

    $this->get(route('employee.formulas.index'))->assertOk();
    $this->get(route('employee.productions.index'))->assertRedirect(route('employee.home'));
});
