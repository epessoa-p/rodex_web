<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/cash-registers.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/inventory.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/purchases.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/sales.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/workshop.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/motos.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/rentals.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/statistics.php'));
            \Illuminate\Support\Facades\Route::middleware('web')
                ->group(base_path('routes/loyalty.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check-role' => \App\Http\Middleware\CheckRole::class,
            'check-company' => \App\Http\Middleware\CheckCompany::class,
            'check-permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
