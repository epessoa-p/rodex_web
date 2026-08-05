<?php

namespace App\Models\Sales;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Branch;
use App\Models\CashRegisterSession;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const SALE_TYPES = [
        'cash'   => ['label' => 'Contado', 'color' => 'success'],
        'credit' => ['label' => 'Crédito', 'color' => 'warning'],
    ];

    const PAYMENT_STATUSES = [
        'pending' => ['label' => 'Pendiente', 'color' => 'danger'],
        'partial' => ['label' => 'Parcial',   'color' => 'warning'],
        'paid'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'moto_unit_id', 'cash_register_session_id',
        'code', 'sale_type', 'sale_category', 'payment_plan_id', 'credit_application_id',
        'sale_date', 'subtotal', 'discount', 'tax', 'interest', 'total',
        'paid_amount', 'payment_status', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'sale_date'   => 'datetime',
        'subtotal'    => 'decimal:2',
        'discount'    => 'decimal:2',
        'tax'         => 'decimal:2',
        'interest'    => 'decimal:2',
        'total'       => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'deleted_at'  => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(SaleInstallment::class)->orderBy('number');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class)->latest('payment_date');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SaleReturn::class)->latest('return_date');
    }

    public function paymentPlan(): BelongsTo
    {
        return $this->belongsTo(PaymentPlan::class);
    }

    public function motoUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Motos\MotoUnit::class, 'moto_unit_id');
    }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function getSaleTypeLabelAttribute(): string
    {
        return self::SALE_TYPES[$this->sale_type]['label'] ?? $this->sale_type;
    }

    public function getSaleTypeColorAttribute(): string
    {
        return self::SALE_TYPES[$this->sale_type]['color'] ?? 'secondary';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]['label'] ?? $this->payment_status;
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]['color'] ?? 'secondary';
    }

    public function getClientNameAttribute(): string
    {
        return $this->client?->full_name ?? 'Cliente general';
    }

    /** Recalcula el estado de pago según paid_amount */
    public function recalcPaymentStatus(): void
    {
        $paid  = (float) $this->paid_amount;
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
