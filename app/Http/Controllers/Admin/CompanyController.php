<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Services\Admin\CompanyUsageService;
use App\Support\MotoBrandDefaults;
use App\Support\ProductOriginDefaults;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct(private CompanyUsageService $usage)
    {
        $this->middleware('check-role:super_admin');
    }

    public function index()
    {
        $companies = Company::with('subscription.plan')->paginate(15);

        // Última actividad de las empresas de esta página (consultas agregadas).
        $lastActivity = $this->usage->lastActivityFor($companies->pluck('id')->all());

        return view('admin.companies.index', compact('companies', 'lastActivity'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(StoreCompanyRequest $request)
    {
        $data = $request->validated();
        unset($data['logo']);   // se guarda tras crear, para poder usar el id en la ruta

        $company = Company::create($data);

        if ($request->hasFile('logo')) {
            $company->update(['logo' => $this->storeLogo($request, $company)]);
        }

        // Onboarding: catálogos base para arrancar de inmediato.
        MotoBrandDefaults::seedFor($company->id);
        ProductOriginDefaults::seedFor($company->id);

        return redirect()->route('companies.index')->with('success', 'Empresa creada exitosamente');
    }

    public function show(Company $company)
    {
        $company->load('subscription.plan');

        // `last_seen_at` llega con el script 20260911_company_user_last_seen.sql;
        // hasta entonces la columna no existe y no se pide al pivot.
        $hasSeen = \Illuminate\Support\Facades\Schema::hasColumn('company_user', 'last_seen_at');
        $users   = $company->users()
            ->when($hasSeen, fn ($q) => $q->withPivot('last_seen_at'))
            ->paginate(10);

        $usage = $this->usage->dashboardFor($company);

        return view('admin.companies.show', compact('company', 'users', 'usage'));
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(StoreCompanyRequest $request, Company $company)
    {
        $data = $request->validated();
        unset($data['logo']);

        if ($request->hasFile('logo')) {
            // Reemplaza el logo anterior para no dejar archivos huérfanos.
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $this->storeLogo($request, $company);
        }

        $company->update($data);

        return redirect()->route('companies.show', $company)->with('success', 'Empresa actualizada exitosamente');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Empresa eliminada exitosamente');
    }

    /** Guarda el logo en el disco público, segmentado por empresa. */
    private function storeLogo(StoreCompanyRequest $request, Company $company): string
    {
        return $request->file('logo')->store("company/{$company->id}/branding", 'public');
    }
}
