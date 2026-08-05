<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * ¿La empresa ya agotó el cupo de su plan para este recurso?
     * Claves válidas: 'users', 'branches', 'products'.
     *
     * El super_admin (operador de la plataforma) no está sujeto a límites.
     */
    protected function planLimitReached(?int $companyId, string $key): bool
    {
        if (!$companyId || auth()->user()?->is_super_admin) {
            return false;
        }

        $company = Company::find($companyId);

        return $company !== null && !$company->withinLimit($key);
    }

    /** Mensaje estándar cuando se alcanza el límite del plan. */
    protected function planLimitMessage(string $label): string
    {
        return "Alcanzaste el límite de {$label} de tu plan. Contacta a tu proveedor para ampliarlo.";
    }
}
