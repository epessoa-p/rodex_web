<?php

namespace App\Http\Middleware\Api;

use App\Models\Company;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve la empresa activa (tenant) para la API a partir del header
 * X-Company-Id (no hay sesión). Debe ejecutarse DESPUÉS de auth:sanctum.
 *
 * - super_admin: puede pasar cualquier empresa; sin header => null (ve todo).
 * - usuario normal: la empresa debe estar entre sus empresas activas.
 *     · 1 empresa y sin header  => se usa esa.
 *     · varias y sin header     => 409 (debe elegir).
 *     · empresa ajena           => 403.
 *
 * Fija app(Tenancy::class)->set($id) para que el global scope aísle los datos,
 * y deja la Company resuelta en request->attributes('tenant_company') para que
 * los middlewares y controladores de la API la usen sin depender de la sesión.
 */
class SetTenantFromApi
{
    public function __construct(protected Tenancy $tenancy)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $headerId = $request->header('X-Company-Id');
        $headerId = ($headerId !== null && $headerId !== '') ? (int) $headerId : null;

        if ($user->is_super_admin) {
            // El operador puede operar sobre cualquier empresa (o ninguna).
            $company = $headerId ? Company::find($headerId) : null;
            $this->tenancy->set($company?->id);
            $request->attributes->set('tenant_company', $company);

            return $next($request);
        }

        $active = $user->activeCompanies();

        if ($headerId) {
            $company = $active->find($headerId);
            if (! $company) {
                return response()->json([
                    'message' => 'No perteneces a la empresa indicada.',
                ], 403);
            }
        } else {
            $companies = $active->get();

            if ($companies->isEmpty()) {
                return response()->json([
                    'message' => 'No tienes acceso a ninguna empresa.',
                ], 403);
            }

            if ($companies->count() > 1) {
                return response()->json([
                    'message'   => 'Debes seleccionar una empresa (envía el header X-Company-Id).',
                    'code'      => 'company_required',
                    'companies' => $companies->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                ], 409);
            }

            $company = $companies->first();
        }

        $this->tenancy->set($company->id);
        $request->attributes->set('tenant_company', $company);

        return $next($request);
    }
}
