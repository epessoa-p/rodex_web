<?php

namespace App\Models\Workshop;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderPart extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_order_id', 'product_id', 'quantity', 'unit_price', 'unit_cost', 'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'unit_cost'  => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    /**
     * Guarda el costo del producto al crear la línea (de repuesto de la OT), para que el
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
    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
