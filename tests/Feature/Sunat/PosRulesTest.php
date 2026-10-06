<?php

use App\Http\Controllers\Employee\OrderController;
use App\Models\DocumentType;
use App\Models\Product;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Llama a un método privado del controlador del POS (la regla vive ahí, no en una ruta aislada) */
function callOrderController(string $method, mixed ...$args): mixed
{
    $controller = new OrderController();
    $reflection = new ReflectionMethod($controller, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($controller, ...$args);
}

function makeProduct(): Product
{
    $product = new Product([
        'code' => 'P001', 'company_id' => 1, 'name' => 'Paracetamol 500mg',
        'purchase_price' => 0.5, 'unit_sale_price' => 1.20, 'igv_affectation' => '10', 'employee_id' => 1, 'status' => 1,
    ]);
    $product->save();

    return $product;
}

// ── Precios del POS contra la base de datos ───────────────────

it('acepta el precio real del producto', function () {
    $product = makeProduct();

    $result = callOrderController('pricing', [['code' => 'P001', 'name' => 'x', 'price' => 1.20, 'qty' => 2]], 1, null);

    expect($result)->not->toHaveKey('error')
        ->and($result['tax']['total'])->toBe(2.83); // 2.40 + IGV 0.43
});

it('rechaza un precio alterado desde el navegador', function () {
    makeProduct();

    $result = callOrderController('pricing', [['code' => 'P001', 'name' => 'x', 'price' => 0.01, 'qty' => 2]], 1, null);

    expect($result)->toHaveKey('error');
});

it('rechaza productos de otra compañía', function () {
    makeProduct();

    $result = callOrderController('pricing', [['code' => 'P001', 'name' => 'x', 'price' => 1.20, 'qty' => 1]], 99, null);

    expect($result)->toHaveKey('error');
});

// ── Boletas de más de S/ 700 ──────────────────────────────────

it('no exige cliente hasta S/ 700 inclusive', function () {
    expect(callOrderController('boletaIdentificationError', 700.0, null, null, null))->toBeNull();
});

it('exige cliente identificado cuando excede S/ 700', function () {
    $this->seed(DocumentTypeSeeder::class);

    expect(callOrderController('boletaIdentificationError', 700.01, null, null, null))->toBeString()
        ->and(callOrderController('boletaIdentificationError', 800.0, 'JUAN', DocumentType::SIN_DOCUMENTO, null))->toBeString()
        ->and(callOrderController('boletaIdentificationError', 800.0, 'JUAN', DocumentType::DNI, '123'))->toBeString()
        ->and(callOrderController('boletaIdentificationError', 800.0, '', DocumentType::DNI, '12345678'))->toBeString()
        ->and(callOrderController('boletaIdentificationError', 800.0, 'JUAN PEREZ', DocumentType::DNI, '12345678'))->toBeNull();
});
