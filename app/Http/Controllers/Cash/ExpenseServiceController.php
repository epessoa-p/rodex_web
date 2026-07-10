<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Models\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseServiceController extends Controller
{
    private function companyId(): ?int
    {
        return auth()->user()->getCurrentCompany()?->id;
    }

    public function index()
    {
        $cid = auth()->user()->is_super_admin ? null : $this->companyId();
        $services = ExpenseService::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('name')->paginate(20);

        return view('cash.expense-services.index', compact('services'));
    }

    public function create()
    {
        return view('cash.expense-services.form', ['service' => new ExpenseService(['active' => true, 'type' => 'basico'])]);
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateData($request);
        ExpenseService::create($validated + [
            'company_id' => $companyId,
            'active'     => $request->boolean('active', true),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('expense-services.index')->with('success', 'Servicio creado.');
    }

    public function edit(ExpenseService $expenseService)
    {
        $this->authorizeService($expenseService);
        return view('cash.expense-services.form', ['service' => $expenseService]);
    }

    public function update(Request $request, ExpenseService $expenseService)
    {
        $this->authorizeService($expenseService);
        $validated = $this->validateData($request);
        $expenseService->update($validated + ['active' => $request->boolean('active', false)]);

        return redirect()->route('expense-services.index')->with('success', 'Servicio actualizado.');
    }

    public function destroy(ExpenseService $expenseService)
    {
        $this->authorizeService($expenseService);
        $expenseService->delete();

        return back()->with('success', 'Servicio eliminado.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'           => 'required|string|max:150',
            'type'           => ['required', Rule::in(array_keys(ExpenseService::TYPES))],
            'default_amount' => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);
    }

    private function authorizeService(ExpenseService $service): void
    {
        if (!auth()->user()->is_super_admin && $service->company_id !== $this->companyId()) {
            abort(403);
        }
    }
}
