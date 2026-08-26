<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\HandlesSaleCreation;
use App\Models\Sales\Sale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaleController extends Controller
{
    use HandlesSaleCreation;   // incluye ResolvesCashSession

    /**
     * Listado de ventas para el historial del móvil. Scoped por empresa (global
     * scope). Sin el permiso 'sales.view-all-records' solo devuelve las del
     * propio usuario (mismo criterio que el listado web).
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Sale::with('client')->latest('sale_date');

        $canAllRecords = $user->is_super_admin
            || $user->hasPermissionInCompany('sales.view-all-records', $user->getCurrentCompany());
        if (! $canAllRecords) {
            $query->where('created_by', $user->id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sale_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sale_date', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($qq) use ($term) {
                $qq->where('code', 'like', "%{$term}%")
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', "%{$term}%"));
            });
        }

        $sales = $query->paginate(20);

        return response()->json([
            'data' => collect($sales->items())->map(fn (Sale $s) => $this->listItem($s))->values(),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page'    => $sales->lastPage(),
                'total'        => $sales->total(),
            ],
        ]);
    }

    /**
     * Resumen de ventas del día para el inicio del móvil (cantidad y monto).
     * Respeta "solo las mías" salvo permiso 'sales.view-all-records'.
     */
    public function summary(Request $request)
    {
        $user  = auth()->user();
        $today = today();

        $query = Sale::whereDate('sale_date', $today)->where('status', 'completed');

        $canAllRecords = $user->is_super_admin
            || $user->hasPermissionInCompany('sales.view-all-records', $user->getCurrentCompany());
        if (! $canAllRecords) {
            $query->where('created_by', $user->id);
        }

        return response()->json([
            'data' => [
                'date'        => $today->toDateString(),
                'sales_count' => (clone $query)->count(),
                'sales_total' => (float) (clone $query)->sum('total'),
                'scope'       => $canAllRecords ? 'all' : 'own',
            ],
        ]);
    }

    /** Resumen de una venta para el listado (sin items). */
    private function listItem(Sale $sale): array
    {
        return [
            'id'             => $sale->id,
            'code'           => $sale->code,
            'sale_type'      => $sale->sale_type,
            'sale_date'      => $sale->sale_date?->toIso8601String(),
            'client'         => $sale->client?->full_name,
            'total'          => (float) $sale->total,
            'paid_amount'    => (float) $sale->paid_amount,
            'balance'        => (float) $sale->balance,
            'payment_status' => $sale->payment_status,
        ];
    }

    /** Detalle de una venta (para el comprobante). Scoped por global scope. */
    public function show(Sale $sale)
    {
        $sale->load(['items', 'client', 'branch', 'payments']);

        return response()->json(['data' => $this->payload($sale)]);
    }

    /**
     * Registra una venta desde el móvil reutilizando la misma lógica del web
     * (HandlesSaleCreation::confirmSale): descuenta stock, movimiento de inventario,
     * cobro en caja y/o cuotas. Requiere caja abierta con sucursal.
     */
    public function store(Request $request)
    {
        $company   = $request->attributes->get('tenant_company');
        $companyId = $company->id;

        $validated = $request->validate([
            'client_id'          => ['nullable', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'sale_type'          => ['required', 'in:cash,credit'],
            'sale_date'          => ['nullable', 'date'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'tax'                => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount'   => ['nullable', 'numeric', 'min:0'],
            'installments'            => ['nullable', 'array'],
            'installments.*.due_date' => ['required_with:installments', 'date'],
            'installments.*.amount'   => ['required_with:installments', 'numeric', 'min:0.01'],
            'down_payment'            => ['nullable', 'numeric', 'min:0'],
        ]);

        // La sucursal sale de la caja abierta del usuario (mismo modelo que el web).
        $session  = $this->currentOpenSession();
        $branchId = $session?->cashRegister?->branch_id;

        if (! $session || ! $branchId) {
            return response()->json([
                'message' => 'Necesitas tener tu caja abierta (con sucursal) para registrar la venta.',
                'code'    => 'cash_session_required',
            ], 422);
        }

        if ($validated['sale_type'] === 'credit' && empty($validated['installments'])) {
            return response()->json([
                'message' => 'Una venta a crédito requiere al menos una cuota.',
            ], 422);
        }

        try {
            $sale = $this->confirmSale([
                'company_id'   => $companyId,
                'branch_id'    => $branchId,
                'client_id'    => $validated['client_id'] ?? null,
                'sale_type'    => $validated['sale_type'],
                'sale_date'    => $validated['sale_date'] ?? now()->toDateTimeString(),
                'discount'     => $validated['discount'] ?? 0,
                'tax'          => $validated['tax'] ?? 0,
                'interest'     => 0,
                'notes'        => $validated['notes'] ?? null,
                'items'        => $validated['items'],
                'installments' => $validated['installments'] ?? [],
                'down_payment' => $validated['down_payment'] ?? 0,
            ], $session);

            $sale->load(['items', 'client']);

            return response()->json(['data' => $this->payload($sale)], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        }
    }

    private function payload(Sale $sale): array
    {
        return [
            'id'             => $sale->id,
            'code'           => $sale->code,
            'sale_type'      => $sale->sale_type,
            'sale_date'      => $sale->sale_date?->toIso8601String(),
            'client'         => $sale->client?->full_name,
            'subtotal'       => (float) $sale->subtotal,
            'discount'       => (float) $sale->discount,
            'tax'            => (float) $sale->tax,
            'total'          => (float) $sale->total,
            'paid_amount'    => (float) $sale->paid_amount,
            'balance'        => (float) $sale->balance,
            'payment_status' => $sale->payment_status,
            'items'          => $sale->items->map(fn ($it) => [
                'name'       => $it->display_name ?? $it->product?->name,
                'quantity'   => (float) $it->quantity,
                'unit_price' => (float) $it->unit_price,
                'discount'   => (float) ($it->discount ?? 0),
                'subtotal'   => (float) ($it->quantity * $it->unit_price - ($it->discount ?? 0)),
            ])->values(),
        ];
    }
}
