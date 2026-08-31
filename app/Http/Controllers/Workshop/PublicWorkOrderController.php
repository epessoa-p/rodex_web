<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Models\Workshop\WorkOrder;
use App\Support\Tenancy;

/**
 * Seguimiento público de una orden de trabajo (SIN autenticación).
 * El cliente entra con el enlace /ot/{token}. Una sola vista de solo lectura.
 */
class PublicWorkOrderController extends Controller
{
    public function show(string $token)
    {
        $tenancy = app(Tenancy::class);

        // El token es único global: se busca sin filtro de empresa.
        $order = $tenancy->runAs(null, fn () =>
            WorkOrder::where('public_token', $token)->with('company')->first()
        );
        abort_if(! $order || $order->status === 'anulada', 404);

        // Fija el tenant dueño para que el global scope aísle correctamente.
        return $tenancy->runAs($order->company_id, function () use ($order) {
            $order->load(['vehicle', 'mechanic', 'services', 'parts.product']);

            return view('workshop.public-track', [
                'company' => $order->company,
                'order'   => $order,
            ]);
        });
    }
}
