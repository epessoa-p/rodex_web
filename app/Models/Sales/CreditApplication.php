<?php

namespace App\Models\Sales;

use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditApplication extends Model
{
    use HasFactory, SoftDeletes;

    const STATUSES = [
        'pendiente'  => ['label' => 'Pendiente',  'color' => 'warning'],
        'aprobada'   => ['label' => 'Aprobada',   'color' => 'success'],
        'rechazada'  => ['label' => 'Rechazada',  'color' => 'danger'],
        'convertida' => ['label' => 'Convertida', 'color' => 'primary'],
    ];

    protected $fillable = [
        'company_id', 'client_id', 'code', 'requested_amount', 'down_payment',
        'installments_count', 'frequency_days', 'payment_plan_id',
        'guarantor_name', 'guarantor_phone', 'guarantor_notes', 'notes',
        'status', 'approved_amount', 'evaluation_notes', 'evaluated_by',
        'converted_sale_id', 'created_by',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'down_payment'     => 'decimal:2',
        'approved_amount'  => 'decimal:2',
        'installments_count' => 'integer',
        'frequency_days'   => 'integer',
        'deleted_at'       => 'datetime',
    ];

    public function company(): BelongsTo     { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo      { return $this->belongsTo(Client::class); }
    public function paymentPlan(): BelongsTo { return $this->belongsTo(PaymentPlan::class); }
    public function evaluatedBy(): BelongsTo { return $this->belongsTo(User::class, 'evaluated_by'); }
    public function createdBy(): BelongsTo    { return $this->belongsTo(User::class, 'created_by'); }
    public function convertedSale(): BelongsTo { return $this->belongsTo(Sale::class, 'converted_sale_id'); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    public function getClientNameAttribute(): string
    {
        return $this->client?->full_name ?? '—';
    }

    public function isConvertible(): bool
    {
        return $this->status === 'aprobada';
    }
}
