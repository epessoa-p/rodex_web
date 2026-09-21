<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\Purchases\Purchase;
use App\Models\Sales\Sale;
use App\Models\Workshop\WorkOrder;
use App\Models\Warehouse;
use App\Services\Inventory\StockValuationService;
use App\Services\Reports\IncomeStatementService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Reportes del móvil (Reportes → Finanzas / Inventario). Misma lógica que la
 * web: MovementController (movimientos y cierres) y StockValuationService.
 */
class ReportsController extends Controller
{
    /** Movimientos de caja + cierres del período (?preset= o ?from=&to=, ?branch_id=). */
    public function cash(Request $request, IncomeStatementService $periods)
    {
        $cid  = $request->attributes->get('tenant_company')?->id;
        $data = $request->validate([
            'preset'    => ['nullable', 'in:this_week,last_week,this_month,last_month,all'],
            'from'      => ['nullable', 'date'],
            'to'        => ['nullable', 'date'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        [$from, $to] = $this->range($data, $periods);
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
        $branchId = ! empty($data['branch_id'])
            ? Branch::where('company_id', $cid)->whereKey($data['branch_id'])->value('id')
            : null;

        $movQuery = CashMovement::query()
            ->where('company_id', $cid)
            ->whereBetween('movement_date', $range)
            ->when($branchId, fn ($q) => $q->whereHas('cashRegister', fn ($r) => $r->where('branch_id', $branchId)));

        $income  = (float) (clone $movQuery)->where('type', 'income')->sum('amount');
        $expense = (float) (clone $movQuery)->where('type', 'expense')->sum('amount');

        $byCategory = (clone $movQuery)
            ->selectRaw('category, type, SUM(amount) as amt')
            ->groupBy('category', 'type')
            ->orderByDesc('amt')
            ->get()
            ->map(fn ($r) => [
                'category' => $r->category,
                'label'    => CashMovement::CATEGORIES[$r->category]['label'] ?? $r->category,
                'type'     => $r->type,
                'amount'   => (float) $r->amt,
            ])->values();

        $limit = 300;
        $total = (clone $movQuery)->count();
        $movements = (clone $movQuery)
            ->with(['cashRegister.branch', 'user'])
            ->orderByDesc('movement_date')->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (CashMovement $m) => [
                'id'          => $m->id,
                'date'        => $m->movement_date?->toIso8601String(),
                'type'        => $m->type,
                'category'    => CashMovement::CATEGORIES[$m->category]['label'] ?? $m->category,
                'description' => $m->description,
                'amount'      => (float) $m->amount,
                'method'      => $m->method,
                'register'    => $m->cashRegister?->name,
                'branch'      => $m->cashRegister?->branch?->name,
                'user'        => $m->user?->name,
            ])->values();

        // Cierres del período (cerradas en el rango) + las abiertas ahora mismo.
        $closures = CashRegisterSession::with(['cashRegister.branch', 'openedBy', 'closedBy'])
            ->where(function ($w) use ($range) {
                $w->where(fn ($x) => $x->where('status', 'closed')->whereBetween('closed_at', $range))
                  ->orWhere('status', 'open');
            })
            ->whereHas('cashRegister', fn ($r) => $r->where('company_id', $cid)->when($branchId, fn ($q) => $q->where('branch_id', $branchId)))
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('closed_at')
            ->orderByDesc('opened_at')
            ->limit(100)
            ->get()
            ->map(function (CashRegisterSession $s) {
                $inc = $s->totalIncome();
                $exp = $s->totalExpense();
                $expected = $s->status === 'open' ? (float) $s->opening_amount + $inc - $exp : (float) $s->expected_amount;
                return [
                    'id'              => $s->id,
                    'status'          => $s->status,
                    'register'        => $s->cashRegister?->name,
                    'branch'          => $s->cashRegister?->branch?->name,
                    'opened_at'       => $s->opened_at?->toIso8601String(),
                    'closed_at'       => $s->closed_at?->toIso8601String(),
                    'opened_by'       => $s->openedBy?->name,
                    'closed_by'       => $s->closedBy?->name,
                    'opening_amount'  => (float) $s->opening_amount,
                    'income'          => $inc,
                    'expense'         => $exp,
                    'expected_amount' => $expected,
                    'closing_amount'  => $s->closing_amount !== null ? (float) $s->closing_amount : null,
                    'difference'      => $s->difference !== null ? (float) $s->difference : null,
                    'notes'           => $s->notes,
                ];
            })->values();

        $branches = Branch::where('company_id', $cid)->where('active', true)->orderBy('name')
            ->get(['id', 'name'])->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])->values();

        return response()->json(['data' => [
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'summary'     => ['income' => $income, 'expense' => $expense, 'balance' => round($income - $expense, 2)],
            'by_category' => $byCategory,
            'movements'   => $movements,
            'truncated'   => $total > $limit,
            'total_count' => $total,
            'closures'    => $closures,
            'branches'    => $branches,
        ]]);
    }

