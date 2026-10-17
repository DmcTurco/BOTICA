<?php

use App\Models\Branch;
use App\Models\Category;
use App\Models\Client;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\DocumentType;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Sistema recién instalado (sin seeders): las pantallas avisan qué falta en vez de fallar

it('ventas avisa que faltan las series de la sede en vez de fallar', function () {
    $employee = loginEmployee();
    openRegister($employee, 100, 0);
    stockProduct('P1', 10);

    $this->get(route('employee.orders.index'))
        ->assertOk()
        ->assertViewIs('employee.pages.requirements')
        ->assertSee('Series de comprobantes de la sede');
});

it('ventas avisa que faltan productos y stock', function () {
    $employee = loginEmployee();
    openRegister($employee, 100, 0);

    $this->get(route('employee.orders.index'))
        ->assertOk()
        ->assertViewIs('employee.pages.requirements')
        ->assertSee('Registrar producto')
        ->assertSee('Registrar compra');
});

it('ventas solo muestra los comprobantes que tienen serie activa en la sede', function () {
    $employee = loginEmployee();
    posSetup($employee); // solo NV01

    $response = $this->get(route('employee.orders.index'))->assertOk()->assertViewIs('employee.pages.orders.index');

    preg_match_all('/class="btn-comprobante[^"]*"[^>]*data-value="(\d)"/', $response->getContent(), $buttons);

    expect($response->viewData('availableVouchers'))->toBe([3])
        ->and($buttons[1])->toBe(['3']);
});

it('nuevo producto avisa que faltan categorías y unidades', function () {
    loginEmployee();

    $this->get(route('employee.products.create'))
        ->assertOk()
        ->assertViewIs('employee.pages.requirements')
        ->assertSee('Registrar categoría')
        ->assertSee('Registrar unidad');

    Category::create(['company_id' => 1, 'name' => 'Analgésicos', 'status' => 1]);
    Unit::create(['company_id' => 1, 'name' => 'Tableta', 'abbreviation' => 'TAB', 'status' => 1]);

    $this->get(route('employee.products.create'))->assertOk()->assertViewIs('employee.pages.products.form');
});

it('la empresa genera las series faltantes de una sede sin duplicar', function () {
    $this->actingAs(Company::find(1), 'company');
    $branch = Branch::find(1);

    $this->get(route('company.branches.index'))->assertSee('Generar series');

    $this->post(route('company.branches.series.generate', $branch))->assertRedirect(route('company.branches.index'));
    $this->post(route('company.branches.series.generate', $branch));

    expect(DocumentSeries::where('branch_id', $branch->id)->pluck('series')->sort()->values()->all())
        ->toBe(['B001', 'BC01', 'F001', 'FC01', 'NV01'])
        ->and(DocumentSeries::whereNull('branch_id')->count())->toBe(count(DocumentSeries::GLOBAL))
        ->and(DocumentType::count())->toBe(count(DocumentType::BASE));

    $this->get(route('company.branches.index'))->assertDontSee('Generar series');
});

it('los correlativos internos se crean solos y el cliente público no gasta números', function () {
    expect(DocumentSeries::count())->toBe(0);

    $client = Client::publicFor(1);
    Client::publicFor(1);
    Client::publicFor(1);

    expect($client->code)->toBe('C-00000001')
        ->and(DocumentSeries::where('type_code', DocumentSeries::CLIENTE)->value('current_number'))->toBe(1);
});
