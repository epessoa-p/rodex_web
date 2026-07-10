<?php

namespace App\Http\Controllers\Motos;

use App\Http\Controllers\Controller;
use App\Models\Motos\MotoUnit;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MotoDeliveryController extends Controller
{
    /** Unidades vendidas pendientes de entrega + entregas recientes */
    public function index()
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;

        $pending = MotoUnit::with(['model.brand', 'sale.client'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('status', 'vendida')
            ->latest('updated_at')->get();

        $recent = MotoUnit::with(['model.brand', 'sale.client'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('status', 'entregada')
            ->latest('delivered_at')->limit(10)->get();

        return view('motos.deliveries.index', compact('pending', 'recent'));
    }

    public function create(MotoUnit $unit)
    {
        $this->authorizeUnit($unit);
        if ($unit->status !== 'vendida') {
            return back()->withErrors(['error' => 'Solo se entregan unidades vendidas (estado actual: ' . $unit->status_label . ').']);
        }
        $unit->load(['model.brand', 'sale.client']);
        return view('motos.deliveries.create', compact('unit'));
    }

    public function store(Request $request, MotoUnit $unit)
    {
        $this->authorizeUnit($unit);
        if ($unit->status !== 'vendida') {
            return back()->withErrors(['error' => 'Esta unidad no está en estado vendida.']);
        }

        $validated = $request->validate([
            'delivered_to'    => 'required|string|max:255',
            'assigned_plate'  => 'nullable|string|max:20',
            'delivery_notes'  => 'nullable|string',
            'register_vehicle'=> 'sometimes|boolean',
        ]);

        try {
            DB::transaction(function () use ($unit, $validated, $request) {
                $unit->update([
                    'status'         => 'entregada',
                    'delivered_at'   => now(),
                    'delivered_to'   => $validated['delivered_to'],
                    'assigned_plate' => $validated['assigned_plate'] ?? null,
                    'delivery_notes' => $validated['delivery_notes'] ?? null,
                ]);

                // Opcional: registrar el vehículo en la ficha del cliente
                if ($request->boolean('register_vehicle') && $unit->sale?->client_id) {
                    $unit->loadMissing('model.brand');
                    Vehicle::create([
                        'company_id' => $unit->company_id,
                        'client_id'  => $unit->sale->client_id,
                        'brand'      => $unit->model?->brand?->name ?? '—',
                        'model'      => $unit->model?->name,
                        'engine_cc'  => $unit->model?->engine_cc,
                        'year'       => $unit->year,
                        'plate'      => $validated['assigned_plate'] ?? null,
                        'color'      => $unit->color,
                        'vin'        => $unit->chassis_number,
                        'notes'      => 'Registrado desde entrega de moto ' . $unit->chassis_number,
                        'active'     => true,
                    ]);
                }
            });

            return redirect()->route('moto-units.show', $unit)->with('success', 'Unidad entregada.');
        } catch (\Throwable $e) {
            Log::error('Error al entregar moto', ['unit' => $unit->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al entregar: ' . $e->getMessage()]);
        }
    }

    private function authorizeUnit(MotoUnit $unit): void
    {
        if (!auth()->user()->is_super_admin && $unit->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
