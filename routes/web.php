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
    Route::post('cash-register/open',  [Company\CashRegisterController::class, 'open'])->name('cash-register.open');
    Route::post('cash-register/close', [Company\CashRegisterController::class, 'close'])->name('cash-register.close');
    Route::get('cash-register/status', [Company\CashRegisterController::class, 'status'])->name('cash-register.status');

    // Rutas de órdenes (punto de venta) — protegidas por caja abierta
    Route::middleware('cash.open')->group(function () {
        Route::get('orders/historial', [Company\OrderController::class, 'historial'])->name('orders.historial');
        Route::get('orders/{order}/detalle', [Company\OrderController::class, 'detalle'])->name('orders.detalle');
        Route::resource('orders', Company\OrderController::class);
    });
    Route::get('consultar-documento', [Company\OrderController::class, 'consultarDocumento'])->name('consultar-documento');

    // Rutas de catálogos
    Route::resource('products', Company\ProductController::class);
    Route::resource('laboratories', Company\LaboratoryController::class);
    Route::resource('categories', Company\CategoryController::class);
});

Route::prefix(MyApp::EMPLOYEE_SUBDIR)->middleware('auth:employee')->name('employee.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('employee.home');
    })->withoutMiddleware('auth:employee');
    
    Route::get('/home', [Employee\EmployeeController::class, 'index'])->name('home');
});