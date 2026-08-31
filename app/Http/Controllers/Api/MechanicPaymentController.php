<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\Purchases\TreasuryAccount;
use App\Models\Workshop\Mechanic;
use App\Services\Workshop\MechanicPaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Pago a mecánicos desde el móvil: liquidación de comisiones (ganado/pagado/
 * pendiente) y registro de pagos desde caja o tesorería.
 */
class MechanicPaymentController extends Controller
{
    use ResolvesCashSession;

    public function __construct(private MechanicPaymentService $service) {}

    /** Resumen por mecánico. */
    public function summary(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')->id;

        return response()->json([
            'data' => ['mechanics' => $this->service->summary($cid)],
        ]);
    }

    /** Registra un pago a un mecánico. */
    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')->id;

        $data = $request->validate([
            'mechanic_id'         => ['required', Rule::exists('mechanics', 'id')->where('company_id', $cid)],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'payment_source'      => ['required', 'in:cash,treasury'],
            'treasury_account_id' => ['nullable', 'required_if:payment_source,treasury', Rule::exists('treasury_accounts', 'id')->where('company_id', $cid)],
            'method'              => ['nullable', 'string', 'max:30'],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ]);

        $mechanic = Mechanic::findOrFail($data['mechanic_id']);
        $amount   = (float) $data['amount'];

        // Origen caja: requiere sesión abierta.
        $session = null;
        if ($data['payment_source'] === 'cash') {
            $session = $this->currentOpenSession();
            if (! $session) {
                return response()->json([
                    'message' => 'Necesitas tu caja abierta para pagar en efectivo.',
                    'code'    => 'cash_session_required',
                ], 422);
            }
        }

        // Origen tesorería: valida saldo.
        $account = null;
        if ($data['payment_source'] === 'treasury') {
            $account = TreasuryAccount::find($data['treasury_account_id']);
            if ($account && $amount > (float) $account->balance) {
                return response()->json([
                    'message' => 'El monto supera el saldo disponible de la cuenta.',
                    'code'    => 'insufficient_balance',
                ], 422);
            }
        }

        $this->service->pay(
            $mechanic, $amount, $data['payment_source'], $account, $session,
            $data['method'] ?? null, $data['notes'] ?? null
        );

        // Devuelve el pendiente actualizado del mecánico.
        $row = $this->service->summary($cid)->firstWhere('id', $mechanic->id);

        return response()->json(['data' => ['mechanic' => $row]], 201);
    }
}
