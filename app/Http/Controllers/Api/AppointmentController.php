<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workshop\Appointment;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\Service;
use App\Models\Workshop\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Agenda / Citas para el móvil. Vista por día, alta/edición, cambio de estado
 * y conversión a Orden de Trabajo. Aislamiento por empresa vía global scope.
 */
class AppointmentController extends Controller
{
    /** Citas de un día (?date=YYYY-MM-DD, por defecto hoy) + resumen. */
    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : Carbon::today();

        $appointments = Appointment::with(['client', 'vehicle', 'service', 'mechanic'])
            ->whereDate('scheduled_at', $date)
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => [
                'date'         => $date->toDateString(),
                'stats'        => [
                    'total'      => $appointments->count(),
                    'programada' => $appointments->where('status', 'programada')->count(),
                    'confirmada' => $appointments->where('status', 'confirmada')->count(),
                    'completada' => $appointments->where('status', 'completada')->count(),
                ],
                'appointments' => $appointments->map(fn (Appointment $a) => $this->payload($a))->values(),
            ],
        ]);
    }

    /** Citas en un rango (para semana/mes). ?from=YYYY-MM-DD&to=YYYY-MM-DD. */
    public function range(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to   = Carbon::parse($validated['to'])->endOfDay();

        // Tope de seguridad: máximo ~ una cuadrícula de mes (42 días).
        if ($from->diffInDays($to) > 45) {
            $to = $from->copy()->addDays(45)->endOfDay();
        }

        $appointments = Appointment::with(['client', 'vehicle', 'service', 'mechanic'])
            ->whereBetween('scheduled_at', [$from, $to])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'data' => [
                'from'         => $from->toDateString(),
                'to'           => $to->toDateString(),
                'appointments' => $appointments->map(fn (Appointment $a) => $this->payload($a))->values(),
            ],
        ]);
    }

    /** Catálogos para el formulario de cita: servicios y mecánicos. */
    public function meta()
    {
        return response()->json([
            'data' => [
                'services' => Service::where('active', true)->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Service $s) => ['id' => $s->id, 'name' => $s->name]),
                'mechanics' => Mechanic::where('active', true)->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn (Mechanic $m) => ['id' => $m->id, 'name' => $m->name]),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        $data = $this->validateData($request, $company->id);

        $appointment = Appointment::create([
            ...$data,
            'company_id' => $company->id,
            'branch_id'  => \App\Models\Personal::where('user_id', auth()->id())->value('branch_id'),
            'status'     => 'programada',
            'created_by' => auth()->id(),
        ]);

        return response()->json(['data' => $this->payload($appointment->load(['client', 'vehicle', 'service', 'mechanic']))], 201);
    }

    public function update(Request $request, Appointment $appointment)
    {
        $company = $request->attributes->get('tenant_company');
        $data = $this->validateData($request, $company->id);
        $appointment->update($data);

        return response()->json(['data' => $this->payload($appointment->fresh(['client', 'vehicle', 'service', 'mechanic']))]);
    }

    public function changeStatus(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Appointment::STATUSES))],
        ]);
        $appointment->update(['status' => $validated['status']]);

        return response()->json(['data' => $this->payload($appointment->fresh(['client', 'vehicle', 'service', 'mechanic']))]);
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    /** Convierte la cita en OT (requiere cliente registrado + vehículo). */
    public function convertToWorkOrder(Appointment $appointment)
    {
        if ($appointment->work_order_id) {
            return response()->json([
                'data' => ['work_order_id' => $appointment->work_order_id, 'code' => $appointment->workOrder?->code],
            ]);
        }

        if (! $appointment->client_id || ! $appointment->vehicle_id) {
            return response()->json([
                'message' => 'Para crear la OT, la cita debe tener un cliente registrado y su vehículo.',
            ], 422);
        }

        $order = DB::transaction(function () use ($appointment) {
            $order = WorkOrder::create([
                'company_id'     => $appointment->company_id,
                'branch_id'      => $appointment->branch_id,
                'client_id'      => $appointment->client_id,
                'vehicle_id'     => $appointment->vehicle_id,
                'mechanic_id'    => $appointment->mechanic_id,
                'code'           => $this->nextWorkOrderCode($appointment->company_id, $appointment->branch_id),
                'status'         => 'recibida',
                'reception_date' => now()->toDateString(),
                'reported_issue' => $appointment->title ?: $appointment->notes,
                'notes'          => $appointment->notes,
                'payment_status' => 'pendiente',
                'created_by'     => auth()->id(),
            ]);

            $appointment->update(['work_order_id' => $order->id, 'status' => 'completada']);

            return $order;
        });

        return response()->json(['data' => ['work_order_id' => $order->id, 'code' => $order->code]], 201);
    }

    private function validateData(Request $request, int $companyId): array
    {
        $data = $request->validate([
            'client_id'        => ['nullable', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'customer_name'    => ['nullable', 'required_without:client_id', 'string', 'max:255'],
            'customer_phone'   => ['nullable', 'string', 'max:30'],
            'vehicle_id'       => ['nullable', Rule::exists('vehicles', 'id')->where('company_id', $companyId)],
            'service_id'       => ['nullable', Rule::exists('services', 'id')->where('company_id', $companyId)],
            'mechanic_id'      => ['nullable', Rule::exists('mechanics', 'id')->where('company_id', $companyId)],
            'title'            => ['nullable', 'string', 'max:255'],
            'scheduled_at'     => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:1440'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_name.required_without' => 'Indica un cliente o al menos un nombre.',
        ]);

        if (empty($data['title']) && ! empty($data['service_id'])) {
            $data['title'] = Service::whereKey($data['service_id'])->value('name');
        }

        return $data;
    }

    private function nextWorkOrderCode(int $companyId, ?int $branchId): string
    {
        $count = WorkOrder::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->count() + 1;

        return 'OT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function payload(Appointment $a): array
    {
        return [
            'id'               => $a->id,
            'scheduled_at'     => $a->scheduled_at?->toIso8601String(),
            'date'             => $a->scheduled_at?->toDateString(),
            'time'             => $a->scheduled_at?->format('H:i'),
            'end_time'         => $a->ends_at?->format('H:i'),
            'duration_minutes' => $a->duration_minutes,
            'status'           => $a->status,
            'status_label'     => $a->status_label,
            'title'            => $a->title,
            'notes'            => $a->notes,
            'display_name'     => $a->display_name,
            'display_phone'    => $a->display_phone,
            'client_id'        => $a->client_id,
            'customer_name'    => $a->customer_name,
            'customer_phone'   => $a->customer_phone,
            'vehicle_id'       => $a->vehicle_id,
            'vehicle_label'    => $a->vehicle ? trim($a->vehicle->brand . ' ' . $a->vehicle->model) : null,
            'service_id'       => $a->service_id,
            'service_name'     => $a->service?->name,
            'mechanic_id'      => $a->mechanic_id,
            'mechanic_name'    => $a->mechanic?->name,
            'work_order_id'    => $a->work_order_id,
        ];
    }
}
