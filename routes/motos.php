<?php

use App\Http\Controllers\Motos\MotoBrandController;
use App\Http\Controllers\Motos\MotoDeliveryController;
use App\Http\Controllers\Motos\MotoModelController;
use App\Http\Controllers\Motos\MotoSaleController;
use App\Http\Controllers\Motos\MotoUnitController;
use App\Http\Controllers\Motos\WarrantyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    // ── Marcas ────────────────────────────────────────────────
    Route::prefix('motos/brands')->name('moto-brands.')->group(function () {
        Route::get('/',           [MotoBrandController::class, 'index'])->name('index')->middleware('check-permission:moto-brands.view');
        Route::get('/create',     [MotoBrandController::class, 'create'])->name('create')->middleware('check-permission:moto-brands.create');
        Route::post('/',          [MotoBrandController::class, 'store'])->name('store')->middleware('check-permission:moto-brands.create');
        Route::get('/{brand}/edit',[MotoBrandController::class, 'edit'])->name('edit')->middleware('check-permission:moto-brands.edit');
        Route::put('/{brand}',    [MotoBrandController::class, 'update'])->name('update')->middleware('check-permission:moto-brands.edit');
        Route::delete('/{brand}', [MotoBrandController::class, 'destroy'])->name('destroy')->middleware('check-permission:moto-brands.delete');
    });

    // ── Modelos ───────────────────────────────────────────────
    Route::prefix('motos/models')->name('moto-models.')->group(function () {
        Route::get('/',           [MotoModelController::class, 'index'])->name('index')->middleware('check-permission:moto-models.view');
        Route::get('/create',     [MotoModelController::class, 'create'])->name('create')->middleware('check-permission:moto-models.create');
        Route::post('/',          [MotoModelController::class, 'store'])->name('store')->middleware('check-permission:moto-models.create');
        Route::get('/{model}/edit',[MotoModelController::class, 'edit'])->name('edit')->middleware('check-permission:moto-models.edit');
        Route::put('/{model}',    [MotoModelController::class, 'update'])->name('update')->middleware('check-permission:moto-models.edit');
        Route::delete('/{model}', [MotoModelController::class, 'destroy'])->name('destroy')->middleware('check-permission:moto-models.delete');
    });

    // ── Ventas de Motos ───────────────────────────────────────
    Route::prefix('motos/sales')->name('moto-sales.')->group(function () {
        Route::get('/',       [MotoSaleController::class, 'index'])->name('index')->middleware('check-permission:moto-sales.view');
        Route::get('/create', [MotoSaleController::class, 'create'])->name('create')->middleware('check-permission:moto-sales.create');
        Route::post('/',      [MotoSaleController::class, 'store'])->name('store')->middleware('check-permission:moto-sales.create');
    });

    // ── Entregas ──────────────────────────────────────────────
    Route::prefix('motos/deliveries')->name('moto-deliveries.')->group(function () {
        Route::get('/',              [MotoDeliveryController::class, 'index'])->name('index')->middleware('check-permission:moto-deliveries.view');
        Route::get('/create/{unit}', [MotoDeliveryController::class, 'create'])->name('create')->middleware('check-permission:moto-deliveries.manage');
        Route::post('/store/{unit}', [MotoDeliveryController::class, 'store'])->name('store')->middleware('check-permission:moto-deliveries.manage');
    });

    // ── Garantías ─────────────────────────────────────────────
    Route::prefix('motos/warranties')->name('warranties.')->group(function () {
        Route::get('/',             [WarrantyController::class, 'index'])->name('index')->middleware('check-permission:warranties.view');
        Route::get('/create',       [WarrantyController::class, 'create'])->name('create')->middleware('check-permission:warranties.manage');
        Route::post('/',            [WarrantyController::class, 'store'])->name('store')->middleware('check-permission:warranties.manage');
        Route::get('/{warranty}',   [WarrantyController::class, 'show'])->name('show')->middleware('check-permission:warranties.view');
        Route::put('/{warranty}',   [WarrantyController::class, 'update'])->name('update')->middleware('check-permission:warranties.manage');
    });

    // ── Inventario de Motos (unidades) ────────────────────────
    Route::prefix('motos/units')->name('moto-units.')->group(function () {
        Route::get('/',           [MotoUnitController::class, 'index'])->name('index')->middleware('check-permission:moto-units.view');
        Route::get('/create',     [MotoUnitController::class, 'create'])->name('create')->middleware('check-permission:moto-units.create');
        Route::post('/',          [MotoUnitController::class, 'store'])->name('store')->middleware('check-permission:moto-units.create');
        Route::get('/{unit}',     [MotoUnitController::class, 'show'])->name('show')->middleware('check-permission:moto-units.view');
        Route::get('/{unit}/edit',[MotoUnitController::class, 'edit'])->name('edit')->middleware('check-permission:moto-units.edit');
        Route::put('/{unit}',     [MotoUnitController::class, 'update'])->name('update')->middleware('check-permission:moto-units.edit');
        Route::delete('/{unit}',  [MotoUnitController::class, 'destroy'])->name('destroy')->middleware('check-permission:moto-units.delete');
    });
});
