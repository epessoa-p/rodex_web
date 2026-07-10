<?php

namespace App\Models\Sales;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id', 'product_id', 'quantity', 'unit_price', 'discount', 'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
