<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseService extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const TYPES = [
        'basico'     => 'Servicio básico',
        'externo'    => 'Servicio externo',
        'transporte' => 'Transporte',
        'otro'       => 'Otro',
    ];

    protected $fillable = [
        'company_id', 'name', 'type', 'default_amount', 'notes', 'active', 'created_by',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'active'         => 'boolean',
        'deleted_at'     => 'datetime',
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
}
