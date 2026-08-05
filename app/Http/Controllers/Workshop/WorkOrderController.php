<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Workshop\Concerns\HandlesWorkOrderCharge;
use App\Models\Client;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\Service;
use App\Models\Workshop\WorkOrder;
use App\Models\Workshop\WorkOrderPart;
use App\Models\Workshop\WorkOrderPhoto;
use App\Models\Workshop\WorkOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller
{
    use HandlesWorkOrderCharge;

    /** Órdenes de Trabajo activas (no entregadas ni anuladas) */
    public function index(Request $request)
    {
        $cid = $this->companyScope();

        $user = auth()->user();
        $query = WorkOrder::with(['client', 'vehicle', 'mechanic'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereNotIn('status', ['entregada', 'anulada'])
            ->latest();

        // ¿Puede ver TODAS las órdenes? Sin el permiso, solo las que registró.
        $canAllRecords = $user->is_super_admin
            || $user->hasPermissionInCompany('workshop.view-all-records', $user->getCurrentCompany());
        if (!$canAllRecords) {
            $query->where('created_by', $user->id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->mechanic_id) {
            $query->where('mechanic_id', $request->mechanic_id);
        }

        $mechanics = Mechanic::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();

        return view('workshop.orders.index', [
            'orders'        => $query->paginate(15)->withQueryString(),
            'mechanics'     => $mechanics,
            'canAllRecords' => $canAllRecords,
        ]);
    }

    // ── Recepción (alta de OT) ────────────────────────────────
    public function reception()
    {
        return view('workshop.reception.create', $this->formData());
    }

    public function storeReception(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $mode = $request->input('vehicle_mode', 'existing');

        $validated = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'vehicle_mode'   => 'nullable|in:existing,new',
            // Vehículo existente: requerido solo en modo "existing".
            'vehicle_id'     => [Rule::requiredIf($mode !== 'new'), 'nullable', 'exists:vehicles,id'],
            // Vehículo nuevo: la marca es obligatoria solo en modo "new".
            'vehicle'            => 'nullable|array',
            'vehicle.brand'      => [Rule::requiredIf($mode === 'new'), 'nullable', 'string', 'max:100'],
            'vehicle.model'      => 'nullable|string|max:100',
            'vehicle.plate'      => 'nullable|string|max:20',
            'vehicle.engine_cc'  => 'nullable|string|max:30',
            'vehicle.year'       => 'nullable|integer|min:1900|max:2100',
            'vehicle.color'      => 'nullable|string|max:40',
            'vehicle.vin'        => 'nullable|string|max:60',
            'branch_id'      => 'nullable|exists:branches,id',
            'reception_date' => 'required|date',
            'mileage'        => 'nullable|integer|min:0',
            'fuel_level'     => 'nullable|string|max:20',
            'reported_issue' => 'nullable|string',
            'received_items' => 'nullable|string',
            'notes'          => 'nullable|string',
            'photos'         => 'nullable|array|max:12',
            'photos.*'       => 'image|max:5120',
        ]);

        try {
            $wo = DB::transaction(function () use ($request, $validated, $companyId, $mode) {
                // Resolver el vehículo: registrar uno nuevo o usar el existente.
                if ($mode === 'new') {
                    $v = $validated['vehicle'] ?? [];
                    $vehicle = Vehicle::create([
                        'company_id' => $companyId,
                        'client_id'  => $validated['client_id'],
                        'brand'      => $v['brand'],
                        'model'      => $v['model']     ?? null,
                        'plate'      => $v['plate']     ?? null,
                        'engine_cc'  => $v['engine_cc'] ?? null,
                        'year'       => $v['year']      ?? null,
                        'color'      => $v['color']     ?? null,
                        'vin'        => $v['vin']       ?? null,
                        'active'     => true,
                    ]);
                    $vehicleId = $vehicle->id;
                } else {
                    $vehicleId = $validated['vehicle_id'];
                }

                $wo = WorkOrder::create([
                    'company_id'     => $companyId,
                    'client_id'      => $validated['client_id'],
                    'vehicle_id'     => $vehicleId,
                    'branch_id'      => $validated['branch_id'] ?? null,
                    'reception_date' => $validated['reception_date'],
                    'mileage'        => $validated['mileage'] ?? null,
                    'fuel_level'     => $validated['fuel_level'] ?? null,
                    'reported_issue' => $validated['reported_issue'] ?? null,
                    'received_items' => $validated['received_items'] ?? null,
                    'notes'          => $validated['notes'] ?? null,
                    'code'           => $this->nextCode($companyId, $validated['branch_id'] ?? null),
                    'status'         => 'recibida',
                    'payment_status' => 'pendiente',
                    'created_by'     => auth()->id(),
                ]);

                // Fotos de la recepción (estado del vehículo al recibirlo).
                if ($request->hasFile('photos')) {
                    foreach ($request->file('photos') as $i => $file) {
                        $path = $file->store("company/{$companyId}/work-orders/{$wo->id}", 'public');
                        WorkOrderPhoto::create([
                            'company_id'    => $companyId,
                            'work_order_id' => $wo->id,
                            'file_path'     => $path,
                            'file_name'     => $file->getClientOriginalName(),
                            'sort_order'    => $i,
                        ]);
                    }
                }

                return $wo;
            });

            return redirect()->route('workshop.orders.show', $wo)->with('success', 'Recepción registrada: ' . $wo->code);
        } catch (\Throwable $e) {
            Log::error('Error en recepción de taller', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $order->load([
            'client', 'vehicle', 'mechanic', 'branch', 'createdBy',
            'services.mechanic', 'parts.product', 'installments', 'payments.user',
        ]);

        return view('workshop.orders.show', array_merge(
            $this->formData($order->company_id),
            ['order' => $order]
        ));
    }

    public function edit(WorkOrder $order)
    {
        $this->authorizeOrder($order);
        return view('workshop.orders.edit', array_merge($this->formData($order->company_id), ['order' => $order]));
    }

    public function update(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);

        $validated = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'vehicle_id'     => 'required|exists:vehicles,id',
            'branch_id'      => 'nullable|exists:branches,id',
            'reception_date' => 'required|date',
            'mileage'        => 'nullable|integer|min:0',
            'fuel_level'     => 'nullable|string|max:20',
            'reported_issue' => 'nullable|string',
            'received_items' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        $order->update($validated);
        return redirect()->route('workshop.orders.show', $order)->with('success', 'Orden actualizada.');
    }

    // ── Diagnóstico ───────────────────────────────────────────
    public function diagnosis(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $validated = $request->validate(['diagnosis' => 'required|string']);

        $order->update([
            'diagnosis'      => $validated['diagnosis'],
            'diagnosis_date' => now()->toDateString(),
            'status'         => $order->status === 'recibida' ? 'diagnosticada' : $order->status,
        ]);

        if ($request->expectsJson()) {
            $order->refresh();
            return response()->json([
                'ok'           => true,
                'message'      => 'Diagnóstico guardado.',
                'diagnosis'    => $order->diagnosis,
                'status_label' => $order->status_label,
                'status_color' => $order->status_color,
            ]);
        }
        return back()->with('success', 'Diagnóstico guardado.');
    }

    // ── Asignar mecánico ──────────────────────────────────────
    public function assignMechanic(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $validated = $request->validate(['mechanic_id' => 'required|exists:mechanics,id']);
        $order->update(['mechanic_id' => $validated['mechanic_id']]);
        return back()->with('success', 'Mecánico asignado.');
    }

    // ── Servicios (mano de obra) ──────────────────────────────
    public function addService(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $this->guardEditable($order);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'quantity'    => 'required|integer|min:1',
            'mechanic_id' => 'nullable|exists:mechanics,id',
        ]);

        $name = trim($validated['description']);

        // Buscar el servicio por nombre (empresa) o crearlo al vuelo.
        $service = Service::where('company_id', $order->company_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if (!$service) {
            $service = Service::create([
                'company_id' => $order->company_id,
                'name'       => $name,
                'price'      => $validated['price'],
                'active'     => true,
            ]);
        }

        WorkOrderService::create([
            'work_order_id' => $order->id,
            'service_id'    => $service->id,
            'mechanic_id'   => $validated['mechanic_id'] ?? $order->mechanic_id,
            'description'   => $name,
            'price'         => $validated['price'],
            'quantity'      => $validated['quantity'],
            'subtotal'      => (float) $validated['price'] * (float) $validated['quantity'],
        ]);

        $order->recalcTotals();

        if ($request->expectsJson()) {
            return $this->cardsJson($order, 'Servicio agregado.');
        }
        return back()->with('success', 'Servicio agregado.');
    }

    public function removeService(WorkOrder $order, WorkOrderService $service)
    {
        $this->authorizeOrder($order);
        $this->guardEditable($order);
        abort_unless($service->work_order_id === $order->id, 404);
        $service->delete();
        $order->recalcTotals();

        if (request()->expectsJson()) {
            return $this->cardsJson($order, 'Servicio eliminado.');
        }
        return back()->with('success', 'Servicio eliminado.');
    }

    // ── Repuestos (inventario) ────────────────────────────────
    public function addPart(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $this->guardEditable($order);

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        WorkOrderPart::create([
            'work_order_id' => $order->id,
            'product_id'    => $validated['product_id'],
            'quantity'      => $validated['quantity'],
            'unit_price'    => $validated['unit_price'],
            'subtotal'      => (float) $validated['quantity'] * (float) $validated['unit_price'],
        ]);

        $order->recalcTotals();

        if ($request->expectsJson()) {
            return $this->cardsJson($order, 'Repuesto agregado.');
        }
        return back()->with('success', 'Repuesto agregado.');
    }

    public function removePart(WorkOrder $order, WorkOrderPart $part)
    {
        $this->authorizeOrder($order);
        $this->guardEditable($order);
        abort_unless($part->work_order_id === $order->id, 404);
        $part->delete();
        $order->recalcTotals();

        if (request()->expectsJson()) {
            return $this->cardsJson($order, 'Repuesto eliminado.');
        }
        return back()->with('success', 'Repuesto eliminado.');
    }

    /** Respuesta JSON con los parciales re-renderizados (para altas/bajas AJAX). */
    private function cardsJson(WorkOrder $order, string $message)
    {
        $order->refresh()->load(['services.mechanic', 'parts.product', 'mechanic']);
        $data = $this->formData($order->company_id) + ['order' => $order];

        return response()->json([
            'ok'       => true,
            'message'  => $message,
            'services' => view('workshop.orders._services', $data)->render(),
            'parts'    => view('workshop.orders._parts', $data)->render(),
            'totals'   => view('workshop.orders._totals', $data)->render(),
        ]);
    }

    /** Vista imprimible tamaño carta de la OT. */
    public function print(WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $order->load([
            'client', 'vehicle', 'mechanic', 'branch', 'company', 'createdBy',
            'services.mechanic', 'parts.product',
        ]);
        return view('workshop.orders.print', compact('order'));
    }

    // ── Cambiar estado ────────────────────────────────────────
    public function changeStatus(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);
        $validated = $request->validate([
            'status' => ['required', Rule::in(['diagnosticada', 'en_proceso', 'terminada'])],
        ]);

        if (in_array($order->status, ['entregada', 'anulada'])) {
            return back()->withErrors(['error' => 'La orden ya está cerrada.']);
        }

        $order->update(['status' => $validated['status']]);
        return back()->with('success', 'Estado actualizado a: ' . $order->status_label);
    }

    // ── Registrar abono de crédito ────────────────────────────
    public function registerPayment(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);

        if ($order->payment_status === 'pagada') {
            return back()->withErrors(['error' => 'Esta orden ya está pagada.']);
        }

        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method'       => 'nullable|string|max:50',
            'reference'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        $balance = (float) $order->total - (float) $order->paid_amount;
        $amount  = (float) $validated['amount'];
        if ($amount > $balance + 0.001) {
            return back()->withErrors(['error' => 'El monto supera el saldo pendiente (' . number_format($balance, 2) . ').']);
        }

        $session = $this->currentOpenSession();
        if (!$session) {
            return back()->withErrors(['error' => 'Necesitas tener tu caja abierta para registrar el cobro.']);
        }

        try {
            $this->applyWoCredit($order, $amount, $session, [
                'method'       => $validated['method'] ?? 'efectivo',
                'reference'    => $validated['reference'] ?? null,
                'notes'        => $validated['notes'] ?? 'Cobro de crédito taller',
                'payment_date' => $validated['payment_date'],
            ]);
            $order->refresh()->recalcPaymentStatus();
            return back()->with('success', 'Cobro registrado.');
        } catch (\Throwable $e) {
            Log::error('Error al cobrar OT', ['id' => $order->id, 'msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al registrar el cobro: ' . $e->getMessage()]);
        }
    }

    public function cancel(WorkOrder $order)
    {
        $this->authorizeOrder($order);
        if ($order->status === 'entregada') {
            return back()->withErrors(['error' => 'No se puede anular una orden ya entregada.']);
        }
        $order->update(['status' => 'anulada']);
        return back()->with('success', 'Orden anulada.');
    }

    // ── Historial ─────────────────────────────────────────────
    public function history(Request $request)
    {
        $cid = $this->companyScope();

        $query = WorkOrder::with(['client', 'vehicle', 'mechanic'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['entregada', 'anulada'])
            ->latest('delivered_at');

        if ($request->mechanic_id) $query->where('mechanic_id', $request->mechanic_id);
        if ($request->client_id)   $query->where('client_id', $request->client_id);
        if ($request->date_from)   $query->whereDate('delivered_at', '>=', $request->date_from);
        if ($request->date_to)     $query->whereDate('delivered_at', '<=', $request->date_to);

        $mechanics = Mechanic::when($cid, fn ($q) => $q->where('company_id', $cid))->orderBy('name')->get();
        $clients   = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->orderBy('full_name')->get(['id', 'full_name']);

        return view('workshop.history.index', [
            'orders'    => $query->paginate(20)->withQueryString(),
            'mechanics' => $mechanics,
            'clients'   => $clients,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────

    private function nextCode(int $companyId, ?int $branchId = null): string
    {
        // Correlativo independiente por sucursal
        $count = WorkOrder::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->count() + 1;
        return 'OT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function guardEditable(WorkOrder $order): void
    {
        if (in_array($order->status, ['entregada', 'anulada'])) {
            abort(403, 'La orden está cerrada y no se puede modificar.');
        }
    }

    private function authorizeOrder(WorkOrder $order): void
    {
        if (!auth()->user()->is_super_admin && $order->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }

    private function formData(?int $companyId = null): array
    {
        $cid = $companyId ?? auth()->user()->getCurrentCompany()?->id;

        $clients   = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('full_name')->get();
        $vehicles  = Vehicle::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->with('client')->orderBy('brand')->get();
        $mechanics = Mechanic::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $services  = Service::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $products  = Product::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $branches  = \App\Models\Branch::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();

        return compact('clients', 'vehicles', 'mechanics', 'services', 'products', 'branches');
    }
}
