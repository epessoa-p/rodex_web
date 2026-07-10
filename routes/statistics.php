<?php

use App\Http\Controllers\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/statistics', [StatisticsController::class, 'index'])
        ->name('statistics.index')
        ->middleware('check-permission:statistics.view');
});
