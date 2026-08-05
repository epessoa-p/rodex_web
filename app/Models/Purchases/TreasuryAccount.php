<?php

namespace App\Models\Purchases;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryAccount extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const TYPES = [
        'cash' => 'Efectivo',
        'bank' => 'Banco',
    ];

    protected $fillable = [
        'company_id', 'name', 'type', 'bank_name',
        'account_number', 'balance', 'active',
    ];

    protected $casts = [
        'balance'    => 'decimal:2',
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(TreasuryMovement::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** Recalcula el balance desde los movimientos (auditoría) */
    public function recalculateBalance(): void
    {
        $in  = $this->movements()->where('type', 'in')->sum('amount');
        $out = $this->movements()->where('type', 'out')->sum('amount');
        $this->update(['balance' => $in - $out]);
    }
}
