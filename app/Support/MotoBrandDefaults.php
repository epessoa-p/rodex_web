<?php

namespace App\Support;

use App\Models\Motos\MotoBrand;

/**
 * Marcas de moto comunes (Bolivia) para sembrar el catálogo de una empresa nueva
 * y acelerar el onboarding. Se usa al crear empresa y en el backfill de existentes.
 */
class MotoBrandDefaults
{
    /** Marca => país de origen. */
    public const BRANDS = [
        'Honda'    => 'Japón',
        'Yamaha'   => 'Japón',
        'Suzuki'   => 'Japón',
        'Kawasaki' => 'Japón',
        'Bajaj'    => 'India',
        'TVS'      => 'India',
        'Hero'     => 'India',
        'KTM'      => 'Austria',
        'Keeway'   => 'China',
        'Loncin'   => 'China',
        'Zongshen' => 'China',
        'Haojue'   => 'China',
        'Sukida'   => 'China',
        'Kenton'   => 'China',
        'Vento'    => 'México',
    ];

    /**
     * Siembra las marcas por defecto para una empresa (idempotente: no duplica).
     */
    public static function seedFor(int $companyId): void
    {
        foreach (self::BRANDS as $name => $country) {
            MotoBrand::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['country' => $country, 'active' => true]
            );
        }
    }
}
