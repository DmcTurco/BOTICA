<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin as Admin;
use App\Http\Controllers\Company as Company;
use App\Http\Controllers\Employee as Employee;
use App\MyApp;

Route::get('/', function () {
    return view('welcome');
});


Route::prefix(MyApp::ADMINS_SUBDIR)->middleware('auth:admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.home');
    })->withoutMiddleware('auth:admin');

    Route::get('/home', [Admin\AdminController::class, 'index'])->name('home');
});

Route::prefix(MyApp::COMPANY_SUBDIR)->middleware('auth:company')->name('company.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('company.home');
    })->withoutMiddleware('auth:company');

    Route::get('/home', [Company\CompanyController::class, 'index'])->name('home');

    // Administración — sedes y empleados
    Route::resource('branches',  Company\BranchController::class)->except(['show']);
    Route::resource('employees', Company\EmployeeController::class)->except(['show']);
});

Route::prefix(MyApp::EMPLOYEE_SUBDIR)->middleware('auth:employee')->name('employee.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('employee.home');
    })->withoutMiddleware('auth:employee');

    Route::get('/home', [Employee\EmployeeController::class, 'index'])->name('home');

    // Rutas de caja registradora — con privilegios
    Route::middleware('privilege:abrir_caja')->group(function () {
        Route::get('cash-register/open',  [Employee\CashRegisterController::class, 'showOpen'])->name('cash-register.show-open');
        Route::post('cash-register/open', [Employee\CashRegisterController::class, 'open'])->name('cash-register.open');
    });
    Route::middleware('privilege:editar_apertura')->group(function () {
        Route::get('cash-register/edit', [Employee\CashRegisterController::class, 'edit'])->name('cash-register.edit');
        Route::put('cash-register/edit', [Employee\CashRegisterController::class, 'update'])->name('cash-register.update');
    });
    Route::middleware('privilege:cerrar_caja')->group(function () {
        Route::post('cash-register/close', [Employee\CashRegisterController::class, 'close'])->name('cash-register.close');
    });
    // Estado de caja accesible para todos (necesario para el sidebar/layout)
    Route::get('cash-register/status', [Employee\CashRegisterController::class, 'status'])->name('cash-register.status');

    // Rutas de órdenes — historial con privilegio
    Route::middleware('privilege:ver_historial')->group(function () {
        Route::get('orders/historial', [Employee\OrderController::class, 'historial'])->name('orders.historial');
        Route::get('orders/{order}/detalle', [Employee\OrderController::class, 'detalle'])->name('orders.detalle');
        Route::get('consultar-documento', [Employee\OrderController::class, 'consultarDocumento'])->name('consultar-documento');
    });

    // Punto de venta — requiere privilegio ver_ventas + caja abierta
    Route::middleware(['privilege:ver_ventas', 'cash.open'])->group(function () {
        Route::get('orders', [Employee\OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [Employee\OrderController::class, 'store'])->name('orders.store');
    });

    // Clientes, catálogos y compras — requieren al menos un privilegio asignado
    // (empleados sin ningún privilegio solo pueden ver el dashboard)
    Route::middleware('privilege:any')->group(function () {
        // Rutas de clientes
        Route::get('clients/search', [Employee\ClientController::class, 'search'])->name('clients.search');
        Route::resource('clients', Employee\ClientController::class)->except(['show', 'destroy']);

        // Rutas de catálogos
        Route::resource('products', Employee\ProductController::class);
        Route::resource('laboratories', Employee\LaboratoryController::class);
        Route::resource('categories', Employee\CategoryController::class);

        // Kardex de inventario
        Route::get('kardex', [Employee\KardexController::class, 'index'])->name('kardex.index');

        // Rutas de compras (ingreso de stock)
        Route::get('purchases', [Employee\PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', [Employee\PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [Employee\PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('purchases/{purchase}', [Employee\PurchaseController::class, 'show'])->name('purchases.show');
    });

    // Gestión de empleados — solo branch_admin (role_id = 2)
    Route::middleware('branch.admin')->group(function () {
        Route::resource('employees', Employee\EmployeeManagementController::class)->except(['show']);
    });
});
