<?php

namespace App\Models\Purchases;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'purchase_id', 'treasury_account_id', 'amount',
        'payment_date', 'method', 'reference', 'notes', 'user_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
