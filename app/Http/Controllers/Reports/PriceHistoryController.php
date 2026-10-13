<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Inventory\ProductPriceChange;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\PriceAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Cambios de precio y costo (auditoría del dueño): quién cambió qué, cuándo y
 * desde dónde, con alertas de "bajó, vendió y volvió a subir".
 */
class PriceHistoryController extends Controller
{
    public function index(Request $request, PriceAuditService $audit)
    {
        $data = $request->validate([
            'from'       => ['nullable', 'date'],
            'to'         => ['nullable', 'date'],
            'user_id'    => ['nullable', 'integer'],
            'product_id' => ['nullable', 'integer'],
            'field'      => ['nullable', 'in:price,cost'],
        ]);

        $company = auth()->user()->getCurrentCompany();
        abort_unless($company, 403, 'Elige una empresa.');

        $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::today()->subDays(29);
        $to   = isset($data['to']) ? Carbon::parse($data['to']) : Carbon::today();
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        // Antes de correr el SQL la tabla no existe: la vista lo explica.
        $ready = Schema::hasTable('product_price_changes');

        $changes = collect();
        $alerts  = collect();
        $users   = collect();
        if ($ready) {
            $changes = ProductPriceChange::with(['product:id,name,sku', 'user:id,name'])
                ->where('company_id', $company->id)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->when($data['user_id'] ?? null, fn ($q, $v) => $q->where('user_id', $v))
                ->when($data['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
                ->when($data['field'] ?? null, fn ($q, $v) => $q->where('field', $v))
                ->latest('created_at')->latest('id')
                ->paginate(50)->withQueryString();

            $alerts = $audit->suspicious($company->id, $from, $to)
                ->when($data['product_id'] ?? null, fn ($c, $v) => $c->filter(fn ($a) => $a['product']?->id === (int) $v))
                ->when($data['user_id'] ?? null, fn ($c, $v) => $c->filter(fn ($a) => in_array((int) $v, [$a['drop']->user_id, $a['restore']->user_id], true)))
                ->values();

            // Solo quienes hicieron cambios (para el filtro).
            $users = User::whereIn('id', ProductPriceChange::where('company_id', $company->id)->whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')->get(['id', 'name']);
        }

        $product = ! empty($data['product_id']) ? Product::find($data['product_id']) : null;

        return view('reports.price-history', [
            'ready'   => $ready,
            'changes' => $changes,
            'alerts'  => $alerts,
            'users'   => $users,
            'product' => $product,
            'filters' => $data + ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'window'  => PriceAuditService::WINDOW_DAYS,
        ]);
    }
}
