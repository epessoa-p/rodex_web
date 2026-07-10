<?php

namespace App\Http\Controllers\Motos;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\HandlesSaleCreation;
use App\Models\Client;
use App\Models\Motos\MotoUnit;
use App\Models\Sales\PaymentPlan;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MotoSaleController extends Controller
{
    use HandlesSaleCreation;

    /** Ventas de motos (lista) */
    public function index()
    {
        $user = auth()->user();
        $cid  = $user->is_super_admin ? null : $user->getCurrentCompany()?->id;

        $query = Sale::with(['client', 'motoUnit.model.brand'])
            ->where('sale_category', 'moto')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest();

        // ¿Puede ver TODAS las ventas de motos? Sin el permiso, solo las que registró.
        $canAllRecords = $user->is_super_admin
            || $user->hasPermissionInCompany('moto-sales.view-all-records', $user->getCurrentCompany());
        if (!$canAllRecords) {
            $query->where('created_by', $user->id);
        }

        $sales = $query->paginate(15);

        return view('motos.sales.index', compact('sales', 'canAllRecords'));
    }

    public function create()
    {
        return view('motos.sales.create', $this->formData());
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $request->validate([
            'moto_unit_id'   => 'required|exists:moto_units,id',
            'client_id'      => 'required|exists:clients,id',
            'sale_type'      => 'required|in:cash,credit',
            'sale_date'      => 'required|date',
            'price'          => 'required|numeric|min:0.01',
            'interest'       => 'nullable|numeric|min:0',
            'payment_plan_id'=> 'nullable|exists:payment_plans,id',
            'notes'          => 'nullable|string',
            'installments'             => 'nullable|array',
            'installments.*.due_date'  => 'required_with:installments|date',
            'installments.*.amount'    => 'required_with:installments|numeric|min:0.01',
            'down_payment'             => 'nullable|numeric|min:0',
        ]);

        $unit = MotoUnit::find($validated['moto_unit_id']);
        if (!$unit || $unit->company_id !== $companyId) {
            return back()->withInput()->withErrors(['error' => 'Unidad no válida.']);
        }
        if ($unit->status !== 'disponible') {
            return back()->withInput()->withErrors(['error' => 'La unidad ya no está disponible (estado: ' . $unit->status_label . ').']);
        }

        // Caja para contado o enganche
        $needsCash = $validated['sale_type'] === 'cash' || (float) ($validated['down_payment'] ?? 0) > 0;
        $session   = $needsCash ? $this->currentOpenSession() : null;
        if ($validated['sale_type'] === 'cash' && !$session) {
            return back()->withInput()->withErrors(['error' => 'Para vender al contado necesitas tener tu caja abierta.']);
        }
        if ($validated['sale_type'] === 'credit' && empty($validated['installments'])) {
            return back()->withInput()->withErrors(['installments' => 'Una venta a crédito requiere al menos una cuota.']);
        }

        try {
            $sale = DB::transaction(function () use ($validated, $companyId, $unit, $session) {
                $price    = (float) $validated['price'];
                $interest = (float) ($validated['interest'] ?? 0);
                $total    = $price + $interest;
                $saleType = $validated['sale_type'];

                $sale = Sale::create([
                    'company_id'            => $companyId,
                    'branch_id'             => $unit->branch_id,
                    'client_id'             => $validated['client_id'],
                    'moto_unit_id'          => $unit->id,
                    'cash_register_session_id' => $session?->id,
                    'code'                  => $this->nextSaleCode($companyId),
                    'sale_type'             => $saleType,
                    'sale_category'         => 'moto',
                    'payment_plan_id'       => $validated['payment_plan_id'] ?? null,
                    'sale_date'             => $validated['sale_date'],
                    'subtotal'              => $price,
                    'discount'              => 0,
                    'tax'                   => 0,
                    'interest'              => $interest,
                    'total'                 => $total,
                    'paid_amount'           => 0,
                    'payment_status'        => 'pending',
                    'status'                => 'completed',
                    'notes'                 => $validated['notes'] ?? ('Venta de moto · ' . $unit->display_name),
                    'created_by'            => auth()->id(),
                ]);

                // Marcar unidad vendida
                $unit->update(['status' => 'vendida', 'sale_id' => $sale->id]);

                // Cobro
                if ($saleType === 'cash') {
                    $this->registerSalePayment($sale, null, $total, $session, [
                        'method' => 'efectivo', 'notes' => 'Pago de contado (moto)',
                    ]);
                } else {
                    $number = 1;
                    foreach ($validated['installments'] as $inst) {
                        SaleInstallment::create([
                            'company_id'  => $companyId,
                            'sale_id'     => $sale->id,
                            'number'      => $number++,
                            'due_date'    => $inst['due_date'],
                            'amount'      => (float) $inst['amount'],
                            'paid_amount' => 0,
                            'status'      => 'pending',
                        ]);
                    }
                    $down = (float) ($validated['down_payment'] ?? 0);
                    if ($down > 0) {
                        $this->registerSalePayment($sale, null, $down, $session, [
                            'method' => 'efectivo', 'notes' => 'Pago inicial (moto)',
                        ]);
                    }
                }

                $sale->refresh()->recalcPaymentStatus();

                // Fidelización: acreditar puntos por la venta de moto
                app(\App\Services\Loyalty\LoyaltyService::class)->award($sale);

                return $sale;
            });

            return redirect()->route('sales.show', $sale)->with('success', 'Venta de moto registrada: ' . $sale->code);
        } catch (\Throwable $e) {
            Log::error('Error al vender moto', ['msg' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    private function formData(): array
    {
        $cid = auth()->user()->getCurrentCompany()?->id;
        $units = MotoUnit::with('model.brand')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->where('status', 'disponible')->orderBy('id')->get();
        $clients = Client::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('full_name')->get();
        $plans   = PaymentPlan::when($cid, fn ($q) => $q->where('company_id', $cid))->where('active', true)->orderBy('name')->get();
        return compact('units', 'clients', 'plans');
    }
}
