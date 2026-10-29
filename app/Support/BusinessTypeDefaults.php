<?php

namespace App\Support;

use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductUnit;
use App\Models\Motos\MotoBrand;

/**
 * Rubro de la empresa (motorepuestos, autorepuestos, lubricentro) y los
 * catálogos base que se siembran en el alta para arrancar de inmediato:
 * marcas de vehículo, categorías, marcas de producto, unidades y orígenes.
 * Todo idempotente: re-sembrar no duplica ni pisa lo que el cliente editó.
 */
class BusinessTypeDefaults
{
    /** Rubros: valor => etiqueta. */
    public const OPTIONS = [
        'moto'        => 'Motorepuestos',
        'auto'        => 'Autorepuestos',
        'lubricentro' => 'Lubricentro',
        'none'        => 'Otro / sin precarga',
    ];

    /** Qué se precarga en cada rubro (texto de ayuda del formulario). */
    public const HINTS = [
        'moto'        => 'Marcas de moto (Honda, Yamaha, Bajaj…) y categorías de repuestos.',
        'auto'        => 'Marcas de auto (Toyota, Nissan, Hyundai…) y categorías de repuestos.',
        'lubricentro' => 'Marcas de auto y moto, categorías (aceite de motor, de caja, grasas, filtros…) y marcas de lubricantes y filtros.',
        'none'        => 'Solo unidades y orígenes.',
    ];

    /** Categorías base de repuestos (moto y auto). */
    public const PARTS_CATEGORIES = [
        'Motor', 'Frenos', 'Suspensión', 'Transmisión', 'Eléctrico', 'Filtros',
        'Lubricantes', 'Llantas y neumáticos', 'Carrocería', 'Accesorios',
    ];

    /** Categorías de lubricentro. */
    public const LUBRICENTRO_CATEGORIES = [
        'Aceite de motor', 'Aceite de motor moto (2T/4T)', 'Aceite de caja / transmisión',
        'ATF (caja automática)', 'Aceite hidráulico / dirección', 'Grasas', 'Refrigerante',
        'Líquido de frenos', 'Aditivos', 'Filtro de aceite', 'Filtro de aire',
        'Filtro de combustible', 'Filtro de cabina', 'Plumillas limpiaparabrisas',
        'Baterías', 'Limpieza y accesorios', 'Servicios',
    ];

    /** Marcas de producto de lubricentro: lubricantes y filtros. */
    public const LUBRICENTRO_BRANDS = [
        'Castrol', 'Mobil', 'Shell', 'Total', 'Valvoline', 'Motul', 'Repsol', 'YPF',
        'Petronas', 'Lubrax', 'Havoline', 'Liqui Moly',
        'Mann', 'Wix', 'Fram', 'Bosch', 'Mahle', 'Tecfil', 'Sakura',
    ];

    /** Unidades para cualquier rubro. */
    public const UNITS = [
        'Unidad', 'Pieza', 'Juego', 'Caja', 'Par', 'Litro', 'Kilogramo', 'Metro', 'Docena',
    ];

    /** Unidades extra del lubricentro (envases de aceite y grasa). */
    public const LUBRICENTRO_UNITS = ['Galón', 'Cuarto', 'Balde', 'Tambor'];

    /** Siembra los catálogos base del rubro para una empresa. */
    public static function seedFor(int $companyId, string $type = 'moto'): void
    {
        $vehicleBrands = match ($type) {
            'moto'        => MotoBrandDefaults::BRANDS,
            'auto'        => MotoBrandDefaults::AUTO_BRANDS,
            'lubricentro' => MotoBrandDefaults::AUTO_BRANDS + MotoBrandDefaults::BRANDS,
            default       => [],
        };
        foreach ($vehicleBrands as $name => $country) {
            MotoBrand::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['country' => $country, 'active' => true]
            );
        }

        $categories = match ($type) {
            'moto', 'auto' => self::PARTS_CATEGORIES,
            'lubricentro'  => self::LUBRICENTRO_CATEGORIES,
            default        => [],
        };
        self::seedNames(ProductCategory::class, $companyId, $categories);

        if ($type === 'lubricentro') {
            self::seedNames(ProductBrand::class, $companyId, self::LUBRICENTRO_BRANDS);
        }

        $units = $type === 'lubricentro' ? [...self::UNITS, ...self::LUBRICENTRO_UNITS] : self::UNITS;
        self::seedNames(ProductUnit::class, $companyId, $units);

        ProductOriginDefaults::seedFor($companyId);
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private static function seedNames(string $model, int $companyId, array $names): void
    {
        foreach ($names as $name) {
            $model::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => $name],
                ['active' => true]
            );
        }
    }
}
