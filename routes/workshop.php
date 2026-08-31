<?php

use App\Http\Controllers\Workshop\AppointmentController;
use App\Http\Controllers\Workshop\DeliveryController;
use App\Http\Controllers\Workshop\MechanicController;
use App\Http\Controllers\Workshop\MechanicPaymentController;
use App\Http\Controllers\Workshop\PublicWorkOrderController;
use App\Http\Controllers\Workshop\ServiceController;
use App\Http\Controllers\Workshop\WorkOrderController;
use App\Http\Controllers\Workshop\WorkshopDashboardController;
use Illuminate\Support\Facades\Route;

// ── Seguimiento público de la OT (SIN autenticación) ──────────
Route::get('/ot/{token}', [PublicWorkOrderController::class, 'show'])->name('workshop.public.track');

Route::middleware(['auth', 'plan:workshop'])->group(function () {

    // ── Dashboard ─────────────────────────────────────────────
    Route::get('workshop/dashboard', [WorkshopDashboardController::class, 'index'])
        ->name('workshop.dashboard')->middleware('check-permission:workshop-dashboard.view');

    // ── Servicios (catálogo) ──────────────────────────────────
    Route::prefix('workshop/services')->name('services.')->group(function () {
        Route::get('/',              [ServiceController::class, 'index'])->name('index')->middleware('check-permission:services.view');
        Route::get('/create',        [ServiceController::class, 'create'])->name('create')->middleware('check-permission:services.create');
        Route::post('/',             [ServiceController::class, 'store'])->name('store')->middleware('check-permission:services.create');
        Route::get('/{service}/edit',[ServiceController::class, 'edit'])->name('edit')->middleware('check-permission:services.edit');
        Route::put('/{service}',     [ServiceController::class, 'update'])->name('update')->middleware('check-permission:services.edit');
        Route::delete('/{service}',  [ServiceController::class, 'destroy'])->name('destroy')->middleware('check-permission:services.delete');
    });

    // ── Mecánicos (catálogo) ──────────────────────────────────
    Route::prefix('workshop/mechanics')->name('mechanics.')->group(function () {
        Route::get('/',               [MechanicController::class, 'index'])->name('index')->middleware('check-permission:mechanics.view');
        Route::get('/create',         [MechanicController::class, 'create'])->name('create')->middleware('check-permission:mechanics.create');
        Route::post('/quick',         [MechanicController::class, 'quickStore'])->name('quick-store')->middleware('check-permission:mechanics.create');
        Route::post('/',              [MechanicController::class, 'store'])->name('store')->middleware('check-permission:mechanics.create');
        Route::get('/{mechanic}/edit',[MechanicController::class, 'edit'])->name('edit')->middleware('check-permission:mechanics.edit');
        Route::put('/{mechanic}',     [MechanicController::class, 'update'])->name('update')->middleware('check-permission:mechanics.edit');
        Route::delete('/{mechanic}',  [MechanicController::class, 'destroy'])->name('destroy')->middleware('check-permission:mechanics.delete');
    });

    // ── Agenda / Citas ────────────────────────────────────────
    Route::prefix('workshop/agenda')->name('workshop.agenda.')->group(function () {
        Route::get('/',                    [AppointmentController::class, 'index'])->name('index')->middleware('check-permission:appointments.view');
        Route::post('/',                   [AppointmentController::class, 'store'])->name('store')->middleware('check-permission:appointments.create');
        Route::put('/{appointment}',       [AppointmentController::class, 'update'])->name('update')->middleware('check-permission:appointments.edit');
        Route::post('/{appointment}/status',[AppointmentController::class, 'changeStatus'])->name('status')->middleware('check-permission:appointments.edit');
        Route::post('/{appointment}/convert',[AppointmentController::class, 'convertToWorkOrder'])->name('convert')->middleware('check-permission:workshop.create');
        Route::delete('/{appointment}',    [AppointmentController::class, 'destroy'])->name('destroy')->middleware('check-permission:appointments.delete');
    });

    // ── Pago a mecánicos ──────────────────────────────────────
    Route::prefix('workshop/mechanic-payments')->name('workshop.mechanic-payments.')->group(function () {
        Route::get('/',                    [MechanicPaymentController::class, 'index'])->name('index')->middleware('check-permission:mechanic-payments.view');
        Route::get('/receipt/{payment}',   [MechanicPaymentController::class, 'receipt'])->name('receipt')->middleware('check-permission:mechanic-payments.view');
        Route::get('/{mechanic}',          [MechanicPaymentController::class, 'show'])->name('show')->middleware('check-permission:mechanic-payments.view');
        Route::post('/',           [MechanicPaymentController::class, 'store'])->name('store')->middleware('check-permission:mechanic-payments.pay');
    });

    // ── Recepción (alta de OT) ────────────────────────────────
    Route::get('workshop/reception',  [WorkOrderController::class, 'reception'])->name('workshop.reception')->middleware('check-permission:workshop.create');
    Route::post('workshop/reception', [WorkOrderController::class, 'storeReception'])->name('workshop.reception.store')->middleware('check-permission:workshop.create');

    // ── Historial ─────────────────────────────────────────────
    Route::get('workshop/history', [WorkOrderController::class, 'history'])->name('workshop.history')->middleware('check-permission:workshop.view');

    // ── Entregas ──────────────────────────────────────────────
    Route::prefix('workshop/deliveries')->name('workshop.deliveries.')->group(function () {
        Route::get('/',              [DeliveryController::class, 'index'])->name('index')->middleware('check-permission:workshop.view');
        Route::get('/create/{order}',[DeliveryController::class, 'create'])->name('create')->middleware('check-permission:workshop.deliver');
        Route::post('/store/{order}',[DeliveryController::class, 'store'])->name('store')->middleware('check-permission:workshop.deliver');
    });

    // ── Órdenes de Trabajo ────────────────────────────────────
    Route::prefix('workshop/orders')->name('workshop.orders.')->group(function () {
        Route::get('/',                     [WorkOrderController::class, 'index'])->name('index')->middleware('check-permission:workshop.view');
        Route::get('/{order}',              [WorkOrderController::class, 'show'])->name('show')->middleware('check-permission:workshop.view');
        Route::get('/{order}/print',        [WorkOrderController::class, 'print'])->name('print')->middleware('check-permission:workshop.view');
        Route::get('/{order}/edit',         [WorkOrderController::class, 'edit'])->name('edit')->middleware('check-permission:workshop.edit');
        Route::put('/{order}',              [WorkOrderController::class, 'update'])->name('update')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/diagnosis',   [WorkOrderController::class, 'diagnosis'])->name('diagnosis')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/mechanic',    [WorkOrderController::class, 'assignMechanic'])->name('mechanic')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/services',    [WorkOrderController::class, 'addService'])->name('services.add')->middleware('check-permission:workshop.edit');
        Route::delete('/{order}/services/{service}', [WorkOrderController::class, 'removeService'])->name('services.remove')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/parts',       [WorkOrderController::class, 'addPart'])->name('parts.add')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/parts/direct-purchase', [WorkOrderController::class, 'directPurchasePart'])->name('parts.purchase')->middleware('check-permission:workshop.edit');
        Route::delete('/{order}/parts/{part}', [WorkOrderController::class, 'removePart'])->name('parts.remove')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/status',      [WorkOrderController::class, 'changeStatus'])->name('status')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/payment',     [WorkOrderController::class, 'registerPayment'])->name('payment')->middleware('check-permission:workshop.deliver');
        Route::post('/{order}/cancel',      [WorkOrderController::class, 'cancel'])->name('cancel')->middleware('check-permission:workshop.edit');
        Route::post('/{order}/photos',         [WorkOrderController::class, 'addPhotos'])->name('photos.add')->middleware('check-permission:workshop.edit');
        Route::put('/{order}/photos/{photo}',  [WorkOrderController::class, 'updatePhoto'])->name('photos.update')->middleware('check-permission:workshop.edit');
        Route::delete('/{order}/photos/{photo}',[WorkOrderController::class, 'deletePhoto'])->name('photos.remove')->middleware('check-permission:workshop.edit');
    });
});