    /** Valorización del inventario (consolidada o ?warehouse_id=). */
    public function inventory(Request $request, StockValuationService $valuation)
    {
        $cid  = $request->attributes->get('tenant_company')?->id;
        $data = $request->validate(['warehouse_id' => ['nullable', 'integer']]);

        $warehouses = Warehouse::where('company_id', $cid)->where('active', true)->orderBy('name')->get(['id', 'name']);
        $whId = ! empty($data['warehouse_id']) && $warehouses->firstWhere('id', (int) $data['warehouse_id'])
            ? (int) $data['warehouse_id']
            : null;

        return response()->json(['data' => $valuation->build($cid, $whId) + [
            'warehouse_id' => $whId,
            'warehouses'   => $warehouses->map(fn ($w) => ['id' => $w->id, 'name' => $w->name])->values(),
        ]]);
    }

    /**
     * Cuentas por cobrar: ventas a crédito y OTs entregadas con saldo. Sin
     * período trae TODO lo pendiente; con ?from/&to o ?preset= acota por fecha
     * de la venta/entrega. Igual que la web (MovementController "Por cobrar").
     */
    public function receivables(Request $request, IncomeStatementService $periods)
    {
        $cid  = $request->attributes->get('tenant_company')?->id;
        $data = $request->validate([
            'preset' => ['nullable', 'in:this_week,last_week,this_month,last_month,all'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
        ]);
        $range = $this->optionalRange($data, $periods);
        $today = Carbon::today();
        $rows  = collect();

        Sale::with(['client', 'installments'])
            ->where('company_id', $cid)
            ->where('sale_type', 'credit')->where('status', 'completed')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($range, fn ($q) => $q->whereBetween('sale_date', $range))
            ->get()->each(function (Sale $s) use (&$rows, $today) {
                if ((float) $s->balance <= 0.001) {
                    return;
                }
                $next = $s->installments->first(fn ($i) => (float) $i->paid_amount < (float) $i->amount - 0.001);
                $rows->push([
                    'id'       => $s->id,
                    'type'     => 'sale',
                    'code'     => $s->code,
                    'name'     => $s->client?->full_name ?? 'Cliente general',
                    'phone'    => $s->client?->phone,
                    'date'     => $s->sale_date?->toDateString(),
                    'due_date' => $next?->due_date?->toDateString(),
                    'total'    => (float) $s->total,
                    'paid'     => (float) $s->paid_amount,
                    'balance'  => (float) $s->balance,
                    'days'     => $s->sale_date ? (int) $s->sale_date->copy()->startOfDay()->diffInDays($today, false) : 0,
                    'overdue'  => $next?->due_date ? $next->due_date->lt($today) : false,
                ]);
            });

        WorkOrder::with(['client', 'installments'])
            ->where('company_id', $cid)
            ->whereIn('payment_status', ['pendiente', 'parcial'])
            ->where('status', 'entregada')
            ->when($range, fn ($q) => $q->whereBetween('delivered_at', $range))
            ->get()->each(function (WorkOrder $o) use (&$rows, $today) {
                if ((float) $o->balance <= 0.001) {
                    return;
                }
                $date = $o->delivered_at ?? $o->reception_date;
                $next = $o->installments->first(fn ($i) => (float) $i->paid_amount < (float) $i->amount - 0.001);
                $rows->push([
                    'id'       => $o->id,
                    'type'     => 'work_order',
                    'code'     => $o->code,
                    'name'     => $o->client_display,
                    'phone'    => $o->client?->phone,
                    'date'     => $date?->toDateString(),
                    'due_date' => $next?->due_date?->toDateString(),
                    'total'    => (float) $o->total,
                    'paid'     => (float) $o->paid_amount,
                    'balance'  => (float) $o->balance,
                    'days'     => $date ? (int) $date->copy()->startOfDay()->diffInDays($today, false) : 0,
                    'overdue'  => $next?->due_date ? $next->due_date->lt($today) : false,
                ]);
            });

        return response()->json(['data' => $this->accountsPayload($rows)]);
    }

    /** Cuentas por pagar: compras a proveedor con saldo (pendiente/parcial). */
    public function payables(Request $request, IncomeStatementService $periods)
    {
        $cid  = $request->attributes->get('tenant_company')?->id;
        $data = $request->validate([
            'preset' => ['nullable', 'in:this_week,last_week,this_month,last_month,all'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
        ]);
        $range = $this->optionalRange($data, $periods);
        $today = Carbon::today();

        $rows = Purchase::with('supplier')
            ->where('company_id', $cid)
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($range, fn ($q) => $q->whereBetween('purchase_date', $range))
            ->get()
            ->filter(fn (Purchase $p) => (float) $p->balance > 0.001)
            ->map(fn (Purchase $p) => [
                'id'       => $p->id,
                'type'     => 'purchase',
                'code'     => $p->code,
                'name'     => $p->supplier?->name ?? 'Proveedor',
                'phone'    => $p->supplier?->phone,
                'date'     => $p->purchase_date?->toDateString(),
                'due_date' => null,
                'total'    => (float) $p->total,
                'paid'     => (float) $p->paid_amount,
                'balance'  => (float) $p->balance,
                'days'     => $p->purchase_date ? (int) $p->purchase_date->copy()->startOfDay()->diffInDays($today, false) : 0,
                'overdue'  => false,
            ])->values();

        return response()->json(['data' => $this->accountsPayload($rows)]);
    }

    /** Totales, antigüedad (0–30 / 31–60 / +60 días) y filas ordenadas por saldo. */
    private function accountsPayload($rows): array
    {
        $rows = collect($rows)->sortByDesc('balance')->values();
        $bucket = fn (int $min, ?int $max) => (float) $rows
            ->filter(fn ($r) => $r['days'] >= $min && ($max === null || $r['days'] <= $max))
            ->sum('balance');

        return [
            'total' => round((float) $rows->sum('balance'), 2),
            'count' => $rows->count(),
            'aging' => [
                ['label' => '0–30 días',  'amount' => round($bucket(0, 30), 2)],
                ['label' => '31–60 días', 'amount' => round($bucket(31, 60), 2)],
                ['label' => '+60 días',   'amount' => round($bucket(61, null), 2)],
            ],
            'rows'  => $rows->all(),
        ];
    }

    /** null = sin filtro de fecha (todo lo pendiente); si no, [from, to]. */
    private function optionalRange(array $data, IncomeStatementService $periods): ?array
    {
        $noPeriod = empty($data['preset']) && empty($data['from']) && empty($data['to']);
        if (($data['preset'] ?? null) === 'all' || $noPeriod) {
            return null;
        }
        [$from, $to] = $this->range($data, $periods);

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    /** Rango por preset (mismos que el estado de resultados) o from/to; por defecto este mes. */
    private function range(array $data, IncomeStatementService $periods): array
    {
        if (! empty($data['preset'])) {
            return $periods->presetRange($data['preset']);
        }
        $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::today()->startOfMonth();
        $to   = isset($data['to']) ? Carbon::parse($data['to']) : Carbon::today();

        return [$from, $to];
    }
}
