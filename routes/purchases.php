<?php

use App\Http\Controllers\Purchases\AccountsPayableController;
use App\Http\Controllers\Purchases\GoodsReceiptController;
use App\Http\Controllers\Purchases\PurchaseController;
use App\Http\Controllers\Purchases\PurchaseDashboardController;
use App\Http\Controllers\Purchases\PurchaseOrderController;
use App\Http\Controllers\Purchases\SupplierController;
use App\Http\Controllers\Purchases\TreasuryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ── Dashboard de Compras ──────────────────────────────────
    Route::get('purchases/dashboard', [PurchaseDashboardController::class, 'index'])
        ->name('purchases-dashboard.index')
        ->middleware('check-permission:purchases-dashboard.view');

    // ── Proveedores ───────────────────────────────────────────
    Route::prefix('purchases/suppliers')->name('suppliers.')->group(function () {
        Route::get('/',               [SupplierController::class, 'index'])->name('index')->middleware('check-permission:suppliers.view');
        Route::get('/create',         [SupplierController::class, 'create'])->name('create')->middleware('check-permission:suppliers.create');
        Route::post('/',              [SupplierController::class, 'store'])->name('store')->middleware('check-permission:suppliers.create');
        Route::get('/{supplier}',     [SupplierController::class, 'show'])->name('show')->middleware('check-permission:suppliers.view');
        Route::get('/{supplier}/edit',[SupplierController::class, 'edit'])->name('edit')->middleware('check-permission:suppliers.edit');
        Route::put('/{supplier}',     [SupplierController::class, 'update'])->name('update')->middleware('check-permission:suppliers.edit');
        Route::delete('/{supplier}',  [SupplierController::class, 'destroy'])->name('destroy')->middleware('check-permission:suppliers.delete');
    });

    // ── Tesorería ─────────────────────────────────────────────
    Route::prefix('purchases/treasury')->name('treasury.')->group(function () {
        Route::get('/',                    [TreasuryController::class, 'index'])->name('index')->middleware('check-permission:treasury.view');
        Route::get('/create',              [TreasuryController::class, 'createAccount'])->name('create')->middleware('check-permission:treasury.manage');
        Route::post('/',                   [TreasuryController::class, 'storeAccount'])->name('store')->middleware('check-permission:treasury.manage');
        Route::get('/{account}',           [TreasuryController::class, 'show'])->name('show')->middleware('check-permission:treasury.view');
        Route::get('/{account}/edit',      [TreasuryController::class, 'editAccount'])->name('edit')->middleware('check-permission:treasury.manage');
        Route::put('/{account}',           [TreasuryController::class, 'updateAccount'])->name('update')->middleware('check-permission:treasury.manage');
        Route::post('/{account}/capital',  [TreasuryController::class, 'storeCapital'])->name('capital')->middleware('check-permission:treasury.manage');
    });

    // ── Órdenes de Compra ─────────────────────────────────────
    Route::prefix('purchases/orders')->name('purchase-orders.')->group(function () {
        Route::get('/',                      [PurchaseOrderController::class, 'index'])->name('index')->middleware('check-permission:purchase-orders.view');
        Route::get('/create',                [PurchaseOrderController::class, 'create'])->name('create')->middleware('check-permission:purchase-orders.create');
        Route::post('/',                     [PurchaseOrderController::class, 'store'])->name('store')->middleware('check-permission:purchase-orders.create');
        Route::get('/{purchaseOrder}',       [PurchaseOrderController::class, 'show'])->name('show')->middleware('check-permission:purchase-orders.view');
        Route::get('/{purchaseOrder}/edit',  [PurchaseOrderController::class, 'edit'])->name('edit')->middleware('check-permission:purchase-orders.edit');
        Route::put('/{purchaseOrder}',       [PurchaseOrderController::class, 'update'])->name('update')->middleware('check-permission:purchase-orders.edit');
        Route::post('/{purchaseOrder}/cancel',[PurchaseOrderController::class, 'cancel'])->name('cancel')->middleware('check-permission:purchase-orders.edit');
        Route::delete('/{purchaseOrder}',    [PurchaseOrderController::class, 'destroy'])->name('destroy')->middleware('check-permission:purchase-orders.delete');
    });

    // ── Recepción de Mercadería ───────────────────────────────
    Route::prefix('purchases/receipts')->name('goods-receipts.')->group(function () {
        Route::get('/',                          [GoodsReceiptController::class, 'index'])->name('index')->middleware('check-permission:goods-receipts.view');
        Route::get('/create/{purchaseOrder}',    [GoodsReceiptController::class, 'create'])->name('create')->middleware('check-permission:goods-receipts.create');
        Route::post('/store/{purchaseOrder}',    [GoodsReceiptController::class, 'store'])->name('store')->middleware('check-permission:goods-receipts.create');
        Route::get('/{goodsReceipt}',            [GoodsReceiptController::class, 'show'])->name('show')->middleware('check-permission:goods-receipts.view');
    });

    // ── Compras / Facturas ────────────────────────────────────
    Route::prefix('purchases/invoices')->name('purchases.')->group(function () {
        Route::get('/',              [PurchaseController::class, 'index'])->name('index')->middleware('check-permission:purchases.view');
        Route::get('/create',        [PurchaseController::class, 'create'])->name('create')->middleware('check-permission:purchases.create');
        Route::post('/',             [PurchaseController::class, 'store'])->name('store')->middleware('check-permission:purchases.create');
        Route::get('/{purchase}',    [PurchaseController::class, 'show'])->name('show')->middleware('check-permission:purchases.view');
        Route::delete('/{purchase}', [PurchaseController::class, 'destroy'])->name('destroy')->middleware('check-permission:purchases.delete');
    });

    // ── Cuentas por Pagar ─────────────────────────────────────
    Route::prefix('purchases/payables')->name('accounts-payable.')->group(function () {
        Route::get('/',                    [AccountsPayableController::class, 'index'])->name('index')->middleware('check-permission:accounts-payable.view');
        Route::post('/{purchase}/payment', [AccountsPayableController::class, 'registerPayment'])->name('payment')->middleware('check-permission:accounts-payable.pay');
    });
});
