<?php

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Cajero de la sede 1 (sin privilegios de caja) */
function cashier(): Employee
{
    return Employee::create([
        'company_id' => 1, 'branch_id' => 1, 'role_id' => App\Models\Role::EMPLOYEE,
        'name' => 'Cajero Turno', 'email' => 'cajero' . random_int(1000, 9999) . '@example.com', 'password' => 'secret', 'privileges' => [],
    ]);
}

it('el administrador de sede corrige la apertura de la caja abierta de un empleado', function () {
    $cashier = cashier();
    $register = openRegister($cashier, 100, 0);
    loginEmployee(); // administrador de sede

    $this->get(route('employee.cash-closures.opening.edit', $register))->assertOk()->assertSee('Cajero Turno');

    $this->put(route('employee.cash-closures.opening.update', $register), ['den_100' => 1, 'den_20' => 2, 'den_5' => 1, 'notes' => 'Se contó mal'])
        ->assertRedirect(route('employee.cash-closures.show', $register));

    expect((float) $register->fresh()->opening_amount)->toBe(145.0)
        ->and($register->fresh()->notes)->toBe('Se contó mal')
        ->and(AuditLog::where('action', 'cash.opening_edit')->count())->toBe(1);
});

it('un empleado sin permiso no puede corregir la apertura de otro', function () {
    $register = openRegister(cashier(), 100, 0);
    loginEmployee(['editar_apertura', 'ver_cierres_caja']); // aun con permisos, no es administrador de sede

    $this->get(route('employee.cash-closures.opening.edit', $register))->assertForbidden();
    $this->put(route('employee.cash-closures.opening.update', $register), ['den_100' => 5])->assertForbidden();
    expect((float) $register->fresh()->opening_amount)->toBe(100.0);
});

it('no se corrige la apertura de una caja cerrada ni de otra sede', function () {
    $closed = openRegister(cashier(), 100, 0);
    $closed->update(['status' => 0]);
    $other = Branch::create(['company_id' => 1, 'name' => 'Otra', 'status' => 1]);
    $foreign = CashRegister::create([
        'company_id' => 1, 'branch_id' => $other->id, 'employee_id' => cashier()->id, 'opening_amount' => 1,
        'status' => 1, 'register_date' => today(), 'approval_status' => 0, 'opened_at' => now(),
    ]);
    loginEmployee();

    $this->get(route('employee.cash-closures.opening.edit', $closed))->assertForbidden();
    $this->get(route('employee.cash-closures.opening.edit', $foreign))->assertForbidden();
});
