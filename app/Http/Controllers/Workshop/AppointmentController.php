<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Personal;
use App\Models\Vehicle;
use App\Models\Workshop\Appointment;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\Service;
use App\Models\Workshop\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Agenda / Citas del taller. Vista por día con línea de tiempo para revisar la
 * disponibilidad y agendar. Las citas pueden convertirse en Órdenes de Trabajo.
 */
class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;

        $view = in_array($request->query('view'), ['day', 'week', 'month'], true)
            ? $request->query('view') : 'day';

        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : Carbon::today();

        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd   = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        // Tira de la semana (lun-dom) con conteo por día (para la vista de día)
        $weekCounts = Appointment::whereBetween('scheduled_at', [$weekStart, $weekEnd->copy()->endOfDay()])
            ->selectRaw('DATE(scheduled_at) as d, COUNT(*) as total')
            ->groupBy('d')->pluck('total', 'd');
        $week = [];
        for ($i = 0; $i < 7; $i++) {
            $d = $weekStart->copy()->addDays($i);
            $week[] = [
                'date'     => $d,
                'count'    => (int) ($weekCounts[$d->toDateString()] ?? 0),
                'isActive' => $d->isSameDay($date),
                'isToday'  => $d->isToday(),
            ];
        }

        $with = ['client', 'vehicle', 'service', 'mechanic', 'workOrder'];
        $appointments = collect();   // vista día
        $weekDays     = [];          // vista semana
        $monthCells   = [];          // vista mes
        $forJson      = collect();
        $stats = ['total' => 0, 'programada' => 0, 'confirmada' => 0, 'completada' => 0];

        if ($view === 'day') {
            $appointments = Appointment::with($with)
                ->whereDate('scheduled_at', $date)->orderBy('scheduled_at')->get();
            $stats = [
                'total'      => $appointments->count(),
                'programada' => $appointments->where('status', 'programada')->count(),
                'confirmada' => $appointments->where('status', 'confirmada')->count(),
                'completada' => $appointments->where('status', 'completada')->count(),
            ];
            $forJson = $appointments;
        } elseif ($view === 'week') {
            $range = Appointment::with($with)
                ->whereBetween('scheduled_at', [$weekStart, $weekEnd->copy()->endOfDay()])
                ->orderBy('scheduled_at')->get();
            for ($i = 0; $i < 7; $i++) {
                $d = $weekStart->copy()->addDays($i);
                $weekDays[] = [
                    'date'    => $d,
                    'isToday' => $d->isToday(),
                    'items'   => $range->filter(fn ($a) => $a->scheduled_at->isSameDay($d))->values(),
                ];
            }
            $forJson = $range;
        } else { // month
            $gridStart = $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $gridEnd   = $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
            $counts = Appointment::whereBetween('scheduled_at', [$gridStart, $gridEnd->copy()->endOfDay()])
                ->selectRaw('DATE(scheduled_at) as d, COUNT(*) as total')
                ->groupBy('d')->pluck('total', 'd');
            for ($cur = $gridStart->copy(); $cur <= $gridEnd; $cur->addDay()) {
                $monthCells[] = [
                    'date'    => $cur->copy(),
                    'inMonth' => $cur->month === $date->month,
                    'isToday' => $cur->isToday(),
                    'count'   => (int) ($counts[$cur->toDateString()] ?? 0),
                ];
            }
        }

        // Catálogos para el modal de alta
        $clients   = Client::where('company_id', $companyId)->orderBy('full_name')->get(['id', 'full_name', 'phone']);
        $vehicles  = Vehicle::where('company_id', $companyId)->orderBy('brand')->get(['id', 'client_id', 'brand', 'model', 'plate']);
        $services  = Service::where('company_id', $companyId)->where('active', true)->orderBy('name')->get(['id', 'name']);
        $mechanics = Mechanic::where('company_id', $companyId)->where('active', true)->orderBy('name')->get(['id', 'name']);

        // Datos para poblar el modal de edición desde el front (keyed por id)
        $appointmentsJson = $forJson->keyBy('id')->map(fn (Appointment $a) => [
            'id'               => $a->id,
            'client_id'        => $a->client_id,
            'vehicle_id'       => $a->vehicle_id,
            'customer_name'    => $a->customer_name,
            'customer_phone'   => $a->customer_phone,
            'service_id'       => $a->service_id,
            'mechanic_id'      => $a->mechanic_id,
            'title'            => $a->title,
            'date'             => $a->scheduled_at?->toDateString(),
            'time'             => $a->scheduled_at?->format('H:i'),
            'duration_minutes' => $a->duration_minutes,
            'notes'            => $a->notes,
        ]);

        return view('workshop.agenda.index', compact(
            'view', 'date', 'weekStart', 'weekEnd', 'appointments', 'week', 'stats',
            'weekDays', 'monthCells', 'appointmentsJson',
            'clients', 'vehicles', 'services', 'mechanics'
        ));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;

        $data = $this->validateData($request, $companyId);

        Appointment::create([
            ...$data,
            'company_id' => $companyId,
            'branch_id'  => Personal::where('user_id', auth()->id())->value('branch_id'),
            'status'     => 'programada',
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('workshop.agenda.index', ['date' => Carbon::parse($data['scheduled_at'])->toDateString()])
            ->with('success', 'Cita agendada.');
    }

    public function update(Request $request, Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);
        $companyId = auth()->user()->getCurrentCompany()?->id;

        $data = $this->validateData($request, $companyId);
        $appointment->update($data);

        return redirect()
            ->route('workshop.agenda.index', ['date' => Carbon::parse($data['scheduled_at'])->toDateString()])
            ->with('success', 'Cita actualizada.');
    }

    public function changeStatus(Request $request, Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Appointment::STATUSES))],
        ]);

        $appointment->update(['status' => $validated['status']]);

        return back()->with('success', 'Estado de la cita actualizado.');
    }

    public function destroy(Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);
        $date = $appointment->scheduled_at?->toDateString();
        $appointment->delete();

        return redirect()
            ->route('workshop.agenda.index', ['date' => $date])
            ->with('success', 'Cita eliminada.');
    }

    /** Convierte la cita en una Orden de Trabajo (requiere cliente y vehículo). */
    public function convertToWorkOrder(Appointment $appointment)
    {
        $this->authorizeAppointment($appointment);

        if ($appointment->work_order_id) {
            return redirect()->route('workshop.orders.show', $appointment->work_order_id)
                ->with('info', 'Esta cita ya tenía una Orden de Trabajo.');
        }

        if (! $appointment->client_id || ! $appointment->vehicle_id) {
            return back()->withErrors([
                'error' => 'Para crear la OT, la cita debe tener un cliente registrado y su vehículo.',
            ]);
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

            $appointment->update([
                'work_order_id' => $order->id,
                'status'        => 'completada',
            ]);

            return $order;
        });

        return redirect()->route('workshop.orders.show', $order->id)
            ->with('success', "Orden de trabajo {$order->code} creada desde la cita.");
    }

    /** Reglas compartidas de validación de una cita. */
    private function validateData(Request $request, ?int $companyId): array
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

        // Si eligió un servicio y no puso título, usa el nombre del servicio.
        if (empty($data['title']) && ! empty($data['service_id'])) {
            $data['title'] = Service::whereKey($data['service_id'])->value('name');
        }

        return $data;
    }

    private function authorizeAppointment(Appointment $appointment): void
    {
        if ($appointment->company_id !== auth()->user()->getCurrentCompany()?->id
            && ! auth()->user()->is_super_admin) {
            abort(403);
        }
    }

    private function nextWorkOrderCode(int $companyId, ?int $branchId): string
    {
        $count = WorkOrder::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->count() + 1;

        return 'OT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
