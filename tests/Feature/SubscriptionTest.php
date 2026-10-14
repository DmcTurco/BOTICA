<?php

use App\Models\Admin;
use App\Models\Company;
use App\Models\SubscriptionPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Fecha fija: los estados dependen de "hoy". Aviso 5 días antes y 5 días de gracia
    $this->travelTo(Carbon::parse('2026-10-15 10:00:00'));
    config(['billing.warning_days' => 5, 'billing.grace_days' => 5]);
});

function companyPaidUntil(?string $date, float $fee = 80, string $cycle = Company::CYCLE_DATE, ?string $trialUntil = null): Company
{
    $company = Company::find(1);
    $company->update(['paid_until' => $date, 'monthly_fee' => $fee, 'billing_cycle' => $cycle, 'trial_until' => $trialUntil]);

    return $company->fresh();
}

function payment(Company $company, array $data = []): \Illuminate\Testing\TestResponse
{
    test()->actingAs(Admin::first(), 'admin');

    return test()->post(route('admin.subscriptions.payments.store', $company), array_merge([
        'months' => 1, 'amount' => 80, 'paid_at' => today()->toDateString(), 'method' => 'yape', 'operation_number' => '123456',
    ], $data));
}

function configurePlan(Company $company, array $data): \Illuminate\Testing\TestResponse
{
    test()->actingAs(Admin::first(), 'admin');

    return test()->put(route('admin.subscriptions.update', $company), $data);
}

// ── Estados ────────────────────────────────────────────────────────

it('calcula el estado de la mensualidad según la fecha', function (?string $paidUntil, string $expected) {
    expect(companyPaidUntil($paidUntil)->subscriptionStatus())->toBe($expected);
})->with([
    'sin fecha: gratis'             => [null, Company::SUB_EXEMPT],
    'faltan 10 días: al día'        => ['2026-10-25', Company::SUB_ACTIVE],
    'faltan 5 días: por vencer'     => ['2026-10-20', Company::SUB_DUE_SOON],
    'vence hoy: por vencer'         => ['2026-10-15', Company::SUB_DUE_SOON],
    'venció ayer: vencida'          => ['2026-10-14', Company::SUB_OVERDUE],
    'último día de gracia: vencida' => ['2026-10-10', Company::SUB_OVERDUE],
    'pasó la gracia: suspendida'    => ['2026-10-09', Company::SUB_SUSPENDED],
]);

// ── Empresas nacen gratis; el cobro se configura en Suscripciones ──

it('una empresa nueva nace gratis hasta que se le configure el cobro', function () {
    $this->actingAs(Admin::first(), 'admin');

    $this->post(route('admin.companies.store'), [
        'name' => 'Botica Nueva', 'email' => 'nueva@example.com', 'password' => 'secreto123', 'password_confirmation' => 'secreto123',
        'branch_name' => 'Sede Principal',
    ])->assertSessionHasNoErrors();

    $company = Company::where('email', 'nueva@example.com')->sole();
    expect($company->paid_until)->toBeNull()
        ->and($company->subscriptionStatus())->toBe(Company::SUB_EXEMPT);
});

it('activar el cobro "el mismo día": prueba desde hoy hasta el mismo día del mes siguiente', function () {
    $company = companyPaidUntil(null);

    // La pantalla sugiere 15/11 (mismo día del mes siguiente) para ese ciclo
    $this->actingAs(Admin::first(), 'admin');
    $this->get(route('admin.subscriptions.show', $company))->assertOk()->assertSee('2026-11-15');

    configurePlan($company, ['plan' => 'monthly', 'monthly_fee' => 80, 'billing_cycle' => 'date', 'covered_until' => '2026-11-15'])
        ->assertSessionHasNoErrors();

    $company->refresh();
    expect($company->trial_until->toDateString())->toBe('2026-11-15')
        ->and($company->paid_until->toDateString())->toBe('2026-11-15')
        ->and($company->subscriptionStatus())->toBe(Company::SUB_TRIAL);

    // Primer pago: cubre del 16/11 al 15/12 (paga cada día 15)
    payment($company);
    expect($company->fresh()->paid_until->toDateString())->toBe('2026-12-15');
});

it('activar el cobro "a fin de mes": prueba hasta fin de mes y luego paga cada fin de mes', function () {
    $company = companyPaidUntil(null);

    $this->actingAs(Admin::first(), 'admin');
    $this->get(route('admin.subscriptions.show', $company))->assertSee('2026-10-31');

    configurePlan($company, ['plan' => 'monthly', 'monthly_fee' => 80, 'billing_cycle' => 'month_end', 'covered_until' => '2026-10-31'])
        ->assertSessionHasNoErrors();
    expect($company->fresh()->paid_until->toDateString())->toBe('2026-10-31');

    payment($company);
    expect($company->fresh()->paid_until->toDateString())->toBe('2026-11-30');

    payment($company);
    expect($company->fresh()->paid_until->toDateString())->toBe('2026-12-31');
});

