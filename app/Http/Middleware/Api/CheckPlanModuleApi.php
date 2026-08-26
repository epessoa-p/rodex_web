<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versión API de CheckPlanModule: bloquea con JSON 403 si el plan de la empresa
 * no incluye el módulo. Uso: ->middleware('api.plan:sales').
 */
class CheckPlanModuleApi
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $company = $request->attributes->get('tenant_company');

        if (! $company || ! $company->planAllows($module)) {
            return response()->json([
                'message' => 'Tu plan no incluye este módulo.',
                'code'    => 'plan_module_forbidden',
                'module'  => $module,
            ], 403);
        }

        return $next($request);
    }
}
