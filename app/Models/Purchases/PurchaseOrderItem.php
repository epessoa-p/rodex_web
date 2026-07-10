<?php

namespace App\Models\Purchases;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id', 'product_id', 'quantity',
        'unit_cost', 'subtotal', 'received_quantity',
    ];

    protected $casts = [
        'quantity'          => 'integer',
        'unit_cost'         => 'decimal:2',
        'subtotal'          => 'decimal:2',
        'received_quantity' => 'integer',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getPendingQuantityAttribute(): float
    {
        return (float) ($this->quantity - $this->received_quantity);
    }
}
