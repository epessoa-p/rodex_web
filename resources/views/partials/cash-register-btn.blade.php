@php
    $cashPersonal      = auth()->user()->personal ?? null;
    $activeCashSession = null;
    $availableCashRegisters = collect();

    if ($cashPersonal) {
        $activeCashSession = \App\Models\CashRegisterSession::whereHas(
            'cashRegister',
            fn($q) => $q->where('assigned_personal_id', $cashPersonal->id)->where('active', true)
        )->whereNull('closed_at')->with('cashRegister.branch')->latest()->first();

        if (!$activeCashSession) {
            $availableCashRegisters = \App\Models\CashRegister::where('assigned_personal_id', $cashPersonal->id)
                ->where('active', true)
                ->with('branch')
                ->get();
        }
    }
@endphp

@if($cashPersonal)

    @if($activeCashSession)
        {{-- Caja ABIERTA --}}
        <div class="dropdown">
            <button class="btn btn-cash-open dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-circle-fill me-1" style="font-size:.5rem;vertical-align:middle;color:#4ade80;"></i>
                Caja: {{ $activeCashSession->cashRegister?->branch?->name ?? $activeCashSession->cashRegister?->name }}
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-1" style="min-width:220px;">
                <li>
                    <span class="dropdown-item-text text-muted small">
                        {{ $activeCashSession->cashRegister?->name }}
                        &nbsp;·&nbsp; desde {{ $activeCashSession->opened_at->format('H:i') }}
                    </span>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <a class="dropdown-item" href="{{ route('cash.session.show', $activeCashSession) }}">
                        <i class="bi bi-list-ul me-2"></i> Ver movimientos
                    </a>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li>
                    <button class="dropdown-item text-danger" type="button"
                            data-bs-toggle="modal" data-bs-target="#modalCerrarCajaNavbar">
                        <i class="bi bi-lock me-2"></i> Cerrar caja
                    </button>
                </li>
            </ul>
        </div>

        {{-- Modal cierre desde navbar --}}
        @php
            $income   = $activeCashSession->totalIncome();
            $expense  = $activeCashSession->totalExpense();
            $expected = $activeCashSession->expectedBalance();
        @endphp
        <div class="modal fade" id="modalCerrarCajaNavbar" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title"><i class="bi bi-lock text-danger"></i> Cerrar caja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('cash.session.close', $activeCashSession) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="rounded-3 bg-light p-3 mb-3">
                                <div class="row g-2 text-center">
                                    <div class="col-4">
                                        <div class="text-muted small">Monto inicial</div>
                                        <div class="fw-bold">{{ number_format($activeCashSession->opening_amount, 2) }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted small">Ingresos</div>
                                        <div class="fw-bold text-success">+{{ number_format($income, 2) }}</div>
                                    </div>
                                    <div class="col-4">
                                        <div class="text-muted small">Egresos</div>
                                        <div class="fw-bold text-danger">-{{ number_format($expense, 2) }}</div>
                                    </div>
                                </div>
                                <hr class="my-2">
                                <div class="text-center">
                                    <div class="text-muted small">Saldo esperado</div>
                                    <div class="fs-5 fw-bold text-primary">{{ number_format($expected, 2) }}</div>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Monto físico contado <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="closing_amount" class="form-control form-control-lg"
                                               step="0.01" min="0" required placeholder="0.00"
                                               id="navClosingAmount">
                                    </div>
                                    <div id="navDiffPreview" class="mt-2 text-center fw-semibold d-none"></div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Notas</label>
                                    <textarea name="notes" class="form-control" rows="2"
                                              placeholder="Observaciones opcionales..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-lock"></i> Confirmar cierre
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    @elseif($availableCashRegisters->isNotEmpty())
        {{-- Caja CERRADA con cajas disponibles --}}
        <button class="btn btn-cash-closed" type="button"
                data-bs-toggle="modal" data-bs-target="#modalAbrirCajaNavbar">
            Abrir caja
        </button>

        {{-- Modal apertura --}}
        <div class="modal fade" id="modalAbrirCajaNavbar" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title"><i class="bi bi-cash-register text-primary"></i> Apertura de caja</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('cash.open') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="row g-3">
                                @if($availableCashRegisters->count() > 1)
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Seleccionar caja <span class="text-danger">*</span></label>
                                    <select name="cash_register_id" class="form-select" required>
                                        @foreach($availableCashRegisters as $reg)
                                            <option value="{{ $reg->id }}">
                                                {{ $reg->name }} — {{ $reg->branch?->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @else
                                    <input type="hidden" name="cash_register_id" value="{{ $availableCashRegisters->first()->id }}">
                                    <div class="col-12">
                                        <div class="alert alert-light border mb-0">
                                            <i class="bi bi-cash-register text-primary me-1"></i>
                                            <strong>{{ $availableCashRegisters->first()->name }}</strong>
                                            — {{ $availableCashRegisters->first()->branch?->name }}
                                        </div>
                                    </div>
                                @endif

                                <div class="col-12">
                                    <label class="form-label fw-semibold">Monto inicial <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" name="opening_amount" class="form-control form-control-lg"
                                               step="0.01" min="0" required placeholder="0.00" value="0">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Notas de apertura</label>
                                    <textarea name="opening_notes" class="form-control" rows="2"
                                              placeholder="Observaciones opcionales..."></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-unlock"></i> Aperturar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endif

@once
@push('scripts')
<script>
    (function () {
        const navClosing = document.getElementById('navClosingAmount');
        if (!navClosing) return;
        const expected = {{ isset($expected) ? $expected : 0 }};
        navClosing.addEventListener('input', function () {
            const diff = parseFloat(this.value || 0) - expected;
            const el   = document.getElementById('navDiffPreview');
            if (!el) return;
            el.classList.remove('d-none', 'text-success', 'text-danger', 'text-muted');
            if (isNaN(diff)) { el.classList.add('d-none'); return; }
            el.textContent = 'Diferencia: ' + (diff >= 0 ? '+' : '') + diff.toFixed(2) + (diff > 0 ? ' (sobrante)' : diff < 0 ? ' (faltante)' : ' (exacto)');
            el.classList.add(diff > 0 ? 'text-success' : diff < 0 ? 'text-danger' : 'text-muted');
        });
    })();
</script>
@endpush
@endonce
