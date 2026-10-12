<?php

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('ver productos permite consultar pero no crear ni editar', function () {
    loginEmployee([Employee::PRIV_VER_INVENTARIO]);
    stockProduct('P1', 1);

    $this->get(route('employee.products.index'))->assertOk()->assertDontSee('Nuevo Producto');
    $this->get(route('employee.categories.index'))->assertOk()->assertDontSee('Nueva Categoría');
    $this->get(route('employee.laboratories.index'))->assertOk()->assertDontSee('Nuevo Laboratorio');
    $this->get(route('employee.units.index'))->assertOk()->assertDontSee('Nueva Unidad');

    $this->get(route('employee.products.create'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.products.edit', 'P1'))->assertRedirect(route('employee.home'));
    $this->delete(route('employee.products.destroy', 'P1'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.categories.create'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.laboratories.create'))->assertRedirect(route('employee.home'));
    $this->post(route('employee.units.store'), ['name' => 'X', 'abbreviation' => 'X', 'sunat_code' => 'NIU', 'status' => 1])
        ->assertRedirect(route('employee.home'));
});

it('crear y editar productos habilita el formulario de productos', function () {
    loginEmployee([Employee::PRIV_VER_INVENTARIO, Employee::PRIV_CREAR_PRODUCTOS]);
    stockProduct('P1', 1);

    $this->get(route('employee.products.index'))->assertOk()->assertSee('Nuevo Producto');
    $this->get(route('employee.products.create'))->assertOk();
    $this->get(route('employee.products.edit', 'P1'))->assertOk();
    // Pero sigue sin poder tocar categorías
    $this->get(route('employee.categories.create'))->assertRedirect(route('employee.home'));
});

it('mantener categorías, laboratorios y unidades habilita sus formularios', function () {
    loginEmployee([Employee::PRIV_VER_INVENTARIO, Employee::PRIV_MANTENIMIENTO_FAMILIAS]);

    $this->get(route('employee.categories.index'))->assertOk()->assertSee('Nueva Categoría');
    $this->get(route('employee.categories.create'))->assertOk();
    $this->get(route('employee.laboratories.create'))->assertOk();
    $this->post(route('employee.units.store'), ['name' => 'Sachet', 'abbreviation' => 'SCH', 'sunat_code' => 'NIU', 'status' => 1])
        ->assertSessionHasNoErrors();
    $this->get(route('employee.products.create'))->assertRedirect(route('employee.home'));
});

it('la pantalla de asignar privilegios lista los grupos reordenados sin pendientes falsos', function () {
    loginEmployee();

    $groups = Employee::PRIVILEGES_GROUPS;

    expect(array_keys($groups))->toBe(['Ventas', 'Caja', 'Catálogo', 'Inventario', 'Laboratorio', 'Reportes', 'SUNAT', 'Herramientas']);

    // Los únicos pendientes son los dos módulos SUNAT que faltan
    $pending = collect($groups)->flatMap(fn ($g) => collect($g['items'])->reject(fn ($i) => $i['ready'])->keys())->all();
    expect($pending)->toBe([Employee::PRIV_CREAR_BAJA_FE, Employee::PRIV_VER_GUIAS_REGISTRADAS]);

    $this->get(route('employee.employees.create'))->assertOk()->assertSee('Crear y Editar Productos');
});
