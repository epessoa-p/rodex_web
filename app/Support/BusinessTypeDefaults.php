<?php

namespace App\Support;

use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductUnit;
use App\Models\Motos\MotoBrand;

/**
 * Rubro de la empresa (motorepuestos, autorepuestos, lubricentro, neumáticos) y los
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
        'neumaticos'  => 'Neumáticos y aros',
        'none'        => 'Otro / sin precarga',
    ];

    /** Qué se precarga en cada rubro (texto de ayuda del formulario). */
    public const HINTS = [
        'moto'        => 'Marcas de moto (Honda, Yamaha, Bajaj…) y categorías de repuestos.',
        'auto'        => 'Marcas de auto (Toyota, Nissan, Hyundai…) y categorías de repuestos.',
        'lubricentro' => 'Marcas de auto y moto, categorías (aceite de motor, de caja, grasas, filtros…) y marcas de lubricantes y filtros.',
        'neumaticos'  => 'Marcas de auto y moto, categorías (neumáticos por tipo de vehículo, aros, cámaras, válvulas…), marcas de neumáticos y aros, y servicios. Consejo: pon la medida en el nombre del producto (ej. 205/55 R16) para encontrarla rápido en el POS.',
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

    /**
     * Categorías de neumáticos y aros. Las categorías no tienen jerarquía, así que el
     * tipo va en el nombre; la medida (205/55 R16) va en el nombre del producto.
     * Moto y camioneta se dividen por uso porque así los pide el cliente.
     */
    public const NEUMATICOS_CATEGORIES = [
        'Neumáticos auto',
        'Neumáticos camioneta / SUV ruta (HT)', 'Neumáticos camioneta todo terreno (AT/MT)',
        'Neumáticos camión y bus',
        'Neumáticos moto pistera', 'Neumáticos moto doble propósito', 'Neumáticos moto todo terreno',
        'Neumáticos agrícolas / industriales',
        'Aros de aleación', 'Aros de acero', 'Aros de moto',
        'Cámaras (tubos)', 'Válvulas', 'Pernos y tuercas de rueda', 'Plomos de balanceo',
        'Parches y reparación', 'Accesorios', 'Servicios',
    ];

    /** Marcas de producto de neumáticos y aros. */
    public const NEUMATICOS_BRANDS = [
        'Bridgestone', 'Firestone', 'Michelin', 'BFGoodrich', 'Goodyear', 'Pirelli', 'Continental',
        'Yokohama', 'Hankook', 'Kumho', 'Dunlop', 'Toyo', 'Falken',
        'Linglong', 'Triangle', 'Westlake', 'Maxxis', 'Sailun', 'Metzeler',
        'Enkei', 'OZ', 'BBS',
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
            'lubricentro', 'neumaticos' => MotoBrandDefaults::AUTO_BRANDS + MotoBrandDefaults::BRANDS,
            default       => [],
        };
        foreach ($vehicleBrands as $name => $country) {
            MotoBrand::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => mb_strtoupper($name)],
                ['country' => mb_strtoupper($country), 'active' => true]
            );
        }

        $categories = match ($type) {
            'moto', 'auto' => self::PARTS_CATEGORIES,
            'lubricentro'  => self::LUBRICENTRO_CATEGORIES,
            'neumaticos'   => self::NEUMATICOS_CATEGORIES,
            default        => [],
        };
        self::seedNames(ProductCategory::class, $companyId, $categories);

        $productBrands = match ($type) {
            'lubricentro' => self::LUBRICENTRO_BRANDS,
            'neumaticos'  => self::NEUMATICOS_BRANDS,
            default       => [],
        };
        self::seedNames(ProductBrand::class, $companyId, $productBrands);

        $units = $type === 'lubricentro' ? [...self::UNITS, ...self::LUBRICENTRO_UNITS] : self::UNITS;
        self::seedNames(ProductUnit::class, $companyId, $units);

        ProductOriginDefaults::seedFor($companyId);
    }

    /**
     * Los catálogos precargados se guardan en MAYÚSCULAS.
     *
     * @param class-string<\Illuminate\Database\Eloquent\Model> $model
     */
    private static function seedNames(string $model, int $companyId, array $names): void
    {
        foreach ($names as $name) {
            $model::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => mb_strtoupper($name)],
                ['active' => true]
            );
        }
    }
}
