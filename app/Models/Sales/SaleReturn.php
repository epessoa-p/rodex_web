<?php

namespace App\Models\Sales;

use App\Models\CashRegisterSession;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleReturn extends Model
{
    use HasFactory, SoftDeletes;

    const REFUND_METHODS = [
        'cash'        => ['label' => 'Efectivo (egreso de caja)', 'color' => 'danger'],
        'credit_note' => ['label' => 'Nota de crédito',          'color' => 'info'],
    ];

    protected $fillable = [
        'company_id', 'sale_id', 'cash_register_session_id', 'code',
        'return_date', 'refund_method', 'reason', 'total', 'refunded_amount', 'notes', 'created_by',
    ];

    protected $casts = [
        'return_date'     => 'date',
        'total'           => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'deleted_at'      => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
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
        return $this->hasMany(SaleReturnItem::class);
    }

    public function getRefundMethodLabelAttribute(): string
    {
        return self::REFUND_METHODS[$this->refund_method]['label'] ?? $this->refund_method;
    }

    public function getRefundMethodColorAttribute(): string
    {
        return self::REFUND_METHODS[$this->refund_method]['color'] ?? 'secondary';
    }
}
