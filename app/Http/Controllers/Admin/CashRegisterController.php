<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Company;
use App\Models\Personal;
use Illuminate\Support\Facades\Log;

class CashRegisterController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = CashRegister::with(['branch', 'assignedPersonal'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        $registers = $query->paginate(15);

        $registers->getCollection()->each(function (CashRegister $register) {
            $register->setRelation('activeSession', $register->activeSession());
        });

        return view('admin.cash-registers.index', compact('registers'));
    }

    public function create()
    {
        return view('admin.cash-registers.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin ? request('company_id') : $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa seleccionada.']);
        }

        $validated = request()->validate([
            'branch_id'            => 'required|exists:branches,id',
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string|max:500',
            'assigned_personal_id' => 'required|exists:personals,id',
            'active'               => 'sometimes|boolean',
        ]);

        try {
            CashRegister::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('cash-registers.index')->with('success', 'Caja creada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al crear caja', ['message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(CashRegister $cashRegister)
    {
        $this->authorizeRegister($cashRegister);

        $cashRegister->load(['branch', 'assignedPersonal.user']);

        $sessions = $cashRegister->sessions()
            ->with(['openedBy', 'closedBy'])
            ->latest('opened_at')
            ->paginate(20);

        return view('admin.cash-registers.show', compact('cashRegister', 'sessions'));
    }

    public function edit(CashRegister $cashRegister)
    {
        $this->authorizeRegister($cashRegister);
        return view('admin.cash-registers.edit', array_merge(
            $this->formData($cashRegister->company_id),
            ['cashRegister' => $cashRegister]
        ));
    }

    public function update(CashRegister $cashRegister)
    {
        $this->authorizeRegister($cashRegister);

        $validated = request()->validate([
            'branch_id'            => 'required|exists:branches,id',
            'name'                 => 'required|string|max:255',
            'description'          => 'nullable|string|max:500',
            'assigned_personal_id' => 'required|exists:personals,id',
            'active'               => 'sometimes|boolean',
        ]);

        try {
            $cashRegister->update([
                ...$validated,
                'active' => request()->boolean('active', false),
            ]);

            return redirect()->route('cash-registers.index')->with('success', 'Caja actualizada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar caja', ['id' => $cashRegister->id, 'message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function destroy(CashRegister $cashRegister)
    {
        $this->authorizeRegister($cashRegister);

        if ($cashRegister->activeSession()) {
            return back()->withErrors(['error' => 'No se puede eliminar una caja con sesión activa.']);
        }

        try {
            $cashRegister->delete();
            return redirect()->route('cash-registers.index')->with('success', 'Caja eliminada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error al eliminar caja', ['id' => $cashRegister->id, 'message' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No fue posible eliminar la caja.']);
        }
    }

    protected function authorizeRegister(CashRegister $cashRegister): void
    {
        if (!auth()->user()->is_super_admin && $cashRegister->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    protected function formData(?int $companyId = null): array
    {
        $user = auth()->user();
        $cid  = $companyId ?? $user->getCurrentCompany()?->id;

        $branches  = Branch::where('company_id', $cid)->where('active', true)->orderBy('name')->get();
        $personals = Personal::where('company_id', $cid)->where('active', true)->orderBy('full_name')->get();

        $companies = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter();

        return compact('branches', 'personals', 'companies');
    }
}
