<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Client;
use App\Models\Product;
use App\Models\Sales\Quote;
use App\Models\Sales\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class QuoteController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
        $query = Quote::with(['client', 'branch'])->latest();

        if (!$user->is_super_admin) {
            $query->where('company_id', $user->getCurrentCompany()?->id);
        }
        if ($status = request('status')) {
            $query->where('status', $status);
        }

        return view('sales.quotes.index', ['quotes' => $query->paginate(15)->withQueryString()]);
    }

    public function create()
    {
        return view('sales.quotes.create', $this->formData());
    }

    /** Punto de Cotización (POC) — pantalla estilo POS */
    public function pos()
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;

        $products = Product::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->with(['category', 'brand', 'photos', 'motoModels.brand'])
            ->orderBy('name')
            ->get();

        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'id_number']);

        $branches = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get();

        $categories = \App\Models\Inventory\ProductCategory::when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('active', true)->orderBy('name')->get(['id', 'name']);

        return view('sales.quotes.pos', compact('products', 'clients', 'branches', 'categories'));
    }

    public function store(Request $request)
    {
        $user      = auth()->user();
        $companyId = $user->getCurrentCompany()?->id;

        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $this->validateQuote($request);

        try {
            $quote = DB::transaction(function () use ($validated, $companyId) {
                $subtotal = collect($validated['items'])
                    ->sum(fn ($i) => ((float) $i['quantity'] * (float) $i['unit_price']) - (float) ($i['discount'] ?? 0));
                $discount = (float) ($validated['discount'] ?? 0);
                $tax      = (float) ($validated['tax'] ?? 0);

                $quote = Quote::create([
                    'company_id'  => $companyId,
                    'branch_id'   => $validated['branch_id'] ?? null,
                    'client_id'   => $validated['client_id'] ?? null,
                    'code'        => $this->nextCode($companyId),
                    'status'      => 'draft',
                    'quote_date'  => $validated['quote_date'],
                    'valid_until' => $validated['valid_until'] ?? null,
                    'subtotal'    => $subtotal,
                    'discount'    => $discount,
                    'tax'         => $tax,
                    'total'       => max(0, $subtotal - $discount + $tax),
                    'notes'       => $validated['notes'] ?? null,
                    'created_by'  => auth()->id(),
                ]);

                $this->syncItems($quote, $validated['items']);
                return $quote;
            });

            return redirect()->route('quotes.show', $quote)->with('success', 'Cotización creada: ' . $quote->code);
        } catch (\Throwable $e) {
            Log::error('Error al crear cotización', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show(Quote $quote)
    {
        $this->authorizeQuote($quote);
        $quote->load(['client', 'branch', 'createdBy', 'items.product', 'convertedSale']);
        return view('sales.quotes.show', compact('quote'));
    }

    public function edit(Quote $quote)
    {
        $this->authorizeQuote($quote);
        if ($quote->status === 'converted') {
            return back()->withErrors(['error' => 'No se puede editar una cotización ya convertida en venta.']);
        }
        $quote->load('items.product');
        return view('sales.quotes.edit', array_merge($this->formData(), compact('quote')));
    }

    public function update(Request $request, Quote $quote)
    {
        $this->authorizeQuote($quote);
        if ($quote->status === 'converted') {
            return back()->withErrors(['error' => 'No se puede editar una cotización ya convertida.']);
        }

        $validated = $this->validateQuote($request);

        try {
            DB::transaction(function () use ($validated, $quote) {
                $subtotal = collect($validated['items'])
                    ->sum(fn ($i) => ((float) $i['quantity'] * (float) $i['unit_price']) - (float) ($i['discount'] ?? 0));
                $discount = (float) ($validated['discount'] ?? 0);
                $tax      = (float) ($validated['tax'] ?? 0);

                $quote->update([
                    'branch_id'   => $validated['branch_id'] ?? null,
                    'client_id'   => $validated['client_id'] ?? null,
                    'quote_date'  => $validated['quote_date'],
                    'valid_until' => $validated['valid_until'] ?? null,
                    'subtotal'    => $subtotal,
                    'discount'    => $discount,
                    'tax'         => $tax,
                    'total'       => max(0, $subtotal - $discount + $tax),
                    'notes'       => $validated['notes'] ?? null,
                ]);

                $quote->items()->delete();
                $this->syncItems($quote, $validated['items']);
            });

            return redirect()->route('quotes.show', $quote)->with('success', 'Cotización actualizada.');
        } catch (\Throwable $e) {
            Log::error('Error al actualizar cotización', ['id' => $quote->id, 'msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function changeStatus(Quote $quote, Request $request)
    {
        $this->authorizeQuote($quote);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['sent', 'accepted', 'rejected', 'expired'])],
        ]);

        if ($quote->status === 'converted') {
            return back()->withErrors(['error' => 'La cotización ya fue convertida.']);
        }

        $quote->update(['status' => $validated['status']]);
        return back()->with('success', 'Estado actualizado a: ' . $quote->status_label);
    }

    public function convert(Quote $quote)
    {
        $this->authorizeQuote($quote);

        if (!$quote->isConvertible()) {
            return back()->withErrors(['error' => 'Esta cotización no se puede convertir (estado: ' . $quote->status_label . ').']);
        }

        // Redirige al formulario de ventas precargado
        return redirect()->route('sales.create', ['quote_id' => $quote->id]);
    }

    public function destroy(Quote $quote)
    {
        $this->authorizeQuote($quote);
        if ($quote->status === 'converted') {
            return back()->withErrors(['error' => 'No se puede eliminar una cotización convertida.']);
        }
        $quote->delete();
        return redirect()->route('quotes.index')->with('success', 'Cotización eliminada.');
    }

    // ── Helpers ───────────────────────────────────────────────

    private function validateQuote(Request $request): array
    {
        return $request->validate([
            'branch_id'          => 'nullable|exists:branches,id',
            'client_id'          => 'nullable|exists:clients,id',
            'quote_date'         => 'required|date',
            'valid_until'        => 'nullable|date',
            'discount'           => 'nullable|numeric|min:0',
            'tax'                => 'nullable|numeric|min:0',
            'notes'              => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount'   => 'nullable|numeric|min:0',
        ]);
    }

    private function syncItems(Quote $quote, array $items): void
    {
        foreach ($items as $i) {
            $qty   = (float) $i['quantity'];
            $price = (float) $i['unit_price'];
            $disc  = (float) ($i['discount'] ?? 0);
            QuoteItem::create([
                'quote_id'   => $quote->id,
                'product_id' => $i['product_id'],
                'quantity'   => $qty,
                'unit_price' => $price,
                'discount'   => $disc,
                'subtotal'   => max(0, $qty * $price - $disc),
            ]);
        }
    }

    private function nextCode(int $companyId): string
    {
        $count = Quote::withTrashed()->where('company_id', $companyId)->count() + 1;
        return 'QT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeQuote(Quote $quote): void
    {
        if (!auth()->user()->is_super_admin && $quote->company_id !== auth()->user()->getCurrentCompany()?->id) {
            abort(403);
        }
    }

    private function formData(): array
    {
        $user = auth()->user();
        $cid  = $user->getCurrentCompany()?->id;

        $branches = Branch::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        $clients  = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('full_name')->get();
        $products = Product::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();

        return compact('branches', 'clients', 'products');
    }
}
