<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\Purchases\Purchase;
use App\Models\Purchases\PurchaseOrder;
use App\Models\Purchases\Supplier;
use App\Models\Purchases\TreasuryAccount;
use Illuminate\Support\Facades\DB;

class PurchaseDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $scope = fn ($q) => $cid ? $q->where('company_id', $cid) : $q;

        // Comprado este mes
        $purchasedThisMonth = Purchase::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereMonth('purchase_date', now()->month)
            ->whereYear('purchase_date', now()->year)
            ->sum('total');

        // Total por pagar
        $pendingPurchases = Purchase::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('payment_status', ['pending', 'partial'])->get();
        $totalPayable = $pendingPurchases->sum(fn ($p) => $p->total - $p->paid_amount);

        // Saldo de tesorería
        $treasuryBalance = TreasuryAccount::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->sum('balance');

        // OCs pendientes (no recibidas ni anuladas)
        $pendingOrdersCount = PurchaseOrder::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['draft', 'sent', 'partial'])->count();

        // Top proveedores (por monto comprado)
        $topSuppliers = Supplier::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->withSum('purchases as total_purchased', 'total')
            ->orderByDesc('total_purchased')
            ->limit(5)->get();

        // Compras recientes
        $recentPurchases = Purchase::with('supplier')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest('purchase_date')->limit(8)->get();

        // OCs pendientes recientes
        $pendingOrders = PurchaseOrder::with('supplier')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereIn('status', ['draft', 'sent', 'partial'])
            ->latest()->limit(8)->get();

        // Cuentas de tesorería
        $accounts = TreasuryAccount::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        return view('purchases.dashboard.index', compact(
            'purchasedThisMonth', 'totalPayable', 'treasuryBalance', 'pendingOrdersCount',
            'topSuppliers', 'recentPurchases', 'pendingOrders', 'accounts'
        ));
    }
}
