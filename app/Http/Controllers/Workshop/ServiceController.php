<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\Service;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Service::latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        return view('workshop.services.index', ['services' => $query->paginate(20)]);
    }

    public function create()
    {
        return view('workshop.services.create');
    }

    public function store()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateService();

        try {
            Service::create([...$validated, 'company_id' => $companyId, 'active' => request()->boolean('active', true)]);
            return redirect()->route('services.index')->with('success', 'Servicio creado.');
        } catch (\Throwable $e) {
            Log::error('Error al crear servicio', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit(Service $service)
    {
        $this->authorizeService($service);
        return view('workshop.services.edit', compact('service'));
    }

    public function update(Service $service)
    {
        $this->authorizeService($service);
        $validated = $this->validateService();
        try {
            $service->update([...$validated, 'active' => request()->boolean('active', false)]);
            return redirect()->route('services.index')->with('success', 'Servicio actualizado.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(Service $service)
    {
        $this->authorizeService($service);
        try {
            $service->delete();
            return redirect()->route('services.index')->with('success', 'Servicio eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function validateService(): array
    {
        return request()->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string|max:500',
            'price'          => 'required|numeric|min:0',
            'estimated_time' => 'nullable|string|max:50',
            'active'         => 'sometimes|boolean',
        ]);
    }

    private function authorizeService(Service $service): void
    {
        if (!auth()->user()->is_super_admin && $service->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
