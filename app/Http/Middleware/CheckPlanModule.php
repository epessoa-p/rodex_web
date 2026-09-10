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
 * Acepta VARIOS módulos con semántica OR (igual que CheckPermission con los
 * permisos): ->middleware('plan:motos,rentals') pasa si el plan incluye alguno.
 * Sirve para datos maestros compartidos por más de un módulo, como el
 * inventario de motos, que usan tanto la venta como el alquiler.
 *
 * Es la frontera de seguridad real: ocultar el módulo del menú no basta,
 * porque la URL se puede escribir a mano.
 */
class CheckPlanModule
{
    public function handle(Request $request, Closure $next, string ...$modules): Response
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

        foreach ($modules as $module) {
            if ($company && $company->planAllows($module)) {
                return $next($request);
            }
        }

        return response()->view('errors.plan', [
            'modules' => $modules,
            'company' => $company,
        ], 403);
    }
}
