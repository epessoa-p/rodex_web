<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * "Mi empresa" para el móvil: la empresa activa consulta y edita sus datos
 * (teléfono, dirección, foto/logo y vigencia del enlace de seguimiento).
 */
class CompanyProfileController extends Controller
{
    public function show(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        return response()->json(['data' => $this->payload($company)]);
    }

    public function update(Request $request)
    {
        $company = $request->attributes->get('tenant_company');

        $data = $request->validate([
            'phone'              => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string', 'max:500'],
            'tracking_link_days' => ['required', 'integer', 'min:0', 'max:365'],
            'dashboard_order'    => ['nullable', 'string', 'max:60'],
            'logo'               => ['nullable', 'image', 'max:4096'],
        ]);

        $update = [
            'phone'              => $data['phone'] ?? null,
            'address'            => $data['address'] ?? null,
            'tracking_link_days' => $data['tracking_link_days'],
            'dashboard_order'    => \App\Support\DashboardOrder::sanitize($data['dashboard_order'] ?? null),
        ];

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $update['logo'] = $request->file('logo')->store("company/{$company->id}/branding", 'public');
        }

        $company->update($update);

        return response()->json(['data' => $this->payload($company->fresh())]);
    }

    private function payload(Company $c): array
    {
        return [
            'id'                 => $c->id,
            'name'               => $c->name,
            'phone'              => $c->phone,
            'address'            => $c->address,
            'logo_url'           => $c->logo_url,
            'tracking_link_days' => (int) ($c->tracking_link_days ?? 1),
            'dashboard_order'    => $c->dashboard_order ?: 'ventas,taller,compras',
        ];
    }
}
