<?php

namespace App\Models\Workshop;

use App\Models\CashRegisterSession;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Purchases\TreasuryAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pago (liquidación) a un mecánico. Descuenta el pendiente de comisión y
 * registra el gasto en caja o en una cuenta de tesorería.
 */
class MechanicPayment extends Model
{
    use BelongsToCompany;

    use HasFactory;

    protected $fillable = [
        'company_id', 'mechanic_id', 'amount', 'payment_source',
        'treasury_account_id', 'cash_register_session_id', 'method', 'notes',
        'payment_date', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
