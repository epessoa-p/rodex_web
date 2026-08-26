<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Versión API de CheckPermission: exige al menos uno de los permisos indicados
 * en la empresa activa, respondiendo JSON 403 si falla.
 * Uso: ->middleware('api.permission:sales.create,sales.view').
 */
class CheckPermissionApi
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if ($user?->is_super_admin) {
            return $next($request);
        }

        $company = $request->attributes->get('tenant_company');

        if ($company) {
            foreach ($permissions as $permission) {
                if ($user->hasPermissionInCompany($permission, $company)) {
                    return $next($request);
                }
            }
        }

        return response()->json([
            'message'     => 'No tienes permiso para esta acción.',
            'code'        => 'permission_denied',
            'permissions' => $permissions,
        ], 403);
    }
}
