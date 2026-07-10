<?php

namespace App\Models\Rentals;

use App\Models\CashRegisterSession;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentalPayment extends Model
{
    use HasFactory;

    const TYPES = [
        'alquiler'            => 'Alquiler',
        'deposito'            => 'Depósito',
        'penalizacion'        => 'Penalización',
        'devolucion_deposito' => 'Devolución de depósito',
    ];

    protected $fillable = [
        'company_id', 'rental_contract_id', 'rental_installment_id', 'cash_register_session_id',
        'type', 'amount', 'method', 'payment_date', 'reference', 'notes', 'user_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function contract(): BelongsTo { return $this->belongsTo(RentalContract::class, 'rental_contract_id'); }
    public function installment(): BelongsTo { return $this->belongsTo(RentalInstallment::class, 'rental_installment_id'); }
    public function session(): BelongsTo  { return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id'); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
