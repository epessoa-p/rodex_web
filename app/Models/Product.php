<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Inventory\ProductBrand;
use App\Models\Inventory\ProductCategory;
use App\Models\Inventory\ProductPhoto;
use App\Models\Motos\MotoModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'category_id',
        'brand_id',
        'origin_id',
        'name',
        'sku',
        'code',
        'barcode',
        'description',
        'unit',
        'cost',
        'price',
        'min_stock',
        'current_stock',
        'active',
    ];

    protected $casts = [
        'cost'          => 'decimal:2',
        'price'         => 'decimal:2',
        'min_stock'     => 'integer',
        'current_stock' => 'integer',
        'active'        => 'boolean',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Historial de precios: cada cambio de precio o costo queda registrado con
     * quién, cuándo y desde dónde (edición, tabla, ajuste masivo, importación,
     * app). Al hacerse en el modelo no se escapa ningún camino; por eso los
     * cambios de precio deben pasar por el modelo (no por update() masivo).
     */
    protected static function booted(): void
    {
        static::updated(function (self $product) {
            foreach (['price', 'cost'] as $field) {
                if (! $product->wasChanged($field)) {
                    continue;
                }
                $old = $product->getOriginal($field);
                $new = $product->getAttribute($field);
                if (abs((float) $old - (float) $new) < 0.005) {
                    continue; // 10 → 10.00: no es un cambio
                }
                \App\Models\Inventory\ProductPriceChange::create([
                    'company_id' => $product->company_id,
                    'product_id' => $product->id,
                    'user_id'    => auth()->id(),
                    'field'      => $field,
                    'old_value'  => $old,
                    'new_value'  => $new,
                    'source'     => \App\Models\Inventory\ProductPriceChange::currentSource(),
                ]);
            }
        });
    }

    /** ¿Existe la tabla de ubicaciones? (una vez por proceso; antes del SQL no rompe). */
    public static function locationsReady(): bool
    {
        return self::$locationsReady ??= \Illuminate\Support\Facades\Schema::hasTable('product_locations');
    }

    private static ?bool $locationsReady = null;

    /** Ubicaciones físicas por almacén (ej. "PA5-VI2"). */
    public function locations(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\ProductLocation::class);
    }

    /** Ubicación en un almacén (usa la relación si ya está cargada). */
    public function locationIn(?int $warehouseId): ?string
    {
        if (! $warehouseId || ! self::locationsReady()) {
            return null;
        }
        $rows = $this->relationLoaded('locations') ? $this->locations : $this->locations()->get();

        return $rows->firstWhere('warehouse_id', $warehouseId)?->location;
    }

    /**
     * Guarda la ubicación del producto en un almacén. Vacío = la quita.
     * Se guarda en mayúsculas ("pa5-vi2" → "PA5-VI2").
     */
    public function setLocation(int $warehouseId, ?string $location): void
    {
        if (! self::locationsReady()) {
            return; // falta el SQL de ubicaciones
        }
        $value = \App\Models\Inventory\ProductLocation::normalize($location);
        if ($value === '') {
            $this->locations()->where('warehouse_id', $warehouseId)->delete();
        } else {
            $this->locations()->updateOrCreate(
                ['warehouse_id' => $warehouseId],
                ['location' => $value, 'company_id' => $this->company_id],
            );
        }
        $this->unsetRelation('locations');
    }

    public function priceChanges(): HasMany
    {
        return $this->hasMany(\App\Models\Inventory\ProductPriceChange::class)->latest('created_at')->latest('id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Inventory\ProductOrigin::class, 'origin_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProductPhoto::class)->orderBy('sort_order');
    }

    public function motoModels(): BelongsToMany
    {
        return $this->belongsToMany(MotoModel::class, 'moto_model_product');
    }

    public function mainPhoto(): ?ProductPhoto
    {
        // Con ->with('photos') se resuelve en memoria (evita 2 consultas por producto).
        if ($this->relationLoaded('photos')) {
            return $this->photos->firstWhere('is_main', true) ?? $this->photos->first();
        }

        return $this->photos()->where('is_main', true)->first() ?? $this->photos()->first();
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function stockInWarehouse(int $warehouseId): float
    {
        $in = $this->inventoryMovements()
            ->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                  ->whereIn('type', ['in', 'adjustment']);
            })
            ->orWhere(function ($q) use ($warehouseId) {
                $q->where('destination_warehouse_id', $warehouseId)
                  ->where('type', 'transfer');
            })
            ->where('product_id', $this->id)
            ->sum('quantity');

        $out = $this->inventoryMovements()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('type', ['out', 'transfer'])
            ->sum('quantity');

        return (float) ($in - $out);
    }

    public function stockByWarehouse(): \Illuminate\Support\Collection
    {
        return $this->inventoryMovements()
            ->with('warehouse')
            ->get()
            ->groupBy('warehouse_id')
            ->map(function ($movements, $warehouseId) {
                return $this->stockInWarehouse($warehouseId);
            });
    }
}
