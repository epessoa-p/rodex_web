<?php

use App\Http\Controllers\Loyalty\LoyaltyCampaignController;
use App\Http\Controllers\Loyalty\LoyaltyCatalogController;
use App\Http\Controllers\Loyalty\LoyaltyDashboardController;
use App\Http\Controllers\Loyalty\LoyaltyMovementController;
use App\Http\Controllers\Loyalty\LoyaltyRedemptionController;
use App\Http\Controllers\Loyalty\LoyaltyReportController;
use App\Http\Controllers\Loyalty\LoyaltyRewardController;
use App\Http\Controllers\Loyalty\LoyaltySettingController;
use Illuminate\Support\Facades\Route;

// ── Catálogo público de recompensas (SIN autenticación) ───────
Route::get('/catalogo/{token}', [LoyaltyCatalogController::class, 'public'])->name('loyalty.catalog.public');

Route::middleware(['auth', 'plan:loyalty'])->prefix('loyalty')->name('loyalty.')->group(function () {

    // ── Dashboard (incluye ranking) ───────────────────────────
    Route::get('/', [LoyaltyDashboardController::class, 'index'])->name('dashboard')
        ->middleware('check-permission:loyalty-dashboard.view');

    // ── Configuración + reglas de acumulación ─────────────────
    Route::get('/settings',  [LoyaltySettingController::class, 'edit'])->name('settings.edit')
        ->middleware('check-permission:loyalty-settings.view');
    Route::put('/settings',  [LoyaltySettingController::class, 'update'])->name('settings.update')
        ->middleware('check-permission:loyalty-settings.edit');

    // ── Recompensas (catálogo) ────────────────────────────────
    Route::get('/rewards',                [LoyaltyRewardController::class, 'index'])->name('rewards.index')
        ->middleware('check-permission:loyalty-rewards.view');
    Route::get('/rewards/create',         [LoyaltyRewardController::class, 'create'])->name('rewards.create')
        ->middleware('check-permission:loyalty-rewards.create');
    Route::post('/rewards',               [LoyaltyRewardController::class, 'store'])->name('rewards.store')
        ->middleware('check-permission:loyalty-rewards.create');
    // Alta/edición por AJAX desde el panel doble (producto → recompensa)
    Route::post('/rewards/catalog',              [LoyaltyRewardController::class, 'catalogStore'])->name('rewards.catalog.store')
        ->middleware('check-permission:loyalty-rewards.create');
    Route::put('/rewards/{reward}/catalog',      [LoyaltyRewardController::class, 'catalogUpdate'])->name('rewards.catalog.update')
        ->middleware('check-permission:loyalty-rewards.edit');
    Route::get('/rewards/{reward}/edit',  [LoyaltyRewardController::class, 'edit'])->name('rewards.edit')
        ->middleware('check-permission:loyalty-rewards.edit');
    Route::put('/rewards/{reward}',       [LoyaltyRewardController::class, 'update'])->name('rewards.update')
        ->middleware('check-permission:loyalty-rewards.edit');
    Route::delete('/rewards/{reward}',    [LoyaltyRewardController::class, 'destroy'])->name('rewards.destroy')
        ->middleware('check-permission:loyalty-rewards.delete');

    // ── Canjes ────────────────────────────────────────────────
    Route::get('/redemptions',          [LoyaltyRedemptionController::class, 'index'])->name('redemptions.index')
        ->middleware('check-permission:loyalty-redemptions.view');
    Route::get('/redemptions/create',   [LoyaltyRedemptionController::class, 'create'])->name('redemptions.create')
        ->middleware('check-permission:loyalty.redeem');
    Route::post('/redemptions',         [LoyaltyRedemptionController::class, 'store'])->name('redemptions.store')
        ->middleware('check-permission:loyalty.redeem');
    Route::get('/clients/{client}/data', [LoyaltyRedemptionController::class, 'clientData'])->name('clients.data')
        ->middleware('check-permission:loyalty.redeem');

    // ── Movimientos de puntos ─────────────────────────────────
    Route::get('/movements', [LoyaltyMovementController::class, 'index'])->name('movements.index')
        ->middleware('check-permission:loyalty-movements.view');

    // ── Campañas (multiplicadores temporales) ─────────────────
    Route::get('/campaigns',                  [LoyaltyCampaignController::class, 'index'])->name('campaigns.index')
        ->middleware('check-permission:loyalty-campaigns.view');
    Route::get('/campaigns/create',           [LoyaltyCampaignController::class, 'create'])->name('campaigns.create')
        ->middleware('check-permission:loyalty-campaigns.create');
    Route::post('/campaigns',                 [LoyaltyCampaignController::class, 'store'])->name('campaigns.store')
        ->middleware('check-permission:loyalty-campaigns.create');
    Route::get('/campaigns/{campaign}/edit',  [LoyaltyCampaignController::class, 'edit'])->name('campaigns.edit')
        ->middleware('check-permission:loyalty-campaigns.edit');
    Route::put('/campaigns/{campaign}',       [LoyaltyCampaignController::class, 'update'])->name('campaigns.update')
        ->middleware('check-permission:loyalty-campaigns.edit');
    Route::delete('/campaigns/{campaign}',    [LoyaltyCampaignController::class, 'destroy'])->name('campaigns.destroy')
        ->middleware('check-permission:loyalty-campaigns.delete');

    // ── Reportes ──────────────────────────────────────────────
    Route::get('/reports', [LoyaltyReportController::class, 'index'])->name('reports.index')
        ->middleware('check-permission:loyalty-reports.view');
});
