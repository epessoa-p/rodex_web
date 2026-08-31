<?php

namespace App\Http\Controllers\Workshop;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Workshop\Mechanic;
use App\Services\Workshop\MechanicPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pago a mecánicos (web): liquidación de comisiones y registro de pagos desde
 * caja o tesorería.
 */
class MechanicPaymentController extends Controller
{
    use ResolvesCashSession;

    public function __construct(private MechanicPaymentService $service) {}

    public function index()
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;

        $mechanics = $this->service->summary($companyId);

        return view('workshop.mechanic-payments.index', compact('mechanics'));
    }

    public function show(Mechanic $mechanic)
    {
        $this->authorizeMechanic($mechanic);

        $detail   = $this->service->detail($mechanic);
        $accounts = TreasuryAccount::where('active', true)->orderBy('name')->get();
        $hasCash  = $this->currentOpenSession() !== null;

        return view('workshop.mechanic-payments.show', [
            'mechanic' => $mechanic,
            'summary'  => $detail['mechanic'],
            'pending'  => $detail['pending'],
            'payments' => $detail['payments'],
            'accounts' => $accounts,
            'hasCash'  => $hasCash,
        ]);
    }

    /** Comprobante imprimible de un pago. */
    public function receipt(\App\Models\Workshop\MechanicPayment $payment)
    {
        $this->authorizeMechanic($payment->mechanic);

        $payment->load(['mechanic', 'treasuryAccount', 'workOrders' => function ($q) {
            $q->orderBy('code');
        }]);
        $company = auth()->user()->getCurrentCompany();

        return view('workshop.mechanic-payments.receipt', compact('payment', 'company'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;

        $data = $request->validate([
            'mechanic_id'         => ['required', Rule::exists('mechanics', 'id')->where('company_id', $companyId)],
            'work_order_ids'      => ['nullable', 'array'],
            'work_order_ids.*'    => ['integer', Rule::exists('work_orders', 'id')->where('company_id', $companyId)],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'payment_source'      => ['required', 'in:cash,treasury'],
            'treasury_account_id' => ['nullable', 'required_if:payment_source,treasury', Rule::exists('treasury_accounts', 'id')->where('company_id', $companyId)],
            'method'              => ['nullable', 'string', 'max:30'],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ]);

        $mechanic = Mechanic::findOrFail($data['mechanic_id']);
        $orderIds = $data['work_order_ids'] ?? [];
        $amount   = (float) $data['amount'];

        $session = null;
        if ($data['payment_source'] === 'cash') {
            $session = $this->currentOpenSession();
            if (! $session) {
                return back()->withErrors(['error' => 'Necesitas tu caja abierta para pagar en efectivo.']);
            }
        }

        $account = null;
        if ($data['payment_source'] === 'treasury') {
            $account = TreasuryAccount::find($data['treasury_account_id']);
            if ($account && $amount > (float) $account->balance) {
                return back()->withErrors(['error' => 'El monto supera el saldo disponible de la cuenta.']);
            }
        }

        $this->service->pay(
            $mechanic, $orderIds, $amount, $data['payment_source'], $account, $session,
            $data['method'] ?? null, $data['notes'] ?? null
        );

        return redirect()->route('workshop.mechanic-payments.show', $mechanic)
            ->with('success', "Pago a {$mechanic->name} registrado.");
    }

    private function authorizeMechanic(Mechanic $mechanic): void
    {
        if ($mechanic->company_id !== auth()->user()->getCurrentCompany()?->id
            && ! auth()->user()->is_super_admin) {
            abort(403);
        }
    }
}
