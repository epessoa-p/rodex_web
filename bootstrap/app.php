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
        // 1) SetTenant fija la empresa activa (aísla los datos por global scope).
        // 2) EnsureSubscriptionActive corta el acceso si la suscripción venció.
        $middleware->web(append: [
            \App\Http\Middleware\SetTenant::class,
            \App\Http\Middleware\EnsureSubscriptionActive::class,
        ]);

        $middleware->alias([
            'check-role' => \App\Http\Middleware\CheckRole::class,
            'check-company' => \App\Http\Middleware\CheckCompany::class,
            'check-permission' => \App\Http\Middleware\CheckPermission::class,
            'set-tenant' => \App\Http\Middleware\SetTenant::class,
            'subscription' => \App\Http\Middleware\EnsureSubscriptionActive::class,
            'plan' => \App\Http\Middleware\CheckPlanModule::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
