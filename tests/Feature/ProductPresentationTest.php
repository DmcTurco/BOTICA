<?php

use App\Models\Category;
use App\Models\Presentation;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Datos mínimos válidos para editar el producto P1 */
function productPayload(array $extra = []): array
{
    $category = Category::firstOrCreate(['company_id' => 1, 'name' => 'General'], ['status' => 1]);
    $unit     = Unit::firstOrCreate(['name' => 'Tableta'], ['abbreviation' => 'TAB', 'sunat_code' => 'NIU', 'status' => 1]);

    return array_merge([
        'nombre' => 'Paracetamol', 'categoria_id' => $category->id, 'unidad_medida_id' => $unit->id,
        'afectacion_igv' => '20', 'precio_compra' => 0.2, 'precio_venta_unidad' => 0.5,
    ], $extra);
}

it('guarda las presentaciones al editar el producto y las reemplaza por las enviadas', function () {
    loginEmployee();
    stockProduct('P1', 1);
    $caja = Unit::create(['name' => 'Caja', 'abbreviation' => 'CJA', 'sunat_code' => 'BX', 'status' => 1]);
    $blister = Unit::create(['name' => 'Blíster', 'abbreviation' => 'BLI', 'sunat_code' => 'NIU', 'status' => 1]);

    $this->put(route('employee.products.update', 'P1'), productPayload(['presentaciones' => [
        1 => ['unidad_medida_id' => $caja->id, 'cantidad_equivalente' => 100, 'precio_venta' => 40, 'es_presentacion_principal' => '1'],
        2 => ['unidad_medida_id' => $blister->id, 'cantidad_equivalente' => 10, 'precio_venta' => 5],
    ]]))->assertSessionHasNoErrors();

    $saved = Presentation::where('product_code', 'P1')->orderBy('equivalent_amount')->get();
    expect($saved)->toHaveCount(2)
        ->and((float) $saved[0]->equivalent_amount)->toBe(10.0)->and((float) $saved[0]->sale_price)->toBe(5.0)->and((bool) $saved[0]->main_presentation)->toBeFalse()
        ->and((float) $saved[1]->equivalent_amount)->toBe(100.0)->and((bool) $saved[1]->main_presentation)->toBeTrue();

    // Agregar una tercera conserva las anteriores (el formulario las reenvía todas)
    $this->put(route('employee.products.update', 'P1'), productPayload(['presentaciones' => [
        1 => ['unidad_medida_id' => $caja->id, 'cantidad_equivalente' => 100, 'precio_venta' => 40],
        2 => ['unidad_medida_id' => $blister->id, 'cantidad_equivalente' => 10, 'precio_venta' => 5],
        3 => ['unidad_medida_id' => $caja->id, 'cantidad_equivalente' => 50, 'precio_venta' => 22],
    ]]))->assertSessionHasNoErrors();

    expect(Presentation::where('product_code', 'P1')->count())->toBe(3);
});

it('si el formulario vuelve con errores no pierde las presentaciones escritas', function () {
    loginEmployee();
    stockProduct('P1', 1);
    $caja = Unit::create(['name' => 'Caja', 'abbreviation' => 'CJA', 'sunat_code' => 'BX', 'status' => 1]);

    // Falta el precio de compra: la validación falla y el formulario regresa
    $this->from(route('employee.products.edit', 'P1'))
        ->put(route('employee.products.update', 'P1'), productPayload([
            'precio_compra' => '',
            'presentaciones' => [1 => ['unidad_medida_id' => $caja->id, 'cantidad_equivalente' => 12, 'precio_venta' => 9.9]],
        ]))->assertSessionHasErrors('precio_compra');

    $this->followingRedirects()->get(route('employee.products.edit', 'P1'))->assertOk();   // el formulario carga sin romperse

    $html = $this->withSession(['_old_input' => [
        'presentaciones' => [1 => ['unidad_medida_id' => (string) $caja->id, 'cantidad_equivalente' => '12', 'precio_venta' => '9.9']],
    ]])->get(route('employee.products.edit', 'P1'))->assertOk()->getContent();

    expect($html)->toContain('"12"')->toContain('"9.9"');       // vuelven a cargarse en el formulario
    expect(Presentation::count())->toBe(0);
});
