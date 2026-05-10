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
    Route::get('sales/historial', [Company\SalesController::class, 'historial'])->name('sales.historial');
    Route::get('sales/{sale}/detalle', [Company\SalesController::class, 'detalle'])->name('sales.detalle');
    Route::resource('sales', Company\SalesController::class);
    Route::get('consultar-documento', [Company\SalesController::class, 'consultarDocumento'])->name('consultar-documento');
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