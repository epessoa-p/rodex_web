<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sales\Concerns\ResolvesCashSession;
use App\Models\ExpenseService;
use App\Services\Expenses\ExpenseException;
use App\Services\Expenses\ExpenseRecorder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Gastos desde el móvil (Pagos → Gastos / Personal): servicios recurrentes del
 * catálogo (luz, agua, internet…), gastos libres, transporte y pago a personal,
 * saliendo de la caja abierta o de una cuenta de tesorería.
 *
 * La lógica vive en ExpenseRecorder (compartida con el hub "Pagos" de la web).
 */
class ExpenseController extends Controller
{
    use ResolvesCashSession;

    public function __construct(private ExpenseRecorder $recorder) {}

    /** Todo lo que necesitan los tabs Gastos y Personal, en un request. */
    public function overview()
    {
        return response()->json(['data' => $this->recorder->overview()]);
    }

    /** Registra un gasto (servicio / otro / transporte / nómina) desde caja o tesorería. */
    public function store(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

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
        ]);

        try {
            $result = $this->recorder->record($data, $cid, auth()->id(), $this->currentOpenSession());
        } catch (ExpenseException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => $e->businessCode()], 422);
        }

        return response()->json(['data' => $result], 201);
    }

    /** Alta rápida de un servicio recurrente (luz, agua, internet…) desde el móvil. */
    public function storeService(Request $request)
    {
        $cid = $request->attributes->get('tenant_company')?->id;

        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'type'           => ['required', Rule::in(array_keys(ExpenseService::TYPES))],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $s = ExpenseService::create([
            'company_id'     => $cid,
            'name'           => trim($data['name']),
            'type'           => $data['type'],
            'default_amount' => (float) ($data['default_amount'] ?? 0),
            'active'         => true,
            'created_by'     => auth()->id(),
        ]);

        return response()->json(['data' => [
            'id'             => $s->id,
            'name'           => $s->name,
            'type'           => $s->type,
            'type_label'     => $s->type_label,
            'default_amount' => (float) $s->default_amount,
            'paid_this_month' => null,
        ]], 201);
    }
}
