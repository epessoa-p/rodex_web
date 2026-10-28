<?php

namespace App\Models\Inventory;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ubicación física de un producto en un almacén (ej. "PA5-VI2" = pasillo 5,
 * vitrina 2). Una por producto y almacén; ver Product::setLocation().
 */
class ProductLocation extends Model
{
    use BelongsToCompany;

    protected $fillable = ['company_id', 'product_id', 'warehouse_id', 'location'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /** "pa5 - vi2 " → "PA5-VI2" (mayúsculas, sin espacios alrededor de guiones). */
    public static function normalize(?string $value): string
    {
        $v = mb_strtoupper(trim((string) $value));
        $v = preg_replace('/\s*-\s*/', '-', $v) ?? $v;
        $v = preg_replace('/\s+/', ' ', $v) ?? $v;

        return mb_substr($v, 0, 40);
    }
}