it('al activar el cobro, la prueba no puede terminar antes de hoy', function () {
    configurePlan(companyPaidUntil(null), ['plan' => 'monthly', 'monthly_fee' => 80, 'billing_cycle' => 'date', 'covered_until' => '2026-10-01'])
        ->assertSessionHasErrors('covered_until');
});

it('con cobro activo se puede cambiar el monto, corregir la fecha o pasarla a gratis', function () {
    $company = companyPaidUntil('2026-11-15', trialUntil: '2026-11-15');

    configurePlan($company, ['plan' => 'monthly', 'monthly_fee' => 100, 'billing_cycle' => 'date', 'covered_until' => '2026-11-20'])
        ->assertSessionHasNoErrors();
    $company->refresh();
    expect((float) $company->monthly_fee)->toBe(100.0)
        ->and($company->paid_until->toDateString())->toBe('2026-11-20')
        ->and($company->trial_until->toDateString())->toBe('2026-11-15'); // la prueba original no cambia

    configurePlan($company, ['plan' => 'free'])->assertSessionHasNoErrors();
    expect($company->fresh()->subscriptionStatus())->toBe(Company::SUB_EXEMPT);
});

it('a una empresa gratis no se le registran pagos', function () {
    $company = companyPaidUntil(null);

    payment($company)->assertSessionHas('error');

    expect(SubscriptionPayment::count())->toBe(0)
        ->and($company->fresh()->paid_until)->toBeNull();
});

// ── Avisos y bloqueo ───────────────────────────────────────────────

it('muestra el aviso de vencimiento a la empresa y a sus empleados', function () {
    companyPaidUntil('2026-10-18');

    $this->actingAs(Company::find(1), 'company');
    $this->get(route('company.home'))->assertOk()->assertSee('vence en')->assertSee('3 días', false);

    loginEmployee();
    $this->get(route('employee.home'))->assertOk()->assertSee('vence en');
});

it('avisa que la prueba termina y luego que terminó', function () {
    companyPaidUntil('2026-10-18', trialUntil: '2026-10-18');
    $this->actingAs(Company::find(1), 'company');
    $this->get(route('company.home'))->assertSee('El periodo de prueba gratis termina en');

    // Se vuelve a autenticar con la empresa recargada (en la app cada petición la lee de la BD)
    $this->actingAs(companyPaidUntil('2026-10-12', trialUntil: '2026-10-12'), 'company');
    $this->get(route('company.home'))->assertSee('El periodo de prueba gratis terminó el 12/10/2026');
});

it('lejos del fin de la prueba no muestra aviso', function () {
    companyPaidUntil('2026-11-15', trialUntil: '2026-11-15');
    $this->actingAs(Company::find(1), 'company');

    $this->get(route('company.home'))->assertOk()->assertDontSee('periodo de prueba');
});

it('con la mensualidad vencida en gracia sigue funcionando con aviso rojo', function () {
    companyPaidUntil('2026-10-12');

    $this->actingAs(Company::find(1), 'company');
    $this->get(route('company.home'))->assertOk()->assertSee('venció el 12/10/2026')->assertSee('18/10/2026');
});

it('pasada la gracia bloquea a la empresa y a sus empleados', function () {
    companyPaidUntil('2026-10-01');

    $this->actingAs(Company::find(1), 'company');
    $this->get(route('company.home'))->assertRedirect(route('company.suspended'));
    $this->get(route('company.suspended'))->assertOk()->assertSee('Servicio suspendido');

    loginEmployee();
    $this->get(route('employee.home'))->assertRedirect(route('employee.suspended'));
    $this->get(route('employee.suspended'))->assertOk()->assertSee('Avisa al responsable');

    // Una venta enviada por AJAX recibe el mensaje en JSON
    $this->postJson(route('employee.orders.store'), [])->assertStatus(402)->assertJsonPath('success', false);
});

it('la pantalla de suspendido devuelve al inicio si ya está al día', function () {
    companyPaidUntil('2026-11-15');

    $this->actingAs(Company::find(1), 'company');
    $this->get(route('company.suspended'))->assertRedirect(route('company.home'));
});

it('una empresa gratis nunca se bloquea ni ve avisos', function () {
    companyPaidUntil(null);

    $this->actingAs(Company::find(1), 'company');
    $this->get(route('company.home'))->assertOk()->assertDontSee('mensualidad del servicio');
});

// ── Pagos ──────────────────────────────────────────────────────────

it('registrar un pago extiende la fecha desde el vencimiento actual', function () {
    $company = companyPaidUntil('2026-10-20');

    payment($company, ['months' => 2, 'amount' => 160])->assertSessionHasNoErrors();

    $payment = SubscriptionPayment::sole();
    expect($company->fresh()->paid_until->toDateString())->toBe('2026-12-20')
        ->and($payment->period_from->toDateString())->toBe('2026-10-21')
        ->and($payment->period_to->toDateString())->toBe('2026-12-20')
        ->and($payment->admin_id)->toBe(Admin::first()->id);
});

