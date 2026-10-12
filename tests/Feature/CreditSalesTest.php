<?php

use App\Models\CashRegister;
use App\Models\Client;
use App\Models\CreditPayment;
use App\Models\Employee;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function creditClient(float $limit = 100): Client
{
    static $n = 0;
    $n++;

    return Client::create([
        'company_id' => 1, 'code' => sprintf('C-%06d', $n), 'name' => "Cliente {$n}", 'document_type_id' => 1,
        'document_number' => '4000000' . $n, 'credit_limit' => $limit, 'status' => 1,
    ]);
}

/** Venta de 2 unidades de P1 (total S/ 23.60) a crédito para el cliente dado */
function creditSale(?Client $client, array $extra = [])
{
    return posSale(array_merge([
        'payment_type' => 5, 'total' => 23.60, 'client_id' => $client?->id,
        'customer_name' => $client?->name, 'customer_document' => $client?->document_number,
    ], $extra));
}

it('vende a crédito a un cliente con línea y deja la deuda en la venta', function () {
    posSetup(loginEmployee());
    $client = creditClient(100);

    creditSale($client, ['credit_due_date' => today()->addDays(15)->toDateString()])->assertOk();

    $order = Order::firstOrFail();
    expect($order->client_id)->toBe($client->id)->and($order->payment_type)->toBe(5)
        ->and((float) $order->credit_balance)->toBe(23.6)->and($order->credit_due_date->toDateString())->toBe(today()->addDays(15)->toDateString())
        ->and($client->creditDebt())->toBe(23.6)->and($client->creditAvailable())->toBe(76.4)
        ->and(stockOf('P1'))->toBe(8.0);
});

it('rechaza el fiado sin cliente, al público, sin línea, sobre el tope o en factura', function () {
    posSetup(loginEmployee());

    creditSale(null)->assertStatus(422);                                                       // sin cliente
    creditSale(creditClient(0))->assertStatus(422);                                            // sin línea
    creditSale(creditClient(20))->assertStatus(422)->assertJsonFragment(['message' => 'Supera el crédito disponible de «Cliente 3»: puede usar S/ 20.00 y la venta es de S/ 23.60.']);
    creditSale(creditClient(500), ['voucher_type' => 2, 'document_type_id' => 3, 'customer_document' => '20123456789'])->assertStatus(422); // factura

    expect(Order::count())->toBe(0)->and(stockOf('P1'))->toBe(10.0);
});

it('suma la deuda de varias ventas contra la misma línea', function () {
    posSetup(loginEmployee());
    $client = creditClient(40);

    creditSale($client)->assertOk();                  // 23.60 de 40
    creditSale($client)->assertStatus(422);           // otros 23.60 superan lo disponible (16.40)

    expect(Order::count())->toBe(1);
});

it('el abono se reparte entre las ventas más antiguas y suma a la caja si es efectivo', function () {
    $employee = loginEmployee();
    posSetup($employee);
    $client = creditClient(200);
    creditSale($client, ['credit_due_date' => today()->addDays(5)->toDateString()])->assertOk();
    creditSale($client, ['credit_due_date' => today()->addDays(40)->toDateString()])->assertOk();
    $register = CashRegister::firstOrFail();
    $before   = $register->expectedCash();

    $this->post(route('employee.receivables.pay', $client), ['amount' => 30, 'method' => 1, 'paid_at' => today()->toDateString()])->assertSessionHasNoErrors();

    $orders = Order::orderBy('credit_due_date')->get();
    expect((float) $orders[0]->credit_balance)->toBe(0.0)           // la primera se salda (23.60)
        ->and((float) $orders[1]->credit_balance)->toBe(17.2)       // el resto a la segunda
        ->and(CreditPayment::count())->toBe(2)
        ->and($client->creditDebt())->toBe(17.2)
        ->and($register->expectedCash())->toBe(round($before + 30, 2));
});

it('un abono por transferencia no mueve el efectivo de la caja', function () {
    $employee = loginEmployee();
    posSetup($employee);
    $client = creditClient(100);
    creditSale($client)->assertOk();
    $register = CashRegister::firstOrFail();
    $before   = $register->expectedCash();

    $this->post(route('employee.receivables.pay', $client), ['amount' => 10, 'method' => 3, 'paid_at' => today()->toDateString(), 'reference' => 'OP-9'])->assertSessionHasNoErrors();

    expect($register->expectedCash())->toBe($before)->and($client->creditDebt())->toBe(13.6);
});

it('no permite abonar más de la deuda ni en efectivo sin caja abierta', function () {
    $employee = loginEmployee();
    posSetup($employee);
    $client = creditClient(100);
    creditSale($client)->assertOk();

    $this->post(route('employee.receivables.pay', $client), ['amount' => 50, 'method' => 3, 'paid_at' => today()->toDateString()])->assertSessionHas('error');
    expect(CreditPayment::count())->toBe(0);

    CashRegister::query()->update(['status' => 0]);       // cierra la caja
    $this->post(route('employee.receivables.pay', $client), ['amount' => 5, 'method' => 1, 'paid_at' => today()->toDateString()])->assertSessionHas('error');
    expect(CreditPayment::count())->toBe(0)->and($client->creditDebt())->toBe(23.6);
});

it('las pantallas de cuentas por cobrar muestran la deuda', function () {
    posSetup(loginEmployee());
    $client = creditClient(100);
    creditSale($client)->assertOk();

    $index = $this->get(route('employee.receivables.index'))->assertOk()->assertSee($client->name);
    expect($index->viewData('totalDebt'))->toBe(23.6);
    $this->get(route('employee.receivables.show', $client))->assertOk()->assertSee('Registrar abono');
});

it('anular la venta a crédito con una nota de crédito cancela la deuda', function () {
    posSetup(loginEmployee());
    $client = creditClient(100);
    creditSale($client)->assertOk();
    $order = Order::firstOrFail();

    // Lo que hace CreditNoteService al anular
    $order->update(['status' => 0, 'credit_balance' => 0]);

    expect($client->creditDebt())->toBe(0.0);
    expect($this->get(route('employee.receivables.index'))->viewData('totalDebt'))->toBe(0.0);
});

it('las ventas a crédito no se editan y el crédito exige privilegio para cobrar', function () {
    $employee = loginEmployee();
    posSetup($employee);
    creditSale($client = creditClient(100))->assertOk();
    $order = Order::firstOrFail();
    CashRegister::query()->update(['approval_status' => CashRegister::APPROVAL_PENDING]);

    $this->putJson(route('employee.orders.update-historical', $order), [
        'items' => [['code' => 'P1', 'name' => 'x', 'price' => 10, 'qty' => 1]], 'payment_type' => 1, 'total' => 11.8,
    ])->assertStatus(422);

    $this->actingAs(Employee::create([
        'company_id' => 1, 'branch_id' => 1, 'role_id' => App\Models\Role::EMPLOYEE, 'name' => 'Cajero', 'email' => 'c@example.com',
        'password' => 'x', 'privileges' => [Employee::PRIV_VER_VENTAS],
    ]), 'employee');
    $this->get(route('employee.receivables.index'))->assertRedirect(route('employee.home'));
});

it('el cliente guarda su línea de crédito', function () {
    loginEmployee();
    $this->seed(Database\Seeders\DocumentSeriesSeeder::class);   // correlativo de clientes (C-000001)

    $this->post(route('employee.clients.store'), ['name' => 'Doña Rosa', 'credit_limit' => 150])->assertSessionHasNoErrors();

    expect((float) Client::where('name', 'Doña Rosa')->value('credit_limit'))->toBe(150.0);
});
