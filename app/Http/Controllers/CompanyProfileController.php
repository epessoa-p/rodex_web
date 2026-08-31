<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * "Mi empresa": la empresa activa edita sus propios datos (teléfono, dirección,
 * foto/logo y vigencia del enlace de seguimiento). Gateado por permiso.
 */
class CompanyProfileController extends Controller
{
    public function edit()
    {
        $company = auth()->user()->getCurrentCompany();
        abort_unless($company, 404);

        return view('company.profile', compact('company'));
    }

    public function update(Request $request)
    {
        $company = auth()->user()->getCurrentCompany();
        abort_unless($company, 404);

        $data = $request->validate([
            'phone'              => ['nullable', 'string', 'max:30'],
            'address'            => ['nullable', 'string', 'max:500'],
            'tracking_link_days' => ['required', 'integer', 'min:0', 'max:365'],
            'logo'               => ['nullable', 'image', 'max:4096'],
        ]);

        $update = [
            'phone'              => $data['phone'] ?? null,
            'address'            => $data['address'] ?? null,
            'tracking_link_days' => $data['tracking_link_days'],
        ];

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $update['logo'] = $request->file('logo')->store("company/{$company->id}/branding", 'public');
        }

        $company->update($update);

        return back()->with('success', 'Datos de tu empresa actualizados.');
    }
}
