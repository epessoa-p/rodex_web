<?php

namespace App\Models\Inventory;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;

class ProductPhoto extends Model
{
    use BelongsToCompany;

    use HasFactory;

    protected $fillable = [
        'product_id',
        'company_id',
        'file_path',
        'thumb_path',
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

    /**
     * URL de la miniatura (320 px) para los listados. Las fotos subidas antes
     * de que existieran las miniaturas caen al original.
     */
    public function getThumbUrlAttribute(): string
    {
        return $this->thumb_path ? asset('storage/' . $this->thumb_path) : $this->url;
    }
}
