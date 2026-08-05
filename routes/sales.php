<?php

use App\Http\Controllers\Sales\CreditApplicationController;
use App\Http\Controllers\Sales\CreditController;
use App\Http\Controllers\Sales\PaymentPlanController;
use App\Http\Controllers\Sales\PosController;
use App\Http\Controllers\Sales\QuoteController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SaleReturnController;
use App\Http\Controllers\Sales\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plan:sales'])->group(function () {

    // ── Punto de Venta (POS) ──────────────────────────────────
    Route::get('sales/pos',  [PosController::class, 'index'])->name('pos.index')->middleware('check-permission:pos.access');
    Route::post('sales/pos', [PosController::class, 'store'])->name('pos.store')->middleware('check-permission:pos.access');

    // ── Planes de Pago (plantillas) ───────────────────────────
    Route::prefix('sales/credit/plans')->name('payment-plans.')->group(function () {
        Route::get('/',           [PaymentPlanController::class, 'index'])->name('index')->middleware('check-permission:payment-plans.view');
        Route::get('/create',     [PaymentPlanController::class, 'create'])->name('create')->middleware('check-permission:payment-plans.create');
        Route::post('/',          [PaymentPlanController::class, 'store'])->name('store')->middleware('check-permission:payment-plans.create');
        Route::get('/{plan}/edit',[PaymentPlanController::class, 'edit'])->name('edit')->middleware('check-permission:payment-plans.edit');
        Route::put('/{plan}',     [PaymentPlanController::class, 'update'])->name('update')->middleware('check-permission:payment-plans.edit');
        Route::delete('/{plan}',  [PaymentPlanController::class, 'destroy'])->name('destroy')->middleware('check-permission:payment-plans.delete');
    });

    // ── Solicitudes de Crédito ────────────────────────────────
    Route::prefix('sales/credit/applications')->name('credit-applications.')->group(function () {
        Route::get('/',             [CreditApplicationController::class, 'index'])->name('index')->middleware('check-permission:credit-applications.view');
        Route::get('/create',       [CreditApplicationController::class, 'create'])->name('create')->middleware('check-permission:credit-applications.create');
        Route::post('/',            [CreditApplicationController::class, 'store'])->name('store')->middleware('check-permission:credit-applications.create');
        Route::get('/{application}',     [CreditApplicationController::class, 'show'])->name('show')->middleware('check-permission:credit-applications.view');
        Route::get('/{application}/edit',[CreditApplicationController::class, 'edit'])->name('edit')->middleware('check-permission:credit-applications.edit');
        Route::put('/{application}',     [CreditApplicationController::class, 'update'])->name('update')->middleware('check-permission:credit-applications.edit');
        Route::post('/{application}/approve',[CreditApplicationController::class, 'approve'])->name('approve')->middleware('check-permission:credit-applications.approve');
        Route::post('/{application}/reject', [CreditApplicationController::class, 'reject'])->name('reject')->middleware('check-permission:credit-applications.approve');
        Route::get('/{application}/convert', [CreditApplicationController::class, 'convert'])->name('convert')->middleware('check-permission:credit-applications.approve');
        Route::delete('/{application}',  [CreditApplicationController::class, 'destroy'])->name('destroy')->middleware('check-permission:credit-applications.edit');
    });

    // ── Créditos ──────────────────────────────────────────────
    Route::prefix('sales/credit')->name('credit.')->group(function () {
        Route::get('/sales',    [CreditController::class, 'creditSales'])->name('sales')->middleware('check-permission:credit.view');
        Route::get('/cuotas',   [CreditController::class, 'cuotas'])->name('cuotas')->middleware('check-permission:credit.view');
        Route::get('/morosos',  [CreditController::class, 'morosos'])->name('morosos')->middleware('check-permission:credit.view');
        Route::get('/cobranza', [CreditController::class, 'cobranza'])->name('cobranza')->middleware('check-permission:credit.collect');
        Route::get('/reports',  [CreditController::class, 'reports'])->name('reports')->middleware('check-permission:credit-reports.view');
        Route::post('/{sale}/payment', [CreditController::class, 'registerPayment'])->name('payment')->middleware('check-permission:credit.collect');
        Route::get('/{sale}/receipt',  [CreditController::class, 'paymentReceipt'])->name('receipt')->middleware('check-permission:credit.collect');
    });

    // ── Vehículos ─────────────────────────────────────────────
    Route::prefix('sales/vehicles')->name('vehicles.')->group(function () {
        Route::get('/',              [VehicleController::class, 'index'])->name('index')->middleware('check-permission:vehicles.view');
        Route::get('/create',        [VehicleController::class, 'create'])->name('create')->middleware('check-permission:vehicles.create');
        Route::post('/',             [VehicleController::class, 'store'])->name('store')->middleware('check-permission:vehicles.create');
        Route::get('/{vehicle}',     [VehicleController::class, 'show'])->name('show')->middleware('check-permission:vehicles.view');
        Route::get('/{vehicle}/edit',[VehicleController::class, 'edit'])->name('edit')->middleware('check-permission:vehicles.edit');
        Route::put('/{vehicle}',     [VehicleController::class, 'update'])->name('update')->middleware('check-permission:vehicles.edit');
        Route::delete('/{vehicle}',  [VehicleController::class, 'destroy'])->name('destroy')->middleware('check-permission:vehicles.delete');
    });

    // ── Dashboard de ventas ───────────────────────────────────
    Route::get('sales/dashboard', [SaleController::class, 'dashboard'])->name('sales.dashboard')->middleware('check-permission:sales-dashboard.view');

    // ── Ventas (gestión / formulario) ─────────────────────────
    Route::prefix('sales/invoices')->name('sales.')->group(function () {
        Route::get('/',            [SaleController::class, 'index'])->name('index')->middleware('check-permission:sales.view');
        Route::get('/create',      [SaleController::class, 'create'])->name('create')->middleware('check-permission:sales.create');
        Route::post('/',           [SaleController::class, 'store'])->name('store')->middleware('check-permission:sales.create');
        Route::get('/{sale}',      [SaleController::class, 'show'])->name('show')->middleware('check-permission:sales.view');
        Route::get('/{sale}/receipt', [SaleController::class, 'receipt'])->name('receipt')->middleware('check-permission:sales.view');
        Route::post('/{sale}/cancel', [SaleController::class, 'cancel'])->name('cancel')->middleware('check-permission:sales.delete');
    });

    // ── Cotizaciones ──────────────────────────────────────────
    Route::prefix('sales/quotes')->name('quotes.')->group(function () {
        Route::get('/',              [QuoteController::class, 'index'])->name('index')->middleware('check-permission:quotes.view');
        Route::get('/pos',           [QuoteController::class, 'pos'])->name('pos')->middleware('check-permission:quotes.create');
        Route::get('/create',        [QuoteController::class, 'create'])->name('create')->middleware('check-permission:quotes.create');
        Route::post('/',             [QuoteController::class, 'store'])->name('store')->middleware('check-permission:quotes.create');
        Route::get('/{quote}',       [QuoteController::class, 'show'])->name('show')->middleware('check-permission:quotes.view');
        Route::get('/{quote}/edit',  [QuoteController::class, 'edit'])->name('edit')->middleware('check-permission:quotes.edit');
        Route::put('/{quote}',       [QuoteController::class, 'update'])->name('update')->middleware('check-permission:quotes.edit');
        Route::post('/{quote}/status',[QuoteController::class, 'changeStatus'])->name('status')->middleware('check-permission:quotes.edit');
        Route::get('/{quote}/convert',[QuoteController::class, 'convert'])->name('convert')->middleware('check-permission:quotes.edit');
        Route::delete('/{quote}',    [QuoteController::class, 'destroy'])->name('destroy')->middleware('check-permission:quotes.delete');
    });

    // ── Devoluciones ──────────────────────────────────────────
    Route::prefix('sales/returns')->name('sale-returns.')->group(function () {
        Route::get('/',                   [SaleReturnController::class, 'index'])->name('index')->middleware('check-permission:sale-returns.view');
        Route::get('/create/{sale}',      [SaleReturnController::class, 'create'])->name('create')->middleware('check-permission:sale-returns.create');
        Route::post('/store/{sale}',      [SaleReturnController::class, 'store'])->name('store')->middleware('check-permission:sale-returns.create');
        Route::get('/{saleReturn}',       [SaleReturnController::class, 'show'])->name('show')->middleware('check-permission:sale-returns.view');
    });
});
