<?php

namespace App\Models\Purchases;

use App\Models\Company;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'purchase_order_id', 'purchase_id', 'warehouse_id',
        'code', 'receipt_date', 'notes', 'received_by',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'deleted_at'   => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function isInvoiced(): bool
    {
        return $this->purchase_id !== null;
    }

    public function getSubtotalAttribute(): float
    {
        return (float) $this->items->sum(fn ($i) => $i->quantity * $i->unit_cost);
    }
}
