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

    // Rutas de caja registradora
    Route::get('cash-register/open',   [Company\CashRegisterController::class, 'showOpen'])->name('cash-register.show-open');
    Route::post('cash-register/open',  [Company\CashRegisterController::class, 'open'])->name('cash-register.open');
    Route::get('cash-register/edit',   [Company\CashRegisterController::class, 'edit'])->name('cash-register.edit');
    Route::put('cash-register/edit',   [Company\CashRegisterController::class, 'update'])->name('cash-register.update');
    Route::post('cash-register/close', [Company\CashRegisterController::class, 'close'])->name('cash-register.close');
    Route::get('cash-register/status', [Company\CashRegisterController::class, 'status'])->name('cash-register.status');

    // Rutas de órdenes — historial y detalle sin restricción de caja
    Route::get('orders/historial', [Company\OrderController::class, 'historial'])->name('orders.historial');
    Route::get('orders/{order}/detalle', [Company\OrderController::class, 'detalle'])->name('orders.detalle');
    Route::get('consultar-documento', [Company\OrderController::class, 'consultarDocumento'])->name('consultar-documento');

    // Punto de venta — requiere caja abierta para ver pantalla y registrar órdenes
    Route::middleware('cash.open')->group(function () {
        Route::get('orders', [Company\OrderController::class, 'index'])->name('orders.index');
        Route::post('orders', [Company\OrderController::class, 'store'])->name('orders.store');
    });

    // Rutas de clientes
    Route::get('clients/search', [Company\ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', Company\ClientController::class)->except(['show', 'destroy']);

    // Rutas de catálogos
    Route::resource('products', Company\ProductController::class);
    Route::resource('laboratories', Company\LaboratoryController::class);
    Route::resource('categories', Company\CategoryController::class);

    // Kardex de inventario
    Route::get('kardex', [Company\KardexController::class, 'index'])->name('kardex.index');

    // Rutas de compras (ingreso de stock)
    Route::get('purchases', [Company\PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [Company\PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [Company\PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [Company\PurchaseController::class, 'show'])->name('purchases.show');
});

Route::prefix(MyApp::EMPLOYEE_SUBDIR)->middleware('auth:employee')->name('employee.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('employee.home');
    })->withoutMiddleware('auth:employee');
    
    Route::get('/home', [Employee\EmployeeController::class, 'index'])->name('home');
});