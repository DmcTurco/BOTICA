<?php

use App\Http\Controllers\Employee\OrderController;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Catálogo del punto de venta: se pide paginado al servidor (no se carga todo en la pantalla)

it('el punto de venta no carga el catálogo en la página', function () {
    $employee = loginEmployee();
    posSetup($employee);

    $this->get(route('employee.orders.index'))->assertOk()
        ->assertViewMissing('products')
        ->assertDontSee('Producto P1')
        ->assertDontSee('nroComprobante');
});

it('pagina el catálogo y busca por nombre, correlativo o código de barras', function () {
    $employee = loginEmployee();
    posSetup($employee);
    foreach (range(1, OrderController::POS_PER_PAGE + 5) as $n) {
        stockProduct('X' . str_pad($n, 3, '0', STR_PAD_LEFT), 5);
    }
    Product::where('code', 'X007')->update(['barcode' => '7750001']);

    $first = $this->getJson(route('employee.pos.products'))->assertOk();
    expect($first->json('total'))->toBe(OrderController::POS_PER_PAGE + 6)   // + P1 de posSetup
        ->and($first->json('data'))->toHaveCount(OrderController::POS_PER_PAGE)
        ->and($first->json('last_page'))->toBe(2);

    expect($this->getJson(route('employee.pos.products', ['page' => 2]))->json('data'))->toHaveCount(6);

    expect($this->getJson(route('employee.pos.products', ['q' => 'x007']))->json('data.0.code'))->toBe('X007')
        ->and($this->getJson(route('employee.pos.products', ['q' => '7750001']))->json('data.0.code'))->toBe('X007');
});

it('el lector de códigos solo acepta coincidencia exacta', function () {
    $employee = loginEmployee();
    posSetup($employee);
    stockProduct('P10', 3);

    expect($this->getJson(route('employee.pos.products', ['q' => 'P1', 'exact' => 1]))->json('data'))->toHaveCount(1)
        ->and($this->getJson(route('employee.pos.products', ['q' => 'p10', 'exact' => 1]))->json('data.0.code'))->toBe('P10')
        ->and($this->getJson(route('employee.pos.products', ['q' => 'P', 'exact' => 1]))->json('data'))->toHaveCount(0);
});

it('ofrece solo stock vendible y, si se pide, también los productos sin stock', function () {
    $employee = loginEmployee();
    posSetup($employee);                       // P1 con 10
    stockProduct('SIN', 0);
    addBatch('P1', 4, today()->subDay()->toDateString(), 'VENCIDO');   // 4 vencidas de 14

    $con = $this->getJson(route('employee.pos.products'))->json('data');
    expect(array_column($con, 'code'))->toBe(['P1'])
        ->and($con[0]['stock'])->toBe(10);

    expect(array_column($this->getJson(route('employee.pos.products', ['stock' => 0]))->json('data'), 'code'))->toContain('SIN');
});

it('no muestra productos de otra compañía', function () {
    $employee = loginEmployee();
    posSetup($employee);
    Product::create(['code' => 'AJENO', 'company_id' => 99, 'name' => 'Ajeno', 'purchase_price' => 1, 'unit_sale_price' => 2, 'igv_affectation' => '20', 'employee_id' => 1, 'status' => 1]);

    expect(array_column($this->getJson(route('employee.pos.products', ['stock' => 0]))->json('data'), 'code'))->not->toContain('AJENO');
});
