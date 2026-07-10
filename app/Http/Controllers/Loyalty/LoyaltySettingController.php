<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltySetting;
use Illuminate\Http\Request;

class LoyaltySettingController extends Controller
{
    public function edit()
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        abort_unless($cid, 403, 'Selecciona una empresa.');

        $settings = LoyaltySetting::forCompany($cid);

        return view('loyalty.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        abort_unless($cid, 403, 'Selecciona una empresa.');

        $data = $request->validate([
            'enabled'      => 'nullable|boolean',
            'earn_amount'  => 'required|numeric|min:0.01',
            'earn_points'  => 'required|integer|min:1',
            'rounding'     => 'required|in:down,nearest,up',
            'min_purchase' => 'nullable|numeric|min:0',
            'points_label' => 'nullable|string|max:50',
            'expiration_months' => 'nullable|integer|min:0|max:120',
        ]);

        $settings = LoyaltySetting::forCompany($cid);
        $settings->update([
            'enabled'           => (bool) $request->boolean('enabled'),
            'earn_amount'       => $data['earn_amount'],
            'earn_points'       => $data['earn_points'],
            'rounding'          => $data['rounding'],
            'min_purchase'      => $data['min_purchase'] ?? 0,
            'points_label'      => $data['points_label'] ?: 'puntos',
            'expiration_months' => $data['expiration_months'] ?: null,
        ]);

        return back()->with('success', 'Configuración de fidelización guardada.');
    }
}
