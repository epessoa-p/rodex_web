<?php

use App\Http\Controllers\Admin\CashRegisterController;
use App\Http\Controllers\Cash\ExpenseController;
use App\Http\Controllers\Cash\ExpenseServiceController;
use App\Http\Controllers\CashRegister\CashSessionController;
use App\Http\Controllers\CashRegister\MovementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plan:cash'])->group(function () {

    // ── Movimientos (tablero financiero por sucursal) ─────────────
    Route::get('cash/movimientos', [MovementController::class, 'index'])
        ->name('cash.movements')->middleware('check-permission:cash-registers.view');

    // Detalle de una sesión (parcial cargado por AJAX en la pestaña de cierres)
    Route::get('cash/movimientos/session/{session}/detail', [MovementController::class, 'sessionDetail'])
        ->name('cash.movements.session-detail')->middleware('check-permission:cash-registers.view');

    // Ajuste de diferencia de un cierre (acción sensible: solo admin/gerente)
    Route::post('cash/session/{session}/adjust', [CashSessionController::class, 'adjustDifference'])
        ->name('cash.session.adjust')->middleware('check-permission:cash.adjust');

    // Corrección de conteo (el cajero contó/tecleó mal; no genera movimiento)
    Route::post('cash/session/{session}/recount', [CashSessionController::class, 'recountClosing'])
        ->name('cash.session.recount')->middleware('check-permission:cash.adjust');

    // ── Gastos desde caja (modal navbar) ──────────────────────────
    Route::get('cash/expense/data', [ExpenseController::class, 'data'])
        ->name('cash.expense.data')->middleware('check-permission:cash.operate');
    Route::post('cash/expense',     [ExpenseController::class, 'store'])
        ->name('cash.expense.store')->middleware('check-permission:cash.operate');

    // ── Catálogo: Servicios de gasto ──────────────────────────────
    Route::prefix('admin/expense-services')->name('expense-services.')->group(function () {
        Route::get('/',                      [ExpenseServiceController::class, 'index'])->name('index')->middleware('check-permission:expense-services.view');
        Route::get('/create',                [ExpenseServiceController::class, 'create'])->name('create')->middleware('check-permission:expense-services.manage');
        Route::post('/',                     [ExpenseServiceController::class, 'store'])->name('store')->middleware('check-permission:expense-services.manage');
        Route::get('/{expenseService}/edit', [ExpenseServiceController::class, 'edit'])->name('edit')->middleware('check-permission:expense-services.manage');
        Route::put('/{expenseService}',      [ExpenseServiceController::class, 'update'])->name('update')->middleware('check-permission:expense-services.manage');
        Route::delete('/{expenseService}',   [ExpenseServiceController::class, 'destroy'])->name('destroy')->middleware('check-permission:expense-services.manage');
    });

    // ── Admin: gestión de cajas ───────────────────────────────────
    // IMPORTANTE: /create debe ir ANTES de /{cashRegister} (wildcard)
    Route::prefix('admin/cash-registers')->name('cash-registers.')->group(function () {
        Route::get('/',          [CashRegisterController::class, 'index'])->name('index')->middleware('check-permission:cash-registers.view');
        Route::get('/create',    [CashRegisterController::class, 'create'])->name('create')->middleware('check-permission:cash-registers.create');
        Route::post('/',         [CashRegisterController::class, 'store'])->name('store')->middleware('check-permission:cash-registers.create');
        Route::get('/{cashRegister}',      [CashRegisterController::class, 'show'])->name('show')->middleware('check-permission:cash-registers.view');
        Route::get('/{cashRegister}/edit', [CashRegisterController::class, 'edit'])->name('edit')->middleware('check-permission:cash-registers.edit');
        Route::put('/{cashRegister}',      [CashRegisterController::class, 'update'])->name('update')->middleware('check-permission:cash-registers.edit');
        Route::delete('/{cashRegister}',   [CashRegisterController::class, 'destroy'])->name('destroy')->middleware('check-permission:cash-registers.delete');
    });

    // ── Operaciones de caja (cajero) ──────────────────────────────
    Route::middleware('check-permission:cash.operate')->prefix('cash')->name('cash.')->group(function () {
        Route::post('/open',                       [CashSessionController::class, 'open'])->name('open');
        Route::post('/session/{session}/close',    [CashSessionController::class, 'close'])->name('session.close');
        Route::get('/session/{session}',           [CashSessionController::class, 'show'])->name('session.show');
        Route::post('/session/{session}/movement', [CashSessionController::class, 'addMovement'])->name('movement.store');
    });
});
