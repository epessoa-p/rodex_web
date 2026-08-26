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
