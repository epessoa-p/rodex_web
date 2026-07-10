<?php

namespace App\Models\Motos;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Sales\Sale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MotoUnit extends Model
{
    use HasFactory, SoftDeletes;

    const STATUSES = [
        'disponible'   => ['label' => 'Disponible',    'color' => 'success'],
        'reservada'    => ['label' => 'Reservada',     'color' => 'info'],
        'alquilada'    => ['label' => 'Alquilada',     'color' => 'warning'],
        'mantenimiento'=> ['label' => 'Mantenimiento', 'color' => 'primary'],
        'vendida'      => ['label' => 'Vendida',       'color' => 'warning'],
        'entregada'    => ['label' => 'Entregada',     'color' => 'dark'],
        'anulada'      => ['label' => 'Anulada',       'color' => 'danger'],
    ];

    protected $fillable = [
        'company_id', 'moto_model_id', 'branch_id', 'chassis_number', 'engine_number',
        'color', 'placa', 'year', 'cost', 'price', 'status', 'sale_id',
        'delivered_at', 'delivered_to', 'assigned_plate', 'delivery_notes', 'notes',
    ];

    protected $casts = [
        'year'         => 'integer',
        'cost'         => 'decimal:2',
        'price'        => 'decimal:2',
        'delivered_at' => 'datetime',
        'deleted_at'   => 'datetime',
    ];

    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }
    public function model(): BelongsTo    { return $this->belongsTo(MotoModel::class, 'moto_model_id'); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function sale(): BelongsTo      { return $this->belongsTo(Sale::class); }
    public function warranties(): HasMany { return $this->hasMany(Warranty::class); }
    public function rentalContracts(): HasMany { return $this->hasMany(\App\Models\Rentals\RentalContract::class, 'moto_unit_id'); }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    public function getDisplayNameAttribute(): string
    {
        $model = $this->model?->display_name ?? 'Moto';
        return $model . ' · ' . $this->chassis_number;
    }
}
