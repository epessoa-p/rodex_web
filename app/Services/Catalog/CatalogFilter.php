<?php

namespace App\Services\Catalog;

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Motos\MotoModel;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filtros del catálogo público (página web y PDF): para mandar a un cliente
 * solo una parte (p. ej. Cascos, o lo compatible con CG y CB) y que el PDF no
 * salga pesado.
 *
 * Viajan en la URL con nombres cortos: ?cat=1,4&mar=2&mod=7,9&stock=1
 * Dentro de un filtro es "o" (Cascos o Aceites); entre filtros es "y".
 * Debe usarse dentro del contexto de la empresa (global scope), así un id de
 * otra empresa simplemente no encuentra nada.
 */
class CatalogFilter
{
    private const MAX_IDS = 50;

    /** Avisar antes de generar un PDF con más productos que esto. */
    public const PDF_WARN = 500;

    public function __construct(
        public readonly array $categories = [],
        public readonly array $brands = [],
        public readonly array $models = [],
        public readonly bool $onlyInStock = false,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            self::ids($request->query('cat')),
            self::ids($request->query('mar')),
            self::ids($request->query('mod')),
            filter_var($request->query('stock', false), FILTER_VALIDATE_BOOLEAN),
        );
    }

    /** "1,4" o ['1','4'] → [1, 4] (enteros positivos, sin repetir, con tope). */
    private static function ids($raw): array
    {
        $parts = is_array($raw) ? $raw : explode(',', (string) $raw);
        $ids = array_values(array_unique(array_filter(array_map('intval', $parts), fn ($v) => $v > 0)));

        return array_slice($ids, 0, self::MAX_IDS);
    }

    public function isEmpty(): bool
    {
        return ! $this->categories && ! $this->brands && ! $this->models && ! $this->onlyInStock;
    }

    /** Parámetros para armar la URL pública (sin los vacíos). */
    public function query(): array
    {
        return array_filter([
            'cat'   => implode(',', $this->categories),
            'mar'   => implode(',', $this->brands),
            'mod'   => implode(',', $this->models),
            'stock' => $this->onlyInStock ? 1 : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** Aplica los filtros a una consulta de productos de la sucursal. */
    public function apply(Builder $query, Branch $branch): Builder
    {
        if ($this->categories) {
            $query->whereIn('category_id', $this->categories);
        }
        if ($this->brands) {
            $query->whereIn('brand_id', $this->brands);
        }
        if ($this->models) {
            $query->whereHas('motoModels', fn ($m) => $m->whereIn('moto_models.id', $this->models));
        }
        if ($this->onlyInStock) {
            $ids = $branch->warehouse_id
                ? array_keys(array_filter(self::stockMap($branch->company_id, (int) $branch->warehouse_id), fn ($q) => $q > 0))
                : [];
            $query->whereIn('id', $ids ?: [0]);
        }

        return $query;
    }

    /** Productos activos del catálogo de la sucursal con estos filtros. */
    public function count(Branch $branch): int
    {
        return $this->apply(Product::where('active', true), $branch)->count();
    }

    /**
     * Texto del filtro para el encabezado: "Cascos, Aceites · Honda · CG 150, CB 190 · Solo disponibles".
     * null si no hay filtro.
     */
    public function labels(): ?string
    {
        $parts = [];
        if ($this->categories) {
            $parts[] = ProductCategory::whereIn('id', $this->categories)->orderBy('name')->pluck('name')->implode(', ');
        }
        if ($this->brands) {
            $parts[] = ProductBrand::whereIn('id', $this->brands)->orderBy('name')->pluck('name')->implode(', ');
        }
        if ($this->models) {
            $parts[] = MotoModel::whereIn('id', $this->models)->orderBy('name')->pluck('name')->implode(', ');
        }
        if ($this->onlyInStock) {
            $parts[] = 'Solo disponibles';
        }
        $parts = array_filter($parts);

        return $parts ? implode(' · ', $parts) : null;
    }

    /** Parte del nombre de archivo: "cascos-aceites" (corto). */
    public function slug(): string
    {
        $label = $this->labels();

        return $label ? \Illuminate\Support\Str::limit(\Illuminate\Support\Str::slug($label), 40, '') : '';
    }

    /**
     * Stock neto por producto en un almacén (derivado de inventory_movements).
     * Replica la lógica de StockController::warehouseStockMap.
     */
    public static function stockMap(int $companyId, int $warehouseId, ?array $productIds = null): array
    {
        // Si hay una página de productos, no se agrega el inventario completo.
        if ($productIds !== null && $productIds === []) {
            return [];
        }

        $in = InventoryMovement::where('company_id', $companyId)
            ->when($productIds !== null, fn ($q) => $q->whereIn('product_id', $productIds))
            ->where(function ($q) use ($warehouseId) {
                $q->where(fn ($w) => $w->where('warehouse_id', $warehouseId)->whereIn('type', ['in', 'adjustment']))
                  ->orWhere(fn ($w) => $w->where('destination_warehouse_id', $warehouseId)->where('type', 'transfer'));
            })
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')
            ->pluck('q', 'product_id');

        $out = InventoryMovement::where('company_id', $companyId)
            ->when($productIds !== null, fn ($q) => $q->whereIn('product_id', $productIds))
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', ['out', 'transfer'])
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(quantity) as q')
            ->pluck('q', 'product_id');

        $map = [];
        foreach ($in as $pid => $q) {
            $map[$pid] = ($map[$pid] ?? 0) + (float) $q;
        }
        foreach ($out as $pid => $q) {
            $map[$pid] = ($map[$pid] ?? 0) - (float) $q;
        }

        return $map;
    }

    /**
     * Opciones para el armador: categorías, marcas y modelos que tienen
     * productos activos en la empresa (contexto actual).
     */
    public static function options(): array
    {
        $active = fn ($q) => $q->where('active', true);

        return [
            'categories' => ProductCategory::whereHas('products', $active)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
            'brands' => ProductBrand::whereHas('products', $active)->orderBy('name')->get(['id', 'name'])
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values()->all(),
            'models' => MotoModel::whereHas('products', $active)->with('brand:id,name')->orderBy('name')
                ->get(['id', 'name', 'moto_brand_id'])
                ->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'brand' => $m->brand?->name])->values()->all(),
        ];
    }
}
