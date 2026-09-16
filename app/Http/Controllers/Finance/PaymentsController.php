<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\Purchases\TreasuryAccount;
use App\Services\Expenses\ExpenseException;
use App\Services\Expenses\ExpenseRecorder;
use App\Support\PaymentsTabs;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Hub "Pagos" (Finanzas), igual que en el móvil: Mecánicos · Proveedores ·
 * Personal · Gastos. Mecánicos y Proveedores son las pantallas existentes
 * (pago a mecánicos, cuentas por pagar) con la misma barra de tabs; Personal
 * y Gastos se renderizan aquí sobre ExpenseRecorder (compartido con la API).
 */
class PaymentsController extends Controller
{
    use ResolvesCashSession;

    public function __construct(private ExpenseRecorder $recorder) {}

    /** Entrada del menú: abre el primer tab que el usuario puede ver. */
    public function index()
    {
        $first = PaymentsTabs::first(auth()->user());

        return $first
            ? redirect()->route($first['route'])
            : redirect()->route('dashboard')->withErrors(['error' => 'No tienes acceso a ningún tab de Pagos.']);
    }

    public function personal()
    {
        return view('finance.payments.personal', $this->tabData('personal'));
    }

    public function expenses()
    {
        return view('finance.payments.expenses', $this->tabData('expenses'));
    }

    /** Registra un gasto / pago a personal desde la web (caja abierta o tesorería). */
    public function store(Request $request)
    {
        $cid = auth()->user()->getCurrentCompany()?->id;

        $data = $request->validate([
            'kind'                => ['required', Rule::in(['service', 'other', 'transport', 'payroll'])],
            'expense_service_id'  => ['nullable', Rule::exists('expense_services', 'id')->where('company_id', $cid)],
            'personal_id'         => ['nullable', 'required_if:kind,payroll', Rule::exists('personals', 'id')->where('company_id', $cid)],
            'concept'             => ['nullable', 'string', 'max:255'],
            'period'              => ['nullable', 'string', 'max:30'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'payment_source'      => ['required', Rule::in(['cash', 'treasury'])],
            'treasury_account_id' => ['nullable', 'required_if:payment_source,treasury', Rule::exists('treasury_accounts', 'id')->where('company_id', $cid)],
            'method'              => ['nullable', 'string', 'max:30'],
            'notes'               => ['nullable', 'string', 'max:500'],
            'back'                => ['nullable', Rule::in(['personal', 'expenses'])],
        ], [
            'personal_id.required_if'         => 'Elige al personal.',
            'treasury_account_id.required_if' => 'Elige la cuenta de tesorería.',
        ]);

        $back = route($data['back'] === 'personal' ? 'payments.personal' : 'payments.expenses');

        try {
            $r = $this->recorder->record($data, $cid, auth()->id(), $this->currentOpenSession());
        } catch (ExpenseException $e) {
            return redirect($back)->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect($back)->with('success', "Registrado: {$r['description']} · " . money($r['amount']) . " ({$r['source']}).");
    }

    /** Datos comunes de Personal y Gastos: resumen + origen de pago disponible. */
    private function tabData(string $tab): array
    {
        $session = $this->currentOpenSession();

        return [
            'tab'      => $tab,
            'overview' => $this->recorder->overview(),
            'session'  => $session,
            'accounts' => TreasuryAccount::where('active', true)->orderBy('name')->get(['id', 'name', 'balance']),
            'canPay'   => auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('cash.operate', auth()->user()->getCurrentCompany()),
        ];
    }
}
