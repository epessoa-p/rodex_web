<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltyCampaign;
use Illuminate\Http\Request;

class LoyaltyCampaignController extends Controller
{
    public function index()
    {
        $cid = $this->companyScope();

        $campaigns = LoyaltyCampaign::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderByDesc('starts_at')
            ->paginate(20);

        return view('loyalty.campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        return view('loyalty.campaigns.form', ['campaign' => new LoyaltyCampaign(['active' => true, 'multiplier' => 2])]);
    }

    public function store(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        abort_unless($cid, 403, 'Selecciona una empresa.');

        $data = $this->validateData($request);
        $data['company_id'] = $cid;
        $data['created_by'] = auth()->id();
        $data['active']     = (bool) $request->boolean('active');

        LoyaltyCampaign::create($data);

        return redirect()->route('loyalty.campaigns.index')->with('success', 'Campaña creada.');
    }

    public function edit(LoyaltyCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        return view('loyalty.campaigns.form', compact('campaign'));
    }

    public function update(Request $request, LoyaltyCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);

        $data = $this->validateData($request);
        $data['active'] = (bool) $request->boolean('active');
        $campaign->update($data);

        return redirect()->route('loyalty.campaigns.index')->with('success', 'Campaña actualizada.');
    }

    public function destroy(LoyaltyCampaign $campaign)
    {
        $this->authorizeCampaign($campaign);
        $campaign->delete();

        return back()->with('success', 'Campaña eliminada.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'       => 'required|string|max:255',
            'multiplier' => 'required|numeric|min:1|max:99',
            'starts_at'  => 'required|date',
            'ends_at'    => 'required|date|after_or_equal:starts_at',
        ]);
    }

    private function authorizeCampaign(LoyaltyCampaign $campaign): void
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $campaign->company_id !== $user->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
