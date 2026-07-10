<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Loyalty\LoyaltyPointMovement;
use App\Models\Loyalty\LoyaltyRedemption;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltySetting;

class LoyaltyDashboardController extends Controller
{
    public function index()
    {
        $cid = $this->companyScope();

        $settings = $cid ? LoyaltySetting::where('company_id', $cid)->first() : null;

        $movements = LoyaltyPointMovement::query()->when($cid, fn ($q) => $q->where('company_id', $cid));

        $pointsEarned   = (clone $movements)->where('type', 'earn')->sum('points');
        $pointsRedeemed = abs((clone $movements)->where('type', 'redeem')->sum('points'));
        $pointsBalance  = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->sum('points_balance');
        $redemptionsCount = LoyaltyRedemption::query()->when($cid, fn ($q) => $q->where('company_id', $cid))->count();

        // Ranking de clientes (top por saldo)
        $ranking = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('points_balance', '>', 0)
            ->orderByDesc('points_balance')
            ->limit(10)
            ->get(['id', 'full_name', 'points_balance']);

        // Recompensas más canjeadas
        $topRewards = LoyaltyRedemption::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->selectRaw('reward_id, COUNT(*) as total')
            ->groupBy('reward_id')
            ->orderByDesc('total')
            ->with('reward:id,name')
            ->limit(5)
            ->get();

        $recentRedemptions = LoyaltyRedemption::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->with(['client:id,full_name', 'reward:id,name'])
            ->latest('redeemed_at')
            ->limit(8)
            ->get();

        return view('loyalty.dashboard', compact(
            'settings', 'pointsEarned', 'pointsRedeemed', 'pointsBalance',
            'redemptionsCount', 'ranking', 'topRewards', 'recentRedemptions'
        ));
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
