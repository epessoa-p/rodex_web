<?php

namespace App\Http\Controllers\Inventory\Concerns;

use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductOrigin;
use App\Models\Inventory\ProductUnit;
use App\Models\Motos\MotoModel;
use App\Models\Product;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Importación GENÉRICA de productos por Excel (SaaS).
 *
 * - Lee las columnas por NOMBRE DE ENCABEZADO (no por posición fija), tolerante
 *   al orden. La plantilla estándar define los encabezados, pero se aceptan
 *   alias comunes por compatibilidad.
 * - Sin convenciones a medida (nada de extraer códigos de paréntesis "(999)").
 * - Código/SKU opcional: si no viene, se autogenera genérico (PRD-00001).
 * - "Modelos compatibles" son modelos de moto que se registran/enlazan en el catálogo (moto_models).
 */
trait ParsesProductImport
{
    /** Encabezados de la plantilla estándar (orden sugerido). */
    protected function importHeaders(): array
    {
        return ['Nombre', 'Categoría', 'Marca', 'Origen', 'Precio', 'Costo', 'Stock', 'Unidad', 'Código', 'Modelos compatibles', 'Descripción'];
    }

    /** Alias aceptados por campo (encabezados normalizados: minúscula, sin acentos). */
    protected function headerAliases(): array
    {
        return [
            'name'     => ['nombre', 'nombre producto', 'producto'],
            'category' => ['categoria', 'categoria producto'],
            'brand'    => ['marca'],
            'origin'   => ['origen', 'procedencia', 'pais', 'pais de origen', 'pais origen'],
            'price'    => ['precio', 'precio venta', 'pvp'],
            'cost'     => ['costo', 'precio costo'],
            'qty'      => ['stock', 'cantidad', 'cantidad disponible', 'existencia'],
            'unit'     => ['unidad', 'unidad de medida'],
            'code'     => ['codigo', 'code', 'sku'],
            'models'   => ['modelos compatibles', 'modelos', 'modelo(s)', 'modelo', 'compatibilidad'],
            'notes'    => ['descripcion', 'detalle', 'observaciones'],
        ];
    }

    protected function normalizeHeader(string $h): string
    {
        return (string) Str::of($h)->lower()->ascii()->replaceMatches('/\s+/', ' ')->trim();
    }

