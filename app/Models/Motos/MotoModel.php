<?php

namespace App\Models\Motos;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotoModel extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'moto_brand_id', 'name', 'engine_cc', 'year',
        'suggested_price', 'daily_rate', 'description', 'active',
    ];

    protected $casts = [
        'year'            => 'integer',
        'suggested_price' => 'decimal:2',
        'daily_rate'      => 'decimal:2',
        'active'          => 'boolean',
        'deleted_at'      => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(MotoBrand::class, 'moto_brand_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(MotoUnit::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'moto_model_product');
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->brand?->name ? $this->brand->name . ' ' : '') . $this->name);
    }
}
