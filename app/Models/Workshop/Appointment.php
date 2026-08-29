<?php

namespace App\Models\Workshop;

use App\Models\Concerns\BelongsToCompany;

use App\Models\Branch;
use App\Models\Client;
use App\Models\Company;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cita de la agenda: un cliente reserva día/hora para un servicio. Puede tener
 * un cliente registrado o solo nombre/teléfono suelto (walk-in). Opcionalmente
 * se convierte en una Orden de Trabajo (work_order_id).
 */
class Appointment extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    const STATUSES = [
        'programada' => ['label' => 'Programada', 'color' => 'info'],
        'confirmada' => ['label' => 'Confirmada', 'color' => 'primary'],
        'completada' => ['label' => 'Completada', 'color' => 'success'],
        'cancelada'  => ['label' => 'Cancelada',  'color' => 'danger'],
        'no_asistio' => ['label' => 'No asistió', 'color' => 'secondary'],
    ];

    /** Estados que ocupan un espacio en la agenda (para chequear disponibilidad). */
    const ACTIVE_STATUSES = ['programada', 'confirmada'];

    protected $fillable = [
        'company_id', 'branch_id', 'client_id', 'vehicle_id', 'service_id',
        'mechanic_id', 'work_order_id', 'customer_name', 'customer_phone',
        'title', 'scheduled_at', 'duration_minutes', 'status', 'notes', 'created_by',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'duration_minutes' => 'integer',
        'deleted_at'       => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Hora de fin calculada (inicio + duración). */
    public function getEndsAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->scheduled_at?->copy()->addMinutes($this->duration_minutes ?: 0);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return self::STATUSES[$this->status]['color'] ?? 'secondary';
    }

    /** Nombre a mostrar: cliente registrado o el nombre suelto. */
    public function getDisplayNameAttribute(): string
    {
        return $this->client?->full_name
            ?? $this->customer_name
            ?? 'Sin nombre';
    }

    /** Teléfono a mostrar: del cliente o el suelto. */
    public function getDisplayPhoneAttribute(): ?string
    {
        return $this->client?->phone ?? $this->customer_phone;
    }
}
