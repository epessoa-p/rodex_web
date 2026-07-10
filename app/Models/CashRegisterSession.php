<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegisterSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_register_id',
        'personal_id',
        'opened_by',
        'closed_by',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'difference',
        'status',
        'notes',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_amount'  => 'decimal:2',
        'closing_amount'  => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference'      => 'decimal:2',
        'opened_at'       => 'datetime',
        'closed_at'       => 'datetime',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function totalIncome(): float
    {
        return (float) $this->movements()->where('type', 'income')->sum('amount');
    }

    public function totalExpense(): float
    {
        return (float) $this->movements()->where('type', 'expense')->sum('amount');
    }

    public function expectedBalance(): float
    {
        return (float) $this->opening_amount + $this->totalIncome() - $this->totalExpense();
    }
}
