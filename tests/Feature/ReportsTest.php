<?php

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PriceChange;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/** Crea una venta con un solo ítem: neta (sin IGV), con su IGV opcional */
function reportSale(Employee $employee, string $code, float $qty, float $net, array $overrides = []): Order
{
    static $n = 0;
    $n++;

    $createdAt = $overrides['created_at'] ?? null;
    unset($overrides['created_at']);

    $order = Order::create(array_merge([
        'company_id' => 1, 'branch_id' => $employee->branch_id, 'employee_id' => $employee->id,
        'voucher_type' => 3, 'voucher_number' => 'NV01-' . str_pad((string) $n, 8, '0', STR_PAD_LEFT),
        'payment_type' => 1, 'subtotal' => $net, 'igv' => 0, 'total' => $net, 'status' => 1,
    ], $overrides));

    // created_at no es asignable en masa: se fija aparte para simular ventas de otras fechas
    if ($createdAt) {
        $order->forceFill(['created_at' => $createdAt])->save();
    }

    OrderItem::create([
        'order_id' => $order->id, 'product_code' => $code, 'product_name' => "Producto {$code}",
        'unit_price' => $net / $qty, 'quantity' => $qty, 'subtotal' => $net, 'igv_affectation' => '20', 'igv_amount' => 0,
    ]);

    return $order;
}

// ── Ventas ────────────────────────────────────────────────────

it('el récord mensual suma las ventas del mes y excluye las anuladas', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);
    reportSale($employee, 'A', 2, 100);
    reportSale($employee, 'A', 1, 50);
    reportSale($employee, 'A', 1, 999, ['status' => 0]);                       // anulada
    reportSale($employee, 'A', 1, 777, ['created_at' => now()->subMonths(2)]); // otro mes

    $response = $this->get(route('employee.reports.monthly'))->assertOk();

    expect($response->viewData('totals'))->toMatchArray(['count' => 2, 'total' => 150.0]);
});

it('el récord general agrupa por mes de un año', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);
    reportSale($employee, 'A', 1, 40, ['created_at' => now()->setDate(now()->year, 3, 10)]);
    reportSale($employee, 'A', 1, 60, ['created_at' => now()->setDate(now()->year, 3, 20)]);
    reportSale($employee, 'A', 1, 25, ['created_at' => now()->setDate(now()->year, 7, 1)]);

    $months = $this->get(route('employee.reports.general', ['anio' => now()->year]))->assertOk()->viewData('months');

    expect($months[2]['total'])->toBe(100.0)->and($months[6]['total'])->toBe(25.0)->and($months[0]['count'])->toBe(0);
});

it('lista los documentos del mes con filtro por comprobante', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);
    reportSale($employee, 'A', 1, 30, ['voucher_type' => 3]);
    reportSale($employee, 'A', 1, 70, ['voucher_type' => 1]);

    $this->get(route('employee.reports.documents'))->assertOk()->assertSee('NV01-');
    $totals = $this->get(route('employee.reports.documents', ['tipo' => 1]))->viewData('totals');

    expect($totals['count'])->toBe(1)->and($totals['total'])->toBe(70.0);
});

it('el reporte por fechas separa comprobantes y formas de pago', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);
    reportSale($employee, 'A', 1, 30, ['payment_type' => 1]);
    reportSale($employee, 'A', 1, 20, ['payment_type' => 4]);

    $data = $this->get(route('employee.reports.range'))->assertOk()->viewData('byPayment');

    expect($data[1]['total'])->toBe(30.0)->and($data[4]['total'])->toBe(20.0)->and($data[2]['total'])->toBe(0.0);
});

it('no mezcla ventas de otra sede', function () {
    $employee = loginEmployee();
    $other = App\Models\Branch::create(['company_id' => 1, 'name' => 'Otra', 'status' => 1]);
    stockProduct('A', 10, 2);
    reportSale($employee, 'A', 1, 10);
    reportSale($employee, 'A', 1, 500, ['branch_id' => $other->id]);

    expect($this->get(route('employee.reports.monthly'))->viewData('totals')['total'])->toBe(10.0);
});

