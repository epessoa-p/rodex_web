<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Workshop\Concerns\HandlesWorkOrderCharge;
use App\Models\Workshop\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryController extends Controller
{
    use HandlesWorkOrderCharge;

    /** OT terminadas listas para entregar + entregadas recientes */
    public function index()
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;

        $pending = WorkOrder::with(['client', 'vehicle', 'mechanic'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('status', 'terminada')
            ->latest()
            ->get();

        $recent = WorkOrder::with(['client', 'vehicle'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('status', 'entregada')
            ->latest('delivered_at')
            ->limit(10)
            ->get();

        return view('workshop.deliveries.index', compact('pending', 'recent'));
    }

    public function create(WorkOrder $order)
    {
        $this->authorizeOrder($order);

        if (in_array($order->status, ['entregada', 'anulada'])) {
            return back()->withErrors(['error' => 'Esta orden ya está cerrada.']);
        }
        if ($order->parts->isEmpty() && $order->services->isEmpty()) {
            return back()->withErrors(['error' => 'Agrega servicios o repuestos antes de entregar.']);
        }

        $order->load(['client', 'vehicle', 'mechanic', 'services', 'parts.product']);
        $session = $this->currentOpenSession();

        return view('workshop.deliveries.create', compact('order', 'session'));
    }

    public function store(Request $request, WorkOrder $order)
    {
        $this->authorizeOrder($order);

        if (in_array($order->status, ['entregada', 'anulada'])) {
            return back()->withErrors(['error' => 'Esta orden ya está cerrada.']);
        }

        $validated = $request->validate([
            'payment_type'            => 'required|in:contado,credito',
            'discount'                => 'nullable|numeric|min:0',
            'tax'                     => 'nullable|numeric|min:0',
            'delivered_to'            => 'nullable|string|max:255',
            'delivery_notes'          => 'nullable|string',
            'method'                  => 'nullable|string|max:50',
            'installments'            => 'nullable|array',
            'installments.*.due_date' => 'required_with:installments|date',
            'installments.*.amount'   => 'required_with:installments|numeric|min:0.01',
            'down_payment'            => 'nullable|numeric|min:0',
        ]);

        // Caja requerida si hay cobro de efectivo (contado, o enganche en crédito)
        $needsCash = $validated['payment_type'] === 'contado' || (float) ($validated['down_payment'] ?? 0) > 0;
        $session   = $needsCash ? $this->currentOpenSession() : null;

        if ($validated['payment_type'] === 'contado' && !$session) {
            return back()->withInput()->withErrors(['error' => 'Para cobrar al contado necesitas tener tu caja abierta.']);
        }
        if ($validated['payment_type'] === 'credito' && empty($validated['installments'])) {
            return back()->withInput()->withErrors(['installments' => 'Una entrega a crédito requiere al menos una cuota.']);
        }

        try {
            $this->deliverWorkOrder($order, $validated, $session);
            return redirect()->route('workshop.orders.show', $order)->with('success', 'OT entregada: ' . $order->code);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Error al entregar OT', ['id' => $order->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al entregar: ' . $e->getMessage()]);
        }
    }

    private function authorizeOrder(WorkOrder $order): void
    {
        if (!auth()->user()->is_super_admin && $order->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
