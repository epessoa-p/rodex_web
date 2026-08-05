<?php

namespace App\Models\Workshop;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderInstallment extends Model
{
    use BelongsToCompany;

    use HasFactory;

    const STATUSES = [
        'pendiente' => ['label' => 'Pendiente', 'color' => 'secondary'],
        'parcial'   => ['label' => 'Parcial',   'color' => 'warning'],
        'pagada'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    protected $fillable = [
        'company_id', 'work_order_id', 'number', 'due_date', 'amount', 'paid_amount', 'status',
    ];

    protected $casts = [
        'due_date'    => 'date',
        'amount'      => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->amount - $this->paid_amount);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'pagada' && $this->due_date->isPast() && !$this->due_date->isToday();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

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
