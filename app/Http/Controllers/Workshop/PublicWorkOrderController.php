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

        // Caducidad: el enlace deja de servir N días después de entregada.
        if ($order->status === 'entregada' && $order->delivered_at) {
            $days = (int) ($order->company?->tracking_link_days ?? 1);
            if ($days > 0 && $order->delivered_at->copy()->addDays($days)->isPast()) {
                return response()->view('workshop.public-track-expired', [
                    'company' => $order->company,
                ], 410);
            }
        }

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
