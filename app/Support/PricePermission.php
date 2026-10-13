<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;

/**
 * ¿Puede el usuario cambiar el precio o el costo de productos ya creados?
 * Permiso 'products.prices' (Cargos). Crear un producto con su precio inicial
 * no lo requiere; cambiarlo después, sí.
 */
class PricePermission
{
    public const SLUG = 'products.prices';

    public static function allowed(?User $user = null, ?Company $company = null): bool
    {
        $user ??= auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->is_super_admin) {
            return true;
        }
        // API: la empresa viene en el request (X-Company-Id); web: la activa.
        $company ??= request()->attributes->get('tenant_company') ?? $user->getCurrentCompany();

        return $user->hasPermissionInCompany(self::SLUG, $company);
    }

    public const DENIED = 'No tienes permiso para cambiar precios ni costos. Pídeselo al administrador.';
}