// ── Productos ─────────────────────────────────────────────────

it('ordena los más vendidos por unidades', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 2);
    stockProduct('B', 10, 2);
    reportSale($employee, 'A', 3, 30);
    reportSale($employee, 'B', 10, 80);
    reportSale($employee, 'A', 2, 20);

    $rows = $this->get(route('employee.reports.best-sellers'))->assertOk()->viewData('rows');

    expect($rows->pluck('product_code')->all())->toBe(['B', 'A'])->and((float) $rows[1]->qty)->toBe(5.0);
});

it('detecta productos con stock que no se vendieron', function () {
    $employee = loginEmployee();
    stockProduct('VENDIDO', 10, 2);
    stockProduct('PARADO', 8, 3);
    stockProduct('SINSTOCK', 0, 3);
    reportSale($employee, 'VENDIDO', 1, 5);

    $view = $this->get(route('employee.reports.no-rotation'))->assertOk();

    expect($view->viewData('products')->pluck('code')->all())->toBe(['PARADO'])
        ->and($view->viewData('totalValue'))->toBe(24.0);
});

it('calcula la utilidad con el costo actual del producto', function () {
    $employee = loginEmployee();
    stockProduct('A', 10, 4);              // costo 4 por unidad
    reportSale($employee, 'A', 5, 50);     // vendió 5 por 50 neto → costo 20 → utilidad 30

    $view = $this->get(route('employee.reports.profit'))->assertOk();

    expect($view->viewData('sales'))->toBe(50.0)->and($view->viewData('cost'))->toBe(20.0)->and($view->viewData('profit'))->toBe(30.0)
        ->and($view->viewData('rows')[0]->margin)->toBe(60.0);
});

// ── Comisiones ────────────────────────────────────────────────

it('calcula comisiones sobre la venta neta con el porcentaje de cada empleado', function () {
    $admin = loginEmployee();
    stockProduct('A', 10, 2);
    $seller = Employee::create(['company_id' => 1, 'branch_id' => 1, 'role_id' => App\Models\Role::EMPLOYEE, 'name' => 'Vendedor', 'email' => 'v@example.com', 'password' => 'x']);

    $this->put(route('employee.commissions.update'), ['rates' => [$seller->id => 5]])->assertSessionHasNoErrors();
    reportSale($seller, 'A', 1, 200);

    $rows = $this->get(route('employee.reports.commissions'))->assertOk()->viewData('rows');
    $row = $rows->firstWhere('name', 'Vendedor');

    expect($row->commission)->toBe(10.0)->and($row->rate)->toBe(5.0);
    $this->put(route('employee.commissions.update'), ['rates' => [$seller->id => 150]])->assertSessionHasErrors('rates.*');
});

// ── Inventario ────────────────────────────────────────────────

it('valoriza el inventario a costo y a precio de venta', function () {
    loginEmployee();
    stockProduct('A', 10, 2);   // costo 20 · venta 40
    stockProduct('B', 0, 5);    // sin stock

    $valued = $this->get(route('employee.reports.inventory.valued'))->assertOk();
    expect($valued->viewData('totalCost'))->toBe(20.0)->and($valued->viewData('totalSale'))->toBe(40.0)->and($valued->viewData('productCount'))->toBe(1);

    expect($this->get(route('employee.reports.inventory.total'))->viewData('productCount'))->toBe(2);
    expect($this->get(route('employee.reports.inventory.stock'))->viewData('productCount'))->toBe(1);
});

