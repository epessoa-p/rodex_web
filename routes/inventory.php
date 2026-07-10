<?php

use App\Http\Controllers\Inventory\ProductController;
use App\Http\Controllers\Inventory\ProductBrandController;
use App\Http\Controllers\Inventory\ProductCategoryController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ── Inventario (listado editable + migración) ─────────────────────────────
    Route::prefix('inventory/stock')->name('inventory.stock')->group(function () {
        Route::get('/',                   [StockController::class, 'index'])->middleware('check-permission:products.view');
        Route::get('/template',           [StockController::class, 'template'])->name('.template')->middleware('check-permission:products.view');
        Route::get('/export/excel',       [StockController::class, 'exportExcel'])->name('.export.excel')->middleware('check-permission:products.view');
        Route::get('/export/pdf',         [StockController::class, 'exportPdf'])->name('.export.pdf')->middleware('check-permission:products.view');
        Route::get('/import',             [StockController::class, 'import'])->name('.import')->middleware('check-permission:products.create');
        Route::post('/import',            [StockController::class, 'processImport'])->name('.import.process')->middleware('check-permission:products.create');
        Route::post('/import/preview',    [StockController::class, 'previewImport'])->name('.import.preview')->middleware('check-permission:products.create');
        Route::post('/import/confirm',    [StockController::class, 'confirmImport'])->name('.import.confirm')->middleware('check-permission:products.create');
        Route::post('/{product}/field',   [StockController::class, 'updateField'])->name('.field')->middleware('check-permission:products.edit');
        Route::post('/{product}/quantity',[StockController::class, 'setQuantity'])->name('.quantity')->middleware('check-permission:products.edit');
    });

    // ── Categorías ────────────────────────────────────────────────────────────
    Route::prefix('inventory/categories')->name('product-categories.')->group(function () {
        Route::get('/',               [ProductCategoryController::class, 'index'])->name('index')->middleware('check-permission:product-categories.view');
        Route::get('/create',         [ProductCategoryController::class, 'create'])->name('create')->middleware('check-permission:product-categories.create');
        Route::post('/',              [ProductCategoryController::class, 'store'])->name('store')->middleware('check-permission:product-categories.create');
        Route::get('/{category}/edit',[ProductCategoryController::class, 'edit'])->name('edit')->middleware('check-permission:product-categories.edit');
        Route::put('/{category}',     [ProductCategoryController::class, 'update'])->name('update')->middleware('check-permission:product-categories.edit');
        Route::delete('/{category}',  [ProductCategoryController::class, 'destroy'])->name('destroy')->middleware('check-permission:product-categories.delete');
    });

    // ── Marcas ────────────────────────────────────────────────────────────────
    Route::prefix('inventory/brands')->name('product-brands.')->group(function () {
        Route::get('/',           [ProductBrandController::class, 'index'])->name('index')->middleware('check-permission:product-brands.view');
        Route::get('/create',     [ProductBrandController::class, 'create'])->name('create')->middleware('check-permission:product-brands.create');
        Route::post('/',          [ProductBrandController::class, 'store'])->name('store')->middleware('check-permission:product-brands.create');
        Route::get('/{brand}/edit',[ProductBrandController::class, 'edit'])->name('edit')->middleware('check-permission:product-brands.edit');
        Route::put('/{brand}',    [ProductBrandController::class, 'update'])->name('update')->middleware('check-permission:product-brands.edit');
        Route::delete('/{brand}', [ProductBrandController::class, 'destroy'])->name('destroy')->middleware('check-permission:product-brands.delete');
    });

    // ── Kardex general ────────────────────────────────────────────────────────
    Route::get('inventory/kardex', [ProductController::class, 'kardexGeneral'])
        ->name('inventory.kardex')
        ->middleware('check-permission:inventory.kardex');

    Route::get('inventory/products/{product}/kardex', [ProductController::class, 'kardex'])
        ->name('products.kardex.view')
        ->middleware('check-permission:inventory.kardex');

    // ── Productos ─────────────────────────────────────────────────────────────
    Route::prefix('inventory/products')->name('products.')->group(function () {
        Route::get('/',                  [ProductController::class, 'index'])->name('index')->middleware('check-permission:products.view');
        Route::get('/create',            [ProductController::class, 'create'])->name('create')->middleware('check-permission:products.create');
        Route::post('/',                 [ProductController::class, 'store'])->name('store')->middleware('check-permission:products.create');
        Route::get('/import',            [ProductController::class, 'import'])->name('import')->middleware('check-permission:products.create');
        Route::post('/import',           [ProductController::class, 'processImport'])->name('import.process')->middleware('check-permission:products.create');
        Route::delete('/photos/{photo}', [ProductController::class, 'destroyPhoto'])->name('photos.destroy')->middleware('check-permission:products.edit');
        Route::get('/{product}',         [ProductController::class, 'show'])->name('show')->middleware('check-permission:products.view');
        Route::get('/{product}/kardex',  [ProductController::class, 'kardex'])->name('kardex')->middleware('check-permission:products.view');
        Route::get('/{product}/edit',    [ProductController::class, 'edit'])->name('edit')->middleware('check-permission:products.edit');
        Route::put('/{product}',         [ProductController::class, 'update'])->name('update')->middleware('check-permission:products.edit');
        Route::delete('/{product}',      [ProductController::class, 'destroy'])->name('destroy')->middleware('check-permission:products.delete');
    });

    // ── Almacenes ─────────────────────────────────────────────────────────────
    Route::prefix('inventory/warehouses')->name('warehouses.')->group(function () {
        Route::get('/',                       [WarehouseController::class, 'index'])->name('index')->middleware('check-permission:warehouses.view');
        Route::get('/create',                 [WarehouseController::class, 'create'])->name('create')->middleware('check-permission:warehouses.create');
        Route::post('/',                      [WarehouseController::class, 'store'])->name('store')->middleware('check-permission:warehouses.create');
        Route::get('/{warehouse}',            [WarehouseController::class, 'show'])->name('show')->middleware('check-permission:warehouses.view');
        Route::get('/{warehouse}/edit',       [WarehouseController::class, 'edit'])->name('edit')->middleware('check-permission:warehouses.edit');
        Route::put('/{warehouse}',            [WarehouseController::class, 'update'])->name('update')->middleware('check-permission:warehouses.edit');
        Route::delete('/{warehouse}',         [WarehouseController::class, 'destroy'])->name('destroy')->middleware('check-permission:warehouses.delete');
        Route::post('/{warehouse}/movements', [WarehouseController::class, 'storeMovement'])->name('movements.store')->middleware('check-permission:warehouses.edit');
    });
});
