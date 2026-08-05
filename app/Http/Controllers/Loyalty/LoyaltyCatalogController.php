<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltySetting;
use App\Support\Tenancy;

class LoyaltyCatalogController extends Controller
{
    /** Página pública del catálogo de recompensas (sin autenticación). */
    public function public(string $token)
    {
        $tenancy = app(Tenancy::class);

        // El token es único global: se busca sin filtro de empresa (contexto público).
        $settings = $tenancy->runAs(null, fn () =>
            LoyaltySetting::where('public_token', $token)->with('company')->first()
        );
        abort_if(!$settings || !$settings->enabled, 404);

        // A partir de aquí fijamos explícitamente el tenant dueño del catálogo,
        // de modo que el global scope aísle correctamente aunque no haya sesión.
        return $tenancy->runAs($settings->company_id, function () use ($settings) {
            $rewards = LoyaltyReward::where('active', true)
                ->orderBy('points_cost')
                ->get();

            return view('loyalty.catalog', [
                'company'   => $settings->company,
                'settings'  => $settings,
                'rewards'   => $rewards,
                'autoPrint' => request()->boolean('print'),
            ]);
        });
    }
}
