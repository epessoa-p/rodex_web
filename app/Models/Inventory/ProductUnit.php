<?php

namespace App\Models\Inventory;

use App\Models\Company;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Unidad de medida (catálogo por empresa): Unidad, Pieza, Juego, Litro…
 * El producto guarda el NOMBRE de la unidad como texto (products.unit); esta
 * tabla es la lista gestionable que alimenta el selector y el import.
 */
class ProductUnit extends Model
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
}
