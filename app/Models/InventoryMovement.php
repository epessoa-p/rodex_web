<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryMovement extends Model
{
    use HasFactory, SoftDeletes;

    const TYPES = [
        'in'         => ['label' => 'Entrada',      'color' => 'success'],
        'out'        => ['label' => 'Salida',        'color' => 'danger'],
        'transfer'   => ['label' => 'Transferencia', 'color' => 'info'],
        'adjustment' => ['label' => 'Ajuste',        'color' => 'warning'],
    ];

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'destination_warehouse_id',
        'branch_id',
        'product_id',
        'user_id',
        'type',
        'quantity',
        'unit_cost',
        'reference',
        'notes',
        'movement_date',
        'adjustment_reason',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'unit_cost'     => 'decimal:2',
        'movement_date' => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
