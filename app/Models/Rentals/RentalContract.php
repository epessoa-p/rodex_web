<?php

namespace App\Models\Rentals;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Branch;
use App\Models\CashRegisterSession;
use App\Models\Client;
use App\Models\Company;
use App\Models\Motos\MotoUnit;
use App\Models\User;
use App\Models\Workshop\WorkOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalContract extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const STATUSES = [
        'reservada'  => ['label' => 'Reservada',  'color' => 'info'],
        'contrato'   => ['label' => 'Contrato',   'color' => 'primary'],
        'entregada'  => ['label' => 'En alquiler','color' => 'warning'],
        'devuelta'   => ['label' => 'Devuelta',   'color' => 'secondary'],
        'cerrada'    => ['label' => 'Cerrada',     'color' => 'success'],
        'anulada'    => ['label' => 'Anulada',     'color' => 'danger'],
    ];

    const PAYMENT_STATUSES = [
        'pendiente' => ['label' => 'Pendiente', 'color' => 'danger'],
        'parcial'   => ['label' => 'Parcial',   'color' => 'warning'],
        'pagada'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    const DEPOSIT_STATUSES = [
        'retenido' => ['label' => 'Retenido', 'color' => 'info'],
        'devuelto' => ['label' => 'Devuelto', 'color' => 'success'],
        'parcial'  => ['label' => 'Parcial',  'color' => 'warning'],
        'aplicado' => ['label' => 'Aplicado', 'color' => 'secondary'],
    ];

    const PAYMENT_MODES = [
        'renta' => 'Renta periódica',
        'unico' => 'Pago único (en entrega)',
    ];

    const BILLING_PERIODS = [
        'diario'  => 'Diaria',
        'semanal' => 'Semanal',
        'mensual' => 'Mensual',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'moto_unit_id', 'cash_register_session_id',
        'code', 'status', 'start_date', 'end_date', 'days', 'daily_rate',
        'payment_mode', 'billing_period', 'period_amount', 'late_fee_per_day', 'rental_total',
        'deposit', 'penalties_total', 'total', 'paid_amount', 'payment_status',
        'deposit_status', 'deposit_refunded',
        'delivered_at', 'delivery_mileage', 'delivery_fuel', 'delivery_notes',
        'returned_at', 'return_mileage', 'return_fuel', 'return_notes',
        'work_order_id', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'delivered_at'    => 'datetime',
        'returned_at'     => 'datetime',
        'days'            => 'integer',
        'daily_rate'      => 'decimal:2',
        'period_amount'   => 'decimal:2',
        'late_fee_per_day'=> 'decimal:2',
        'rental_total'    => 'decimal:2',
        'deposit'         => 'decimal:2',
        'penalties_total' => 'decimal:2',
        'total'           => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'deposit_refunded'=> 'decimal:2',
        'deleted_at'      => 'datetime',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function client(): BelongsTo   { return $this->belongsTo(Client::class); }
    public function motoUnit(): BelongsTo { return $this->belongsTo(MotoUnit::class, 'moto_unit_id'); }
    public function session(): BelongsTo  { return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function workOrder(): BelongsTo { return $this->belongsTo(WorkOrder::class, 'work_order_id'); }
    public function payments(): HasMany    { return $this->hasMany(RentalPayment::class)->latest('payment_date'); }
    public function penalties(): HasMany   { return $this->hasMany(RentalPenalty::class)->latest('penalty_date'); }
    public function installments(): HasMany { return $this->hasMany(RentalInstallment::class)->orderBy('number'); }
    public function inspections(): HasMany  { return $this->hasMany(RentalInspection::class)->orderBy('id'); }

    public function isRenta(): bool
    {
        return $this->payment_mode === 'renta';
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function getPaymentModeLabelAttribute(): string { return self::PAYMENT_MODES[$this->payment_mode] ?? $this->payment_mode; }
    public function getBillingPeriodLabelAttribute(): string { return self::BILLING_PERIODS[$this->billing_period] ?? ($this->billing_period ?? '—'); }

    public function getStatusLabelAttribute(): string   { return self::STATUSES[$this->status]['label'] ?? $this->status; }
    public function getStatusColorAttribute(): string   { return self::STATUSES[$this->status]['color'] ?? 'secondary'; }
    public function getPaymentStatusLabelAttribute(): string { return self::PAYMENT_STATUSES[$this->payment_status]['label'] ?? $this->payment_status; }
    public function getPaymentStatusColorAttribute(): string { return self::PAYMENT_STATUSES[$this->payment_status]['color'] ?? 'secondary'; }
    public function getDepositStatusLabelAttribute(): string  { return self::DEPOSIT_STATUSES[$this->deposit_status]['label'] ?? $this->deposit_status; }

    /** Recalcula total (alquiler + penalizaciones) y estado de pago */
    public function recalcTotals(): void
    {
        $this->update(['total' => (float) $this->rental_total + (float) $this->penalties_total]);
        $this->recalcPaymentStatus();
    }

    public function recalcPaymentStatus(): void
    {
        $paid  = (float) $this->paid_amount;
        $total = (float) $this->total;
        $status = 'pendiente';
        if ($paid >= $total && $total > 0) $status = 'pagada';
        elseif ($paid > 0) $status = 'parcial';
        $this->update(['payment_status' => $status]);
    }
}