    /**
     * Mapea la fila de encabezados a [campo => letra de columna].
     */
    protected function mapHeaders(array $headerRow): array
    {
        $aliases = $this->headerAliases();
        $map = [];

        foreach ($headerRow as $col => $text) {
            $norm = $this->normalizeHeader((string) $text);
            if ($norm === '') {
                continue;
            }
            foreach ($aliases as $field => $names) {
                if (! isset($map[$field]) && in_array($norm, $names, true)) {
                    $map[$field] = $col;
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * Lee el Excel y devuelve filas normalizadas [name, category, brand, price,
     * cost, qty, unit, code, models, notes]. Requiere la columna Nombre.
     */
    protected function parseImportRows(string $fullPath): array
    {
        $raw = IOFactory::load($fullPath)->getActiveSheet()->toArray(null, true, true, true);
        if (empty($raw)) {
            return [];
        }

        $headerRow = reset($raw);
        $map = $this->mapHeaders($headerRow);

        // Sin columna Nombre no se puede importar.
        if (! isset($map['name'])) {
            return [];
        }

        $get = fn (array $row, string $field) => isset($map[$field]) ? trim((string) ($row[$map[$field]] ?? '')) : '';

        $out = [];
        $first = true;
        foreach ($raw as $row) {
            if ($first) { $first = false; continue; } // saltar cabecera

            $name = $get($row, 'name');
            if ($name === '') {
                continue;
            }

            $out[] = [
                'name'     => $name,
                'category' => $get($row, 'category'),
                'brand'    => $get($row, 'brand'),
                'origin'   => $get($row, 'origin'),
                'price'    => (float) str_replace(',', '.', $get($row, 'price')),
                'cost'     => (float) str_replace(',', '.', $get($row, 'cost')),
                'qty'      => (float) str_replace(',', '.', $get($row, 'qty')),
                'unit'     => $get($row, 'unit'),
                'code'     => $get($row, 'code') ?: null,
                'models'   => $get($row, 'models'),
                'notes'    => $get($row, 'notes'),
            ];
        }

        return $out;
    }

    /**
     * Crea o actualiza un producto (categoría/marca por nombre) SIN tocar stock.
     * Devuelve el producto. Emparejamiento por nombre (clave natural del negocio).
     */
    protected function upsertProduct(array $d, int $companyId, array &$counters): Product
    {
        $name = trim((string) ($d['name'] ?? ''));

        $categoryId = null;
        $catName = trim((string) ($d['category'] ?? ''));
        if ($catName !== '') {
            $categoryId = ProductCategory::firstOrCreate(
                ['company_id' => $companyId, 'name' => $catName],
                ['active' => true]
            )->id;
        }

        $brandId = null;
        $brandName = trim((string) ($d['brand'] ?? ''));
        if ($brandName !== '') {
            $brandId = ProductBrand::firstOrCreate(
                ['company_id' => $companyId, 'name' => $brandName],
                ['active' => true]
            )->id;
        }

        $originId = null;
        $originName = trim((string) ($d['origin'] ?? ''));
        if ($originName !== '') {
            $originId = ProductOrigin::firstOrCreate(
                ['company_id' => $companyId, 'name' => $originName],
                ['active' => true]
            )->id;
        }

        $payload = [
            'category_id' => $categoryId,
            'brand_id'    => $brandId,
            'origin_id'   => $originId,
            'code'        => $d['code'] ?? null,
            'cost'        => (float) ($d['cost'] ?? 0),
            'price'       => (float) ($d['price'] ?? 0),
            'description' => trim((string) ($d['notes'] ?? '')) ?: null,
        ];

        $product = Product::where('company_id', $companyId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($product) {
            $product->update($payload);
            $counters['updated'] = ($counters['updated'] ?? 0) + 1;
        } else {
            $unit = trim((string) ($d['unit'] ?? '')) ?: config('inventory.default_unit', 'Unidad');
            // Registra la unidad en el catálogo de la empresa si no existe.
            ProductUnit::firstOrCreate(
                ['company_id' => $companyId, 'name' => $unit],
                ['active' => true]
            );
            $product = Product::create(array_merge($payload, [
                'company_id'    => $companyId,
                'name'          => $name,
                'sku'           => $this->generateProductCode($companyId),
                'unit'          => $unit,
                'min_stock'     => 0,
                'current_stock' => 0,
                'active'        => true,
            ]));
            $counters['created'] = ($counters['created'] ?? 0) + 1;
        }

        // "Modelos compatibles" → filas estructuradas en moto_models (por empresa)
        // enlazadas al producto por el pivote. Se crean por nombre, sin marca.
        $this->syncCompatibleModels($product, $d['models'] ?? null, $companyId);

        return $product;
    }

    /**
     * Crea (o reutiliza) los MotoModel de la empresa a partir del texto de modelos
     * (tokens separados por comas) y los enlaza al producto sin quitar los previos.
     */
    protected function syncCompatibleModels(Product $product, ?string $modelsText, int $companyId): void
    {
        $tokens = collect(explode(',', (string) $modelsText))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->unique(fn ($t) => mb_strtolower($t));

        if ($tokens->isEmpty()) {
            return;
        }

        $ids = [];
        foreach ($tokens as $modelName) {
            // MySQL compara varchar sin distinguir mayúsculas, así que firstOrCreate
            // reutiliza un modelo existente con el mismo nombre (evita duplicados).
            $model = MotoModel::withoutGlobalScopes()->firstOrCreate(
                ['company_id' => $companyId, 'name' => $modelName],
                ['active' => true, 'suggested_price' => 0, 'daily_rate' => 0]
            );
            $ids[] = $model->id;
        }

        $product->motoModels()->syncWithoutDetaching($ids);
    }

    /**
     * Código/SKU interno genérico y único por empresa: {prefijo}-{correlativo}.
     */
    protected function generateProductCode(int $companyId): string
    {
        $prefix = config('inventory.code_prefix', 'PRD');
        $seq = Product::withTrashed()->where('company_id', $companyId)->count() + 1;

        do {
            $sku = $prefix . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
            $seq++;
        } while (Product::withTrashed()->where('company_id', $companyId)->where('sku', $sku)->exists());

        return $sku;
    }
}
