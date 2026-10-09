<?php

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Purchase;
use App\Services\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Bitácora ──────────────────────────────────────────────────

it('registra quién anuló una compra, ajustó stock y cambió precios', function () {
    $employee = loginEmployee();
    stockProduct('P1', 0, 2);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [['product_code' => 'P1', 'quantity' => 5, 'unit_cost' => 2]],
    ]);
    $this->post(route('employee.purchases.void', Purchase::firstOrFail()), ['void_reason' => 'Se duplicó']);
    $this->post(route('employee.adjustments.store'), ['product_code' => 'P1', 'mode' => 'add', 'quantity' => 3, 'reason' => 'conteo']);
    $this->put(route('employee.prices.update'), ['prices' => ['P1' => ['purchase_price' => 2, 'unit_sale_price' => 9]]]);

    $actions = AuditLog::orderBy('id')->pluck('action')->all();
    expect($actions)->toBe(['purchase.void', 'stock.adjust', 'price.update']);

    $log = AuditLog::where('action', 'purchase.void')->first();
    expect($log->employee_id)->toBe($employee->id)->and($log->employee_name)->toBe($employee->name)
        ->and($log->details['motivo'])->toBe('Se duplicó')->and($log->subject)->toBe('Compra #' . Purchase::first()->id);
});

it('registra descuentos, ventas a crédito y cambios de privilegios', function () {
    $admin = loginEmployee();
    posSetup($admin);
    $client = App\Models\Client::create(['company_id' => 1, 'code' => 'C-900001', 'name' => 'Cliente Fiado', 'credit_limit' => 100, 'status' => 1]);

    posSale(['discount_percent' => 10, 'total' => 21.24])->assertOk();
    posSale(['payment_type' => 5, 'client_id' => $client->id, 'total' => 23.60])->assertOk();

    $worker = Employee::create(['company_id' => 1, 'branch_id' => 1, 'role_id' => App\Models\Role::EMPLOYEE, 'name' => 'Cajera', 'email' => 'cajera@example.com', 'password' => 'x', 'privileges' => []]);
    $this->put(route('employee.employees.update', $worker), [
        'name' => 'Cajera', 'email' => 'cajera@example.com', 'role_id' => App\Models\Role::EMPLOYEE, 'privileges' => [Employee::PRIV_VER_VENTAS],
    ]);

    expect(AuditLog::pluck('action')->all())->toContain('sale.discount', 'sale.credit', 'employee.update');
});

it('la pantalla de bitácora filtra por grupo, empleado y texto', function () {
    $employee = loginEmployee();
    AuditLog::record('price.update', 'Cambió 3 precio(s) de productos');
    AuditLog::record('purchase.void', 'Anuló la compra #7');

    $this->get(route('employee.audit.index'))->assertOk()->assertSee('Anuló la compra #7')->assertSee($employee->name);
    $this->get(route('employee.audit.index', ['grupo' => 'Precios']))->assertSee('Cambió 3 precio')->assertDontSee('Anuló la compra');
    $this->get(route('employee.audit.index', ['buscar' => 'compra']))->assertSee('Anuló la compra')->assertDontSee('Cambió 3 precio');
});

it('la bitácora no mezcla empresas y exige privilegio', function () {
    loginEmployee();
    AuditLog::create(['company_id' => 99, 'action' => 'price.update', 'description' => 'Acción de otra empresa']);

    $this->get(route('employee.audit.index'))->assertOk()->assertDontSee('otra empresa');

    loginEmployee([Employee::PRIV_VER_VENTAS]);
    $this->get(route('employee.audit.index'))->assertRedirect(route('employee.home'));
});

it('un fallo al escribir la bitácora no rompe la operación', function () {
    loginEmployee();
    Illuminate\Support\Facades\Schema::drop('audit_logs');

    AuditLog::record('price.update', 'No debería lanzar excepción');

    expect(true)->toBeTrue();
});

// ── Respaldo ──────────────────────────────────────────────────

it('arma el comando de pg_dump con la conexión y sin la contraseña en la línea de comandos', function () {
    config(['backup.pg_dump' => '/usr/bin/pg_dump']);

    $cmd = (new DatabaseBackup())->postgresCommand(['host' => 'db', 'port' => 5432, 'username' => 'botica', 'password' => 'secreto', 'database' => 'farmacia'], '/tmp/x.dump');

    expect($cmd)->toBe(['/usr/bin/pg_dump', '--host', 'db', '--port', '5432', '--username', 'botica', '--format', 'custom', '--no-owner', '--file', '/tmp/x.dump', 'farmacia'])
        ->and(implode(' ', $cmd))->not->toContain('secreto');
});

it('respalda una base SQLite copiando el archivo y borra los respaldos viejos', function () {
    $dir = sys_get_temp_dir() . '/botica-backup-test-' . uniqid();
    $db  = $dir . '/live.sqlite';
    mkdir($dir);
    file_put_contents($db, 'datos');
    config(['backup.path' => $dir . '/out', 'backup.keep_days' => 7, 'database.default' => 'sqlite', 'database.connections.sqlite.database' => $db]);

    $backup = new DatabaseBackup();
    $file   = $backup->run();
    expect(file_get_contents($file))->toBe('datos');

    // Un respaldo de hace 30 días se elimina; el nuevo se conserva
    $old = $dir . '/out/botica-vieja.sqlite';
    file_put_contents($old, 'x');
    touch($old, now()->subDays(30)->getTimestamp());

    expect($backup->prune())->toBe(1)->and(file_exists($old))->toBeFalse()->and(file_exists($file))->toBeTrue();

    array_map('unlink', glob($dir . '/out/*'));
    @rmdir($dir . '/out');
    @unlink($db);
    @rmdir($dir);
});

it('no respalda una base en memoria y el comando avisa del error', function () {
    config(['backup.path' => sys_get_temp_dir() . '/botica-backup-mem']);

    $this->artisan('db:backup')->expectsOutputToContain('no es un archivo')->assertExitCode(1);
});
