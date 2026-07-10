<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltyPointMovement;
use App\Models\Loyalty\LoyaltyRedemption;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LoyaltyReportController extends Controller
{
    public function index(Request $request)
    {
        $cid  = $this->companyScope();
        $from = $request->date_from ?: now()->startOfMonth()->toDateString();
        $to   = $request->date_to   ?: now()->toDateString();

        $movements = fn () => LoyaltyPointMovement::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59']);

        $issued   = (clone $movements())->where('type', 'earn')->sum('points');
        $redeemed = abs((clone $movements())->where('type', 'redeem')->sum('points'));
        $expired  = abs((clone $movements())->where('type', 'expire')->sum('points'));
        $adjusted = (clone $movements())->where('type', 'adjust')->sum('points');

        // Serie por día (emitidos vs canjeados)
        $series = (clone $movements())
            ->selectRaw('DATE(created_at) d,
                         SUM(CASE WHEN type = "earn" THEN points ELSE 0 END) earned,
                         SUM(CASE WHEN type = "redeem" THEN -points ELSE 0 END) redeemed')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('d')
            ->get();

        // Top recompensas canjeadas en el rango
        $topRewards = LoyaltyRedemption::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->whereBetween('redeemed_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->selectRaw('reward_id, COUNT(*) total, SUM(points_spent) puntos')
            ->groupBy('reward_id')
            ->orderByDesc('total')
            ->with('reward:id,name')
            ->limit(10)
            ->get();

        // Top clientes por puntos acumulados en el rango
        $topClients = (clone $movements())
            ->where('type', 'earn')
            ->selectRaw('client_id, SUM(points) total')
            ->groupBy('client_id')
            ->orderByDesc('total')
            ->with('client:id,full_name')
            ->limit(10)
            ->get();

        return view('loyalty.reports.index', compact(
            'from', 'to', 'issued', 'redeemed', 'expired', 'adjusted',
            'series', 'topRewards', 'topClients'
        ));
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
