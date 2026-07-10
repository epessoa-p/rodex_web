<?php

namespace App\Models\Rentals;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class RentalInstallment extends Model
{
    use HasFactory;

    const STATUSES = [
        'pendiente' => ['label' => 'Pendiente', 'color' => 'secondary'],
        'parcial'   => ['label' => 'Parcial',   'color' => 'warning'],
        'pagada'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    protected $fillable = [
        'company_id', 'rental_contract_id', 'number', 'period_label',
        'period_start', 'period_end', 'due_date', 'amount', 'paid_amount',
        'late_fee_charged', 'status',
    ];

    protected $casts = [
        'period_start'     => 'date',
        'period_end'       => 'date',
        'due_date'         => 'date',
        'amount'           => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'late_fee_charged' => 'decimal:2',
        'number'           => 'integer',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function contract(): BelongsTo  { return $this->belongsTo(RentalContract::class, 'rental_contract_id'); }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->amount - $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'pagada'
            && $this->due_date
            && $this->due_date->isPast()
            && !$this->due_date->isToday();
    }

    public function getOverdueDaysAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }
        return $this->due_date->diffInDays(Carbon::today());
    }

    /** Mora acumulada aún no cobrada (tarifa por día × días de atraso − ya cobrada) */
    public function getAccruedLateFeeAttribute(): float
    {
        $perDay = (float) ($this->contract?->late_fee_per_day ?? 0);
        if ($perDay <= 0) {
            return 0.0;
        }
        $total = $perDay * $this->overdue_days;
        return max(0.0, $total - (float) $this->late_fee_charged);
    }

    public function getStatusLabelAttribute(): string { return self::STATUSES[$this->status]['label'] ?? $this->status; }
    public function getStatusColorAttribute(): string { return self::STATUSES[$this->status]['color'] ?? 'secondary'; }

    /** Recalcula el estado según paid_amount */
    public function recalcStatus(): void
    {
        $paid   = (float) $this->paid_amount;
        $amount = (float) $this->amount;

        $status = 'pendiente';
        if ($paid >= $amount && $amount > 0) {
            $status = 'pagada';
        } elseif ($paid > 0) {
            $status = 'parcial';
        }
        $this->update(['status' => $status]);
    }
}
