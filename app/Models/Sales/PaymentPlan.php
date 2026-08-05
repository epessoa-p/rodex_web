<?php

namespace App\Models\Sales;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentPlan extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'number_of_installments', 'frequency_days', 'interest_rate', 'active',
    ];

    protected $casts = [
        'number_of_installments' => 'integer',
        'frequency_days'         => 'integer',
        'interest_rate'          => 'decimal:2',
        'active'                 => 'boolean',
        'deleted_at'             => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Interés calculado (simple) sobre un monto financiado */
    public function interestFor(float $financed): float
    {
        return round($financed * ((float) $this->interest_rate / 100), 2);
    }

    /** Monto total a financiar (capital + interés) */
    public function totalFor(float $financed): float
    {
        return round($financed + $this->interestFor($financed), 2);
    }

    /**
     * Construye el cronograma de cuotas a partir de un monto financiado.
     * Devuelve array de ['due_date' => 'Y-m-d', 'amount' => float].
     */
    public function buildSchedule(float $financed, ?Carbon $firstDate = null): array
    {
        $n = max(1, (int) $this->number_of_installments);
        $total = $this->totalFor($financed);
        $base  = floor(($total / $n) * 100) / 100;       // redondeo hacia abajo
        $date  = ($firstDate ?? now()->addDays($this->frequency_days))->copy();

        $rows = [];
        $acc  = 0;
        for ($i = 1; $i <= $n; $i++) {
            $amount = $i === $n ? round($total - $acc, 2) : $base; // última cuota ajusta el residuo
            $acc += $amount;
            $rows[] = ['due_date' => $date->toDateString(), 'amount' => $amount];
            $date->addDays($this->frequency_days);
        }
        return $rows;
    }
}
