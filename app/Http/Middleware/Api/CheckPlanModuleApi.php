<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versión API de CheckPlanModule: bloquea con JSON 403 si el plan de la empresa
 * no incluye el módulo. Uso: ->middleware('api.plan:sales').
 *
 * Acepta varios módulos con semántica OR: ->middleware('api.plan:motos,rentals').
 */
class CheckPlanModuleApi
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $company = $request->attributes->get('tenant_company');

        foreach ($modules as $module) {
            if ($company && $company->planAllows($module)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'Tu plan no incluye este módulo.',
            'code'    => 'plan_module_forbidden',
            'modules' => $modules,
        ], 403);
    }
}
