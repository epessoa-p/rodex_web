<?php

namespace App\Models\Inventory;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un cambio de precio o costo de un producto (historial para auditoría).
 * Lo crea solo `Product` al guardarse (ver Product::booted); no se edita.
 */
class ProductPriceChange extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    /** Desde dónde se hizo el cambio (etiquetas para la vista). */
    public const SOURCES = [
        'web'        => 'Edición del producto (web)',
        'tabla'      => 'Tabla de inventario',
        'masivo'     => 'Ajuste masivo de precios',
        'importacion'=> 'Importación',
        'app'        => 'App móvil',
        'sistema'    => 'Sistema',
    ];

    protected $fillable = [
        'company_id', 'product_id', 'user_id', 'field', 'old_value', 'new_value', 'source',
    ];

    protected $casts = [
        'old_value'  => 'decimal:2',
        'new_value'  => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    /** Variación en % respecto del valor anterior (null si no había). */
    public function getPercentAttribute(): ?float
    {
        $old = (float) $this->old_value;

        return $old > 0 ? round(((float) $this->new_value - $old) / $old * 100, 1) : null;
    }

    /** Origen del cambio según la ruta que lo hizo. */
    public static function currentSource(): string
    {
        if (app()->runningInConsole()) {
            return 'sistema';
        }
        $route = request()->route();
        $name = $route?->getName() ?? '';
        if (request()->is('api/*')) {
            return 'app';
        }

        return match (true) {
            $name === 'products.update'                     => 'web',
            $name === 'inventory.stock.field'               => 'tabla',
            str_starts_with($name, 'inventory.stock.bulk-price') => 'masivo',
            str_contains($name, 'import')                   => 'importacion',
            default                                         => 'sistema',
        };
    }
}
