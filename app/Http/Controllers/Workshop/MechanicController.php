<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\Mechanic;
use Illuminate\Support\Facades\Log;

class MechanicController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Mechanic::withCount('workOrders')->latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        return view('workshop.mechanics.index', ['mechanics' => $query->paginate(20)]);
    }

    public function create()
    {
        return view('workshop.mechanics.create');
    }

    /** Alta rápida (AJAX) desde otros formularios, ej. recepción de taller. */
    public function quickStore()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return response()->json(['ok' => false, 'message' => 'No hay una empresa activa.'], 422);
        }

        $validated = request()->validate([
            'name'      => 'required|string|max:255',
            'specialty' => 'nullable|string|max:150',
            'phone'     => 'nullable|string|max:30',
        ]);

        try {
            $mechanic = Mechanic::create([
                'company_id' => $companyId,
                'name'       => $validated['name'],
                'specialty'  => $validated['specialty'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'active'     => true,
            ]);

            return response()->json([
                'ok'       => true,
                'mechanic' => [
                    'id'        => $mechanic->id,
                    'name'      => $mechanic->name,
                    'specialty' => $mechanic->specialty,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Error en alta rápida de mecánico', ['msg' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Error al guardar el mecánico.'], 500);
        }
    }

    public function store()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateMechanic();

        try {
            Mechanic::create([...$validated, 'company_id' => $companyId, 'active' => request()->boolean('active', true)]);
            return redirect()->route('mechanics.index')->with('success', 'Mecánico registrado.');
        } catch (\Throwable $e) {
            Log::error('Error al crear mecánico', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit(Mechanic $mechanic)
    {
        $this->authorizeMechanic($mechanic);
        return view('workshop.mechanics.edit', compact('mechanic'));
    }

    public function update(Mechanic $mechanic)
    {
        $this->authorizeMechanic($mechanic);
        $validated = $this->validateMechanic();
        try {
            $mechanic->update([...$validated, 'active' => request()->boolean('active', false)]);
            return redirect()->route('mechanics.index')->with('success', 'Mecánico actualizado.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(Mechanic $mechanic)
    {
        $this->authorizeMechanic($mechanic);
        try {
            $mechanic->delete();
            return redirect()->route('mechanics.index')->with('success', 'Mecánico eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function validateMechanic(): array
    {
        return request()->validate([
            'name'            => 'required|string|max:255',
            'specialty'       => 'nullable|string|max:150',
            'phone'           => 'nullable|string|max:30',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'active'          => 'sometimes|boolean',
        ]);
    }

    private function authorizeMechanic(Mechanic $mechanic): void
    {
        if (!auth()->user()->is_super_admin && $mechanic->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
