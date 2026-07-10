<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Loyalty\LoyaltyRedemption;
use App\Models\Loyalty\LoyaltyReward;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoyaltyRedemptionController extends Controller
{
    public function __construct(private LoyaltyService $loyalty)
    {
    }

    public function index(Request $request)
    {
        $cid = $this->companyScope();

        $query = LoyaltyRedemption::with(['client:id,full_name', 'reward:id,name', 'user:id,name'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest('redeemed_at');

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        $redemptions = $query->paginate(20)->withQueryString();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('full_name')->get(['id', 'full_name']);

        return view('loyalty.redemptions.index', compact('redemptions', 'clients'));
    }

    public function create()
    {
        $cid = $this->companyScope();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('full_name')->get(['id', 'full_name', 'id_number', 'points_balance']);
        $rewards = LoyaltyReward::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('points_cost')->get();

        return view('loyalty.redemptions.create', compact('clients', 'rewards'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reward_id' => 'required|exists:loyalty_rewards,id',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $client = Client::findOrFail($data['client_id']);
        $reward = LoyaltyReward::findOrFail($data['reward_id']);
        $this->authorizeCompany($client->company_id);
        $this->authorizeCompany($reward->company_id);

        try {
            $redemption = $this->loyalty->redeem($client, $reward, $data['branch_id'] ?? null);
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
            }
            return back()->withErrors($e->errors());
        }

        $client->refresh();
        if ($request->expectsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Canje registrado: ' . $reward->name,
                'balance' => $client->points_balance,
            ]);
        }

        return back()->with('success', 'Canje registrado: ' . $reward->name . ' para ' . $client->full_name . '.');
    }

    /** AJAX: saldo + recompensas disponibles para un cliente (modal del POS y del canje). */
    public function clientData(Client $client)
    {
        $this->authorizeCompany($client->company_id);

        $rewards = LoyaltyReward::where('company_id', $client->company_id)
            ->where('active', true)
            ->orderBy('points_cost')
            ->get(['id', 'name', 'points_cost', 'stock', 'image'])
            ->map(fn ($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'points_cost' => $r->points_cost,
                'available'   => $r->stock === null || $r->stock > 0,
                'affordable'  => $client->points_balance >= $r->points_cost,
                'image'       => $r->image_url,
            ]);

        return response()->json([
            'balance' => $client->points_balance,
            'rewards' => $rewards,
        ]);
    }

    private function authorizeCompany(int $companyId): void
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $companyId !== $user->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
