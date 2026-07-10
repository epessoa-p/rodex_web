<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Sales\CreditApplication;
use App\Models\Sales\PaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditApplicationController extends Controller
{
    public function index(Request $request)
    {
        $cid = $this->companyScope();
        $query = CreditApplication::with(['client', 'paymentPlan'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return view('sales.credit.applications.index', ['applications' => $query->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('sales.credit.applications.create', $this->formData());
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateApplication($request);

        try {
            CreditApplication::create([
                ...$validated,
                'company_id' => $companyId,
                'code'       => $this->nextCode($companyId),
                'status'     => 'pendiente',
                'created_by' => auth()->id(),
            ]);
            return redirect()->route('credit-applications.index')->with('success', 'Solicitud registrada.');
        } catch (\Throwable $e) {
            Log::error('Error al crear solicitud', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(CreditApplication $application)
    {
        $this->authorizeApp($application);
        $application->load(['client', 'paymentPlan', 'evaluatedBy', 'convertedSale', 'createdBy']);
        return view('sales.credit.applications.show', ['application' => $application]);
    }

    public function edit(CreditApplication $application)
    {
        $this->authorizeApp($application);
        if ($application->status !== 'pendiente') {
            return back()->withErrors(['error' => 'Solo se pueden editar solicitudes pendientes.']);
        }
        return view('sales.credit.applications.edit', array_merge($this->formData(), ['application' => $application]));
    }

    public function update(Request $request, CreditApplication $application)
    {
        $this->authorizeApp($application);
        if ($application->status !== 'pendiente') {
            return back()->withErrors(['error' => 'Solo se pueden editar solicitudes pendientes.']);
        }
        $validated = $this->validateApplication($request);
        $application->update($validated);
        return redirect()->route('credit-applications.show', $application)->with('success', 'Solicitud actualizada.');
    }

    public function approve(Request $request, CreditApplication $application)
    {
        $this->authorizeApp($application);
        if ($application->status !== 'pendiente') {
            return back()->withErrors(['error' => 'La solicitud ya fue evaluada.']);
        }
        $validated = $request->validate([
            'approved_amount'  => 'required|numeric|min:0.01',
            'evaluation_notes' => 'nullable|string',
        ]);
        $application->update([
            'status'           => 'aprobada',
            'approved_amount'  => $validated['approved_amount'],
            'evaluation_notes' => $validated['evaluation_notes'] ?? null,
            'evaluated_by'     => auth()->id(),
        ]);
        return back()->with('success', 'Solicitud aprobada.');
    }

    public function reject(Request $request, CreditApplication $application)
    {
        $this->authorizeApp($application);
        if ($application->status !== 'pendiente') {
            return back()->withErrors(['error' => 'La solicitud ya fue evaluada.']);
        }
        $validated = $request->validate(['evaluation_notes' => 'nullable|string']);
        $application->update([
            'status'           => 'rechazada',
            'evaluation_notes' => $validated['evaluation_notes'] ?? null,
            'evaluated_by'     => auth()->id(),
        ]);
        return back()->with('success', 'Solicitud rechazada.');
    }

    public function convert(CreditApplication $application)
    {
        $this->authorizeApp($application);
        if (!$application->isConvertible()) {
            return back()->withErrors(['error' => 'Solo se pueden convertir solicitudes aprobadas.']);
        }
        return redirect()->route('sales.create', ['application_id' => $application->id]);
    }

    public function destroy(CreditApplication $application)
    {
        $this->authorizeApp($application);
        if ($application->status === 'convertida') {
            return back()->withErrors(['error' => 'No se puede eliminar una solicitud convertida.']);
        }
        $application->delete();
        return redirect()->route('credit-applications.index')->with('success', 'Solicitud eliminada.');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function validateApplication(Request $request): array
    {
        return $request->validate([
            'client_id'          => 'required|exists:clients,id',
            'requested_amount'   => 'required|numeric|min:0.01',
            'down_payment'       => 'nullable|numeric|min:0',
            'payment_plan_id'    => 'nullable|exists:payment_plans,id',
            'installments_count' => 'nullable|integer|min:1',
            'frequency_days'     => 'nullable|integer|min:1',
            'guarantor_name'     => 'nullable|string|max:255',
            'guarantor_phone'    => 'nullable|string|max:30',
            'guarantor_notes'    => 'nullable|string',
            'notes'              => 'nullable|string',
        ]);
    }

    private function nextCode(int $companyId): string
    {
        $count = CreditApplication::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'SOL-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeApp(CreditApplication $application): void
    {
        if (!auth()->user()->is_super_admin && $application->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }

    private function formData(): array
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('full_name')->get();
        $plans   = PaymentPlan::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        return compact('clients', 'plans');
    }
}
