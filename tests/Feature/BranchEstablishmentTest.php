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

it('una sede que aún no es anexo en SUNAT emite con el código del domicilio fiscal', function () {
    $this->actingAs(Company::find(1), 'company');

    // Dos locales sin anexo: no piden código ni chocan con la sede 0000 ni entre ellos
    $this->post(route('company.branches.store'), branchData(['name' => 'Local Norte', 'uses_fiscal_establishment' => 1, 'sunat_establishment_code' => '']))
        ->assertSessionHasNoErrors();
    $this->post(route('company.branches.store'), branchData(['name' => 'Local Sur', 'uses_fiscal_establishment' => 1]))
        ->assertSessionHasNoErrors();

    $norte = Branch::where('name', 'Local Norte')->first();
    expect($norte->uses_fiscal_establishment)->toBe(1)
        ->and($norte->sunatEstablishmentCode())->toBe('0000')
        ->and(Branch::duplicateEstablishmentCodes(1))->toBe([])
        // Cada local sigue con sus propias series
        ->and($norte->documentSeries()->where('type_code', 'BOLETA')->value('series'))
            ->not->toBe(Branch::where('name', 'Local Sur')->first()->documentSeries()->where('type_code', 'BOLETA')->value('series'));

    $this->get(route('company.branches.index'))->assertOk()->assertSee('anexo pendiente en SUNAT')
        ->assertDontSee('Hay sedes con el mismo código de establecimiento SUNAT');

    // Al registrar el anexo se desmarca y se pone su código propio
    $this->put(route('company.branches.update', $norte), branchData(['name' => 'Local Norte', 'sunat_establishment_code' => '0001']))
        ->assertSessionHasNoErrors();
    expect($norte->fresh()->sunatEstablishmentCode())->toBe('0001');
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
