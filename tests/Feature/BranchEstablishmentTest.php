<?php

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Código de establecimiento SUNAT de cada sede: va en cada comprobante (local que lo emite)
 * y no se puede repetir dentro de la empresa. La sede 1 de la compañía 1 nace con 0000.
 */
function branchData(array $overrides = []): array
{
    return array_merge(['name' => 'Sede Norte', 'sunat_establishment_code' => '0001', 'status' => 1], $overrides);
}

it('no deja dos sedes de la empresa con el mismo código de establecimiento', function () {
    $this->actingAs(Company::find(1), 'company');

    $this->post(route('company.branches.store'), branchData(['sunat_establishment_code' => '0000']))
        ->assertSessionHasErrors('sunat_establishment_code');

    $this->post(route('company.branches.store'), branchData())->assertSessionHasNoErrors();

    expect(Branch::where('company_id', 1)->pluck('sunat_establishment_code')->sort()->values()->all())->toBe(['0000', '0001']);
});

it('al editar una sede puede conservar su propio código', function () {
    $this->actingAs(Company::find(1), 'company');
    $branch = Branch::find(1);

    $this->put(route('company.branches.update', $branch), branchData(['name' => 'Sede Principal', 'sunat_establishment_code' => '0000']))
        ->assertSessionHasNoErrors();
});

it('otra empresa sí puede usar el mismo código (cada RUC tiene los suyos)', function () {
    Branch::create(['company_id' => 2, 'name' => 'Sede de otra empresa', 'sunat_establishment_code' => '0001', 'status' => 1]);
    $this->actingAs(Company::find(1), 'company');

    $this->post(route('company.branches.store'), branchData())->assertSessionHasNoErrors();
});

it('al crear una sede propone el siguiente código libre', function () {
    $this->actingAs(Company::find(1), 'company');
    Branch::create(['company_id' => 1, 'name' => 'Sede Sur', 'sunat_establishment_code' => '0001', 'status' => 1]);

    $this->get(route('company.branches.create'))->assertOk()->assertViewHas('suggestedCode', '0002');
});

it('avisa cuando hay sedes con el mismo código (creadas antes de la validación)', function () {
    $this->actingAs(Company::find(1), 'company');

    $this->get(route('company.branches.index'))->assertOk()->assertDontSee('mismo código de establecimiento');

    Branch::create(['company_id' => 1, 'name' => 'Sede Repetida', 'sunat_establishment_code' => '0000', 'status' => 1]);

    $this->get(route('company.branches.index'))->assertOk()
        ->assertSee('Hay sedes con el mismo código de establecimiento SUNAT')
        ->assertSee('Sede Repetida');
    $this->get(route('company.sunat.edit'))->assertOk()->assertSee('Revisa los códigos de establecimiento');
});
