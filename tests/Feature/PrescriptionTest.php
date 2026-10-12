<?php

use App\Models\Employee;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rxData(array $overrides = []): array
{
    return array_merge([
        'patient_name' => 'Rosa Quispe', 'patient_document' => '45678912', 'doctor_name' => 'Dr. Luis Paredes',
        'doctor_license' => '12345', 'prescription_number' => 'R-0001', 'prescription_date' => today()->toDateString(),
        'establishment' => 'Clínica Norte',
    ], $overrides);
}

/** Venta de 2 unidades de P1 (S/ 10 gravado): total 23.60 */
function rxSale(array $extra = [])
{
    return posSale(array_merge(['total' => 23.60], $extra));
}

// ── Nivel de receta ───────────────────────────────────────────

it('un producto libre se vende sin receta', function () {
    posSetup(loginEmployee());

    rxSale()->assertOk();

    expect(Prescription::count())->toBe(0);
});

it('un producto con receta exige los datos mínimos de la receta', function () {
    posSetup(loginEmployee());
    Product::where('code', 'P1')->update(['requires_recipe' => 1]);

    rxSale()->assertStatus(422)->assertJsonPath('needs_prescription', 1);
    rxSale(['prescription' => rxData(['doctor_license' => ''])])->assertStatus(422)->assertJsonFragment(['message' => 'Esta venta incluye productos con receta: falta el N° de colegiatura (CMP) del médico.']);
    expect(Order::count())->toBe(0)->and(stockOf('P1'))->toBe(10.0);

    // No pide documento ni número de receta
    rxSale(['prescription' => rxData(['patient_document' => '', 'prescription_number' => ''])])->assertOk();

    $rx = Prescription::firstOrFail();
    expect($rx->order_id)->toBe(Order::first()->id)->and($rx->doctor_license)->toBe('12345')->and(stockOf('P1'))->toBe(8.0);
});

it('un producto controlado exige además documento del paciente y número de receta', function () {
    posSetup(loginEmployee());
    Product::where('code', 'P1')->update(['controlled_type' => 'psicotropico']);

    rxSale(['prescription' => rxData(['patient_document' => ''])])->assertStatus(422)->assertJsonPath('needs_prescription', 2);
    rxSale(['prescription' => rxData(['prescription_number' => ''])])->assertStatus(422);
    rxSale(['prescription' => rxData(['prescription_date' => today()->addDay()->toDateString()])])->assertStatus(422);
    expect(Order::count())->toBe(0);

    rxSale(['prescription' => rxData()])->assertOk();
    expect(Prescription::count())->toBe(1);
});

it('el navegador no puede saltarse la receta ocultando el nivel', function () {
    posSetup(loginEmployee());
    Product::where('code', 'P1')->update(['controlled_type' => 'estupefaciente']);

    // Aunque envíe prescription vacía, el servidor lee el nivel de la base de datos
    rxSale(['prescription' => []])->assertStatus(422);
    rxSale(['prescription' => null])->assertStatus(422);

    expect(Order::count())->toBe(0);
});

it('marcar un producto como controlado lo deja también como que requiere receta', function () {
    loginEmployee();
    stockProduct('P1', 1);
    $category = App\Models\Category::create(['company_id' => 1, 'name' => 'Psicotrópicos', 'status' => 1]);
    $unit     = App\Models\Unit::create(['name' => 'Tableta', 'abbreviation' => 'TAB', 'sunat_code' => 'NIU', 'status' => 1]);

    $this->put(route('employee.products.update', 'P1'), [
        'nombre' => 'Clonazepam 2mg', 'categoria_id' => $category->id, 'unidad_medida_id' => $unit->id, 'afectacion_igv' => '20',
        'precio_compra' => 1, 'precio_venta_unidad' => 2, 'tipo_controlado' => 'psicotropico', 'registro_sanitario' => 'EE-01234',
    ])->assertSessionHasNoErrors();

    $product = Product::find('P1');
    expect($product->controlled_type)->toBe('psicotropico')->and($product->sanitary_registry)->toBe('EE-01234')
        ->and($product->requires_recipe)->toBeTrue()->and($product->recipe_level)->toBe(2);
});

// ── Registro y libro ──────────────────────────────────────────

it('lista las recetas despachadas con los productos que las exigían', function () {
    posSetup(loginEmployee());
    Product::where('code', 'P1')->update(['controlled_type' => 'psicotropico']);
    rxSale(['prescription' => rxData()])->assertOk();

    $this->get(route('employee.prescriptions.index'))->assertOk()
        ->assertSee('Rosa Quispe')->assertSee('Dr. Luis Paredes')->assertSee('controlado');
    $this->get(route('employee.prescriptions.index', ['buscar' => 'inexistente']))->assertOk()->assertDontSee('Rosa Quispe');
});

it('el libro de controlados muestra entradas, salidas con su receta y saldo', function () {
    posSetup(loginEmployee());
    Product::where('code', 'P1')->update(['controlled_type' => 'psicotropico']);
    stockProduct('OTRO', 5);                    // un producto libre no debe aparecer
    rxSale(['prescription' => rxData()])->assertOk();

    $view = $this->get(route('employee.controlled-book.index'))->assertOk()
        ->assertSee('Rosa Quispe')->assertSee('R-0001');
    $section = $view->viewData('sections')->first();

    expect($view->viewData('sections'))->toHaveCount(1)
        ->and($section['opening'])->toBe(10.0)
        ->and($section['closing'])->toBe(8.0)
        ->and($section['rows'][0]['out'])->toBe(2.0)
        ->and($section['rows'][0]['rx']->prescription_number)->toBe('R-0001');

    $this->get(route('employee.controlled-book.print'))->assertOk()->assertSee('Libro de control de psicotrópicos');
});

it('recetas y libro de controlados exigen su privilegio', function () {
    loginEmployee([Employee::PRIV_VER_RECETAS]);

    $this->get(route('employee.prescriptions.index'))->assertOk();
    $this->get(route('employee.controlled-book.index'))->assertRedirect(route('employee.home'));
});
