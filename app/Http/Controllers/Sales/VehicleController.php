<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

class VehicleController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Vehicle::with('client')->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        if ($q = request('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('brand', 'like', "%{$q}%")
                    ->orWhere('model', 'like', "%{$q}%")
                    ->orWhere('plate', 'like', "%{$q}%");
            });
        }
        if ($clientId = request('client_id')) {
            $query->where('client_id', $clientId);
        }

        return view('sales.vehicles.index', ['vehicles' => $query->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('sales.vehicles.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateVehicle();

        try {
            Vehicle::create([...$validated, 'company_id' => $companyId, 'active' => request()->boolean('active', true)]);
            return redirect()->route('vehicles.index')->with('success', 'Vehículo registrado.');
        } catch (\Throwable $e) {
            Log::error('Error al crear vehículo', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);
        $vehicle->load('client');
        return view('sales.vehicles.show', compact('vehicle'));
    }

    public function edit(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);
        return view('sales.vehicles.edit', array_merge($this->formData(), compact('vehicle')));
    }

    public function update(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);
        $validated = $this->validateVehicle();

        try {
            $vehicle->update([...$validated, 'active' => request()->boolean('active', false)]);
            return redirect()->route('vehicles.index')->with('success', 'Vehículo actualizado.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(Vehicle $vehicle)
    {
        $this->authorizeVehicle($vehicle);
        try {
            $vehicle->delete();
            return redirect()->route('vehicles.index')->with('success', 'Vehículo eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function validateVehicle(): array
    {
        return request()->validate([
            'client_id' => 'required|exists:clients,id',
            'brand'     => 'required|string|max:100',
            'model'     => 'nullable|string|max:100',
            'engine_cc' => 'nullable|string|max:30',
            'year'      => 'nullable|integer|min:1900|max:2100',
            'plate'     => 'nullable|string|max:20',
            'color'     => 'nullable|string|max:40',
            'vin'       => 'nullable|string|max:60',
            'notes'     => 'nullable|string',
            'active'    => 'sometimes|boolean',
        ]);
    }

    private function authorizeVehicle(Vehicle $vehicle): void
    {
        if (!auth()->user()->is_super_admin && $vehicle->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('full_name')->get();
        return compact('clients');
    }
}
