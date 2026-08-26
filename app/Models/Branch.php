<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'name',
        'code',
        'phone',
        'email',
        'address',
        'manager_name',
        'color',
        'active',
        'public_token',
    ];

    public function getColorOrDefaultAttribute(): string
    {
        return $this->color ?: '#6c757d';
    }

    /**
     * Asegura un token público para el catálogo de esta sucursal y lo devuelve.
     * (Mismo patrón que LoyaltySetting::ensurePublicToken.)
     */
    public function ensurePublicToken(): string
    {
        if (! $this->public_token) {
            $this->forceFill(['public_token' => \Illuminate\Support\Str::random(32)])->save();
        }

        return $this->public_token;
    }

    protected $casts = [
        'active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }
}