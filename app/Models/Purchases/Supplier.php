<?php

namespace App\Models\Purchases;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'nit', 'contact_name',
        'phone', 'email', 'address', 'notes', 'active',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    /** Saldo total pendiente con este proveedor */
    public function getBalanceOwedAttribute(): float
    {
        return (float) $this->purchases()
            ->whereIn('payment_status', ['pending', 'partial'])
            ->get()
            ->sum(fn ($p) => $p->total - $p->paid_amount);
    }
}
