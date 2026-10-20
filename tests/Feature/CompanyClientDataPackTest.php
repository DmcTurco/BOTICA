<?php

use App\Models\Admin;
use App\Models\BranchStock;
use App\Models\Company;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Services\CompanyClientDataPack;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const TEST_PACK = 'zz_test_pack';

/** Crea un pack mínimo en database/data/client_packs (se borra al terminar cada test) */
function makeTestPack(array $rows): void
{
    $dir = base_path(CompanyClientDataPack::DIRECTORY . '/' . TEST_PACK);
    @mkdir($dir, 0777, true);
    $file = fopen($dir . '/productos.csv', 'w');
    fputcsv($file, CompanyClientDataPack::COLUMNS);
    foreach ($rows as $row) {
        fputcsv($file, $row);
    }
    fclose($file);
}

afterEach(function () {
    $dir = base_path(CompanyClientDataPack::DIRECTORY . '/' . TEST_PACK);
    @unlink($dir . '/productos.csv');
    @rmdir($dir);
});

/** Alta de una compañía desde el panel admin con (o sin) el pack de datos del cliente */
function createCompanyWithPack(?string $pack)
{
    test()->actingAs(Admin::first(), 'admin');

    return test()->post(route('admin.companies.store'), [
        'name' => 'Botica Migrada', 'email' => 'migrada@example.com',
        'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        'branch_name' => 'Sede Principal', 'load_products' => '0', 'client_pack' => $pack,
    ]);
}

it('carga los productos del cliente con su stock, presentaciones, kardex y lotes en la primera sede', function () {
    makeTestPack([
        // barcode, name, category, laboratory, active_ingredient, stock, purchase, sale, min, max, expiration, recipe, pack_qty, pack_price, blister_qty, blister_price
        ['7750001', 'PARACETAMOL 500 MG', 'Tableta', 'Portugal', 'PARACETAMOL', '24', '0.20', '0.50', 3, 10, '2027-05-31', 0, '100', '40.00', '10', '4.00'],
        ['', 'ALCOHOL 70 120 ML', 'Antisepticos', '', '', '0', '2.00', '3.50', 3, 10, '', 0, '', '', '', ''],
        ['7750003', 'AMOXICILINA 500 MG', 'Tableta', 'PORTUGAL', '', '10', '0.30', '0.80', 2, 8, '', 1, '', '', '', ''],
    ]);

    createCompanyWithPack(TEST_PACK)->assertSessionHasNoErrors()->assertRedirect();

    $company = Company::where('email', 'migrada@example.com')->firstOrFail();
    $branch  = $company->branches()->firstOrFail();

    expect(Product::where('company_id', $company->id)->count())->toBe(3);

    $para = Product::where('company_id', $company->id)->where('barcode', '7750001')->firstOrFail();
    expect($para->code)->toMatch('/^P-\d{8}$/')
        ->and($para->employee_id)->toBeNull()
        ->and((float) $para->purchase_price)->toBe(0.20)
        ->and((float) $para->unit_sale_price)->toBe(0.50)
        ->and($para->laboratory->name)->toBe('Portugal')
        ->and($para->category->name)->toBe('Tableta')
        ->and($para->active_ingredient)->toBe('PARACETAMOL')
        ->and($para->unit->name)->toBe('Unidad');

    // Unidad base principal + blíster + caja
    $pres = Presentation::where('product_id', $para->id)->orderBy('equivalent_amount')->get();
    expect($pres)->toHaveCount(3)
        ->and((float) $pres[0]->equivalent_amount)->toBe(1.0)
        ->and((bool) $pres[0]->main_presentation)->toBeTrue()
        ->and((float) $pres[1]->sale_price)->toBe(4.0)
        ->and((float) $pres[2]->equivalent_amount)->toBe(100.0);

    // Stock en la sede, con mínimo y máximo; el producto sin stock queda en 0
    $stock = BranchStock::where('branch_id', $branch->id)->where('product_id', $para->id)->firstOrFail();
    expect((float) $stock->stock_actual)->toBe(24.0)->and($stock->stock_minimum)->toBe(3)->and($stock->stock_maximum)->toBe(10);
    $alcohol = Product::where('company_id', $company->id)->where('name', 'ALCOHOL 70 120 ML')->firstOrFail();
    expect($alcohol->barcode)->toBeNull()
        ->and((float) BranchStock::where('product_id', $alcohol->id)->value('stock_actual'))->toBe(0.0);

    // Kardex: solo los productos con stock, como "Inventario inicial"
    expect(StockMovement::where('company_id', $company->id)->count())->toBe(2);
    $move = StockMovement::where('product_id', $para->id)->firstOrFail();
    expect($move->type)->toBe('ajuste')->and((float) $move->balance)->toBe(24.0)->and($move->notes)->toContain('Inventario inicial');

    // Lote solo para el que trae vencimiento
    $batch = StockBatch::where('company_id', $company->id)->get();
    expect($batch)->toHaveCount(1)
        ->and($batch[0]->product_id)->toBe($para->id)
        ->and($batch[0]->expiration_date->toDateString())->toBe('2027-05-31')
        ->and($batch[0]->quantity_remaining)->toBe(24.0);

    // "Portugal" y "PORTUGAL" son el mismo laboratorio; requiere receta se respeta
    expect($company->laboratories()->where('name', 'like', '%ortugal%')->count())->toBe(1)
        ->and((bool) Product::where('barcode', '7750003')->value('requires_recipe'))->toBeTrue();
});

it('sin elegir un pack, la compañía nace sin productos', function () {
    createCompanyWithPack(null)->assertSessionHasNoErrors();

    $company = Company::where('email', 'migrada@example.com')->firstOrFail();
    expect(Product::where('company_id', $company->id)->count())->toBe(0);
});

it('rechaza un pack que no existe (incluidas rutas que salen de la carpeta)', function () {
    createCompanyWithPack('../../etc')->assertSessionHasErrors('client_pack');
    createCompanyWithPack('no_existe')->assertSessionHasErrors('client_pack');

    expect(Company::where('email', 'migrada@example.com')->exists())->toBeFalse();
});

it('el formulario de alta lista los packs disponibles con su cantidad de productos', function () {
    makeTestPack([['', 'X', 'Tableta', '', '', '1', '1', '2', 1, 2, '', 0, '', '', '', '']]);
    $this->actingAs(Admin::first(), 'admin');

    $this->get(route('admin.companies.create'))->assertOk()
        ->assertSee('name="client_pack"', false)
        ->assertSee(CompanyClientDataPack::label(TEST_PACK) . ' — 1 productos');
});
