<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /** Listado/búsqueda de clientes de la empresa activa. */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $clients = Client::query()
            ->where('active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('full_name', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('id_number', 'like', "%{$q}%");
                });
            })
            ->orderBy('full_name')
            ->limit(50)
            ->get()
            ->map(fn (Client $c) => $this->payload($c));

        return response()->json(['data' => $clients]);
    }

    /** Alta rápida de cliente desde el móvil. company_id lo asigna el trait. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            // Alta rápida desde el móvil: el teléfono es obligatorio (para
            // WhatsApp/llamada desde citas y OTs). La web sigue sin exigirlo.
            'phone'     => ['required', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:255'],
        ]);

        $client = Client::create($data + [
            'active'     => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $this->payload($client)], 201);
    }

    /**
     * Ficha del cliente para el móvil: datos + actividad por tabs (ventas, OTs,
     * vehículos, citas, alquileres), cada tab solo si el plan y el permiso lo
     * permiten (null = no se muestra). Máximo 50 filas por tab.
     */
    public function show(Request $request, Client $client)
    {
        $user    = $request->user();
        $company = $request->attributes->get('tenant_company');
        $can = fn (string $module, string $perm) => $company
            && $company->planAllows($module)
            && ($user->is_super_admin || $user->hasPermissionInCompany($perm, $company));

        $client->loadCount(['sales', 'workOrders', 'vehicles', 'appointments', 'rentalContracts']);

        $sales = $can('sales', 'sales.view') ? $client->sales()->latest('sale_date')->limit(50)->get()
            ->map(fn ($s) => [
                'id' => $s->id, 'code' => $s->code, 'date' => optional($s->sale_date)->toDateString(),
                'type' => $s->sale_type_label, 'total' => (float) $s->total,
                'payment_status' => $s->payment_status_label,
            ])->values() : null;

        $orders = $can('workshop', 'workshop.view') ? $client->workOrders()->with('vehicle')->latest('reception_date')->limit(50)->get()
            ->map(fn ($o) => [
                'id' => $o->id, 'code' => $o->code, 'date' => optional($o->reception_date)->toDateString(),
                'status' => $o->status, 'status_label' => $o->status_label,
                'vehicle' => $o->vehicle?->display_name, 'total' => (float) $o->total,
                'balance' => (float) $o->balance, 'payment_status' => $o->payment_status_label,
            ])->values() : null;

        $vehicles = $can('workshop', 'workshop.view') ? $client->vehicles()->orderBy('brand')->get()
            ->map(fn ($v) => [
                'id' => $v->id, 'label' => trim($v->brand . ' ' . $v->model), 'plate' => $v->plate,
                'year' => $v->year, 'color' => $v->color,
            ])->values() : null;

        $appointments = $can('workshop', 'appointments.view') ? $client->appointments()->with(['services', 'workOrder'])->limit(50)->get()
            ->map(fn ($a) => [
                'id' => $a->id, 'date' => optional($a->scheduled_at)->toDateString(),
                'time' => optional($a->scheduled_at)->format('H:i'),
                'status' => $a->status, 'status_label' => $a->status_label,
                'services' => $a->services->pluck('name')->implode(', ') ?: ($a->title ?? ''),
                'work_order_code' => $a->workOrder?->code,
            ])->values() : null;

        $rentals = $can('rentals', 'rentals.view') ? $client->rentalContracts()->with('motoUnit.model.brand')->latest('start_date')->limit(50)->get()
            ->map(fn ($r) => [
                'id' => $r->id, 'code' => $r->code, 'date' => optional($r->start_date)->toDateString(),
                'status' => $r->status, 'status_label' => $r->status_label,
                'moto' => $r->motoUnit?->display_name, 'total' => (float) $r->total,
                'payment_status' => $r->payment_status_label,
            ])->values() : null;

        return response()->json(['data' => $this->payload($client) + [
            'notes'         => $client->notes,
            'active'        => (bool) $client->active,
            'photo_url'     => $client->photo_url,
            'created_at'    => optional($client->created_at)->toDateString(),
            'counts'        => [
                'sales' => $client->sales_count, 'work_orders' => $client->work_orders_count,
                'vehicles' => $client->vehicles_count, 'appointments' => $client->appointments_count,
                'rentals' => $client->rental_contracts_count,
            ],
            'sales'         => $sales,
            'work_orders'   => $orders,
            'vehicles'      => $vehicles,
            'appointments'  => $appointments,
            'rentals'       => $rentals,
        ]]);
    }

    /** Edición desde el móvil (mismos campos que la web, sin documentos/foto). */
    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'phone'     => ['required', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:500'],
            'notes'     => ['nullable', 'string', 'max:2000'],
            'active'    => ['nullable', 'boolean'],
        ]);

        $client->update([
            ...$data,
            'full_name' => trim($data['full_name']),
            'active'    => $request->boolean('active', true),
        ]);

        return response()->json(['data' => $this->payload($client->fresh()) + [
            'notes'  => $client->notes,
            'active' => (bool) $client->active,
        ]]);
    }

    private function payload(Client $c): array
    {
        return [
            'id'        => $c->id,
            'full_name' => $c->full_name,
            'id_number' => $c->id_number,
            'phone'     => $c->phone,
            'email'     => $c->email,
            'address'   => $c->address,
        ];
    }
}
