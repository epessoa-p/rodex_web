<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\Workshop\Mechanic;
use Illuminate\Http\Request;

/** Catálogos de apoyo para el taller: mecánicos y vehículos del cliente. */
class WorkshopMetaController extends Controller
{
    public function mechanics()
    {
        $mechanics = Mechanic::where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (Mechanic $m) => ['id' => $m->id, 'name' => $m->name]);

        return response()->json(['data' => $mechanics]);
    }

    /** Vehículos de un cliente (para elegir en la recepción). */
    public function vehicles(Request $request)
    {
        $clientId = (int) $request->query('client_id');

        $vehicles = Vehicle::where('active', true)
            ->when($clientId > 0, fn ($q) => $q->where('client_id', $clientId))
            ->orderBy('brand')
            ->limit(50)
            ->get()
            ->map(fn (Vehicle $v) => [
                'id'    => $v->id,
                'label' => $v->display_name,
                'plate' => $v->plate,
            ]);

        return response()->json(['data' => $vehicles]);
    }
}
