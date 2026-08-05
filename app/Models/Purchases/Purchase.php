<?php

namespace App\Models\Purchases;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const PAYMENT_STATUSES = [
        'pending' => ['label' => 'Pendiente', 'color' => 'danger'],
        'partial' => ['label' => 'Parcial',   'color' => 'warning'],
        'paid'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    protected $fillable = [
        'company_id', 'supplier_id', 'purchase_order_id', 'code', 'invoice_number',
        'purchase_date', 'subtotal', 'tax', 'total', 'paid_amount',
        'payment_status', 'notes', 'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'tax'           => 'decimal:2',
        'total'         => 'decimal:2',
        'paid_amount'   => 'decimal:2',
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

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]['label'] ?? $this->payment_status;
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]['color'] ?? 'secondary';
    }

    /** Recalcula el estado de pago según paid_amount */
    public function recalcPaymentStatus(): void
    {
        $paid = (float) $this->paid_amount;
        $total = (float) $this->total;

        $status = 'pending';
        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        $this->update(['payment_status' => $status]);
    }
}
