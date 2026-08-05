<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija la empresa (tenant) activa para la petición a partir de la sesión.
 *
 * - super_admin => null (sin filtro, ve todas las empresas).
 * - usuario normal => session('current_company_id').
 * - invitado (no autenticado) => no se toca; el Tenancy queda en fallback perezoso
 *   (null), lo cual es seguro porque las rutas públicas no consultan datos de dominio
 *   salvo el catálogo de loyalty, que resuelve su tenant explícitamente por token.
 */
class SetTenant
{
    public function __construct(protected Tenancy $tenancy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            $companyId = $user->is_super_admin
                ? null
                : session('current_company_id');

            $this->tenancy->set($companyId);
        }

        return $next($request);
    }
}
