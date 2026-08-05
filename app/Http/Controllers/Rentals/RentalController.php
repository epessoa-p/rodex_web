<?php

namespace App\Http\Controllers\Rentals;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Rentals\Concerns\HandlesRentalCharge;
use App\Models\Client;
use App\Models\Motos\MotoUnit;
use App\Models\Rentals\RentalContract;
use App\Models\Rentals\RentalInspection;
use App\Models\Rentals\RentalInspectionPhoto;
use App\Models\Rentals\RentalInstallment;
use App\Models\Rentals\RentalPayment;
use App\Models\Rentals\RentalPenalty;
use App\Models\Workshop\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RentalController extends Controller
{
    use HandlesRentalCharge;

    private function companyId(): ?int
    {
        return auth()->user()->getCurrentCompany()?->id;
    }

    private function scopedContracts()
    {
        $cid = auth()->user()->is_super_admin ? null : $this->companyId();
        return RentalContract::query()
            ->with(['client', 'motoUnit.model.brand'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid));
    }

    // ── Reservas ──────────────────────────────────────────────
    public function reservations(Request $request)
    {
        $contracts = $this->scopedContracts()
            ->where('status', 'reservada')
            ->orderBy('start_date')
            ->paginate(15);

        return view('rentals.reservations.index', compact('contracts'));
    }

    public function create()
    {
        return view('rentals.reservations.create', $this->formData());
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();
        if (!$companyId) {
            return back()->withInput()->withErrors(['error' => 'No hay una empresa activa.']);
        }

        $validated = $request->validate([
            'moto_unit_id'   => 'required|exists:moto_units,id',
            'client_id'      => 'required|exists:clients,id',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'payment_mode'   => 'required|in:renta,unico',
            'daily_rate'     => 'nullable|numeric|min:0',
            'deposit'        => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            // Renta periódica
            'billing_period'              => 'required_if:payment_mode,renta|in:diario,semanal,mensual',
            'period_amount'               => 'required_if:payment_mode,renta|nullable|numeric|min:0',
            'late_fee_per_day'            => 'nullable|numeric|min:0',
            'installments'                => 'required_if:payment_mode,renta|array|min:1',
            'installments.*.due_date'     => 'required_with:installments|date',
            'installments.*.amount'       => 'required_with:installments|numeric|min:0.01',
            'installments.*.period_label' => 'nullable|string|max:60',
            'installments.*.period_start' => 'nullable|date',
            'installments.*.period_end'   => 'nullable|date',
        ]);

        $unit = MotoUnit::find($validated['moto_unit_id']);
        if (!$unit || $unit->company_id !== $companyId) {
            return back()->withInput()->withErrors(['error' => 'Unidad no válida.']);
        }
        if (!in_array($unit->status, ['disponible'], true)) {
            return back()->withInput()->withErrors(['moto_unit_id' => 'La unidad no está disponible (estado: ' . $unit->status_label . ').']);
        }

        // Validar solapamiento de fechas para esa unidad
        if ($this->hasOverlap($unit->id, $validated['start_date'], $validated['end_date'])) {
            return back()->withInput()->withErrors(['start_date' => 'La moto ya tiene un alquiler en ese rango de fechas.']);
        }

        $start  = Carbon::parse($validated['start_date']);
        $end    = Carbon::parse($validated['end_date']);
        $days   = max(1, $start->diffInDays($end) + 1);
        $isRenta = $validated['payment_mode'] === 'renta';
        $rate   = (float) ($validated['daily_rate'] ?? 0);
        $deposit = (float) ($validated['deposit'] ?? 0);

        if ($isRenta) {
            $rentalTotal = collect($validated['installments'])->sum(fn ($i) => (float) $i['amount']);
        } else {
            $rentalTotal = $days * $rate;
        }

        $contract = DB::transaction(function () use ($validated, $companyId, $unit, $days, $rate, $rentalTotal, $deposit, $isRenta) {
            $contract = RentalContract::create([
                'company_id'      => $companyId,
                'branch_id'       => $unit->branch_id,
                'client_id'       => $validated['client_id'],
                'moto_unit_id'    => $unit->id,
                'code'            => $this->nextRentalCode($companyId),
                'status'          => 'reservada',
                'start_date'      => $validated['start_date'],
                'end_date'        => $validated['end_date'],
                'days'            => $days,
                'daily_rate'      => $rate,
                'payment_mode'    => $validated['payment_mode'],
                'billing_period'  => $isRenta ? $validated['billing_period'] : null,
                'period_amount'   => $isRenta ? (float) ($validated['period_amount'] ?? 0) : 0,
                'late_fee_per_day'=> $isRenta ? (float) ($validated['late_fee_per_day'] ?? 0) : 0,
                'rental_total'    => $rentalTotal,
                'deposit'         => $deposit,
                'penalties_total' => 0,
                'total'           => $rentalTotal,
                'paid_amount'     => 0,
                'payment_status'  => 'pendiente',
                'deposit_status'  => 'retenido',
                'deposit_refunded'=> 0,
                'notes'           => $validated['notes'] ?? null,
                'created_by'      => auth()->id(),
            ]);

            if ($isRenta) {
                foreach (array_values($validated['installments']) as $i => $inst) {
                    RentalInstallment::create([
                        'company_id'         => $companyId,
                        'rental_contract_id' => $contract->id,
                        'number'             => $i + 1,
                        'period_label'       => $inst['period_label'] ?? null,
                        'period_start'       => $inst['period_start'] ?? null,
                        'period_end'         => $inst['period_end'] ?? null,
                        'due_date'           => $inst['due_date'],
                        'amount'             => (float) $inst['amount'],
                        'paid_amount'        => 0,
                        'status'             => 'pendiente',
                    ]);
                }
            }

            $unit->update(['status' => 'reservada']);

            return $contract;
        });

        return redirect()->route('rentals.show', $contract)->with('success', 'Reserva ' . $contract->code . ' creada.');
    }

    // ── Contratos ─────────────────────────────────────────────
    public function contracts()
    {
        $contracts = $this->scopedContracts()
            ->where('status', 'contrato')
            ->orderBy('start_date')
            ->paginate(15);

        return view('rentals.contracts.index', compact('contracts'));
    }

    // ── Alquileres en curso (motos actualmente alquiladas) ────
    public function active()
    {
        $contracts = $this->scopedContracts()
            ->with('installments')
            ->withCount(['installments as pending_installments' => fn ($q) => $q->whereIn('status', ['pendiente', 'parcial'])])
            ->where('status', 'entregada')
            ->orderBy('end_date')
            ->paginate(15);

        return view('rentals.active.index', compact('contracts'));
    }

    public function confirm(RentalContract $rental)
    {
        $this->authorizeContract($rental);
        if ($rental->status !== 'reservada') {
            return back()->withErrors(['error' => 'Solo una reserva puede pasar a contrato.']);
        }
        $rental->update(['status' => 'contrato']);
        return back()->with('success', 'Reserva confirmada como contrato.');
    }

    // ── Entregas ──────────────────────────────────────────────
    public function deliveries()
    {
        $contracts = $this->scopedContracts()
            ->whereIn('status', ['reservada', 'contrato'])
            ->orderBy('start_date')
            ->paginate(15);

        return view('rentals.deliveries.index', compact('contracts'));
    }

    public function deliver(RentalContract $rental)
    {
        $this->authorizeContract($rental);
        $rental->load(['client', 'motoUnit.model.brand', 'payments']);
        return view('rentals.deliveries.create', compact('rental'));
    }

    public function storeDelivery(Request $request, RentalContract $rental)
    {
        $this->authorizeContract($rental);
        if (!in_array($rental->status, ['reservada', 'contrato'], true)) {
            return back()->withErrors(['error' => 'Este contrato no puede entregarse en su estado actual.']);
        }

        $validated = $request->validate([
            'delivery_mileage' => 'nullable|integer|min:0',
            'delivery_fuel'    => 'nullable|string|max:20',
            'delivery_notes'   => 'nullable|string',
            'collect_now'      => 'nullable|boolean',
            'method'           => 'nullable|string|max:30',
            'checklist'        => 'nullable|array',
            'photos'           => 'nullable|array',
            'photos.*'         => 'image|max:5120',
        ]);

        $collectNow = (bool) ($validated['collect_now'] ?? false);
        $session = $collectNow ? $this->currentOpenSession() : null;
        if ($collectNow && !$session) {
            return back()->withInput()->withErrors(['error' => 'Para cobrar al entregar necesitas tu caja abierta.']);
        }

        $isRenta = $rental->isRenta();

        try {
            DB::transaction(function () use ($validated, $request, $rental, $session, $collectNow, $isRenta) {
                $rental->update([
                    'status'           => 'entregada',
                    'delivered_at'     => now(),
                    'delivery_mileage' => $validated['delivery_mileage'] ?? null,
                    'delivery_fuel'    => $validated['delivery_fuel'] ?? null,
                    'delivery_notes'   => $validated['delivery_notes'] ?? null,
                    'cash_register_session_id' => $session?->id ?? $rental->cash_register_session_id,
                ]);

                $rental->motoUnit?->update(['status' => 'alquilada']);

                // Inspección de salida (checklist + fotos)
                $this->handleInspection($rental, 'salida', $request, $validated);

                if ($collectNow && $session) {
                    $meta = ['method' => $validated['method'] ?? 'efectivo'];
                    // En modalidad renta solo se cobra el depósito; las cuotas se cobran después.
                    if (!$isRenta && (float) $rental->rental_total > 0) {
                        $this->chargeToCaja($rental, 'alquiler', (float) $rental->rental_total, $session, $meta + ['notes' => 'Cobro de alquiler en entrega']);
                    }
                    // Cobro del depósito (ambas modalidades)
                    if ((float) $rental->deposit > 0) {
                        $this->chargeToCaja($rental, 'deposito', (float) $rental->deposit, $session, $meta + ['notes' => 'Depósito de garantía']);
                    }
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al entregar: ' . $e->getMessage()]);
        }

        $msg = $isRenta
            ? 'Moto entregada. Renta en curso — las cuotas se cobran en Cobros.'
            : 'Moto entregada. Alquiler en curso.';

        return redirect()->route('rentals.show', $rental)->with('success', $msg);
    }

    // ── Devoluciones ──────────────────────────────────────────
    public function returns()
    {
        $contracts = $this->scopedContracts()
            ->where('status', 'entregada')
            ->orderBy('end_date')
            ->paginate(15);

        return view('rentals.returns.index', compact('contracts'));
    }

    public function returnForm(RentalContract $rental)
    {
        $this->authorizeContract($rental);
        $rental->load(['client', 'motoUnit.model.brand', 'payments', 'penalties']);

        // Sugerencia de penalización por días extra
        $extraDays = 0;
        if ($rental->end_date && Carbon::today()->gt($rental->end_date)) {
            $extraDays = $rental->end_date->diffInDays(Carbon::today());
        }
        $suggestedLateFee = $extraDays * (float) $rental->daily_rate;

        return view('rentals.returns.create', compact('rental', 'extraDays', 'suggestedLateFee'));
    }

    public function storeReturn(Request $request, RentalContract $rental)
    {
        $this->authorizeContract($rental);
        if ($rental->status !== 'entregada') {
            return back()->withErrors(['error' => 'Solo un alquiler en curso puede devolverse.']);
        }

        $validated = $request->validate([
            'return_mileage'   => 'nullable|integer|min:0',
            'return_fuel'      => 'nullable|string|max:20',
            'return_notes'     => 'nullable|string',
            'late_fee'         => 'nullable|numeric|min:0',
            'damage_fee'       => 'nullable|numeric|min:0',
            'refund_deposit'   => 'nullable|boolean',
            'method'           => 'nullable|string|max:30',
            'needs_maintenance'=> 'nullable|boolean',
            'checklist'        => 'nullable|array',
            'photos'           => 'nullable|array',
            'photos.*'         => 'image|max:5120',
        ]);

        $session = $this->currentOpenSession();

        try {
            DB::transaction(function () use ($validated, $request, $rental, $session) {
                $lateFee   = (float) ($validated['late_fee'] ?? 0);
                $damageFee = (float) ($validated['damage_fee'] ?? 0);
                $method    = $validated['method'] ?? 'efectivo';

                // Registrar penalizaciones (suman a penalties_total y se cobran a caja)
                foreach ([['Mora por días extra', $lateFee], ['Daños / cargos', $damageFee]] as [$concept, $amount]) {
                    if ($amount > 0) {
                        RentalPenalty::create([
                            'company_id'         => $rental->company_id,
                            'rental_contract_id' => $rental->id,
                            'concept'            => $concept,
                            'amount'             => $amount,
                            'penalty_date'       => now()->toDateString(),
                            'created_by'         => auth()->id(),
                        ]);
                        $rental->increment('penalties_total', $amount);
                    }
                }
                $rental->refresh()->recalcTotals();

                $rental->update([
                    'status'         => 'devuelta',
                    'returned_at'    => now(),
                    'return_mileage' => $validated['return_mileage'] ?? null,
                    'return_fuel'    => $validated['return_fuel'] ?? null,
                    'return_notes'   => $validated['return_notes'] ?? null,
                ]);

                // Inspección de entrada (checklist + fotos)
                $this->handleInspection($rental, 'entrada', $request, $validated);

                // Liquidación del depósito: devolver depósito − penalizaciones
                $refund = (bool) ($validated['refund_deposit'] ?? true);
                if ($refund && (float) $rental->deposit > 0) {
                    $penalties = (float) $rental->penalties_total;
                    $refundAmount = max(0, (float) $rental->deposit - $penalties);
                    $depositStatus = 'devuelto';
                    if ($penalties > 0) {
                        $depositStatus = $refundAmount > 0 ? 'parcial' : 'aplicado';
                    }
                    if ($refundAmount > 0 && $session) {
                        $this->chargeToCaja($rental, 'devolucion_deposito', $refundAmount, $session, [
                            'method' => $method,
                            'notes'  => 'Devolución de depósito (menos penalizaciones)',
                        ]);
                    }
                    $rental->update(['deposit_status' => $depositStatus]);
                }

                // Integración con Taller
                if (!empty($validated['needs_maintenance'])) {
                    $wo = $this->createMaintenanceOrder($rental, $validated['return_notes'] ?? null, $validated['return_mileage'] ?? null);
                    $rental->update(['work_order_id' => $wo->id, 'status' => 'cerrada']);
                    $rental->motoUnit?->update(['status' => 'mantenimiento']);
                } else {
                    $rental->update(['status' => 'cerrada']);
                    $rental->motoUnit?->update(['status' => 'disponible']);
                }
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => 'Error al procesar la devolución: ' . $e->getMessage()]);
        }

        return redirect()->route('rentals.show', $rental)->with('success', 'Devolución registrada y contrato cerrado.');
    }

    // ── Pagos ─────────────────────────────────────────────────
    public function payments()
    {
        $cid = auth()->user()->is_super_admin ? null : $this->companyId();
        $payments = RentalPayment::query()
            ->with(['contract.client', 'user'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20);

        return view('rentals.payments.index', compact('payments'));
    }

    public function registerPayment(Request $request, RentalContract $rental)
    {
        $this->authorizeContract($rental);
        $validated = $request->validate([
            'amount'             => 'required|numeric|min:0.01',
            'method'             => 'nullable|string|max:30',
            'reference'          => 'nullable|string|max:100',
            'notes'              => 'nullable|string',
            'charge_late_fee'    => 'nullable|boolean',
            'installment_id'     => 'nullable|integer',
        ]);

        $session = $this->currentOpenSession();
        if (!$session) {
            return back()->withErrors(['error' => 'Necesitas tu caja abierta para registrar el pago.']);
        }

        $meta = [
            'method'    => $validated['method'] ?? 'efectivo',
            'reference' => $validated['reference'] ?? null,
        ];

        try {
            DB::transaction(function () use ($validated, $rental, $session, $meta) {
                // Cobrar mora acumulada de la cuota (si se solicitó)
                if (!empty($validated['charge_late_fee']) && !empty($validated['installment_id'])) {
                    $inst = $rental->installments()->find($validated['installment_id']);
                    if ($inst) {
                        $this->accrueLateFee($inst, $session, $meta);
                    }
                }

                if ($rental->isRenta()) {
                    // Distribuir entre cuotas pendientes (oldest-first)
                    $this->applyRentPayment($rental, (float) $validated['amount'], $session, $meta + [
                        'notes' => $validated['notes'] ?? null,
                    ]);
                } else {
                    $balance = (float) $rental->fresh()->balance;
                    if ((float) $validated['amount'] > $balance + 0.01) {
                        throw ValidationException::withMessages([
                            'amount' => 'El monto supera el saldo pendiente (Bs. ' . number_format($balance, 2) . ').',
                        ]);
                    }
                    $this->chargeToCaja($rental, 'alquiler', (float) $validated['amount'], $session, $meta + [
                        'notes' => $validated['notes'] ?? 'Abono de alquiler',
                    ]);
                }
            });
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return back()->with('success', 'Pago registrado.');
    }

    // ── Cobros de renta (cuotas) ──────────────────────────────
    public function collections(Request $request)
    {
        $cid = auth()->user()->is_super_admin ? null : $this->companyId();
        $filter = $request->get('due', 'vencidas'); // vencidas | hoy | todas
        $today  = Carbon::today()->toDateString();

        $installments = RentalInstallment::query()
            ->with(['contract.client', 'contract.motoUnit.model.brand'])
            ->whereIn('status', ['pendiente', 'parcial'])
            ->whereHas('contract', function ($q) use ($cid) {
                $q->whereIn('status', ['contrato', 'entregada'])
                  ->when($cid, fn ($w) => $w->where('company_id', $cid));
            })
            ->when($filter === 'vencidas', fn ($q) => $q->whereDate('due_date', '<', $today))
            ->when($filter === 'hoy', fn ($q) => $q->whereDate('due_date', $today))
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        return view('rentals.collections.index', compact('installments', 'filter'));
    }

    // ── Penalizaciones ────────────────────────────────────────
    public function penalties()
    {
        $cid = auth()->user()->is_super_admin ? null : $this->companyId();
        $penalties = RentalPenalty::query()
            ->with(['contract.client', 'createdBy'])
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->latest('penalty_date')
            ->latest('id')
            ->paginate(20);

        return view('rentals.penalties.index', compact('penalties'));
    }

    public function addPenalty(Request $request, RentalContract $rental)
    {
        $this->authorizeContract($rental);
        $validated = $request->validate([
            'concept'      => 'required|string|max:150',
            'amount'       => 'required|numeric|min:0.01',
            'penalty_date' => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated, $rental) {
            RentalPenalty::create([
                'company_id'         => $rental->company_id,
                'rental_contract_id' => $rental->id,
                'concept'            => $validated['concept'],
                'amount'             => (float) $validated['amount'],
                'penalty_date'       => $validated['penalty_date'] ?? now()->toDateString(),
                'notes'              => $validated['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);
            $rental->increment('penalties_total', (float) $validated['amount']);
            $rental->refresh()->recalcTotals();
        });

        return back()->with('success', 'Penalización agregada.');
    }

    // ── Historial / Show / Cancelar ───────────────────────────
    public function history(Request $request)
    {
        $contracts = $this->scopedContracts()
            ->whereIn('status', ['cerrada', 'devuelta', 'anulada'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->q;
                $q->where(fn ($w) => $w->where('code', 'like', "%{$term}%")
                    ->orWhereHas('client', fn ($c) => $c->where('full_name', 'like', "%{$term}%")));
            })
            ->latest('returned_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('rentals.history.index', compact('contracts'));
    }

    public function show(RentalContract $rental)
    {
        $this->authorizeContract($rental);
        $rental->load([
            'client', 'motoUnit.model.brand', 'payments.user', 'penalties.createdBy',
            'workOrder', 'createdBy', 'session.cashRegister',
            'installments', 'inspections.photos',
        ]);
        return view('rentals.show', compact('rental'));
    }

    public function cancel(RentalContract $rental)
    {
        $this->authorizeContract($rental);
        if (in_array($rental->status, ['cerrada', 'anulada'], true)) {
            return back()->withErrors(['error' => 'Este contrato ya está finalizado.']);
        }
        if ($rental->status === 'entregada') {
            return back()->withErrors(['error' => 'No puedes anular un alquiler en curso; regístralo como devolución.']);
        }

        DB::transaction(function () use ($rental) {
            $rental->update(['status' => 'anulada']);
            if ($rental->motoUnit && in_array($rental->motoUnit->status, ['reservada'], true)) {
                $rental->motoUnit->update(['status' => 'disponible']);
            }
        });

        return back()->with('success', 'Contrato anulado.');
    }

    // ── Helpers ───────────────────────────────────────────────
    private function authorizeContract(RentalContract $rental): void
    {
        if (auth()->user()->is_super_admin) {
            return;
        }
        if ($rental->company_id !== $this->companyId()) {
            abort(403);
        }
    }

    private function hasOverlap(int $unitId, string $start, string $end, ?int $ignoreId = null): bool
    {
        return RentalContract::where('moto_unit_id', $unitId)
            ->whereIn('status', ['reservada', 'contrato', 'entregada'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(fn ($w) => $w->where('start_date', '<=', $start)->where('end_date', '>=', $end));
            })
            ->exists();
    }

    private function createMaintenanceOrder(RentalContract $rental, ?string $notes, ?int $mileage): WorkOrder
    {
        $count = WorkOrder::withTrashed()->where('company_id', $rental->company_id)->count() + 1;
        $code  = 'OT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

        return WorkOrder::create([
            'company_id'     => $rental->company_id,
            'branch_id'      => $rental->branch_id,
            'moto_unit_id'   => $rental->moto_unit_id,
            'code'           => $code,
            'status'         => 'recibida',
            'reception_date' => now()->toDateString(),
            'mileage'        => $mileage,
            'reported_issue' => 'Mantenimiento tras devolución de alquiler ' . $rental->code . ($notes ? ' — ' . $notes : ''),
            'payment_type'   => 'contado',
            'payment_status' => 'pendiente',
            'created_by'     => auth()->id(),
        ]);
    }

    /**
     * Crea una inspección (salida/entrada) con su checklist y sube las fotos
     * reutilizando el patrón Storage::disk('public'). Se ejecuta dentro de la
     * transacción de entrega/devolución.
     */
    private function handleInspection(RentalContract $rental, string $type, Request $request, array $validated): void
    {
        $hasChecklist = !empty($validated['checklist']);
        $hasPhotos    = $request->hasFile('photos');

        // Datos de km/combustible/notas según el tipo
        if ($type === 'salida') {
            $mileage = $validated['delivery_mileage'] ?? null;
            $fuel    = $validated['delivery_fuel'] ?? null;
            $notes   = $validated['delivery_notes'] ?? null;
        } else {
            $mileage = $validated['return_mileage'] ?? null;
            $fuel    = $validated['return_fuel'] ?? null;
            $notes   = $validated['return_notes'] ?? null;
        }

        // Si no hay nada que registrar más allá de los campos base, igual creamos
        // la inspección para dejar constancia con km/combustible.
        $inspection = RentalInspection::create([
            'company_id'         => $rental->company_id,
            'rental_contract_id' => $rental->id,
            'type'               => $type,
            'mileage'            => $mileage,
            'fuel_level'         => $fuel,
            'checklist'          => $hasChecklist ? $validated['checklist'] : null,
            'notes'              => $notes,
            'created_by'         => auth()->id(),
        ]);

        if ($hasPhotos) {
            foreach ($request->file('photos') as $i => $file) {
                $path = $file->store("company/{$rental->company_id}/rentals/{$rental->id}/{$type}", 'public');
                RentalInspectionPhoto::create([
                    'company_id'           => $rental->company_id,
                    'rental_inspection_id' => $inspection->id,
                    'file_path'            => $path,
                    'file_name'            => $file->getClientOriginalName(),
                    'sort_order'           => $i,
                ]);
            }
        }
    }

    private function formData(): array
    {
        $cid = auth()->user()->is_super_admin ? null : $this->companyId();

        $units = MotoUnit::query()
            ->with('model.brand')
            ->where('status', 'disponible')
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->get();

        $clients = Client::query()
            ->when($cid, fn ($q) => $q->where('company_id', $cid))
            ->orderBy('full_name')
            ->get();

        return compact('units', 'clients');
    }
}
