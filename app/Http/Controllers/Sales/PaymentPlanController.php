<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\PaymentPlan;
use Illuminate\Support\Facades\Log;

class PaymentPlanController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = PaymentPlan::latest();
        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        return view('sales.credit.plans.index', ['plans' => $query->paginate(20)]);
    }

    public function create()
    {
        return view('sales.credit.plans.create');
    }

    public function store()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validatePlan();

        try {
            PaymentPlan::create([...$validated, 'company_id' => $companyId, 'active' => request()->boolean('active', true)]);
            return redirect()->route('payment-plans.index')->with('success', 'Plan de pago creado.');
        } catch (\Throwable $e) {
            Log::error('Error al crear plan de pago', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit(PaymentPlan $plan)
    {
        $this->authorizePlan($plan);
        return view('sales.credit.plans.edit', compact('plan'));
    }

    public function update(PaymentPlan $plan)
    {
        $this->authorizePlan($plan);
        $validated = $this->validatePlan();
        try {
            $plan->update([...$validated, 'active' => request()->boolean('active', false)]);
            return redirect()->route('payment-plans.index')->with('success', 'Plan actualizado.');
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(PaymentPlan $plan)
    {
        $this->authorizePlan($plan);
        try {
            $plan->delete();
            return redirect()->route('payment-plans.index')->with('success', 'Plan eliminado.');
        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'No se puede eliminar: ' . $e->getMessage()]);
        }
    }

    private function validatePlan(): array
    {
        return request()->validate([
            'name'                   => 'required|string|max:255',
            'number_of_installments' => 'required|integer|min:1|max:120',
            'frequency_days'         => 'required|integer|min:1|max:365',
            'interest_rate'          => 'required|numeric|min:0|max:100',
            'active'                 => 'sometimes|boolean',
        ]);
    }

    private function authorizePlan(PaymentPlan $plan): void
    {
        if (!auth()->user()->is_super_admin && $plan->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
