<?php

namespace App\Http\Controllers\Motos;

use App\Http\Controllers\Controller;
use App\Models\Motos\MotoUnit;
use App\Models\Motos\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WarrantyController extends Controller
{
    public function index(Request $request)
    {
        $cid = auth()->user()->is_super_admin ? null : auth()->user()->getCurrentCompany()?->id;

        $query = Warranty::with(['motoUnit.model.brand', 'client'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return view('motos.warranties.index', ['warranties' => $query->paginate(15)->withQueryString()]);
    }

    public function create(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;

        // Unidades vendidas/entregadas elegibles
        $units = MotoUnit::with(['model.brand', 'sale.client'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['vendida', 'entregada'])
            ->latest()->get();

        $selectedUnit = $request->unit_id ? $units->firstWhere('id', (int) $request->unit_id) : null;

        return view('motos.warranties.create', compact('units', 'selectedUnit'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $request->validate([
            'moto_unit_id' => 'required|exists:moto_units,id',
            'start_date'   => 'required|date',
            'months'       => 'required|integer|min:1|max:120',
            'coverage'     => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        $unit = MotoUnit::with('sale')->find($validated['moto_unit_id']);
        if (!$unit || $unit->company_id !== $companyId) {
            return back()->withInput()->withErrors(['error' => 'Unidad no válida.']);
        }

        try {
            Warranty::create([
                'company_id'   => $companyId,
                'moto_unit_id' => $unit->id,
                'sale_id'      => $unit->sale_id,
                'client_id'    => $unit->sale?->client_id,
                'code'         => $this->nextCode($companyId),
                'start_date'   => $validated['start_date'],
                'months'       => $validated['months'],
                'coverage'     => $validated['coverage'] ?? null,
                'status'       => 'vigente',
                'notes'        => $validated['notes'] ?? null,
                'created_by'   => auth()->id(),
            ]);
            return redirect()->route('warranties.index')->with('success', 'Garantía registrada.');
        } catch (\Throwable $e) {
            Log::error('Error al crear garantía', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(Warranty $warranty)
    {
        $this->authorizeWarranty($warranty);
        $warranty->load(['motoUnit.model.brand', 'sale', 'client', 'createdBy']);
        return view('motos.warranties.show', compact('warranty'));
    }

    public function update(Request $request, Warranty $warranty)
    {
        $this->authorizeWarranty($warranty);
        $validated = $request->validate([
            'status'   => 'required|in:vigente,vencida,anulada',
            'coverage' => 'nullable|string',
            'notes'    => 'nullable|string',
        ]);
        $warranty->update($validated);
        return back()->with('success', 'Garantía actualizada.');
    }

    private function nextCode(int $companyId): string
    {
        $count = Warranty::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'GAR-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeWarranty(Warranty $warranty): void
    {
        if (!auth()->user()->is_super_admin && $warranty->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
