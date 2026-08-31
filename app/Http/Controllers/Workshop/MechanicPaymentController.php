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
        $accounts  = TreasuryAccount::where('active', true)->orderBy('name')->get();
        $hasCash   = $this->currentOpenSession() !== null;

        return view('workshop.mechanic-payments.index', compact('mechanics', 'accounts', 'hasCash'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->getCurrentCompany()?->id;

        $data = $request->validate([
            'mechanic_id'         => ['required', Rule::exists('mechanics', 'id')->where('company_id', $companyId)],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'payment_source'      => ['required', 'in:cash,treasury'],
            'treasury_account_id' => ['nullable', 'required_if:payment_source,treasury', Rule::exists('treasury_accounts', 'id')->where('company_id', $companyId)],
            'method'              => ['nullable', 'string', 'max:30'],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ]);

        $mechanic = Mechanic::findOrFail($data['mechanic_id']);
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
            $mechanic, $amount, $data['payment_source'], $account, $session,
            $data['method'] ?? null, $data['notes'] ?? null
        );

        return back()->with('success', "Pago a {$mechanic->name} registrado.");
    }
}
