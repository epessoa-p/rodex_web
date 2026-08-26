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
            'phone'     => ['nullable', 'string', 'max:30'],
            'email'     => ['nullable', 'email', 'max:255'],
            'address'   => ['nullable', 'string', 'max:255'],
        ]);

        $client = Client::create($data + [
            'active'     => true,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $this->payload($client)], 201);
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
