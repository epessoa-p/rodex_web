<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\HandlesSaleCreation;
use App\Models\Client;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreditController extends Controller
{
    use HandlesSaleCreation;

    /** Cronograma global de cuotas */
    public function cuotas(Request $request)
    {
        $cid = $this->companyScope();

        $query = SaleInstallment::with(['sale.client'])
            ->whereHas('sale', fn ($q) => $q
                ->where('sale_type', 'credit')
                ->where('status', 'completed')
                ->when($cid, fn ($s) => $s->where('company_id', $cid)))
            ->orderBy('due_date');

        if ($request->status === 'overdue') {
            $query->where('status', '!=', 'paid')->whereDate('due_date', '<', now()->toDateString());
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->client_id) {
            $query->whereHas('sale', fn ($q) => $q->where('client_id', $request->client_id));
        }

        $installments = $query->paginate(20)->withQueryString();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->orderBy('full_name')->get(['id', 'full_name']);

        return view('sales.credit.cuotas', compact('installments', 'clients'));
    }

    /** Clientes morosos (cuotas vencidas e impagas) */
    public function morosos()
    {
        $cid = $this->companyScope();

        $overdue = SaleInstallment::with(['sale.client'])
            ->whereHas('sale', fn ($q) => $q
                ->where('sale_type', 'credit')
                ->where('status', 'completed')
                ->when($cid, fn ($s) => $s->where('company_id', $cid)))
            ->where('status', '!=', 'paid')
            ->whereDate('due_date', '<', now()->toDateString())
            ->get();

        // Agrupar por cliente
        $morosos = $overdue->groupBy(fn ($i) => $i->sale->client_id ?? 0)
            ->map(function ($rows) {
                $sale = $rows->first()->sale;
                return (object) [
                    'client'        => $sale->client,
                    'client_name'   => $sale->client?->full_name ?? 'Cliente general',
                    'overdue_count' => $rows->count(),
                    'overdue_total' => $rows->sum(fn ($i) => $i->amount - $i->paid_amount),
                    'oldest_due'    => $rows->min('due_date'),
                ];
            })
            ->sortByDesc('overdue_total')
            ->values();

        $totalOverdue = $morosos->sum('overdue_total');

        return view('sales.credit.morosos', compact('morosos', 'totalOverdue'));
    }

    /** Pantalla de cobranza: ventas a crédito con saldo */
    public function cobranza(Request $request)
    {
        $cid = $this->companyScope();

        $query = Sale::with(['client', 'installments'])
            ->where('sale_type', 'credit')
            ->where('status', 'completed')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('sale_date');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $sales   = $query->paginate(15)->withQueryString();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->orderBy('full_name')->get(['id', 'full_name']);

        $totalReceivable = Sale::where('sale_type', 'credit')->where('status', 'completed')
            ->whereIn('payment_status', ['pending', 'partial'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->get()->sum(fn ($s) => $s->total - $s->paid_amount);

        return view('sales.credit.cobranza', compact('sales', 'clients', 'totalReceivable'));
    }

    /** Registrar abono a un crédito */
    public function registerPayment(Sale $sale, Request $request)
    {
        $this->authorizeSale($sale);

        if ($sale->payment_status === 'paid') {
            return back()->withErrors(['error' => 'Esta venta ya está pagada por completo.']);
        }

        $validated = $request->validate([
            'amount'              => 'required|numeric|min:0.01',
            'payment_date'        => 'required|date',
            'method'              => 'nullable|string|max:50',
            'reference'           => 'nullable|string|max:100',
            'notes'               => 'nullable|string|max:500',
            'sale_installment_id' => 'nullable|exists:sale_installments,id',
        ]);

        $balance = (float) $sale->total - (float) $sale->paid_amount;
        $amount  = (float) $validated['amount'];

        if ($amount > $balance + 0.001) {
            return back()->withErrors(['error' => 'El monto (' . number_format($amount, 2) . ') supera el saldo pendiente (' . number_format($balance, 2) . ').']);
        }

        $session = $this->currentOpenSession();
        if (!$session) {
            return back()->withErrors(['error' => 'Necesitas tener tu caja abierta para registrar el cobro.']);
        }

        try {
            $meta = [
                'method'       => $validated['method'] ?? 'efectivo',
                'reference'    => $validated['reference'] ?? null,
                'notes'        => $validated['notes'] ?? 'Cobro de crédito',
                'payment_date' => $validated['payment_date'],
            ];

            // Si se especifica cuota concreta, abonar a esa; si no, distribuir
            if (!empty($validated['sale_installment_id'])) {
                $installment = SaleInstallment::findOrFail($validated['sale_installment_id']);
                $this->registerSalePayment($sale, $installment, $amount, $session, $meta);
            } else {
                $this->applyCreditPayment($sale, $amount, $session, $meta);
            }

            $sale->refresh()->recalcPaymentStatus();

            // Recibo térmico del cobro (auto-impresión en la vista de origen)
            $receiptUrl = route('credit.receipt', [
                'sale'   => $sale->id,
                'amount' => $amount,
                'method' => $meta['method'],
                'date'   => $meta['payment_date'],
            ]);

            return back()
                ->with('success', 'Cobro registrado exitosamente.')
                ->with('cobro_receipt_url', $receiptUrl);
        } catch (\Throwable $e) {
            Log::error('Error al registrar cobro', ['sale' => $sale->id, 'msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al registrar el cobro: ' . $e->getMessage()]);
        }
    }

    /** Recibo térmico (80mm) de un cobro registrado */
    public function paymentReceipt(Sale $sale, Request $request)
    {
        $this->authorizeSale($sale);

        $sale->load(['client', 'company', 'branch', 'createdBy']);

        $amount = (float) $request->query('amount', 0);
        $method = $request->query('method', 'efectivo');
        $date   = $request->query('date', now()->toDateString());

        return view('sales.credit.receipt', compact('sale', 'amount', 'method', 'date'));
    }

    /** Ventas a Crédito (lista dedicada) */
    public function creditSales(Request $request)
    {
        $cid = $this->companyScope();

        $query = Sale::with(['client', 'branch'])
            ->where('sale_type', 'credit')
            ->where('status', 'completed')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest();

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }
        if ($request->sale_category) {
            $query->where('sale_category', $request->sale_category);
        }
        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $sales   = $query->paginate(15)->withQueryString();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->orderBy('full_name')->get(['id', 'full_name']);

        return view('sales.credit.sales', compact('sales', 'clients'));
    }

    /** Reportes de crédito: cartera, antigüedad de saldos, morosidad */
    public function reports()
    {
        $cid = $this->companyScope();

        $creditSales = Sale::with('client')
            ->where('sale_type', 'credit')
            ->where('status', 'completed')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->get();

        $portfolio   = $creditSales->sum(fn ($s) => $s->total - $s->paid_amount); // cartera por cobrar
        $totalCredit = $creditSales->sum('total');
        $totalPaid   = $creditSales->sum('paid_amount');

        // Recuperado del mes (pagos de ventas a crédito)
        $recoveredThisMonth = \App\Models\Sales\SalePayment::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->whereHas('sale', fn ($q) => $q->where('sale_type', 'credit'))
            ->sum('amount');

        // Antigüedad de saldos: cuotas pendientes/parciales agrupadas por días de atraso
        $pendingInstallments = SaleInstallment::with('sale')
            ->whereIn('status', ['pending', 'partial'])
            ->whereHas('sale', fn ($q) => $q
                ->where('sale_type', 'credit')->where('status', 'completed')
                ->when($cid, fn ($s) => $s->where('company_id', $cid)))
            ->get();

        $today  = now()->startOfDay();
        $aging  = ['vigente' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0];
        foreach ($pendingInstallments as $inst) {
            $bal = (float) $inst->amount - (float) $inst->paid_amount;
            if ($bal <= 0) continue;
            $due = $inst->due_date->startOfDay();
            if ($due->gte($today)) {
                $aging['vigente'] += $bal;
            } else {
                $days = $due->diffInDays($today);
                if ($days <= 30)      $aging['1-30']  += $bal;
                elseif ($days <= 60)  $aging['31-60'] += $bal;
                elseif ($days <= 90)  $aging['61-90'] += $bal;
                else                  $aging['90+']   += $bal;
            }
        }

        // Top deudores (por saldo pendiente)
        $topDebtors = $creditSales
            ->filter(fn ($s) => ($s->total - $s->paid_amount) > 0)
            ->groupBy('client_id')
            ->map(function ($sales) {
                $first = $sales->first();
                return (object) [
                    'client_name' => $first->client?->full_name ?? 'Cliente general',
                    'count'       => $sales->count(),
                    'balance'     => $sales->sum(fn ($s) => $s->total - $s->paid_amount),
                ];
            })
            ->sortByDesc('balance')
            ->take(10)
            ->values();

        return view('sales.credit.reports', compact(
            'portfolio', 'totalCredit', 'totalPaid', 'recoveredThisMonth', 'aging', 'topDebtors'
        ));
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }

    private function authorizeSale(Sale $sale): void
    {
        if (!auth()->user()->is_super_admin && $sale->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }
}
