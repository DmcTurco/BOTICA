<?php

use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;

uses(RefreshDatabase::class);

/** Entra al panel de la empresa 1 */
function loginCompany(): Company
{
    $company = Company::find(1) ?? Company::create(['name' => 'Botica SAC', 'ruc' => '20000000001', 'email' => 'botica@example.com', 'password' => 'secret']);
    test()->actingAs($company, 'company');

    return $company;
}

it('compara las ventas, inventario, vencidos y deudas de todas las sedes', function () {
    $employee = loginEmployee();
    $norte    = Branch::create(['company_id' => 1, 'name' => 'Sede Norte', 'status' => 1]);
    stockProduct('A', 10, 2, 5);                                   // inventario 20 en sede 1
    stockProduct('A', 4, 2, 5, $norte->id);                        // inventario 8 en Norte, bajo el mínimo (4 ≤ 5)
    addBatch('A', 3, today()->subDay()->toDateString(), 'VENC');   // 3 unidades vencidas a costo 1 en sede 1

    reportSale($employee, 'A', 2, 100);
    reportSale($employee, 'A', 1, 40, ['branch_id' => $norte->id]);
    Order::first()->update(['payment_type' => 5, 'credit_balance' => 25]);        // fiado pendiente en sede 1

    loginCompany();
    $view = $this->get(route('company.reports.branches'))->assertOk()->assertSee('Sede Norte');
    $rows = $view->viewData('rows')->keyBy('name');

    $principal = $rows->first(fn ($r) => $r->name !== 'Sede Norte');
    expect($rows['Sede Norte']->gross)->toBe(40.0)->and($rows['Sede Norte']->low_stock)->toBe(1)->and($rows['Sede Norte']->inventory)->toBe(8.0)
        ->and($principal->gross)->toBe(100.0)->and($principal->expired)->toBe(3.0)->and($principal->receivable)->toBe(25.0)
        ->and($view->viewData('totals')['gross'])->toBe(140.0)
        ->and($view->viewData('top')->first()->product_code)->toBe('A');
});

it('descarga el resumen por sedes en CSV', function () {
    loginEmployee();
    Branch::create(['company_id' => 1, 'name' => 'Sede Norte', 'status' => 1]);

    loginCompany();
    $response = $this->get(route('company.reports.branches', ['export' => 'csv']))->assertOk();

    expect($response->streamedContent())->toContain('Sede,Documentos')->toContain('Sede Norte');
});

it('la empresa edita sus datos y el nombre sale en la barra lateral del empleado', function () {
    $company = loginCompany();

    $this->get(route('company.profile.edit'))->assertOk()->assertSee('Datos de la empresa');

    $this->put(route('company.profile.update'), ['name' => 'Botica Salud y Vida', 'ruc' => '20123456789', 'email' => 'botica@example.com'])
        ->assertRedirect(route('company.profile.edit'))->assertSessionHasNoErrors();
    expect($company->fresh()->name)->toBe('Botica Salud y Vida');

    // Validación: nombre obligatorio y RUC de 11 dígitos
    $this->put(route('company.profile.update'), ['name' => '', 'ruc' => '123', 'email' => 'botica@example.com'])
        ->assertSessionHasErrors(['name', 'ruc']);

    // El nombre aparece en el panel del empleado
    loginEmployee();
    $this->get(route('employee.home'))->assertOk()->assertSee('Botica Salud y Vida');
});