it('agrupa el inventario por laboratorio', function () {
    loginEmployee();
    $lab = App\Models\Laboratory::create(['company_id' => 1, 'name' => 'Bayer', 'status' => 1]);
    stockProduct('A', 3, 2);
    Product::where('code', 'A')->update(['laboratory_id' => $lab->id]);
    stockProduct('B', 4, 2);

    $groups = $this->get(route('employee.reports.inventory.laboratory'))->assertOk()->viewData('groups');

    expect($groups->keys()->all())->toBe(['Bayer', 'Sin laboratorio']);
});

it('muestra los lotes comprados que vencen pronto y los ya vencidos', function () {
    loginEmployee();
    stockProduct('A', 5, 2);
    stockProduct('B', 5, 2);

    $this->post(route('employee.purchases.store'), [
        'document_type' => 1, 'purchased_at' => today()->toDateString(),
        'items' => [
            ['product_code' => 'A', 'quantity' => 1, 'unit_cost' => 1, 'expiration_date' => today()->addDays(20)->toDateString(), 'batch' => 'L-A'],
            ['product_code' => 'B', 'quantity' => 1, 'unit_cost' => 1, 'expiration_date' => today()->subDays(3)->toDateString(), 'batch' => 'L-B'],
        ],
    ])->assertSessionHasNoErrors();

    $view = $this->get(route('employee.reports.expiring'))->assertOk();

    expect($view->viewData('rows')->pluck('batch')->all())->toBe(['L-B', 'L-A'])->and($view->viewData('expired'))->toBe(1);
});

// ── Actualización de precios ──────────────────────────────────

it('actualiza precios y deja cada cambio en la bitácora', function () {
    loginEmployee();
    stockProduct('A', 1, 2);   // compra 2 · venta 4

    $this->put(route('employee.prices.update'), ['prices' => ['A' => ['purchase_price' => 2, 'unit_sale_price' => 5.5]]])->assertSessionHasNoErrors();

    $product = Product::find('A');
    expect((float) $product->unit_sale_price)->toBe(5.5)->and((float) $product->purchase_price)->toBe(2.0)
        ->and(PriceChange::count())->toBe(1)
        ->and(PriceChange::first()->field)->toBe('unit_sale_price');
});

it('aplica un porcentaje a todos los productos filtrados', function () {
    loginEmployee();
    stockProduct('A', 1, 10);  // venta 20
    stockProduct('B', 1, 5);   // venta 10

    $this->post(route('employee.prices.bulk', ['buscar' => 'Producto A']), ['percent' => 10, 'target' => 'sale'])->assertSessionHasNoErrors();

    expect((float) Product::find('A')->unit_sale_price)->toBe(22.0)->and((float) Product::find('B')->unit_sale_price)->toBe(10.0);
    $this->post(route('employee.prices.bulk'), ['percent' => 0, 'target' => 'sale'])->assertSessionHasErrors('percent');
});

// ── Datos del local ───────────────────────────────────────────

it('edita los datos de la sede sin tocar el código SUNAT', function () {
    $employee = loginEmployee();

    $this->put(route('employee.local.update'), ['name' => 'Botica Central', 'address' => 'Av. Lima 123', 'phone' => '999', 'email' => 'a@b.com'])->assertSessionHasNoErrors();

    $branch = $employee->branch->fresh();
    expect($branch->name)->toBe('Botica Central')->and($branch->address)->toBe('Av. Lima 123')->and($branch->sunat_establishment_code)->toBe('0000');
    $this->get(route('employee.local.edit'))->assertOk()->assertSee('Botica Central');
});

// ── Permisos ──────────────────────────────────────────────────

it('cada reporte exige su propio privilegio', function () {
    loginEmployee([Employee::PRIV_REPORTE_MAS_VENDIDOS]);

    $this->get(route('employee.reports.best-sellers'))->assertOk();
    $this->get(route('employee.reports.profit'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.reports.inventory.valued'))->assertRedirect(route('employee.home'));
    $this->get(route('employee.prices.index'))->assertRedirect(route('employee.home'));
});
