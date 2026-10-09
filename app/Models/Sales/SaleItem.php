<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'description', 'quantity', 'unit_price', 'unit_cost', 'discount', 'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'discount'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    /**
     * Guarda el costo del producto al crear la línea (de venta), para que el
     * reporte de Ganancias no cambie si después se actualiza el costo.
     * Si quien crea la línea ya pasa `unit_cost`, se respeta.
     */
    protected static function booted(): void
    {
        static::creating(function (self $line) {
            if ($line->unit_cost === null && $line->product_id) {
                $line->unit_cost = Product::withoutGlobalScopes()->whereKey($line->product_id)->value('cost');
            }
        });
    }
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Nombre a mostrar: descripción libre (venta rápida) o el nombre del producto. */
    public function getDisplayNameAttribute(): string
    {
        return $this->description ?: ($this->product?->name ?: 'Producto');
    }
}
