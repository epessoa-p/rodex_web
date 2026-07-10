<?php

namespace App\Models\Sales;

use App\Models\CashRegisterSession;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'sale_id', 'sale_installment_id', 'cash_register_session_id',
        'amount', 'payment_date', 'method', 'reference', 'notes', 'user_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(SaleInstallment::class, 'sale_installment_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
