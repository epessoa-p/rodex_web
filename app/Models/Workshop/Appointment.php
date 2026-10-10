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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /** Duración máxima de una cita: 5 días (trabajos largos de taller). */
    public const MAX_DURATION = 7200;

    /** Duraciones que se ofrecen al agendar (minutos => etiqueta). */
    public const DURATIONS = [
        30 => '30 min', 60 => '1 hora', 90 => '1 h 30 min', 120 => '2 horas', 180 => '3 horas',
        240 => '4 horas', 300 => '5 horas', 360 => '6 horas', 480 => '8 horas (jornada)',
        1440 => '1 día', 2880 => '2 días', 4320 => '3 días', 5760 => '4 días', 7200 => '5 días',
    ];

    /** "45 min", "1 h 30 min", "2 días", "1 día 4 h". */
    public static function formatDuration(int $minutes): string
    {
        $days  = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $mins  = $minutes % 60;
        $parts = [];
        if ($days)  $parts[] = $days . ($days === 1 ? ' día' : ' días');
        if ($hours) $parts[] = $hours . ' h';
        if ($mins)  $parts[] = $mins . ' min';

        return $parts ? implode(' ', $parts) : '0 min';
    }

    public function getDurationLabelAttribute(): string
    {
        return self::formatDuration((int) $this->duration_minutes);
    }

    /** Primer servicio (legado). La lista completa está en services(). */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /** Servicios de la cita (varios). */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'appointment_services')->withTimestamps();
    }

    /**
     * Fija los servicios de la cita y mantiene `service_id` = el primero
     * (compatibilidad con la web y con versiones viejas de la APK).
     */
    public function syncServices(array $serviceIds): void
    {
        $ids = array_values(array_unique(array_map('intval', $serviceIds)));
        $this->services()->sync($ids);
        $this->forceFill(['service_id' => $ids[0] ?? null])->save();
    }

    /**
     * Copia los servicios de la cita como líneas de la OT (precio del catálogo,
     * cantidad 1, mecánico de la OT) y recalcula totales. Mismo shape que
     * Api\WorkOrderController::addService.
     */
    public function copyServicesToWorkOrder(WorkOrder $order): void
    {
        $services = $this->services()->get();
        if ($services->isEmpty() && $this->service_id) {
            $services = Service::whereKey($this->service_id)->get();
        }

        foreach ($services as $service) {
            $price = (float) $service->price;
            WorkOrderService::create([
                'work_order_id' => $order->id,
                'service_id'    => $service->id,
                'mechanic_id'   => $order->mechanic_id,
                'description'   => $service->name,
                'price'         => $price,
                'quantity'      => 1,
                'subtotal'      => $price,
            ]);
        }

        if ($services->isNotEmpty()) {
            $order->recalcTotals();
        }
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
