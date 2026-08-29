<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Workshop\Concerns\HandlesWorkOrderCharge;
use App\Models\Vehicle;
use App\Models\Workshop\Service;
use App\Models\Workshop\WorkOrder;
use App\Models\Workshop\WorkOrderPart;
use App\Models\Workshop\WorkOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * API del módulo Taller. Reutiliza la lógica de cobro/entrega del web
 * (HandlesWorkOrderCharge, que incluye ResolvesCashSession). El aislamiento
 * por empresa lo aplica el global scope; el route-model binding queda scoped.
 */
class WorkOrderController extends Controller
{
    use HandlesWorkOrderCharge;

    /** Órdenes activas (no entregadas/anuladas). */
    public function index(Request $request)
    {
        $user = $request->user();
        $company = $request->attributes->get('tenant_company');

        // Incluye entregadas (para consultarlas); oculta solo las anuladas.
        $query = WorkOrder::with(['client', 'vehicle', 'mechanic'])
            ->whereNotIn('status', ['anulada'])
            ->latest();

        // Sin el permiso "ver todo", solo las que registró el usuario.
        $canAll = $user->is_super_admin ||
            $user->hasPermissionInCompany('workshop.view-all-records', $company);
        if (! $canAll) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $orders = $query->limit(50)->get()->map(fn ($e) => $this->summary($e));

        return response()->json(['data' => $orders]);
    }

    /**
     * Resumen de OTs del día para el inicio del móvil: recibidas hoy y activas
     * (no entregadas/anuladas). Respeta "solo las mías" salvo permiso de ver todo.
     */
    public function todaySummary(Request $request)
    {
        $user  = $request->user();
        $company = $request->attributes->get('tenant_company');
        $today = today();

        $canAll = $user->is_super_admin
            || $user->hasPermissionInCompany('workshop.view-all-records', $company);

        $base = fn () => tap(WorkOrder::query(), function ($q) use ($canAll, $user) {
            if (! $canAll) {
                $q->where('created_by', $user->id);
            }
        });

        $receivedToday = $base()->whereDate('reception_date', $today)->count();
        $active = $base()->whereNotIn('status', ['entregada', 'anulada'])->count();

        return response()->json([
            'data' => [
                'date'           => $today->toDateString(),
                'received_today' => $receivedToday,
                'active'         => $active,
                'scope'          => $canAll ? 'all' : 'own',
            ],
        ]);
    }

