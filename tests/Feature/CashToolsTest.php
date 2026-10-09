<?php

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\DocumentSeries;
use App\Models\Employee;
use App\Models\ExchangeRate;
use App\Models\LostSale;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Gastos e ingresos ─────────────────────────────────────────

it('el efectivo esperado suma ingresos y resta gastos', function () {
    $employee = loginEmployee();
    $register = openRegister($employee, 100, 50); // 150 en caja

    $this->post(route('employee.cash-movements.store'), ['type' => 'income', 'concept' => 'Sencillo', 'amount' => 20])->assertSessionHasNoErrors();
    $this->post(route('employee.cash-movements.store'), ['type' => 'expense', 'concept' => 'Flete', 'amount' => 35.5])->assertSessionHasNoErrors();

    expect($register->expectedCash())->toBe(134.5); // 100 + 50 + 20 − 35.50
});

it('no permite un gasto mayor al efectivo disponible', function () {
    $employee = loginEmployee();
    $register = openRegister($employee, 10, 0);

    $this->post(route('employee.cash-movements.store'), ['type' => 'expense', 'concept' => 'Compra grande', 'amount' => 11])
        ->assertSessionHas('error');

    expect(CashMovement::count())->toBe(0)->and($register->expectedCash())->toBe(10.0);
});

it('exige caja abierta para registrar movimientos', function () {
    loginEmployee();

    $this->post(route('employee.cash-movements.store'), ['type' => 'income', 'concept' => 'x', 'amount' => 5])->assertSessionHas('error');

    expect(CashMovement::count())->toBe(0);
});

it('anular un movimiento lo saca del efectivo esperado', function () {
    $employee = loginEmployee();
    $register = openRegister($employee, 100, 0);

    $this->post(route('employee.cash-movements.store'), ['type' => 'expense', 'concept' => 'Taxi', 'amount' => 30]);
    expect($register->expectedCash())->toBe(70.0);

    $this->post(route('employee.cash-movements.void', CashMovement::first()))->assertSessionHasNoErrors();

    expect($register->expectedCash())->toBe(100.0);
});

it('cada tipo de movimiento exige su privilegio', function () {
    $employee = loginEmployee([Employee::PRIV_GASTOS_DIA]);
    openRegister($employee, 100, 0);

    $this->post(route('employee.cash-movements.store'), ['type' => 'expense', 'concept' => 'Taxi', 'amount' => 5])->assertSessionHasNoErrors();
    $this->post(route('employee.cash-movements.store'), ['type' => 'income', 'concept' => 'Extra', 'amount' => 5])->assertForbidden();

    expect(CashMovement::count())->toBe(1);
});

it('el cierre de caja usa el efectivo esperado con gastos e ingresos', function () {
    $employee = loginEmployee();
    $register = openRegister($employee, 100, 50);
    $this->post(route('employee.cash-movements.store'), ['type' => 'expense', 'concept' => 'Flete', 'amount' => 40]);

    $this->post(route('employee.cash-register.close'), ['closing_amount' => 110])->assertSessionHasNoErrors();

    $register->refresh();
    expect((float) $register->expected_amount)->toBe(110.0)->and((float) $register->difference)->toBe(0.0);
});

// ── Cierres de caja ───────────────────────────────────────────

it('lista y muestra las cajas de la sede e imprime el movimiento del día', function () {
    $employee = loginEmployee();
    $register = openRegister($employee, 100, 50);
    $this->post(route('employee.cash-movements.store'), ['type' => 'expense', 'concept' => 'Flete de prueba', 'amount' => 10]);

    $this->get(route('employee.cash-closures.index'))->assertOk()->assertSee('#' . $register->id);
    $this->get(route('employee.cash-closures.show', $register))->assertOk()->assertSee('Flete de prueba');
    $this->get(route('employee.cash-closures.print', ['fecha' => today()->toDateString()]))->assertOk()->assertSee('Flete de prueba');
});

it('no deja ver cajas de otra sede', function () {
    $employee = loginEmployee();
    $other = App\Models\Branch::create(['company_id' => 1, 'name' => 'Otra', 'status' => 1]);
    $foreign = CashRegister::create([
        'company_id' => 1, 'branch_id' => $other->id, 'employee_id' => $employee->id, 'opening_amount' => 1,
        'status' => 1, 'register_date' => today(), 'approval_status' => 0, 'opened_at' => now(),
    ]);

    $this->get(route('employee.cash-closures.show', $foreign))->assertForbidden();
});

// ── Ventas perdidas ───────────────────────────────────────────

it('registra ventas perdidas y arma el ranking', function () {
    loginEmployee();
    stockProduct('P1', 0);

    foreach ([1, 2] as $_) {
        $this->post(route('employee.lost-sales.store'), ['product_name' => 'Amoxicilina 500mg', 'quantity' => 2, 'reason' => 'no_stock'])->assertSessionHasNoErrors();
    }
    $this->post(route('employee.lost-sales.store'), ['product_name' => 'Producto P1', 'product_code' => 'P1', 'quantity' => 1, 'reason' => 'price'])->assertSessionHasNoErrors();

    expect(LostSale::count())->toBe(3);
    $this->get(route('employee.lost-sales.index'))->assertOk()->assertSee('Amoxicilina 500mg')->assertSee('2×');
});

// ── Tipo de cambio ────────────────────────────────────────────

it('guarda el tipo de cambio una sola vez por día y valida compra ≤ venta', function () {
    loginEmployee();

    $this->post(route('employee.exchange-rates.store'), ['rate_date' => today()->toDateString(), 'buy_rate' => 3.70, 'sell_rate' => 3.75])->assertSessionHasNoErrors();
    $this->post(route('employee.exchange-rates.store'), ['rate_date' => today()->toDateString(), 'buy_rate' => 3.71, 'sell_rate' => 3.76])->assertSessionHasNoErrors();
    $this->post(route('employee.exchange-rates.store'), ['rate_date' => today()->toDateString(), 'buy_rate' => 3.80, 'sell_rate' => 3.70])->assertSessionHasErrors('sell_rate');

    expect(ExchangeRate::count())->toBe(1)->and(ExchangeRate::first()->sell_rate)->toBe(3.76);
    $this->get(route('employee.exchange-rates.index'))->assertOk();
});

// ── Correlativos ──────────────────────────────────────────────

it('el correlativo avanza pero no retrocede, y siempre queda una serie activa', function () {
    $employee = loginEmployee();
    $series = DocumentSeries::create([
        'company_id' => 1, 'branch_id' => $employee->branch_id, 'type_code' => DocumentSeries::BOLETA,
        'name' => 'Boleta', 'series' => 'B001', 'current_number' => 10, 'digits' => 8, 'active' => true,
    ]);

    $this->put(route('employee.series.update', $series), ['current_number' => 50, 'active' => 1])->assertSessionHasNoErrors();
    expect($series->fresh()->current_number)->toBe(50);

    $this->put(route('employee.series.update', $series), ['current_number' => 20, 'active' => 1])->assertSessionHasErrors('current_number');
    expect($series->fresh()->current_number)->toBe(50);

    $this->put(route('employee.series.update', $series), ['current_number' => 50, 'active' => 0])->assertSessionHas('error');
    expect($series->fresh()->active)->toBeTrue();
});
