<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;
use App\Models\Plan;
use App\Services\Admin\CompanyOnboardingService;
use App\Services\Admin\CompanyUsageService;
use App\Services\Reports\IncomeStatementService;
use App\Support\MotoBrandDefaults;
use App\Support\ProductOriginDefaults;
use App\Support\Tenancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function __construct(
        private CompanyUsageService $usage,
        private IncomeStatementService $incomeStatement,
        private CompanyOnboardingService $onboarding,
    ) {
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
        return view('admin.companies.create', [
            'plans' => Plan::where('active', true)->ordered()->get(),
        ]);
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

        // Alta "lista para usar": plan, sucursal (+ almacén), cargo, personal
        // con usuario y caja — lo que se haya llenado en el formulario.
        $summary = $this->onboarding->run($company, $request->validated(), auth()->id());

        return redirect()->route('companies.show', $company)
            ->with('success', 'Empresa creada exitosamente')
            ->with('onboarding', $summary);
    }

    public function show(Company $company)
    {
        $company->load('subscription.plan');

        // `last_seen_at` llega con el script 20260911_company_user_last_seen.sql;
        // hasta entonces la columna no existe y no se pide al pivot.
        // withPivot() es de la relación (BelongsToMany), no del query builder:
        // debe llamarse ANTES de encadenar métodos de consulta como when()/paginate().
        $relation = $company->users();
        if (\Illuminate\Support\Facades\Schema::hasColumn('company_user', 'last_seen_at')) {
            $relation->withPivot('last_seen_at');
        }
        $users = $relation->paginate(10);

        $usage   = $this->usage->dashboardFor($company);
        $balance = $this->balanceFor($company);

        return view('admin.companies.show', compact('company', 'users', 'usage', 'balance'));
    }

    /**
     * Balance (ingresos, egresos, resultado) de la empresa por período: semana
     * actual y anterior, mes actual y anterior. Reutiliza el Estado de
     * resultados (movimientos reales de caja + tesorería).
     *
     * IMPORTANTE: los modelos de movimientos llevan el scope de empresa, y el
     * super_admin no tiene empresa activa (sin scope se mezclarían TODAS las
     * empresas). Por eso se ejecuta con Tenancy::runAs() forzando esta empresa.
     */
    private function balanceFor(Company $company): array
    {
        $today = Carbon::today();
        $monday = $today->copy()->startOfWeek(Carbon::MONDAY);

        $periods = [
            'this_week'  => ['label' => 'Esta semana',     'from' => $monday,                                'to' => $today],
            'last_week'  => ['label' => 'Semana anterior', 'from' => $monday->copy()->subWeek(),             'to' => $monday->copy()->subDay()],
            'this_month' => ['label' => 'Este mes',        'from' => $today->copy()->startOfMonth(),         'to' => $today],
            'last_month' => ['label' => 'Mes anterior',    'from' => $today->copy()->subMonthNoOverflow()->startOfMonth(), 'to' => $today->copy()->startOfMonth()->subDay()],
        ];

        try {
            return app(Tenancy::class)->runAs($company->id, function () use ($periods) {
                $out = [];
                foreach ($periods as $key => $p) {
                    $r = $this->incomeStatement->build($p['from'], $p['to']);
                    $out[$key] = [
                        'label'   => $p['label'],
                        'from'    => $p['from'],
                        'to'      => $p['to'],
                        'income'  => $r['total_income'],
                        'expense' => $r['total_expense'],
                        'net'     => $r['net'],
                    ];
                }
                return $out;
            });
        } catch (\Throwable $e) {
            // El panel del operador no debe caerse por un módulo sin instalar.
            return [];
        }
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
