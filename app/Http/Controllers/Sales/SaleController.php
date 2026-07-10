<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\HandlesSaleCreation;
use App\Models\Branch;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sales\Quote;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    use HandlesSaleCreation;

    public function index()
    {
        $user  = auth()->user();
        $cid   = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
        $query = Sale::with(['client', 'branch', 'createdBy'])->withCount('returns')->latest();

        if ($cid) {
            $query->where('company_id', $cid);
        }
        if ($type = request('sale_type')) {
            $query->where('sale_type', $type);
        }
        if ($status = request('payment_status')) {
            $query->where('payment_status', $status);
        }
        if ($returns = request('returns')) {
            if ($returns === 'con') {
                $query->has('returns');
            } elseif ($returns === 'sin') {
                $query->doesntHave('returns');
            }
        }

        // Filtro por fecha: por defecto el día actual (hora Bolivia).
        // Si los campos llegan vacíos explícitamente, no se filtra (ver todas).
        $today    = now()->toDateString();
        $dateFrom = request()->has('date_from') ? request('date_from') : $today;
        $dateTo   = request()->has('date_to')   ? request('date_to')   : $today;
        if ($dateFrom) {
            $query->whereDate('sale_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('sale_date', '<=', $dateTo);
        }

        // ¿Puede ver TODAS las ventas? Sin el permiso, solo ve las que registró.
        $canAllSales = $user->is_super_admin
            || $user->hasPermissionInCompany('sales.view-all-records', $user->getCurrentCompany());
        if (!$canAllSales) {
            $query->where('created_by', $user->id);
        }

        // ¿Puede ver todas las sucursales? (permiso o super admin)
        $canAllBranches = $user->is_super_admin
            || $user->hasPermissionInCompany('sales.view-all-branches', $user->getCurrentCompany());

        // Sucursal(es) de la caja asignada al personal (para acotar a quien no tiene permiso).
        $cajaBranchIds = collect();
        $personal = $user->personal;
        if (!$user->is_super_admin && $personal) {
            $cajaBranchIds = \App\Models\CashRegister::where('assigned_personal_id', $personal->id)
                ->whereNotNull('branch_id')
                ->pluck('branch_id')->unique()->values();
        }

        $activeBranch = request('branch');

        if ($canAllBranches) {
            // Acceso completo: filtro por la sucursal elegida (si la hay).
            if ($activeBranch && $activeBranch !== 'all') {
                $query->where('branch_id', $activeBranch);
            }
        } else {
            // Sin permiso: forzar el alcance a la(s) sucursal(es) de su caja.
            if ($cajaBranchIds->isNotEmpty()) {
                $query->whereIn('branch_id', $cajaBranchIds);
                // Si es una sola, marcarla como activa para resaltar su tab.
                $activeBranch = $cajaBranchIds->count() === 1 ? (string) $cajaBranchIds->first() : null;
            }
        }

        // Sucursales para los tabs: todas (con permiso) o solo las de la caja.
        $branchesQuery = Branch::when($cid, fn ($q) => $q->where('company_id', $cid));
        if (!$canAllBranches && $cajaBranchIds->isNotEmpty()) {
            $branchesQuery->whereIn('id', $cajaBranchIds);
        }
        $branches = $branchesQuery->orderBy('name')->get(['id', 'name']);

        return view('sales.invoices.index', [
            'sales'          => $query->paginate(15)->withQueryString(),
            'branches'       => $branches,
            'activeBranch'   => $activeBranch,
            'canAllBranches' => $canAllBranches,
            'canAllSales'    => $canAllSales,
            'dateFrom'       => $dateFrom,
            'dateTo'         => $dateTo,
            'today'          => $today,
        ]);
    }

    /** Dashboard de ventas (KPIs + gráficos con filtros) */
    public function dashboard(Request $request)
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        // Rango por defecto: últimos 30 días si no se especifica
        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        // Base reutilizable de ventas completadas dentro del filtro
        $applyFilters = function ($q) use ($cid, $request, $dateFrom, $dateTo) {
            $q->when($cid, fn ($w) => $w->where('company_id', $cid))->where('status', 'completed');
            if ($dateFrom) $q->whereDate('sale_date', '>=', $dateFrom);
            if ($dateTo)   $q->whereDate('sale_date', '<=', $dateTo);
            if ($request->sale_type) $q->where('sale_type', $request->sale_type);
            if ($request->client_id) $q->where('client_id', $request->client_id);
            return $q;
        };

        // KPIs
        $totalSold   = $applyFilters(Sale::query())->sum('total');
        $totalCash   = $applyFilters(Sale::query())->where('sale_type', 'cash')->sum('total');
        $totalCredit = $applyFilters(Sale::query())->where('sale_type', 'credit')->sum('total');
        $count       = $applyFilters(Sale::query())->count();
        $avgTicket   = $count > 0 ? $totalSold / $count : 0;

        // Devoluciones del rango
        $returnsQuery = \App\Models\Sales\SaleReturn::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid));
        if ($dateFrom) $returnsQuery->whereDate('return_date', '>=', $dateFrom);
        if ($dateTo)   $returnsQuery->whereDate('return_date', '<=', $dateTo);
        $returnsCount  = (clone $returnsQuery)->count();
        $returnsAmount = (clone $returnsQuery)->sum('total');

        // Ventas por día (línea) — si no hay rango, últimos 30 días
        $byDayQuery = $applyFilters(Sale::query());
        if (!$dateFrom && !$dateTo) {
            $byDayQuery->whereDate('sale_date', '>=', now()->subDays(29)->toDateString());
        }
        $byDay = $byDayQuery
            ->selectRaw('DATE(sale_date) as d, SUM(total) as t')
            ->groupBy('d')->orderBy('d')->get();
        $chartLabels = $byDay->map(fn ($r) => \Illuminate\Support\Carbon::parse($r->d)->format('d/m'))->values();
        $chartData   = $byDay->map(fn ($r) => (float) $r->t)->values();

        // Top 5 productos
        $topProducts = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'completed')
            ->when($cid, fn ($q) => $q->where('sales.company_id', $cid))
            ->when($dateFrom, fn ($q) => $q->whereDate('sales.sale_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('sales.sale_date', '<=', $dateTo))
            ->when($request->sale_type, fn ($q) => $q->where('sales.sale_type', $request->sale_type))
            ->when($request->client_id, fn ($q) => $q->where('sales.client_id', $request->client_id))
            ->selectRaw('products.name as name, SUM(sale_items.quantity) as qty, SUM(sale_items.subtotal) as total')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->limit(5)->get();

        // Top 5 clientes
        $topClients = $applyFilters(Sale::query())
            ->selectRaw('client_id, SUM(total) as total, COUNT(*) as ventas')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->limit(5)->get()
            ->map(function ($row) {
                $name = $row->client_id ? (Client::find($row->client_id)?->full_name ?? 'Cliente') : 'Cliente general';
                return ['name' => $name, 'total' => (float) $row->total, 'ventas' => $row->ventas];
            });

        // Ventas recientes (10)
        $recentSales = Sale::with(['client', 'branch'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->when($dateFrom, fn ($q) => $q->whereDate('sale_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('sale_date', '<=', $dateTo))
            ->when($request->sale_type, fn ($q) => $q->where('sale_type', $request->sale_type))
            ->when($request->client_id, fn ($q) => $q->where('client_id', $request->client_id))
            ->latest('sale_date')->latest('id')
            ->limit(10)->get();

        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->orderBy('full_name')->get(['id', 'full_name']);

        return view('sales.dashboard.index', compact(
            'totalSold', 'totalCash', 'totalCredit', 'count', 'avgTicket',
            'returnsCount', 'returnsAmount',
            'chartLabels', 'chartData', 'topProducts', 'topClients', 'recentSales', 'clients'
        ));
    }

    public function create()
    {
        $fromQuote    = null;
        $prefillItems = collect();
        $fromApplication = null;

        if ($quoteId = request('quote_id')) {
            $fromQuote = Quote::with('items.product')->find($quoteId);
            if ($fromQuote) {
                $this->authorizeCompanyId($fromQuote->company_id);
                $prefillItems = $fromQuote->items->map(fn ($it) => [
                    'product_id' => $it->product_id,
                    'quantity'   => (float) $it->quantity,
                    'unit_price' => (float) $it->unit_price,
                    'discount'   => (float) $it->discount,
                ])->values();
            }
        }

        if ($appId = request('application_id')) {
            $fromApplication = \App\Models\Sales\CreditApplication::with('paymentPlan')->find($appId);
            if ($fromApplication) {
                $this->authorizeCompanyId($fromApplication->company_id);
            }
        }

        $session = $this->currentOpenSession();

        return view('sales.invoices.create', array_merge(
            $this->formData(),
            ['fromQuote' => $fromQuote, 'prefillItems' => $prefillItems, 'fromApplication' => $fromApplication, 'session' => $session]
        ));
    }

    public function store(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $request->validate([
            'client_id'          => 'nullable|exists:clients,id',
            'sale_type'          => 'required|in:cash,credit',
            'sale_date'          => 'required|date',
            'discount'           => 'nullable|numeric|min:0',
            'tax'                => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
            'installments'             => 'nullable|array',
            'installments.*.due_date'  => 'required_with:installments|date',
            'installments.*.amount'    => 'required_with:installments|numeric|min:0.01',
            'down_payment'             => 'nullable|numeric|min:0',
            'quote_id'                 => 'nullable|exists:quotes,id',
            'interest'                 => 'nullable|numeric|min:0',
            'payment_plan_id'          => 'nullable|exists:payment_plans,id',
            'application_id'           => 'nullable|exists:credit_applications,id',
        ]);

        // La sucursal se toma de la caja abierta del personal logueado
        $session  = $this->currentOpenSession();
        $branchId = $session?->cashRegister?->branch_id;

        if (!$session || !$branchId) {
            return back()->withInput()->withErrors([
                'error' => 'Necesitas tener tu caja abierta (con sucursal asignada) para registrar la venta.',
            ]);
        }

        // Validar cuadre de cuotas en crédito
        if ($validated['sale_type'] === 'credit') {
            if (empty($validated['installments'])) {
                return back()->withInput()->withErrors(['installments' => 'Una venta a crédito requiere al menos una cuota.']);
            }
        }

        try {
            $sale = $this->confirmSale([
                'company_id'            => $companyId,
                'branch_id'             => $branchId,
                'client_id'             => $validated['client_id'] ?? null,
                'sale_type'             => $validated['sale_type'],
                'sale_date'             => $validated['sale_date'],
                'discount'              => $validated['discount'] ?? 0,
                'tax'                   => $validated['tax'] ?? 0,
                'interest'              => $validated['interest'] ?? 0,
                'payment_plan_id'       => $validated['payment_plan_id'] ?? null,
                'credit_application_id' => $validated['application_id'] ?? null,
                'notes'                 => $validated['notes'] ?? null,
                'items'                 => $validated['items'],
                'installments'          => $validated['installments'] ?? [],
                'down_payment'          => $validated['down_payment'] ?? 0,
            ], $session);

            // Si viene de una cotización, marcarla como convertida
            if (!empty($validated['quote_id'])) {
                Quote::where('id', $validated['quote_id'])
                    ->where('company_id', $companyId)
                    ->update(['status' => 'converted', 'converted_sale_id' => $sale->id]);
            }

            // Si viene de una solicitud de crédito, marcarla convertida
            if (!empty($validated['application_id'])) {
                \App\Models\Sales\CreditApplication::where('id', $validated['application_id'])
                    ->where('company_id', $companyId)
                    ->update(['status' => 'convertida', 'converted_sale_id' => $sale->id]);
            }

            return redirect()->route('sales.show', $sale)->with('success', 'Venta registrada: ' . $sale->code);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        } catch (\Throwable $e) {
            Log::error('Error al crear venta', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(Sale $sale)
    {
        $this->authorizeSale($sale);
        $sale->load([
            'client', 'branch', 'createdBy', 'items.product', 'installments',
            'payments.user', 'session.cashRegister', 'returns',
        ]);

        // Cantidad devuelta por cada item de venta
        $returnedByItem = \App\Models\Sales\SaleReturnItem::whereHas('saleReturn', fn ($q) => $q->where('sale_id', $sale->id))
            ->selectRaw('sale_item_id, SUM(quantity) as qty')
            ->groupBy('sale_item_id')
            ->pluck('qty', 'sale_item_id');

        $returnableLeft = $sale->items->sum(fn ($it) => max(0, (float) $it->quantity - (float) ($returnedByItem[$it->id] ?? 0)));
        $hasReturns     = $sale->returns->isNotEmpty();
        $fullyReturned  = $hasReturns && $returnableLeft <= 0.0001;
        $totalReturned  = (float) $sale->returns->sum('total');

        return view('sales.invoices.show', compact(
            'sale', 'returnedByItem', 'returnableLeft', 'hasReturns', 'fullyReturned', 'totalReturned'
        ));
    }

    /** Recibo térmico 80mm (vista standalone, auto-imprime) */
    public function receipt(Sale $sale)
    {
        $this->authorizeSale($sale);
        $sale->load([
            'client', 'branch', 'company', 'createdBy',
            'items.product', 'session.cashRegister', 'installments',
        ]);

        return view('sales.invoices.receipt', compact('sale'));
    }

    public function cancel(Sale $sale)
    {
        $this->authorizeSale($sale);

        if ($sale->payments()->exists()) {
            return back()->withErrors(['error' => 'No se puede anular: la venta tiene pagos registrados.']);
        }
        if ($sale->status === 'cancelled') {
            return back()->withErrors(['error' => 'La venta ya está anulada.']);
        }

        try {
            DB::transaction(function () use ($sale) {
                // Revertir stock con movimiento de entrada
                $branch      = $sale->branch;
                $warehouseId = $branch?->warehouse_id;

                foreach ($sale->items as $item) {
                    if ($warehouseId) {
                        InventoryMovement::create([
                            'company_id'    => $sale->company_id,
                            'warehouse_id'  => $warehouseId,
                            'branch_id'     => $sale->branch_id,
                            'product_id'    => $item->product_id,
                            'user_id'       => auth()->id(),
                            'type'          => 'in',
                            'quantity'      => $item->quantity,
                            'unit_cost'     => $item->unit_price,
                            'reference'     => $sale->code,
                            'notes'         => 'Anulación venta ' . $sale->code,
                            'movement_date' => now(),
                        ]);
                    }
                    Product::where('id', $item->product_id)->increment('current_stock', $item->quantity);
                }

                $sale->update(['status' => 'cancelled']);

                // Fidelización: revertir los puntos acreditados por la venta
                app(\App\Services\Loyalty\LoyaltyService::class)->reverse($sale);
            });

            return back()->with('success', 'Venta anulada y stock revertido.');
        } catch (\Throwable $e) {
            Log::error('Error al anular venta', ['id' => $sale->id, 'msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al anular: ' . $e->getMessage()]);
        }
    }

    private function authorizeSale(Sale $sale): void
    {
        $this->authorizeCompanyId($sale->company_id);
    }

    private function authorizeCompanyId(int $companyId): void
    {
        if (!auth()->user()->is_super_admin && $companyId !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;

        $branches = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $clients  = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('full_name')->get();
        $products = Product::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $plans    = \App\Models\Sales\PaymentPlan::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();

        return compact('branches', 'clients', 'products', 'plans');
    }
}
