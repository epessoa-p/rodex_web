<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'full_name',
        'id_number',
        'phone',
        'email',
        'address',
        'location_link',
        'photo',
        'notes',
        'active',
        'points_balance',
        'created_by',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'points_balance' => 'integer',
        'deleted_at'     => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    // ── Actividad del cliente (para tabs de la ficha) ─────────
    public function sales(): HasMany
    {
        return $this->hasMany(\App\Models\Sales\Sale::class)->latest('sale_date');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(\App\Models\Workshop\WorkOrder::class)->latest('reception_date');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Models\Vehicle::class)->latest();
    }

    public function rentalContracts(): HasMany
    {
        return $this->hasMany(\App\Models\Rentals\RentalContract::class)->latest('start_date');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(\App\Models\Sales\Quote::class)->latest('quote_date');
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(\App\Models\Motos\Warranty::class)->latest('start_date');
    }

    // ── Fidelización ──────────────────────────────────────────
    public function pointMovements(): HasMany
    {
        return $this->hasMany(\App\Models\Loyalty\LoyaltyPointMovement::class)->latest();
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(\App\Models\Loyalty\LoyaltyRedemption::class)->latest('redeemed_at');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }
}
