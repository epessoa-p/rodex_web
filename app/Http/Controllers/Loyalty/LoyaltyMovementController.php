<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Loyalty\LoyaltyPointMovement;
use Illuminate\Http\Request;

class LoyaltyMovementController extends Controller
{
    public function index(Request $request)
    {
        $cid = $this->companyScope();

        $query = LoyaltyPointMovement::with(['client:id,full_name', 'user:id,name'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest();

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->type && in_array($request->type, ['earn', 'redeem', 'adjust'])) {
            $query->where('type', $request->type);
        }

        $movements = $query->paginate(30)->withQueryString();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('full_name')->get(['id', 'full_name']);

        return view('loyalty.movements.index', compact('movements', 'clients'));
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
