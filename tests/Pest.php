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
