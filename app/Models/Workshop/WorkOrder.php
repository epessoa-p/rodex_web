<?php

namespace App\Models\Workshop;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Branch;
use App\Models\CashRegisterSession;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkOrder extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const STATUSES = [
        'recibida'      => ['label' => 'Recibida',      'color' => 'info'],
        'diagnosticada' => ['label' => 'Diagnosticada', 'color' => 'primary'],
        'en_proceso'    => ['label' => 'En proceso',    'color' => 'warning'],
        'terminada'     => ['label' => 'Terminada',     'color' => 'success'],
        'entregada'     => ['label' => 'Entregada',     'color' => 'dark'],
        'anulada'       => ['label' => 'Anulada',       'color' => 'danger'],
    ];

    const PAYMENT_STATUSES = [
        'pendiente' => ['label' => 'Pendiente', 'color' => 'danger'],
        'parcial'   => ['label' => 'Parcial',   'color' => 'warning'],
        'pagada'    => ['label' => 'Pagada',     'color' => 'success'],
    ];

    const PAYMENT_TYPES = [
        'contado' => 'Contado',
        'credito' => 'Crédito',
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'vehicle_id', 'moto_unit_id', 'mechanic_id', 'cash_register_session_id',
        'code', 'status',
        'mileage', 'fuel_level', 'reported_issue', 'received_items', 'reception_date',
        'diagnosis', 'diagnosis_date',
        'payment_type', 'subtotal_services', 'subtotal_parts', 'discount', 'tax', 'total',
        'paid_amount', 'payment_status',
        'delivered_at', 'delivered_to', 'delivery_notes',
        'notes', 'created_by',
    ];

    protected $casts = [
        'reception_date'    => 'date',
        'diagnosis_date'    => 'date',
        'delivered_at'      => 'datetime',
        'mileage'           => 'integer',
        'subtotal_services' => 'decimal:2',
        'subtotal_parts'    => 'decimal:2',
        'discount'          => 'decimal:2',
        'tax'               => 'decimal:2',
        'total'             => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'deleted_at'        => 'datetime',
    ];

    public function company(): BelongsTo    { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo     { return $this->belongsTo(Branch::class); }
    public function client(): BelongsTo     { return $this->belongsTo(Client::class); }
    public function vehicle(): BelongsTo    { return $this->belongsTo(Vehicle::class); }
    public function motoUnit(): BelongsTo   { return $this->belongsTo(\App\Models\Motos\MotoUnit::class, 'moto_unit_id'); }
    public function mechanic(): BelongsTo   { return $this->belongsTo(Mechanic::class); }
    public function session(): BelongsTo    { return $this->belongsTo(CashRegisterSession::class, 'cash_register_session_id'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public function services(): HasMany     { return $this->hasMany(WorkOrderService::class); }
    public function parts(): HasMany        { return $this->hasMany(WorkOrderPart::class); }
    public function installments(): HasMany { return $this->hasMany(WorkOrderInstallment::class)->orderBy('number'); }
    public function payments(): HasMany     { return $this->hasMany(WorkOrderPayment::class)->latest('payment_date'); }
    public function photos(): HasMany       { return $this->hasMany(WorkOrderPhoto::class)->orderBy('sort_order'); }

    public function getBalanceAttribute(): float
    {
        return (float) ($this->total - $this->paid_amount);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]['label'] ?? $this->payment_status;
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return self::PAYMENT_STATUSES[$this->payment_status]['color'] ?? 'secondary';
    }

    /** Recalcula subtotales y total desde servicios + repuestos */
    public function recalcTotals(): void
    {
        $services = (float) $this->services()->sum('subtotal');
        $parts    = (float) $this->parts()->sum('subtotal');
        $total    = max(0, $services + $parts - (float) $this->discount + (float) $this->tax);

        $this->update([
            'subtotal_services' => $services,
            'subtotal_parts'    => $parts,
            'total'             => $total,
        ]);
    }

    public function recalcPaymentStatus(): void
    {
        $paid  = (float) $this->paid_amount;
        $total = (float) $this->total;

        $status = 'pendiente';
        if ($paid >= $total && $total > 0) {
            $status = 'pagada';
        } elseif ($paid > 0) {
            $status = 'parcial';
        }
        $this->update(['payment_status' => $status]);
    }
}