it('un pago atrasado cubre primero los meses que debía y desbloquea', function () {
    $company = companyPaidUntil('2026-09-30'); // bloqueada

    payment($company)->assertSessionHasNoErrors();

    $company->refresh();
    expect($company->paid_until->toDateString())->toBe('2026-10-30')
        ->and($company->hasActiveSubscription())->toBeTrue();

    $this->actingAs($company, 'company');
    $this->get(route('company.home'))->assertOk();
});

it('el primer pago tras la prueba deja de ser prueba', function () {
    $company = companyPaidUntil('2026-10-20', trialUntil: '2026-10-20');
    expect($company->subscriptionStatus())->toBe(Company::SUB_TRIAL);

    payment($company);

    expect($company->fresh()->subscriptionStatus())->toBe(Company::SUB_ACTIVE);
});

it('cobrando a fin de mes, cada pago vence el último día del mes', function (string $paidUntil, int $months, string $expected) {
    $company = companyPaidUntil($paidUntil, cycle: Company::CYCLE_MONTH_END);

    payment($company, ['months' => $months, 'amount' => 80 * $months]);

    expect($company->fresh()->paid_until->toDateString())->toBe($expected);
})->with([
    'tras una prueba a mitad de mes: completa el mes siguiente' => ['2026-10-20', 1, '2026-11-30'],
    'de fin de mes a fin de mes'                                 => ['2026-11-30', 1, '2026-12-31'],
    'de enero a febrero (mes corto)'                              => ['2027-01-31', 1, '2027-02-28'],
    'tres meses'                                                  => ['2026-10-31', 3, '2027-01-31'],
]);

it('solo se puede anular el último pago y la fecha vuelve atrás', function () {
    $company = companyPaidUntil('2026-10-20');
    payment($company);
    payment($company);
    [$first, $last] = SubscriptionPayment::orderBy('id')->get()->all();

    $this->delete(route('admin.subscriptions.payments.destroy', [$company, $first]))->assertSessionHas('error');
    expect(SubscriptionPayment::count())->toBe(2);

    $this->delete(route('admin.subscriptions.payments.destroy', [$company, $last]))->assertSessionHas('success');
    expect(SubscriptionPayment::count())->toBe(1)
        ->and($company->fresh()->paid_until->toDateString())->toBe('2026-11-20');
});

it('valida los datos del pago', function () {
    $company = companyPaidUntil('2026-10-20');

    payment($company, ['months' => 0, 'method' => 'bitcoin', 'paid_at' => '2026-12-01'])
        ->assertSessionHasErrors(['months', 'method', 'paid_at']);

    expect(SubscriptionPayment::count())->toBe(0);
});

it('solo el admin registra pagos y configura planes', function () {
    $company = companyPaidUntil('2026-10-20');
    $this->actingAs($company, 'company');

    $this->post(route('admin.subscriptions.payments.store', $company), ['months' => 12, 'amount' => 0, 'paid_at' => '2026-10-15', 'method' => 'yape'])
        ->assertRedirect();
    $this->put(route('admin.subscriptions.update', $company), ['plan' => 'free'])->assertRedirect();

    expect(SubscriptionPayment::count())->toBe(0)
        ->and($company->fresh()->paid_until)->not->toBeNull();
});

// ── Pantallas del admin ────────────────────────────────────────────

it('la lista de suscripciones muestra primero las que deben y filtra por estado', function () {
    companyPaidUntil('2026-10-01'); // bloqueada
    Company::create(['name' => 'Botica Gratis', 'email' => 'g@example.com', 'password' => 'secret123']);
    $this->actingAs(Admin::first(), 'admin');

    $this->get(route('admin.subscriptions.index'))
        ->assertOk()
        ->assertSeeInOrder(['Company Name', 'Botica Gratis'])
        ->assertSee('Registrar pago');

    $this->get(route('admin.subscriptions.index', ['estado' => Company::SUB_EXEMPT]))
        ->assertSee('Botica Gratis')->assertDontSee('Company Name');
});

it('la pantalla de la suscripción muestra hasta qué fecha cubre el próximo pago', function () {
    $company = companyPaidUntil('2026-10-20', cycle: Company::CYCLE_MONTH_END);
    $this->actingAs(Admin::first(), 'admin');

    $this->get(route('admin.subscriptions.show', $company))
        ->assertOk()
        ->assertSee('21/10/2026')
        ->assertSee('30/11/2026');
});

it('el inicio del admin lista las empresas vencidas y lo cobrado en el mes', function () {
    $company = companyPaidUntil('2026-10-01');
    $this->actingAs(Admin::first(), 'admin');

    $this->get(route('admin.home'))->assertOk()->assertSee('Mensualidades vencidas')->assertSee($company->name);

    payment($company, ['amount' => 80]);
    $this->get(route('admin.home'))->assertSee('S/ 80.00');
});