    /** Recepción: crea la OT (con vehículo existente o nuevo). */
    public function store(Request $request)
    {
        $company = $request->attributes->get('tenant_company');
        $companyId = $company->id;
        $mode = $request->input('vehicle_mode', 'existing');

        $data = $request->validate([
            'client_id'         => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'vehicle_mode'      => ['nullable', 'in:existing,new'],
            'vehicle_id'        => [Rule::requiredIf($mode !== 'new'), 'nullable', Rule::exists('vehicles', 'id')->where('company_id', $companyId)],
            'vehicle'           => ['nullable', 'array'],
            'vehicle.brand'     => [Rule::requiredIf($mode === 'new'), 'nullable', 'string', 'max:100'],
            'vehicle.model'     => ['nullable', 'string', 'max:100'],
            'vehicle.plate'     => ['nullable', 'string', 'max:20'],
            'vehicle.year'      => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'vehicle.color'     => ['nullable', 'string', 'max:40'],
            'reception_date'    => ['nullable', 'date'],
            'mileage'           => ['nullable', 'integer', 'min:0'],
            'fuel_level'        => ['nullable', 'string', 'max:20'],
            'reported_issue'    => ['nullable', 'string'],
            'received_items'    => ['nullable', 'string'],
            'notes'             => ['nullable', 'string'],
            // Si la OT nace de una cita de la agenda, se enlaza y se marca completada.
            'appointment_id'    => ['nullable', Rule::exists('appointments', 'id')->where('company_id', $companyId)],
        ]);

        $userId = $request->user()->id;
        // Sucursal base del personal del usuario (como en el web); permite que la
        // entrega descuente stock del almacén de esa sucursal.
        $branchId = \App\Models\Personal::where('user_id', $userId)->value('branch_id');

        $order = DB::transaction(function () use ($data, $companyId, $mode, $userId, $branchId) {
            $vehicleId = $data['vehicle_id'] ?? null;

            if ($mode === 'new') {
                $v = $data['vehicle'] ?? [];
                $vehicle = Vehicle::create([
                    'company_id' => $companyId,
                    'client_id'  => $data['client_id'],
                    'brand'      => $v['brand'],
                    'model'      => $v['model'] ?? null,
                    'plate'      => $v['plate'] ?? null,
                    'year'       => $v['year'] ?? null,
                    'color'      => $v['color'] ?? null,
                    'active'     => true,
                ]);
                $vehicleId = $vehicle->id;
            }

            $order = WorkOrder::create([
                'company_id'     => $companyId,
                'client_id'      => $data['client_id'],
                'vehicle_id'     => $vehicleId,
                'branch_id'      => $branchId,
                'reception_date' => $data['reception_date'] ?? now()->toDateTimeString(),
                'mileage'        => $data['mileage'] ?? null,
                'fuel_level'     => $data['fuel_level'] ?? null,
                'reported_issue' => $data['reported_issue'] ?? null,
                'received_items' => $data['received_items'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'code'           => $this->nextCode($companyId, $branchId),
                'status'         => 'recibida',
                'payment_status' => 'pendiente',
                'created_by'     => $userId,
            ]);

            // Enlaza la cita de origen (si viene de la agenda y no está enlazada).
            if (! empty($data['appointment_id'])) {
                \App\Models\Workshop\Appointment::where('id', $data['appointment_id'])
                    ->whereNull('work_order_id')
                    ->update(['work_order_id' => $order->id, 'status' => 'completada']);
            }

            return $order;
        });

        return response()->json(['data' => $this->detail($order)], 201);
    }

    /** Guardar/actualizar el diagnóstico (recibida → diagnosticada). */
    public function diagnosis(Request $request, WorkOrder $order)
    {
        if (in_array($order->status, ['entregada', 'anulada'], true)) {
            return response()->json(['message' => 'La orden está cerrada y no se puede modificar.'], 422);
        }

        $data = $request->validate(['diagnosis' => ['required', 'string']]);

        $order->update([
            'diagnosis'      => $data['diagnosis'],
            'diagnosis_date' => now()->toDateString(),
            'status'         => $order->status === 'recibida' ? 'diagnosticada' : $order->status,
        ]);

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    public function show(WorkOrder $order)
    {
        return response()->json(['data' => $this->detail($order)]);
    }

    public function addService(Request $request, WorkOrder $order)
    {
        $this->guardEditable($order);
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'quantity'    => ['required', 'integer', 'min:1'],
            'mechanic_id' => ['nullable', 'integer'],
        ]);

        $name = trim($data['description']);
        $service = Service::where('company_id', $order->company_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first()
            ?? Service::create([
                'company_id' => $order->company_id,
                'name'       => $name,
                'price'      => $data['price'],
                'active'     => true,
            ]);

        WorkOrderService::create([
            'work_order_id' => $order->id,
            'service_id'    => $service->id,
            'mechanic_id'   => $data['mechanic_id'] ?? $order->mechanic_id,
            'description'   => $name,
            'price'         => $data['price'],
            'quantity'      => $data['quantity'],
            'subtotal'      => (float) $data['price'] * (float) $data['quantity'],
        ]);

        $order->recalcTotals();

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    public function removeService(WorkOrder $order, WorkOrderService $service)
    {
        $this->guardEditable($order);
        abort_unless($service->work_order_id === $order->id, 404);
        $service->delete();
        $order->recalcTotals();

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    public function addPart(Request $request, WorkOrder $order)
    {
        $this->guardEditable($order);
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $order->company_id)],
            'quantity'   => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        WorkOrderPart::create([
            'work_order_id' => $order->id,
            'product_id'    => $data['product_id'],
            'quantity'      => $data['quantity'],
            'unit_price'    => $data['unit_price'],
            'subtotal'      => (float) $data['quantity'] * (float) $data['unit_price'],
        ]);

        $order->recalcTotals();

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    public function removePart(WorkOrder $order, WorkOrderPart $part)
    {
        $this->guardEditable($order);
        abort_unless($part->work_order_id === $order->id, 404);
        $part->delete();
        $order->recalcTotals();

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    public function changeStatus(Request $request, WorkOrder $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['diagnosticada', 'en_proceso', 'terminada'])],
        ]);

