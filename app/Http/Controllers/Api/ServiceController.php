<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workshop\Service;
use Illuminate\Http\Request;

/**
 * Catálogo de servicios del taller desde el móvil (alta rápida desde la
 * cita). Aislamiento por empresa vía global scope.
 */
class ServiceController extends Controller
{
    /**
     * Listado completo del catálogo (activos e inactivos) para gestionarlo
     * desde el móvil. ?q= filtra por nombre.
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $services = Service::query()
            ->when($q !== '', fn ($w) => $w->where('name', 'like', "%{$q}%"))
            ->orderByDesc('active')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $s) => $this->payload($s));

        return response()->json(['data' => $services]);
    }

    /** Edición desde el móvil (nombre, precio, descripción, tiempo, activo). */
    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'price'          => ['required', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string', 'max:500'],
            'estimated_time' => ['nullable', 'string', 'max:50'],
            'active'         => ['nullable', 'boolean'],
        ]);

        $name = trim($data['name']);
        $dup = Service::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('id', '!=', $service->id)
            ->exists();
        if ($dup) {
            return response()->json([
                'message' => 'Ya existe otro servicio con ese nombre.',
                'code'    => 'service_name_taken',
            ], 422);
        }

        $service->update([
            'name'           => $name,
            'price'          => $data['price'],
            'description'    => $data['description'] ?? null,
            'estimated_time' => $data['estimated_time'] ?? null,
            'active'         => $request->boolean('active', true),
        ]);

        return response()->json(['data' => $this->payload($service->fresh())]);
    }

    private function payload(Service $s): array
    {
        return [
            'id'             => $s->id,
            'name'           => $s->name,
            'price'          => (float) $s->price,
            'description'    => $s->description,
            'estimated_time' => $s->estimated_time,
            'active'         => (bool) $s->active,
        ];
    }

    /**
     * Crea un servicio; si ya existe uno con el mismo nombre en la empresa
     * (sin distinguir mayúsculas) lo reutiliza y lo reactiva, igual que
     * WorkOrderController::addService — así no se duplica el catálogo.
     */
    public function store(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'price'          => ['required', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string', 'max:500'],
            'estimated_time' => ['nullable', 'string', 'max:50'],
        ]);

        $name = trim($data['name']);

        $service = Service::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        if ($service) {
            if (! $service->active) {
                $service->update(['active' => true]);
            }
            $created = false;
        } else {
            $service = Service::create([
                'company_id'     => $company->id,
                'name'           => $name,
                'price'          => $data['price'],
                'description'    => $data['description'] ?? null,
                'estimated_time' => $data['estimated_time'] ?? null,
                'active'         => true,
            ]);
            $created = true;
        }

        return response()->json([
            'data' => [
                'id'      => $service->id,
                'name'    => $service->name,
                'price'   => (float) $service->price,
                'created' => $created,
            ],
        ], $created ? 201 : 200);
    }
}
