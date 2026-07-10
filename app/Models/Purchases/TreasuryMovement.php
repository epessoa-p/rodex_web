<?php

namespace App\Models\Purchases;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryMovement extends Model
{
    use HasFactory;

    const CATEGORIES = [
        'capital_injection' => ['label' => 'Aporte de capital', 'type' => 'in'],
        'supplier_payment'  => ['label' => 'Pago a proveedor',  'type' => 'out'],
        'adjustment_in'     => ['label' => 'Ajuste positivo',   'type' => 'in'],
        'adjustment_out'    => ['label' => 'Ajuste negativo',   'type' => 'out'],
        'expense'           => ['label' => 'Gasto',             'type' => 'out'],
    ];

    protected $fillable = [
        'company_id', 'treasury_account_id', 'user_id', 'type', 'category',
        'amount', 'reference_type', 'reference_id', 'description', 'movement_date',
    ];

    protected $casts = [
        'amount'        => 'decimal:2',
        'movement_date' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category]['label'] ?? $this->category;
    }
}
