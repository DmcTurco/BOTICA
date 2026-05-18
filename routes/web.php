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
        // Cajas históricas
        Route::get('cash-register/historical/{cashRegister}',       [Employee\CashRegisterController::class, 'historical'])->name('cash-register.historical');
        Route::post('cash-register/historical/{cashRegister}/close', [Employee\CashRegisterController::class, 'closeHistorical'])->name('cash-register.close-historical');
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

    // Edición de órdenes históricas — requiere privilegio ver_ventas (sin middleware cash.open)
    Route::middleware('privilege:ver_ventas')->group(function () {
        Route::get('orders/{order}/edit',       [Employee\OrderController::class, 'edit'])->name('orders.edit');
        Route::put('orders/{order}/historical', [Employee\OrderController::class, 'updateHistorical'])->name('orders.update-historical');
    });

    // Búsqueda y listado de clientes — accesible con cualquier privilegio (usada en el POS)
    Route::middleware('privilege:any')->group(function () {
        Route::get('clients/search', [Employee\ClientController::class, 'search'])->name('clients.search');
        Route::get('clients',        [Employee\ClientController::class, 'index'])->name('clients.index');
    });

    // CRUD de clientes — requiere privilegio gestionar_clientes
    Route::middleware('privilege:gestionar_clientes')->group(function () {
        Route::get('clients/create',        [Employee\ClientController::class, 'create'])->name('clients.create');
        Route::post('clients',              [Employee\ClientController::class, 'store'])->name('clients.store');
        Route::get('clients/{client}/edit', [Employee\ClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}',      [Employee\ClientController::class, 'update'])->name('clients.update');
    });

    // Inventario — productos, categorías y laboratorios
    Route::middleware('privilege:ver_inventario')->group(function () {
        Route::resource('products', Employee\ProductController::class);
        Route::resource('laboratories', Employee\LaboratoryController::class);
        Route::resource('categories', Employee\CategoryController::class);
    });

    // Compras (ingreso de stock)
    Route::middleware('privilege:ver_compras')->group(function () {
        Route::get('purchases', [Employee\PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', [Employee\PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [Employee\PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('purchases/{purchase}', [Employee\PurchaseController::class, 'show'])->name('purchases.show');
    });

    // Kardex de inventario
    Route::middleware('privilege:ver_kardex')->group(function () {
        Route::get('kardex', [Employee\KardexController::class, 'index'])->name('kardex.index');
    });

    // Impresión de comprobantes — accesible con historial o ventas
    Route::middleware('privilege:ver_historial')->group(function () {
        Route::get('orders/{order}/print/{template?}', [Employee\PrintController::class, 'show'])
            ->name('orders.print');
    });

    // Gestión de empleados y configuración — solo branch_admin (role_id = 2)
    Route::middleware('branch.admin')->group(function () {
        Route::resource('employees', Employee\EmployeeManagementController::class)->except(['show']);
        Route::get('settings',  [Employee\SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings', [Employee\SettingsController::class, 'update'])->name('settings.update');

        // Aprobación de cajas históricas
        Route::get('approvals',                                              [Employee\ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/cash-register/{cashRegister}/approve',        [Employee\ApprovalController::class, 'approveCashRegister'])->name('approvals.approve');
        Route::post('approvals/cash-register/{cashRegister}/reject',         [Employee\ApprovalController::class, 'rejectCashRegister'])->name('approvals.reject');
    });
});
