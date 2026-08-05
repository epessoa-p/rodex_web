<?php

namespace App\Providers;

use App\Support\Tenancy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolvedor de empresa activa (tenant) compartido por petición.
        $this->app->singleton(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Usar la paginación con estilos de Bootstrap 5 en toda la app
        Paginator::useBootstrapFive();

        // @module('workshop') ... @endmodule
        // Oculta del menú los módulos que el plan de la empresa no incluye.
        // El bloqueo real lo hace el middleware 'plan:' en las rutas.
        Blade::if('module', function (string $feature) {
            $user = auth()->user();

            if (!$user) {
                return false;
            }

            if ($user->is_super_admin) {
                return true;
            }

            return (bool) $user->getCurrentCompany()?->planAllows($feature);
        });
    }
}
