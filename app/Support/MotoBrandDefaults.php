<?php

namespace App\Support;

use App\Models\Motos\MotoBrand;

/**
 * Marcas de vehículo comunes (Bolivia) para sembrar el catálogo de una empresa
 * nueva y acelerar el onboarding. El super admin elige en el alta si precarga
 * marcas de motos, de autos o ninguna (motorepuestos vs. autorepuestos).
 * La tabla sigue llamándose moto_brands por historia: guarda marcas de vehículo.
 */
class MotoBrandDefaults
{
    /** Opciones del alta de empresa: valor => etiqueta. */
    public const OPTIONS = [
        'moto' => 'Motos',
        'auto' => 'Autos',
        'none' => 'Ninguna',
    ];

    /** Marcas de moto => país de origen. */
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

    /** Marcas de auto => país de origen. */
    public const AUTO_BRANDS = [
        'Toyota'     => 'Japón',
        'Nissan'     => 'Japón',
        'Suzuki'     => 'Japón',
        'Mitsubishi' => 'Japón',
        'Mazda'      => 'Japón',
        'Honda'      => 'Japón',
        'Hyundai'    => 'Corea del Sur',
        'Kia'        => 'Corea del Sur',
        'Chevrolet'  => 'Estados Unidos',
        'Ford'       => 'Estados Unidos',
        'Volkswagen' => 'Alemania',
        'Renault'    => 'Francia',
        'Chery'      => 'China',
        'JAC'        => 'China',
    ];

    /**
     * Siembra las marcas por defecto para una empresa (idempotente: no duplica).
     * $type: 'moto' (por defecto, como siempre), 'auto' o 'none'.
     */
    public static function seedFor(int $companyId, string $type = 'moto'): void
    {
        $brands = match ($type) {
            'auto'  => self::AUTO_BRANDS,
            'none'  => [],
            default => self::BRANDS,
        };

        foreach ($brands as $name => $country) {
            MotoBrand::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['country' => $country, 'active' => true]
            );
        }
    }
}
