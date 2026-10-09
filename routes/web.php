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

    // Resumen comparativo de todas las sedes
    Route::get('reports/branches', [Company\BranchReportController::class, 'index'])->name('reports.branches');

    // Administración — sedes y empleados
    Route::resource('branches',  Company\BranchController::class)->except(['show']);
    Route::resource('employees', Company\EmployeeController::class)->except(['show']);

    // Facturación electrónica (SUNAT): datos fiscales, credenciales SOL y certificado
    Route::get('sunat', [Company\SunatSettingController::class, 'edit'])->name('sunat.edit');
    Route::put('sunat', [Company\SunatSettingController::class, 'update'])->name('sunat.update');
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
        Route::post('cash-register/historical/{cashRegister}/discard', [Employee\CashRegisterController::class, 'discardHistorical'])->name('cash-register.discard-historical');
    });
    Route::middleware(['privilege:editar_apertura', 'cash.open'])->group(function () {
        Route::get('cash-register/edit', [Employee\CashRegisterController::class, 'edit'])->name('cash-register.edit');
        Route::put('cash-register/edit', [Employee\CashRegisterController::class, 'update'])->name('cash-register.update');
    });
    Route::middleware(['privilege:cerrar_caja', 'cash.open'])->group(function () {
        Route::get('cash-register/close',  [Employee\CashRegisterController::class, 'showClose'])->name('cash-register.show-close');
        Route::post('cash-register/close', [Employee\CashRegisterController::class, 'close'])->name('cash-register.close');
    });
    // Estado de caja accesible para todos (necesario para el sidebar/layout)
    Route::get('cash-register/status', [Employee\CashRegisterController::class, 'status'])->name('cash-register.status');

    // Rutas de órdenes — historial con privilegio
    Route::middleware('privilege:ver_historial')->group(function () {
        Route::get('orders/historial', [Employee\OrderController::class, 'historial'])->name('orders.historial');
        Route::get('orders/{order}/detalle', [Employee\OrderController::class, 'detalle'])->name('orders.detalle');
        Route::post('orders/{order}/sunat', [Employee\OrderController::class, 'resendSunat'])
            ->middleware('privilege:enviar_fe_sunat')->name('orders.sunat-resend');

        // Resumen diario de boletas: envía las pendientes y consulta los resúmenes en proceso
        Route::post('sunat/summary', [Employee\SunatSummaryController::class, 'store'])
            ->middleware('privilege:enviar_resumen_boletas')->name('sunat.summary');

        // Nota de crédito: anula una boleta o factura aceptada por SUNAT
        Route::post('orders/{order}/credit-note', [Employee\CreditNoteController::class, 'store'])
            ->middleware('privilege:crear_nota_credito')->name('orders.credit-note');
        Route::post('credit-notes/{creditNote}/sunat', [Employee\CreditNoteController::class, 'resend'])
            ->middleware('privilege:enviar_nce_sunat')->name('credit-notes.sunat-resend');
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

    // Alternativas por principio activo para el punto de venta
    Route::get('pos/alternatives', [Employee\ProductAlternativeController::class, 'index'])
        ->middleware('privilege:ver_ventas')->name('pos.alternatives');

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
    // Consulta: ver_inventario · Cambios: crear_productos / mantenimiento_familias
    Route::middleware('privilege:ver_inventario')->group(function () {
        Route::resource('products', Employee\ProductController::class)->only(['index']);
        Route::resource('laboratories', Employee\LaboratoryController::class)->only(['index']);
        Route::resource('categories', Employee\CategoryController::class)->only(['index']);
        Route::get('units', [Employee\UnitController::class, 'index'])->name('units.index');
    });
    Route::middleware('privilege:crear_productos')->group(function () {
        Route::resource('products', Employee\ProductController::class)->except(['index', 'show']);
    });
    Route::middleware('privilege:mantenimiento_familias')->group(function () {
        Route::resource('laboratories', Employee\LaboratoryController::class)->except(['index', 'show']);
        Route::resource('categories', Employee\CategoryController::class)->except(['index', 'show']);
        Route::resource('units', Employee\UnitController::class)->only(['store', 'update', 'destroy']);
    });

    // Compras (ingreso de stock)
    Route::middleware('privilege:ver_compras')->group(function () {
        Route::get('purchases', [Employee\PurchaseController::class, 'index'])->name('purchases.index');
        Route::get('purchases/create', [Employee\PurchaseController::class, 'create'])->name('purchases.create');
        Route::post('purchases', [Employee\PurchaseController::class, 'store'])->name('purchases.store');
        Route::get('purchases/{purchase}', [Employee\PurchaseController::class, 'show'])->name('purchases.show');
        Route::post('purchases/{purchase}/void', [Employee\PurchaseController::class, 'void'])
            ->middleware('privilege:eliminar_guia_ingreso')->name('purchases.void');
    });

    // Laboratorio — fórmulas magistrales y preparaciones (lotes)
    Route::middleware('privilege:ver_formulas')->group(function () {
        Route::resource('formulas', Employee\FormulaController::class);
    });
    Route::middleware('privilege:producir_formulas')->group(function () {
        Route::get('productions', [Employee\ProductionController::class, 'index'])->name('productions.index');
        Route::get('productions/create', [Employee\ProductionController::class, 'create'])->name('productions.create');
        Route::post('productions', [Employee\ProductionController::class, 'store'])->name('productions.store');
        Route::get('productions/{production}', [Employee\ProductionController::class, 'show'])->name('productions.show');
        Route::get('productions/{production}/label', [Employee\ProductionController::class, 'label'])->name('productions.label');
        Route::post('productions/{production}/void', [Employee\ProductionController::class, 'void'])->name('productions.void');
    });

    // Ajuste de inventario (conteo físico, merma, vencidos)
    Route::middleware('privilege:ajuste_inventario')->group(function () {
        Route::get('adjustments', [Employee\InventoryAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('adjustments', [Employee\InventoryAdjustmentController::class, 'store'])->name('adjustments.store');
    });

    // Productos por debajo del stock mínimo
    Route::get('replenishment', [Employee\ReplenishmentController::class, 'index'])
        ->middleware('privilege:productos_reposicion')->name('replenishment.index');

    // Traspasos de mercadería entre sedes
    Route::middleware('privilege:traspaso_salida')->group(function () {
        Route::get('transfers', [Employee\StockTransferController::class, 'index'])->name('transfers.index');
        Route::get('transfers/create', [Employee\StockTransferController::class, 'create'])->name('transfers.create');
        Route::post('transfers', [Employee\StockTransferController::class, 'store'])->name('transfers.store');
        Route::get('transfers/{transfer}', [Employee\StockTransferController::class, 'show'])->name('transfers.show');
        Route::post('transfers/{transfer}/void', [Employee\StockTransferController::class, 'void'])
            ->middleware('privilege:eliminar_guia_salida')->name('transfers.void');
    });

    // Caja: gastos del día / otros ingresos, historial de cierres e impresión del día
    Route::middleware('privilege:gastos_dia,otros_ingresos')->group(function () {
        Route::get('cash-movements', [Employee\CashMovementController::class, 'index'])->name('cash-movements.index');
        Route::post('cash-movements', [Employee\CashMovementController::class, 'store'])->name('cash-movements.store');
        Route::post('cash-movements/{movement}/void', [Employee\CashMovementController::class, 'void'])->name('cash-movements.void');
    });
    Route::get('cash-closures/print', [Employee\CashClosureController::class, 'print'])
        ->middleware('privilege:imprimir_mov_caja')->name('cash-closures.print');
    Route::middleware('privilege:ver_cierres_caja')->group(function () {
        Route::get('cash-closures', [Employee\CashClosureController::class, 'index'])->name('cash-closures.index');
        Route::get('cash-closures/{cashRegister}', [Employee\CashClosureController::class, 'show'])->name('cash-closures.show');
    });

    // Ventas perdidas, tipo de cambio y correlativos
    Route::middleware('privilege:venta_perdida_sin_stock')->group(function () {
        Route::get('lost-sales', [Employee\LostSaleController::class, 'index'])->name('lost-sales.index');
        Route::post('lost-sales', [Employee\LostSaleController::class, 'store'])->name('lost-sales.store');
    });
    Route::middleware('privilege:tipo_cambio')->group(function () {
        Route::get('exchange-rates', [Employee\ExchangeRateController::class, 'index'])->name('exchange-rates.index');
        Route::post('exchange-rates', [Employee\ExchangeRateController::class, 'store'])->name('exchange-rates.store');
    });
    Route::middleware('privilege:admin_correlativos')->group(function () {
        Route::get('series', [Employee\DocumentSeriesController::class, 'index'])->name('series.index');
        Route::put('series/{series}', [Employee\DocumentSeriesController::class, 'update'])->name('series.update');
    });

    // Baja de lotes vencidos
    Route::post('batches/{batch}/write-off', [Employee\BatchController::class, 'writeOff'])
        ->middleware('privilege:ajuste_inventario')->name('batches.write-off');

    // Bitácora de actividad
    Route::get('audit', [Employee\AuditLogController::class, 'index'])
        ->middleware('privilege:ver_bitacora')->name('audit.index');

    // Registros de ventas y compras para el contador
    Route::middleware('privilege:registros_contables')->prefix('accounting')->name('accounting.')->group(function () {
        Route::get('sales', [Employee\AccountingController::class, 'sales'])->name('sales');
        Route::get('purchases', [Employee\AccountingController::class, 'purchases'])->name('purchases');
    });

    // Reportes de ventas, productos e inventario (cada uno con su privilegio)
    Route::prefix('reports')->name('reports.')->group(function () {
        $sales = Employee\SalesReportController::class;
        Route::get('monthly', [$sales, 'monthly'])->middleware('privilege:record_mensual_ventas')->name('monthly');
        Route::get('general', [$sales, 'general'])->middleware('privilege:record_general_ventas')->name('general');
        Route::get('documents', [$sales, 'documents'])->middleware('privilege:ver_docs_mes')->name('documents');
        Route::get('range', [$sales, 'range'])->middleware('privilege:reportes_por_fecha')->name('range');
        Route::get('best-sellers', [$sales, 'bestSellers'])->middleware('privilege:reporte_mas_vendidos')->name('best-sellers');
        Route::get('no-rotation', [$sales, 'noRotation'])->middleware('privilege:productos_sin_rotacion')->name('no-rotation');
        Route::get('profit', [$sales, 'profit'])->middleware('privilege:reporte_utilidad')->name('profit');
        Route::get('commissions', [$sales, 'commissions'])->middleware('privilege:reporte_comisiones')->name('commissions');

        $inventory = Employee\InventoryReportController::class;
        Route::get('expiring', [$inventory, 'expiring'])->middleware('privilege:reporte_vencimientos')->name('expiring');
        Route::get('inventory/total', [$inventory, 'index'])->defaults('variant', 'total')->middleware('privilege:inventario_total')->name('inventory.total');
        Route::get('inventory/stock', [$inventory, 'index'])->defaults('variant', 'stock')->middleware('privilege:inventario_total_stock')->name('inventory.stock');
        Route::get('inventory/laboratory', [$inventory, 'index'])->defaults('variant', 'laboratory')->middleware('privilege:inv_por_laboratorio_total')->name('inventory.laboratory');
        Route::get('inventory/laboratory-stock', [$inventory, 'index'])->defaults('variant', 'laboratory-stock')->middleware('privilege:inv_por_laboratorio_stock')->name('inventory.laboratory-stock');
        Route::get('inventory/valued', [$inventory, 'index'])->defaults('variant', 'valued')->middleware('privilege:reporte_inv_valorizado')->name('inventory.valued');
    });

    // Herramientas: comisiones, actualización de precios y datos del local
    Route::middleware('privilege:admin_comision')->group(function () {
        Route::get('commissions/settings', [Employee\SalesReportController::class, 'commissionSettings'])->name('commissions.settings');
        Route::put('commissions/settings', [Employee\SalesReportController::class, 'updateCommissions'])->name('commissions.update');
    });
    Route::middleware('privilege:actualizacion_precio')->group(function () {
        Route::get('prices', [Employee\PriceUpdateController::class, 'index'])->name('prices.index');
        Route::put('prices', [Employee\PriceUpdateController::class, 'update'])->name('prices.update');
        Route::post('prices/bulk', [Employee\PriceUpdateController::class, 'bulk'])->name('prices.bulk');
    });
    Route::middleware('privilege:editar_datos_local')->group(function () {
        Route::get('local', [Employee\LocalDataController::class, 'edit'])->name('local.edit');
        Route::put('local', [Employee\LocalDataController::class, 'update'])->name('local.update');
    });

    // Proveedores, órdenes de compra y cuentas por pagar
    Route::middleware('privilege:proveedores')->group(function () {
        Route::resource('suppliers', Employee\SupplierController::class)->only(['index', 'store', 'update', 'destroy']);
    });
    Route::middleware('privilege:ver_compras')->group(function () {
        Route::get('purchase-orders', [Employee\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('purchase-orders/create', [Employee\PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('purchase-orders', [Employee\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::get('purchase-orders/{purchaseOrder}', [Employee\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('purchase-orders/{purchaseOrder}/cancel', [Employee\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    });
    Route::middleware('privilege:cuentas_pagar')->group(function () {
        Route::get('payables', [Employee\AccountsPayableController::class, 'index'])->name('payables.index');
        Route::post('payables/{purchase}/pay', [Employee\AccountsPayableController::class, 'pay'])->name('payables.pay');
    });

    // Recetas médicas y libro de controlados
    Route::get('prescriptions', [Employee\PrescriptionController::class, 'index'])
        ->middleware('privilege:ver_recetas')->name('prescriptions.index');
    Route::middleware('privilege:libro_controlados')->group(function () {
        Route::get('controlled-book', [Employee\ControlledBookController::class, 'index'])->name('controlled-book.index');
        Route::get('controlled-book/print', [Employee\ControlledBookController::class, 'print'])->name('controlled-book.print');
    });

    // Cuentas por cobrar (fiado) y abonos de clientes
    Route::middleware('privilege:cuentas_cobrar')->group(function () {
        Route::get('receivables', [Employee\AccountsReceivableController::class, 'index'])->name('receivables.index');
        Route::get('receivables/{client}', [Employee\AccountsReceivableController::class, 'show'])->name('receivables.show');
        Route::post('receivables/{client}/pay', [Employee\AccountsReceivableController::class, 'pay'])->name('receivables.pay');
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
