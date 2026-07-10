<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaleInstallment extends Model
{
    use HasFactory;

    const STATUSES = [
        'pending' => ['label' => 'Pendiente', 'color' => 'secondary'],
        'partial' => ['label' => 'Parcial',   'color' => 'warning'],
        'paid'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    protected $fillable = [
        'company_id', 'sale_id', 'number', 'due_date', 'amount', 'paid_amount', 'status',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->amount - $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid' && $this->due_date->isPast() && !$this->due_date->isToday();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    /** Recalcula el estado según paid_amount */
    public function recalcStatus(): void
    {
        $paid   = (float) $this->paid_amount;
        $amount = (float) $this->amount;

        $status = 'pending';
        if ($paid >= $amount && $amount > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        $this->update(['status' => $status]);
    }
}
