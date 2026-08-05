<?php

namespace App\Models\Purchases;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const STATUSES = [
        'draft'     => ['label' => 'Borrador',         'color' => 'secondary'],
        'sent'      => ['label' => 'Enviada',          'color' => 'info'],
        'partial'   => ['label' => 'Recibida parcial', 'color' => 'warning'],
        'received'  => ['label' => 'Recibida',         'color' => 'success'],
        'cancelled' => ['label' => 'Anulada',          'color' => 'danger'],
    ];

    protected $fillable = [
        'company_id', 'supplier_id', 'branch_id', 'code', 'status',
        'order_date', 'expected_date', 'subtotal', 'tax', 'total',
        'notes', 'created_by',
    ];

    protected $casts = [
        'order_date'    => 'date',
        'expected_date' => 'date',
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'total'         => 'decimal:2',
        'deleted_at'    => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    public function isFullyReceived(): bool
    {
        return $this->items->every(fn ($item) => $item->received_quantity >= $item->quantity);
    }

    public function isPartiallyReceived(): bool
    {
        return $this->items->contains(fn ($item) => $item->received_quantity > 0)
            && !$this->isFullyReceived();
    }

    /** Recalcula el status según las recepciones */
    public function refreshReceiptStatus(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }
        $this->load('items');
        if ($this->isFullyReceived()) {
            $this->update(['status' => 'received']);
        } elseif ($this->isPartiallyReceived()) {
            $this->update(['status' => 'partial']);
        }
    }
}
