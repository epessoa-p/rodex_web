<?php

namespace App\Models\Loyalty;

use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoyaltyReward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'image', 'points_cost',
        'product_id', 'stock', 'active', 'created_by',
    ];

    protected $casts = [
        'points_cost' => 'integer',
        'stock'       => 'integer',
        'active'      => 'boolean',
        'deleted_at'  => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    /** ¿Hay disponibilidad para canjear? (stock null = ilimitado) */
    public function getIsAvailableAttribute(): bool
    {
        return $this->active && ($this->stock === null || $this->stock > 0);
    }
}
