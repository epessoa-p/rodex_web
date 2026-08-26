<?php

namespace App\Models\Inventory;

use App\Models\Company;
use App\Models\Concerns\BelongsToCompany;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Origen/procedencia del producto (país): Nacional, Brasil, China, Japón, India…
 * Catálogo por empresa, al estilo de categorías/marcas/unidades.
 */
class ProductOrigin extends Model
{
    use BelongsToCompany;
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'name', 'active'];

    protected $casts = [
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'origin_id');
    }
}
