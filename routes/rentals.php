<?php

use App\Http\Controllers\Rentals\RentalCalendarController;
use App\Http\Controllers\Rentals\RentalController;
use App\Http\Controllers\Rentals\RentalDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'plan:rentals'])->prefix('rentals')->name('rentals.')->group(function () {

    // ── Dashboard ─────────────────────────────────────────────
    Route::get('/', [RentalDashboardController::class, 'index'])->name('dashboard')->middleware('check-permission:rentals-dashboard.view');

    // ── Calendario ────────────────────────────────────────────
    Route::get('/calendar',      [RentalCalendarController::class, 'index'])->name('calendar')->middleware('check-permission:rentals.view');
    Route::get('/calendar/data', [RentalCalendarController::class, 'data'])->name('calendar.data')->middleware('check-permission:rentals.view');

    // ── Reservas ──────────────────────────────────────────────
    Route::get('/reservations',        [RentalController::class, 'reservations'])->name('reservations')->middleware('check-permission:rentals.view');
    Route::get('/reservations/create', [RentalController::class, 'create'])->name('create')->middleware('check-permission:rentals.create');
    Route::post('/reservations',       [RentalController::class, 'store'])->name('store')->middleware('check-permission:rentals.create');

    // ── Contratos ─────────────────────────────────────────────
    Route::get('/contracts',            [RentalController::class, 'contracts'])->name('contracts')->middleware('check-permission:rentals.view');
    Route::post('/{rental}/confirm',    [RentalController::class, 'confirm'])->name('confirm')->middleware('check-permission:rentals.edit');

    // ── Alquileres en curso ───────────────────────────────────
    Route::get('/active',               [RentalController::class, 'active'])->name('active')->middleware('check-permission:rentals.view');

    // ── Entregas ──────────────────────────────────────────────
    Route::get('/deliveries',           [RentalController::class, 'deliveries'])->name('deliveries')->middleware('check-permission:rentals.view');
    Route::get('/{rental}/deliver',     [RentalController::class, 'deliver'])->name('deliver')->middleware('check-permission:rentals.deliver');
    Route::post('/{rental}/deliver',    [RentalController::class, 'storeDelivery'])->name('deliver.store')->middleware('check-permission:rentals.deliver');

    // ── Devoluciones ──────────────────────────────────────────
    Route::get('/returns',              [RentalController::class, 'returns'])->name('returns')->middleware('check-permission:rentals.view');
    Route::get('/{rental}/return',      [RentalController::class, 'returnForm'])->name('return')->middleware('check-permission:rentals.return');
    Route::post('/{rental}/return',     [RentalController::class, 'storeReturn'])->name('return.store')->middleware('check-permission:rentals.return');

    // ── Cobros de renta (cuotas) ──────────────────────────────
    Route::get('/collections',          [RentalController::class, 'collections'])->name('collections')->middleware('check-permission:rentals.view');

    // ── Pagos ─────────────────────────────────────────────────
    Route::get('/payments',             [RentalController::class, 'payments'])->name('payments')->middleware('check-permission:rentals.view');
    Route::post('/{rental}/pay',        [RentalController::class, 'registerPayment'])->name('pay')->middleware('check-permission:rentals.pay');

    // ── Penalizaciones ────────────────────────────────────────
    Route::get('/penalties',            [RentalController::class, 'penalties'])->name('penalties')->middleware('check-permission:rentals.view');
    Route::post('/{rental}/penalty',    [RentalController::class, 'addPenalty'])->name('penalty')->middleware('check-permission:rentals.edit');

    // ── Historial ─────────────────────────────────────────────
    Route::get('/history',              [RentalController::class, 'history'])->name('history')->middleware('check-permission:rentals.view');

    // ── Show / Cancelar ───────────────────────────────────────
    Route::get('/{rental}',             [RentalController::class, 'show'])->name('show')->middleware('check-permission:rentals.view');
    Route::post('/{rental}/cancel',     [RentalController::class, 'cancel'])->name('cancel')->middleware('check-permission:rentals.edit');
});
