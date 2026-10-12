<?php

use App\Models\Employee;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ingredientProduct(string $code, string $ingredient, float $sale, float $stock): Product
{
    $p = stockProduct($code, $stock, $sale / 2);
    $p->update(['active_ingredient' => $ingredient, 'unit_sale_price' => $sale, 'name' => "Prod {$code}"]);

    return $p;
}

it('ofrece productos con el mismo principio activo, con stock, del más barato al más caro', function () {
    loginEmployee();
    ingredientProduct('GEN', 'Paracetamol', 0.30, 100);
    ingredientProduct('MARCA', 'Paracetamol', 1.20, 50);
    ingredientProduct('SINSTOCK', 'Paracetamol', 0.10, 0);
    ingredientProduct('OTRO', 'Ibuprofeno', 0.50, 40);
    ingredientProduct('PEDIDO', 'Paracetamol', 0.80, 0);

    $lista = $this->getJson(route('employee.pos.alternatives', ['code' => 'PEDIDO']))->assertOk()->json();

    expect(array_column($lista, 'code'))->toBe(['GEN', 'MARCA']);
});

it('no cuenta como alternativa el stock de lotes vencidos', function () {
    loginEmployee();
    ingredientProduct('PEDIDO', 'Amoxicilina', 1, 0);
    ingredientProduct('VENC', 'Amoxicilina', 1, 0);
    addBatch('VENC', 5, today()->subDay()->toDateString(), 'V1');
    ingredientProduct('OK', 'Amoxicilina', 2, 0);
    addBatch('OK', 5, today()->addDays(20)->toDateString(), 'OK1');

    $lista = $this->getJson(route('employee.pos.alternatives', ['code' => 'PEDIDO']))->json();

    expect(array_column($lista, 'code'))->toBe(['OK']);
});

it('busca por texto cuando el producto pedido no existe en la lista', function () {
    loginEmployee();
    ingredientProduct('A', 'Loratadina', 1.5, 10);

    expect(array_column($this->getJson(route('employee.pos.alternatives', ['q' => 'lorata']))->json(), 'code'))->toBe(['A']);
    expect($this->getJson(route('employee.pos.alternatives', ['q' => 'lo']))->json())->toBe([]);          // muy corto
    expect($this->getJson(route('employee.pos.alternatives', ['code' => 'NOEXISTE']))->json())->toBe([]);
});

it('no mezcla productos de otra compañía y exige el privilegio de ventas', function () {
    loginEmployee();
    ingredientProduct('PEDIDO', 'Metformina', 1, 0);
    Product::create(['code' => 'AJENO', 'company_id' => 99, 'name' => 'Ajeno', 'active_ingredient' => 'Metformina', 'purchase_price' => 1, 'unit_sale_price' => 2, 'igv_affectation' => '20', 'employee_id' => 1, 'status' => 1]);

    expect($this->getJson(route('employee.pos.alternatives', ['code' => 'PEDIDO']))->json())->toBe([]);

    loginEmployee([Employee::PRIV_VER_INVENTARIO]);
    $this->get(route('employee.pos.alternatives', ['code' => 'PEDIDO']))->assertRedirect(route('employee.home'));
});
