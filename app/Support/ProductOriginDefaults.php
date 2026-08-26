<?php

namespace App\Support;

use App\Models\Inventory\ProductOrigin;

/**
 * Orígenes (países) por defecto para repuestos de moto. Se siembran al crear una
 * empresa y en el backfill de existentes; el import también los crea si no existen.
 */
class ProductOriginDefaults
{
    public const ORIGINS = [
        'Nacional', 'Brasil', 'China', 'Japón', 'India', 'Tailandia',
        'Taiwán', 'Indonesia', 'Corea', 'Colombia', 'Argentina',
    ];

    /** Siembra los orígenes por defecto para una empresa (idempotente). */
    public static function seedFor(int $companyId): void
    {
        foreach (self::ORIGINS as $name) {
            ProductOrigin::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['active' => true]
            );
        }
    }
}
