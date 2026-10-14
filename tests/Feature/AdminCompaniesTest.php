<?php

use App\Models\Admin;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\DocumentSeries;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function companyData(array $overrides = []): array
{
    return array_merge([
        'name'                  => 'Botica San José',
        'ruc'                   => '20123456789',
        'email'                 => 'sanjose@example.com',
        'password'              => 'secreto123',
        'password_confirmation' => 'secreto123',
        'branch_name'           => 'Sede Principal',
        'branch_address'        => 'Av. Lima 123',
    ], $overrides);
}

// ── Panel admin: compañías ─────────────────────────────────────────

it('el admin crea una compañía lista para vender: usuario, primera sede y series', function () {
    $this->actingAs(Admin::first(), 'admin');

    $this->post(route('admin.companies.store'), companyData())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.companies.index'));

    $company = Company::where('email', 'sanjose@example.com')->firstOrFail();
    $branch  = Branch::where('company_id', $company->id)->sole();

    expect(Hash::check('secreto123', $company->password))->toBeTrue()
        ->and($branch->name)->toBe('Sede Principal')
        ->and(DocumentSeries::where('branch_id', $branch->id)->pluck('type_code')->sort()->values()->all())
        ->toBe(collect(array_keys(DocumentSeries::PER_BRANCH))->sort()->values()->all());

    // Las series no chocan con las de la otra compañía (cada una empieza en 001)
    expect(DocumentSeries::where('branch_id', $branch->id)->where('type_code', 'BOLETA')->value('series'))->toBe('B001');
});

it('el admin lista y edita compañías; la contraseña solo cambia si se escribe', function () {
    $this->actingAs(Admin::first(), 'admin');
    $company = Company::find(1);
    $oldHash = $company->password;

    $this->get(route('admin.companies.index'))->assertOk()->assertSee($company->email);

    $this->put(route('admin.companies.update', $company), ['name' => 'Nuevo nombre', 'email' => $company->email])
        ->assertSessionHasNoErrors();
    expect($company->fresh()->name)->toBe('Nuevo nombre')
        ->and($company->fresh()->password)->toBe($oldHash);

    $this->put(route('admin.companies.update', $company), [
        'name' => 'Nuevo nombre', 'email' => $company->email,
        'password' => 'otraClave99', 'password_confirmation' => 'otraClave99',
    ])->assertSessionHasNoErrors();
    expect(Hash::check('otraClave99', $company->fresh()->password))->toBeTrue();
});

it('no se repite el correo ni el RUC entre compañías', function () {
    $this->actingAs(Admin::first(), 'admin');
    $this->post(route('admin.companies.store'), companyData());

    $this->post(route('admin.companies.store'), companyData(['name' => 'Otra']))
        ->assertSessionHasErrors(['email', 'ruc']);
});

it('solo el admin puede gestionar compañías', function () {
    $this->actingAs(Company::find(1), 'company');

    $this->get(route('admin.companies.index'))->assertRedirect();
    $this->post(route('admin.companies.store'), companyData())->assertRedirect();

    expect(Company::count())->toBe(1);
});

// ── Separación entre compañías ─────────────────────────────────────

it('cada compañía tiene sus propias unidades: no ve ni edita las de otra', function () {
    $other = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secret123']);
    $foreign = Unit::create(['company_id' => $other->id, 'name' => 'Tableta', 'abbreviation' => 'TAB', 'sunat_code' => 'NIU', 'status' => 1]);

    loginEmployee(); // compañía 1

    $this->get(route('employee.units.index'))->assertOk()->assertDontSee('TAB');
    $this->get(route('employee.units.edit', $foreign))->assertForbidden();
    $this->delete(route('employee.units.destroy', $foreign))->assertForbidden();

    // Puede crear su propia "Tableta" aunque la otra compañía ya tenga una
    $this->post(route('employee.units.store'), ['name' => 'Tableta', 'abbreviation' => 'TAB', 'sunat_code' => 'NIU', 'status' => 1])
        ->assertSessionHasNoErrors();
    expect(Unit::where('name', 'Tableta')->pluck('company_id')->sort()->values()->all())->toBe([1, $other->id]);
});

it('dos compañías pueden tener categorías con el mismo nombre', function () {
    $other = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secret123']);
    Category::create(['company_id' => $other->id, 'name' => 'Analgésicos', 'status' => 1]);

    loginEmployee();

    $this->post(route('employee.categories.store'), ['nombre' => 'Analgésicos'])->assertSessionHasNoErrors();
    expect(Category::where('name', 'Analgésicos')->count())->toBe(2);
});

it('un producto no puede usar la categoría ni la unidad de otra compañía', function () {
    $other     = Company::create(['name' => 'Otra', 'email' => 'otra@example.com', 'password' => 'secret123']);
    $category  = Category::create(['company_id' => $other->id, 'name' => 'Ajena', 'status' => 1]);
    $unit      = Unit::create(['company_id' => $other->id, 'name' => 'Ajena', 'abbreviation' => 'AJ', 'sunat_code' => 'NIU', 'status' => 1]);

    loginEmployee();

    $this->post(route('employee.products.store'), [
        'nombre' => 'Producto', 'categoria_id' => $category->id, 'unidad_medida_id' => $unit->id,
        'afectacion_igv' => '10', 'precio_compra' => 1, 'precio_venta_unidad' => 2,
    ])->assertSessionHasErrors(['categoria_id', 'unidad_medida_id']);
});
