<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class ProductPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'company_id',
        'file_path',
        'file_name',
        'is_main',
        'sort_order',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
