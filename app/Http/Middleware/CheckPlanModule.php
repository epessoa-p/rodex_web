<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso a un módulo que el plan contratado no incluye.
 *
 * Uso en rutas:  ->middleware('plan:workshop')
 *
 * Es la frontera de seguridad real: ocultar el módulo del menú no basta,
 * porque la URL se puede escribir a mano.
 */
class CheckPlanModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        if (!auth()->check()) {
            return redirect('login');
        }

        $user = auth()->user();

        // El operador de la plataforma ve todos los módulos.
        if ($user->is_super_admin) {
            return $next($request);
        }

        $company = $user->getCurrentCompany();

        if (!$company || !$company->planAllows($module)) {
            return response()->view('errors.plan', [
                'module'  => $module,
                'company' => $company,
            ], 403);
        }

        return $next($request);
    }
}
