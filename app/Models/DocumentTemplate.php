<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'description',
        'content',
        'active',
        'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    const TYPES = [
        'cotizacion'   => 'Cotización',
        'nota_entrega' => 'Nota de entrega',
        'factura'      => 'Factura',
        'otro'         => 'Otro',
    ];

    const TYPE_BADGES = [
        'cotizacion'   => 'primary',
        'nota_entrega' => 'success',
        'factura'      => 'info',
        'otro'         => 'secondary',
    ];

    const PLACEHOLDERS = [
        'empresa_nombre'  => 'Nombre de la empresa',
        'sucursal_nombre' => 'Nombre de la sucursal',
        'fecha_actual'    => 'Fecha actual',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeBadgeAttribute(): string
    {
        return self::TYPE_BADGES[$this->type] ?? 'secondary';
    }
}
