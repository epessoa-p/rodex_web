<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Warehouse::with(['company', 'primaryBranch'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }

        return view('inventory.warehouses.index', ['warehouses' => $query->paginate(15)]);
    }

    public function create()
    {
        return view('inventory.warehouses.create', $this->formData());
    }

    public function store()
    {
        $user      = auth()->user();
        $companyId = $user->is_super_admin
            ? request('company_id')
            : $user->getCurrentCompany()?->id;

        $validated = request()->validate([
            'company_id'  => ['nullable', 'exists:companies,id'],
            'name'        => 'required|string|max:255',
            'code'        => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')],
            'location'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            Warehouse::create([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', true),
            ]);

            return redirect()->route('warehouses.index')
                ->with('success', 'Almacén creado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al crear almacén', ['message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function show(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        $warehouse->load(['company', 'primaryBranch', 'inventoryMovements.product', 'inventoryMovements.user']);

        $otherWarehouses = Warehouse::where('company_id', $warehouse->company_id)
            ->where('id', '!=', $warehouse->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.warehouses.show', [
            'warehouse'       => $warehouse,
            'products'        => Product::where('company_id', $warehouse->company_id)->where('active', true)->orderBy('name')->get(),
            'otherWarehouses' => $otherWarehouses,
        ]);
    }

    public function edit(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);
        return view('inventory.warehouses.edit', array_merge(
            $this->formData($warehouse->company_id),
            ['warehouse' => $warehouse]
        ));
    }

    public function update(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        $user      = auth()->user();
        $companyId = $user->is_super_admin
            ? request('company_id', $warehouse->company_id)
            : $warehouse->company_id;

        $validated = request()->validate([
            'company_id'  => ['nullable', 'exists:companies,id'],
            'name'        => 'required|string|max:255',
            'code'        => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'location'    => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'active'      => 'sometimes|boolean',
        ]);

        try {
            $warehouse->update([
                ...$validated,
                'company_id' => $companyId,
                'active'     => request()->boolean('active', false),
            ]);

            return redirect()->route('warehouses.index')
                ->with('success', 'Almacén actualizado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al actualizar almacén', ['warehouse_id' => $warehouse->id, 'message' => $exception->getMessage()]);
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function destroy(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        try {
            $warehouse->delete();
            return redirect()->route('warehouses.index')
                ->with('success', 'Almacén eliminado exitosamente.');
        } catch (\Throwable $exception) {
            Log::error('Error al eliminar almacén', ['warehouse_id' => $warehouse->id, 'message' => $exception->getMessage()]);
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function storeMovement(Warehouse $warehouse)
    {
        $this->authorizeWarehouse($warehouse);

        $rules = [
            'product_id'               => ['required', 'exists:products,id'],
            'type'                     => ['required', Rule::in(['in', 'out', 'transfer', 'adjustment'])],
            'quantity'                 => 'required|integer|min:1',
            'unit_cost'                => 'nullable|numeric|min:0',
            'reference'                => 'nullable|string|max:100',
            'notes'                    => 'nullable|string',
            'movement_date'            => 'required|date',
            'destination_warehouse_id' => 'nullable|exists:warehouses,id',
            'adjustment_reason'        => 'nullable|string|max:255',
        ];

        $validated = request()->validate($rules);
        $type      = $validated['type'];

        if ($type === 'transfer' && empty($validated['destination_warehouse_id'])) {
            return back()->withInput()->withErrors([
                'destination_warehouse_id' => 'Selecciona el almacén destino.',
            ]);
        }

        try {
            InventoryMovement::create([
                ...$validated,
                'company_id'  => $warehouse->company_id,
                'warehouse_id' => $warehouse->id,
                'branch_id'   => $warehouse->primaryBranch?->id,
                'user_id'     => auth()->id(),
            ]);

            $product = Product::find($validated['product_id']);
            if ($product) {
                if ($type === 'in') {
                    $product->increment('current_stock', $validated['quantity']);
                } elseif ($type === 'out') {
                    $product->decrement('current_stock', $validated['quantity']);
                } elseif ($type === 'adjustment') {
                    $qty = $validated['quantity'];
                    if (request('adjustment_direction') === 'decrease') {
                        $qty = -$qty;
                    }
                    $product->increment('current_stock', $qty);
                }
                // transfer: global stock unchanged
            }

            return redirect()->route('warehouses.show', $warehouse)
                ->with('success', 'Movimiento registrado exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error movimiento almacén', ['id' => $warehouse->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    protected function formData(?int $companyId = null): array
    {
        $user      = auth()->user();
        $companies = $user->is_super_admin
            ? Company::orderBy('name')->get()
            : collect([$user->getCurrentCompany()])->filter();

        return ['companies' => $companies];
    }

    protected function authorizeWarehouse(Warehouse $warehouse): void
    {
        if (!auth()->user()->is_super_admin
            && $warehouse->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
