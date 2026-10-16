<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Datos válidos para crear un producto de la compañía indicada */
function barcodeProductData(int $companyId, array $extra = []): array
{
    $category = Category::firstOrCreate(['company_id' => $companyId, 'name' => 'General'], ['status' => 1]);
    $unit     = Unit::firstOrCreate(['company_id' => $companyId, 'name' => 'Tableta'], ['abbreviation' => 'TAB', 'sunat_code' => 'NIU', 'status' => 1]);

    return array_merge([
        'nombre' => 'Panadol 500mg', 'categoria_id' => $category->id, 'unidad_medida_id' => $unit->id,
        'afectacion_igv' => '10', 'precio_compra' => 1, 'precio_venta_unidad' => 2,
    ], $extra);
}

/** Empleado (admin de sede) de otra compañía, con su sede */
function loginOtherCompanyEmployee(): Company
{
    $company = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secret123']);
    $branch  = \App\Models\Branch::create(['company_id' => $company->id, 'name' => 'Sede Otra', 'status' => 1]);
    $employee = \App\Models\Employee::create([
        'company_id' => $company->id, 'branch_id' => $branch->id, 'role_id' => \App\Models\Role::BRANCH_ADMIN,
        'name' => 'Otro', 'email' => 'otro@example.com', 'password' => 'secret123',
    ]);
    test()->actingAs($employee, 'employee');

    return $company;
}

it('el código interno lo genera el sistema y el de barras se guarda aparte', function () {
    loginEmployee();

    $this->post(route('employee.products.store'), barcodeProductData(1, ['codigo_barras' => '7751234567890']))
        ->assertSessionHasNoErrors();

    $product = Product::where('name', 'Panadol 500mg')->sole();
    expect($product->code)->toBe('P-000001')
        ->and($product->barcode)->toBe('7751234567890')
        ->and(DocumentSeries::where('type_code', DocumentSeries::PRODUCTO)->value('current_number'))->toBe(1);
});

it('el código de barras es opcional', function () {
    loginEmployee();

    $this->post(route('employee.products.store'), barcodeProductData(1))->assertSessionHasNoErrors();

    expect(Product::sole()->barcode)->toBeNull();
});

it('no se repite el código de barras dentro de la misma compañía', function () {
    loginEmployee();
    $this->post(route('employee.products.store'), barcodeProductData(1, ['codigo_barras' => '7751234567890']));

    $this->post(route('employee.products.store'), barcodeProductData(1, ['nombre' => 'Otro', 'codigo_barras' => '7751234567890']))
        ->assertSessionHasErrors('codigo_barras');

    expect(Product::count())->toBe(1);
});

it('dos compañías pueden registrar el mismo código de barras', function () {
    loginEmployee();
    $this->post(route('employee.products.store'), barcodeProductData(1, ['codigo_barras' => '7751234567890']))
        ->assertSessionHasNoErrors();

    $other = loginOtherCompanyEmployee();
    $this->post(route('employee.products.store'), barcodeProductData($other->id, ['codigo_barras' => '7751234567890']))
        ->assertSessionHasNoErrors();

    expect(Product::where('barcode', '7751234567890')->pluck('company_id')->sort()->values()->all())->toBe([1, $other->id])
        ->and(Product::pluck('code')->sort()->values()->all())->toBe(['P-000001', 'P-000002']);
});

it('al editar se puede conservar o cambiar el código de barras', function () {
    loginEmployee();
    $this->post(route('employee.products.store'), barcodeProductData(1, ['codigo_barras' => '111']));
    $product = Product::sole();

    // Guardar sin cambiarlo no choca consigo mismo
    $this->put(route('employee.products.update', $product), barcodeProductData(1, ['codigo_barras' => '111']))
        ->assertSessionHasNoErrors();

    $this->put(route('employee.products.update', $product), barcodeProductData(1, ['codigo_barras' => '222']))
        ->assertSessionHasNoErrors();

    expect($product->fresh()->barcode)->toBe('222');
});

it('ventas incluye el código de barras para buscar y escanear', function () {
    $employee = loginEmployee();
    posSetup($employee);
    Product::where('code', 'P1')->update(['barcode' => '0012345']);

    $this->get(route('employee.orders.index'))
        ->assertOk()
        ->assertSee('data-barcode="0012345"', false);
});

// ── Stock inicial ──────────────────────────────────────────────────

it('el stock inicial entra al crear el producto, con su lote y queda en el kardex', function () {
    $employee = loginEmployee();

    $this->post(route('employee.products.store'), barcodeProductData(1, [
        'stock_inicial' => 300, 'lote_inicial' => 'L-2026', 'vencimiento_inicial' => '2027-12-31',
    ]))->assertSessionHasNoErrors();

    $product  = Product::sole();
    $movement = \App\Models\StockMovement::where('product_code', $product->code)->sole();
    $batch    = \App\Models\StockBatch::where('product_code', $product->code)->sole();

    expect($product->stockEnSede($employee->branch_id))->toBe(300.0)
        ->and($movement->reference_type)->toBe('initial')
        ->and($movement->referencia_label)->toBe('Inventario inicial')
        ->and((float) $movement->quantity)->toBe(300.0)
        ->and($batch->batch)->toBe('L-2026')
        ->and((float) $batch->quantity_remaining)->toBe(300.0);
});

it('sin stock inicial el producto nace en cero y sin movimientos', function () {
    $employee = loginEmployee();

    $this->post(route('employee.products.store'), barcodeProductData(1))->assertSessionHasNoErrors();

    $product = Product::sole();
    expect($product->stockEnSede($employee->branch_id))->toBe(0.0)
        ->and(\App\Models\StockMovement::count())->toBe(0)
        ->and(\App\Models\StockBatch::count())->toBe(0);
});

it('el stock inicial no puede ser negativo', function () {
    loginEmployee();

    $this->post(route('employee.products.store'), barcodeProductData(1, ['stock_inicial' => -5]))
        ->assertSessionHasErrors('stock_inicial');

    expect(Product::count())->toBe(0);
});
