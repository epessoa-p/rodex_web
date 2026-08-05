<?php

namespace App\Http\Controllers\Loyalty;

use App\Http\Controllers\Controller;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LoyaltyRewardController extends Controller
{
    public function index()
    {
        $cid = $this->companyScope();

        $rewards = LoyaltyReward::with('product:id,name')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderByDesc('active')
            ->orderBy('points_cost')
            ->get();

        $rewardProductIds = $rewards->pluck('product_id')->filter()->values()->all();

        // Productos disponibles con su imagen principal y stock.
        $products = Product::with('photos')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id'    => $p->id,
                'name'  => $p->name,
                'image' => $p->mainPhoto()?->url,
                'stock' => (float) $p->current_stock,
            ])
            ->values();

        // Link público del catálogo (solo cuando hay empresa seleccionada)
        $catalogUrl = null;
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if ($companyId) {
            $token = \App\Models\Loyalty\LoyaltySetting::forCompany($companyId)->ensurePublicToken();
            $catalogUrl = route('loyalty.catalog.public', $token);
        }

        return view('loyalty.rewards.index', compact('rewards', 'products', 'rewardProductIds', 'catalogUrl'));
    }

    /** Alta AJAX de una recompensa a partir de un producto (panel doble). */
    public function catalogStore(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        abort_unless($cid, 403, 'Selecciona una empresa.');

        $data = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'points_cost' => 'required|integer|min:1',
            'stock'       => 'nullable|integer|min:0',
        ]);

        $product = Product::with('photos')->where('company_id', $cid)->findOrFail($data['product_id']);

        if (LoyaltyReward::where('company_id', $cid)->where('product_id', $product->id)->exists()) {
            return response()->json(['ok' => false, 'message' => 'Este producto ya está en el catálogo.'], 422);
        }

        $reward = LoyaltyReward::create([
            'company_id'  => $cid,
            'created_by'  => auth()->id(),
            'product_id'  => $product->id,
            'name'        => $product->name,
            'description' => $product->description,
            'image'       => $product->mainPhoto()?->file_path,
            'points_cost' => $data['points_cost'],
            'stock'       => $data['stock'] ?? null,
            'active'      => true,
        ]);

        return response()->json(['ok' => true, 'message' => 'Recompensa agregada.', 'reward' => $this->rewardJson($reward)]);
    }

    /** Edición AJAX de puntos / stock / activo. */
    public function catalogUpdate(Request $request, LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);

        $request->validate([
            'points_cost' => 'required|integer|min:1',
            'stock'       => 'nullable|integer|min:0',
            'active'      => 'nullable|boolean',
        ]);

        $reward->update([
            'points_cost' => $request->integer('points_cost'),
            'stock'       => $request->input('stock') === null || $request->input('stock') === '' ? null : $request->integer('stock'),
            'active'      => $request->boolean('active'),
        ]);

        return response()->json(['ok' => true, 'message' => 'Recompensa actualizada.', 'reward' => $this->rewardJson($reward)]);
    }

    private function rewardJson(LoyaltyReward $reward): array
    {
        return [
            'id'          => $reward->id,
            'product_id'  => $reward->product_id,
            'name'        => $reward->name,
            'image_url'   => $reward->image_url,
            'points_cost' => $reward->points_cost,
            'stock'       => $reward->stock,
            'active'      => (bool) $reward->active,
        ];
    }

    public function create()
    {
        return view('loyalty.rewards.form', [
            'reward'   => new LoyaltyReward(['active' => true]),
            'products' => $this->products(),
        ]);
    }

    public function store(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        abort_unless($cid, 403, 'Selecciona una empresa.');

        $data = $this->validateData($request);

        $data['company_id'] = $cid;
        $data['created_by'] = auth()->id();
        $data['active']     = (bool) $request->boolean('active');
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store("company/{$cid}/loyalty/rewards", 'public');
        }

        LoyaltyReward::create($data);

        return redirect()->route('loyalty.rewards.index')->with('success', 'Recompensa creada.');
    }

    public function edit(LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);

        return view('loyalty.rewards.form', [
            'reward'   => $reward,
            'products' => $this->products(),
        ]);
    }

    public function update(Request $request, LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);

        $data = $this->validateData($request);
        $data['active'] = (bool) $request->boolean('active');

        if ($request->hasFile('image')) {
            if ($reward->image) {
                Storage::disk('public')->delete($reward->image);
            }
            $data['image'] = $request->file('image')->store("company/{$reward->company_id}/loyalty/rewards", 'public');
        }

        $reward->update($data);

        return redirect()->route('loyalty.rewards.index')->with('success', 'Recompensa actualizada.');
    }

    public function destroy(LoyaltyReward $reward)
    {
        $this->authorizeReward($reward);
        $productId = $reward->product_id;
        $reward->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Recompensa quitada del catálogo.', 'product_id' => $productId]);
        }
        return back()->with('success', 'Recompensa eliminada.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'points_cost' => 'required|integer|min:1',
            'product_id'  => 'nullable|exists:products,id',
            'stock'       => 'nullable|integer|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);
    }

    private function products()
    {
        $cid = $this->companyScope();
        return Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeReward(LoyaltyReward $reward): void
    {
        $user = auth()->user();
        if (!$user->is_super_admin && $reward->company_id !== $user->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function companyScope(): ?int
    {
        $user = auth()->user();
        return $user->is_super_admin ? null : $user->getCurrentCompany()?->id;
    }
}
