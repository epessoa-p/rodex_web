<?php

namespace App\Models\Rentals;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalInspection extends Model
{
    use BelongsToCompany;

    use HasFactory;

    const TYPES = [
        'salida'  => 'Salida (entrega)',
        'entrada' => 'Entrada (devolución)',
    ];

    /** Componentes evaluados en el checklist */
    const CHECKLIST_ITEMS = [
        'carroceria'   => 'Carrocería',
        'espejos'      => 'Espejos',
        'luces'        => 'Luces',
        'llantas'      => 'Llantas',
        'frenos'       => 'Frenos',
        'documentos'   => 'Documentos',
        'casco'        => 'Casco',
        'herramientas' => 'Herramientas',
    ];

    const CONDITIONS = ['bien' => 'Bien', 'regular' => 'Regular', 'mal' => 'Mal'];

    protected $fillable = [
        'company_id', 'rental_contract_id', 'type', 'mileage', 'fuel_level',
        'checklist', 'notes', 'created_by',
    ];

    protected $casts = [
        'checklist' => 'array',
        'mileage'   => 'integer',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function contract(): BelongsTo  { return $this->belongsTo(RentalContract::class, 'rental_contract_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function photos(): HasMany      { return $this->hasMany(RentalInspectionPhoto::class)->orderBy('sort_order'); }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
