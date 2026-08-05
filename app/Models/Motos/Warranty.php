<?php

namespace App\Models\Motos;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Client;
use App\Models\Company;
use App\Models\Sales\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warranty extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const STATUSES = [
        'vigente' => ['label' => 'Vigente', 'color' => 'success'],
        'vencida' => ['label' => 'Vencida', 'color' => 'secondary'],
        'anulada' => ['label' => 'Anulada', 'color' => 'danger'],
    ];

    protected $fillable = [
        'company_id', 'moto_unit_id', 'sale_id', 'client_id', 'code',
        'start_date', 'months', 'coverage', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'months'     => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function motoUnit(): BelongsTo { return $this->belongsTo(MotoUnit::class, 'moto_unit_id'); }
    public function sale(): BelongsTo     { return $this->belongsTo(Sale::class); }
    public function client(): BelongsTo   { return $this->belongsTo(Client::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getEndDateAttribute()
    {
        return $this->start_date?->copy()->addMonths((int) $this->months);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'vigente' && $this->end_date && $this->end_date->isFuture();
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }
}
