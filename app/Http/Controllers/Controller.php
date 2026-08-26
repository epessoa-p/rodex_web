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

    /**
     * Estado del cupo del plan para pintar el botón "crear" en las vistas:
     * uso actual, tope efectivo (con override de empresa) y si ya se alcanzó.
     * Claves válidas: 'users', 'branches', 'products'.
     *
     * El super_admin no está sujeto a límites => nunca "reached".
     */
    protected function planLimitStatus(?int $companyId, string $key): array
    {
        if (!$companyId || auth()->user()?->is_super_admin) {
            return ['reached' => false, 'usage' => 0, 'max' => null, 'unlimited' => true];
        }

        $company = Company::find($companyId);

        if ($company === null) {
            return ['reached' => false, 'usage' => 0, 'max' => null, 'unlimited' => true];
        }

        $max   = $company->effectiveLimit($key);
        $usage = $company->usageFor($key);

        return [
            'reached'   => $max !== null && $usage >= $max,
            'usage'     => $usage,
            'max'       => $max,
            'unlimited' => $max === null,
        ];
    }

    /**
     * Estado de cupos de una empresa para MOSTRAR (indicador de "quedan N"),
     * siempre referido a la empresa indicada, sin la excepción de super_admin
     * (que sí aplica para el bloqueo en planLimitStatus). Útil cuando el
     * super_admin edita datos de una empresa y quiere ver sus cupos reales.
     */
    protected function companyLimitStatus(?int $companyId, string $key): array
    {
        $company = $companyId ? Company::find($companyId) : null;

        if ($company === null) {
            return ['usage' => 0, 'max' => null, 'unlimited' => true];
        }

        $max = $company->effectiveLimit($key);

        return [
            'usage'     => $company->usageFor($key),
            'max'       => $max,
            'unlimited' => $max === null,
        ];
    }
}
