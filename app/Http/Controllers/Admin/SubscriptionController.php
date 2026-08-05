<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel del operador de la plataforma (super_admin): activación MANUAL de las
 * suscripciones. Aquí se asigna el plan a cada empresa-cliente y se activa,
 * renueva, suspende o cancela su acceso. No hay pasarela de pago: el cobro se
 * gestiona fuera y aquí se refleja.
 */
class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-role:super_admin');
    }

    /** Listado de empresas con el estado de su suscripción y su uso vs. límites. */
    public function index()
    {
        $companies = Company::with('subscription.plan')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.subscriptions.index', compact('companies'));
    }

    /** Formulario para asignar/editar la suscripción de una empresa. */
    public function edit(Company $company)
    {
        $company->load('subscription.plan');
        $plans = Plan::where('active', true)->orderBy('price')->get();

        return view('admin.subscriptions.edit', compact('company', 'plans'));
    }

    /** Crea o actualiza la suscripción de la empresa. */
    public function update(Request $request, Company $company)
    {
        $validated = $request->validate([
            'plan_id'            => ['required', 'exists:plans,id'],
            'status'             => ['required', Rule::in(array_keys(Subscription::STATUSES))],
            'trial_ends_at'      => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date'],
            'grace_days'         => ['required', 'integer', 'min:0', 'max:60'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);

        $subscription = $company->subscription;

        // Alta: si arranca en prueba y no se indicó fin, se toma del plan.
        if (!$subscription && $validated['status'] === 'trial' && empty($validated['trial_ends_at'])) {
            $plan = Plan::find($validated['plan_id']);
            $validated['trial_ends_at'] = now()->addDays($plan->trial_days ?: 15);
        }

        $validated['created_by'] = auth()->id();

        if ($subscription) {
            $subscription->update($validated);
        } else {
            $company->subscription()->create($validated);
        }

        return redirect()->route('subscriptions.index')
            ->with('success', "Suscripción de «{$company->name}» actualizada.");
    }

    /** Activa o renueva: extiende el periodo pagado N meses. */
    public function renew(Request $request, Company $company)
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:36'],
        ]);

        $subscription = $company->subscription;

        if (!$subscription) {
            return back()->withErrors(['error' => 'La empresa aún no tiene un plan asignado.']);
        }

        $subscription->renew($validated['months']);

        return back()->with('success', sprintf(
            'Suscripción de «%s» renovada hasta el %s.',
            $company->name,
            $subscription->current_period_end->format('d/m/Y')
        ));
    }

    public function suspend(Company $company)
    {
        $company->subscription?->suspend();

        return back()->with('success', "Se suspendió el acceso de «{$company->name}».");
    }

    public function cancel(Company $company)
    {
        $company->subscription?->cancel();

        return back()->with('success', "Se canceló la suscripción de «{$company->name}».");
    }
}
