<?php

namespace App\Models\Rentals;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalPenalty extends Model
{
    use BelongsToCompany;

    use HasFactory;

    const STATUSES = [
        'pendiente' => ['label' => 'Pendiente', 'color' => 'danger'],
        'parcial'   => ['label' => 'Parcial',   'color' => 'warning'],
        'pagada'    => ['label' => 'Pagada',    'color' => 'success'],
    ];

    protected $fillable = [
        'company_id', 'rental_contract_id', 'concept', 'amount', 'paid_amount', 'penalty_date', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'paid_amount'  => 'decimal:2',
        'penalty_date' => 'date',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function contract(): BelongsTo { return $this->belongsTo(RentalContract::class, 'rental_contract_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function payments(): HasMany   { return $this->hasMany(RentalPayment::class, 'rental_penalty_id'); }

    /** Saldo pendiente de cobro de la penalización. */
    public function getBalanceAttribute(): float
    {
        return max(0.0, (float) $this->amount - (float) $this->paid_amount);
    }

    /** pendiente | parcial | pagada (derivado de paid_amount). */
    public function getStatusAttribute(): string
    {
        $paid = (float) $this->paid_amount;
        if ($paid >= (float) $this->amount - 0.009) return 'pagada';
        if ($paid > 0.009) return 'parcial';
        return 'pendiente';
    }

    public function getStatusLabelAttribute(): string { return self::STATUSES[$this->status]['label']; }
    public function getStatusColorAttribute(): string { return self::STATUSES[$this->status]['color']; }
}
