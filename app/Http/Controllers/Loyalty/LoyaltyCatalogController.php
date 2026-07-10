<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltySetting;

class LoyaltyCatalogController extends Controller
{
    /** Página pública del catálogo de recompensas (sin autenticación). */
    public function public(string $token)
    {
        $settings = LoyaltySetting::where('public_token', $token)->with('company')->first();
        abort_if(!$settings || !$settings->enabled, 404);

        $rewards = LoyaltyReward::where('company_id', $settings->company_id)
            ->where('active', true)
            ->orderBy('points_cost')
            ->get();

        return view('loyalty.catalog', [
            'company'  => $settings->company,
            'settings' => $settings,
            'rewards'  => $rewards,
            'autoPrint' => request()->boolean('print'),
        ]);
    }
}