        if (in_array($order->status, ['entregada', 'anulada'])) {
            return response()->json(['message' => 'La orden ya está cerrada.'], 422);
        }

        $order->update(['status' => $data['status']]);

        return response()->json(['data' => $this->detail($order->fresh())]);
    }

    /** Entrega al contado: cierra la OT y cobra el total (requiere caja abierta). */
    public function deliver(Request $request, WorkOrder $order)
    {
        if (in_array($order->status, ['entregada', 'anulada'])) {
            return response()->json(['message' => 'Esta orden ya está cerrada.'], 422);
        }

        $data = $request->validate([
            'discount'     => ['nullable', 'numeric', 'min:0'],
            'delivered_to' => ['nullable', 'string', 'max:255'],
            'method'       => ['nullable', 'string', 'max:50'],
        ]);

        $session = $this->currentOpenSession();
        if (! $session) {
            return response()->json([
                'message' => 'Necesitas tu caja abierta para cobrar la entrega.',
                'code'    => 'cash_session_required',
            ], 422);
        }

        try {
            $this->deliverWorkOrder($order, [
                'payment_type'   => 'contado',
                'discount'       => $data['discount'] ?? 0,
                'tax'            => 0,
                'delivered_to'   => $data['delivered_to'] ?? null,
                'method'         => $data['method'] ?? 'efectivo',
                'installments'   => [],
                'down_payment'   => 0,
            ], $session);

            return response()->json(['data' => $this->detail($order->fresh())]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'No se pudo entregar.',
            ], 422);
        }
    }

    /** Bloquea la edición de órdenes ya cerradas. */
    private function guardEditable(WorkOrder $order): void
    {
        if (in_array($order->status, ['entregada', 'anulada'])) {
            abort(response()->json(['message' => 'La orden está cerrada y no se puede modificar.'], 403));
        }
    }

    /** Correlativo de OT por empresa/sucursal (OT-00001). */
    private function nextCode(int $companyId, ?int $branchId = null): string
    {
        $count = WorkOrder::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->count() + 1;

        return 'OT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    // ── Serializadores ────────────────────────────────────────────

    private function summary(WorkOrder $o): array
    {
        return [
            'id'                   => $o->id,
            'code'                 => $o->code,
            'status'               => $o->status,
            'status_label'         => $o->status_label,
            'payment_status'       => $o->payment_status,
            'total'                => (float) $o->total,
            'balance'              => (float) $o->balance,
            'client'               => $o->client?->full_name,
            'vehicle'              => $o->vehicle?->display_name,
            'mechanic'             => $o->mechanic?->name,
            'reception_date'       => $o->reception_date?->toIso8601String(),
        ];
    }

    private function detail(WorkOrder $o): array
    {
        $o->load(['client', 'vehicle', 'mechanic', 'services.mechanic', 'parts.product']);

        return $this->summary($o) + [
            'reported_issue'    => $o->reported_issue,
            'diagnosis'         => $o->diagnosis,
            'mileage'           => $o->mileage,
            'fuel_level'        => $o->fuel_level,
            'received_items'    => $o->received_items,
            'notes'             => $o->notes,
            'subtotal_services' => (float) $o->subtotal_services,
            'subtotal_parts'    => (float) $o->subtotal_parts,
            'discount'          => (float) $o->discount,
            'paid_amount'       => (float) $o->paid_amount,
            'services'          => $o->services->map(fn ($s) => [
                'id'          => $s->id,
                'description' => $s->description,
                'price'       => (float) $s->price,
                'quantity'    => (int) $s->quantity,
                'subtotal'    => (float) $s->subtotal,
                'mechanic'    => $s->mechanic?->name,
            ])->values(),
            'parts'             => $o->parts->map(fn ($p) => [
                'id'         => $p->id,
                'name'       => $p->product?->name,
                'quantity'   => (int) $p->quantity,
                'unit_price' => (float) $p->unit_price,
                'subtotal'   => (float) $p->subtotal,
            ])->values(),
        ];
    }
}
