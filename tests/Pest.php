<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

use App\Models\BranchStock;
use App\Models\CashRegister;
use App\Models\Order;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Role;

/** Empleado autenticado en el guard employee (administrador de sede = acceso a todo, o con privilegios dados) */
function loginEmployee(?array $privileges = null, int $branchId = 1): Employee
{
    static $n = 0;
    $n++;

    $employee = Employee::create([
        'company_id' => 1, 'branch_id' => $branchId,
        'role_id'    => $privileges === null ? Role::BRANCH_ADMIN : Role::EMPLOYEE,
        'name'       => "Empleado {$n}", 'email' => "empleado{$n}@example.com", 'password' => 'secret',
        'privileges' => $privileges,
    ]);
    test()->actingAs($employee, 'employee');

    return $employee;
}

/** Producto de la compañía 1 con stock en una sede */
function stockProduct(string $code, float $stock = 0, float $purchasePrice = 1, ?float $minimum = null, int $branchId = 1): Product
{
    $product = Product::find($code) ?? new Product([
        'code' => $code, 'company_id' => 1, 'name' => "Producto {$code}",
        'purchase_price' => $purchasePrice, 'unit_sale_price' => $purchasePrice * 2, 'igv_affectation' => '20',
        'employee_id' => 1, 'status' => 1,
    ]);
    $product->save();

    BranchStock::updateOrCreate(
        ['branch_id' => $branchId, 'product_code' => $code],
        ['stock_actual' => $stock, 'stock_minimum' => $minimum]
    );

    return $product;
}

function stockOf(string $code, int $branchId = 1): float
{
    return (float) BranchStock::where('branch_id', $branchId)->where('product_code', $code)->value('stock_actual');
}

/** Abre una caja normal del empleado, con efectivo de apertura y una venta en efectivo */
function openRegister(Employee $employee, float $opening = 100, float $cashSale = 50): CashRegister
{
    $register = CashRegister::create([
        'company_id' => 1, 'branch_id' => $employee->branch_id, 'employee_id' => $employee->id,
        'opening_amount' => $opening, 'status' => 1, 'register_date' => today(),
        'approval_status' => CashRegister::APPROVAL_NORMAL, 'opened_at' => now(),
    ]);

    if ($cashSale > 0) {
        Order::create([
            'company_id' => 1, 'branch_id' => $employee->branch_id, 'employee_id' => $employee->id,
            'cash_register_id' => $register->id, 'voucher_type' => 3, 'voucher_number' => 'NV01-' . random_int(10000000, 99999999),
            'payment_type' => CashRegister::PAYMENT_CASH, 'subtotal' => $cashSale, 'total' => $cashSale, 'status' => 1,
        ]);
    }

    return $register;
}

/** Deja lista una sede para vender: serie de nota de venta, caja abierta y un producto gravado de S/ 10 */
function posSetup(\App\Models\Employee $employee): void
{
    // Correlativos globales (cliente público) y tipos de documento
    test()->seed([Database\Seeders\DocumentSeriesSeeder::class, Database\Seeders\DocumentTypeSeeder::class]);
    \App\Models\DocumentSeries::create([
        'company_id' => 1, 'branch_id' => $employee->branch_id, 'type_code' => \App\Models\DocumentSeries::NOTA_VENTA,
        'name' => 'Nota de Venta', 'series' => 'NV01', 'current_number' => 0, 'digits' => 8, 'active' => true,
    ]);
    openRegister($employee, 100, 0);
    stockProduct('P1', 10, 4);
    App\Models\Product::where('code', 'P1')->update(['igv_affectation' => '10', 'unit_sale_price' => 10]);
}

function posSale(array $extra = []): Illuminate\Testing\TestResponse
{
    return test()->postJson(route('employee.orders.store'), array_merge([
        'items'        => [['code' => 'P1', 'name' => 'Producto P1', 'price' => 10, 'qty' => 2]],
        'payment_type' => 1, 'voucher_type' => 3,
    ], $extra));
}

/** Crea una venta con un solo ítem: neta (sin IGV), con su IGV opcional */
function reportSale(\App\Models\Employee $employee, string $code, float $qty, float $net, array $overrides = []): \App\Models\Order
{
    static $n = 0;
    $n++;

    $createdAt = $overrides['created_at'] ?? null;
    unset($overrides['created_at']);

    $order = \App\Models\Order::create(array_merge([
        'company_id' => 1, 'branch_id' => $employee->branch_id, 'employee_id' => $employee->id,
        'voucher_type' => 3, 'voucher_number' => 'NV01-' . str_pad((string) $n, 8, '0', STR_PAD_LEFT),
        'payment_type' => 1, 'subtotal' => $net, 'igv' => 0, 'total' => $net, 'status' => 1,
    ], $overrides));

    // created_at no es asignable en masa: se fija aparte para simular ventas de otras fechas
    if ($createdAt) {
        $order->forceFill(['created_at' => $createdAt])->save();
    }

    \App\Models\OrderItem::create([
        'order_id' => $order->id, 'product_code' => $code, 'product_name' => "Producto {$code}",
        'unit_price' => $net / $qty, 'quantity' => $qty, 'subtotal' => $net, 'igv_affectation' => '20', 'igv_amount' => 0,
    ]);

    return $order;
}

/** Ingresa un lote directamente (stock total + lote), sin pasar por una compra */
function addBatch(string $code, float $qty, ?string $expires, string $batch, int $branchId = 1): \App\Models\StockBatch
{
    \App\Models\BranchStock::where('branch_id', $branchId)->where('product_code', $code)->increment('stock_actual', $qty);

    return app(\App\Services\BatchService::class)->receive(1, $branchId, $code, $qty, $batch, $expires, 1, 'purchase', null);
}

function remaining(string $batch): float
{
    return \App\Models\StockBatch::where('batch', $batch)->value('quantity_remaining');
}
