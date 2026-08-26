@php
    $income   = $session->totalIncome();
    $expense  = $session->totalExpense();
    $expected = $session->expectedBalance();
    $isOpen   = $session->isOpen();
    $diff     = (float) $session->difference;
    $money    = fn ($n) => money($n);
@endphp

<div class="session-detail p-3 p-lg-4">

    {{-- Mini KPIs --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-lg">
            <div class="sd-kpi">
                <div class="sd-kpi-label"><i class="bi bi-wallet2 me-1"></i>Inicial</div>
                <div class="sd-kpi-value">{{ $money($session->opening_amount) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="sd-kpi sd-kpi-income">
                <div class="sd-kpi-label"><i class="bi bi-arrow-down-circle me-1"></i>Ingresos</div>
                <div class="sd-kpi-value text-success">+{{ number_format($income, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="sd-kpi sd-kpi-expense">
                <div class="sd-kpi-label"><i class="bi bi-arrow-up-circle me-1"></i>Egresos</div>
                <div class="sd-kpi-value text-danger">−{{ number_format($expense, 2) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="sd-kpi sd-kpi-expected">
                <div class="sd-kpi-label"><i class="bi bi-calculator me-1"></i>Esperado</div>
                <div class="sd-kpi-value">{{ $money($expected) }}</div>
            </div>
        </div>
        @unless($isOpen)
        <div class="col-6 col-lg">
            <div class="sd-kpi">
                <div class="sd-kpi-label"><i class="bi bi-cash-coin me-1"></i>Contado</div>
                <div class="sd-kpi-value">{{ $money($session->closing_amount) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg">
            <div class="sd-kpi {{ $diff == 0 ? '' : ($diff > 0 ? 'sd-kpi-income' : 'sd-kpi-expense') }}">
                <div class="sd-kpi-label"><i class="bi bi-plus-slash-minus me-1"></i>Diferencia</div>
                <div class="sd-kpi-value {{ $diff == 0 ? 'text-muted' : ($diff > 0 ? 'text-success' : 'text-danger') }}">
                    {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                    @if($diff > 0)
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.6rem;">sobrante</span>
                    @elseif($diff < 0)
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.6rem;">faltante</span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.6rem;">exacto</span>
                    @endif
                </div>
            </div>
        </div>
        @endunless
    </div>

    {{-- Barra de acciones --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="text-muted small">
            @if($isOpen)
                <span class="text-success"><i class="bi bi-circle-fill me-1" style="font-size:.5rem;vertical-align:middle;"></i>Sesión abierta</span>
                · Abrió {{ $session->openedBy?->name ?? '—' }}
                @if($session->opened_at) el {{ $session->opened_at->format('d/m/Y H:i') }} @endif
            @else
                Cerró {{ $session->closedBy?->name ?? '—' }}
                @if($session->closed_at) el {{ $session->closed_at->format('d/m/Y H:i') }} @endif
            @endif
            @if($session->notes)
                <div class="mt-1"><i class="bi bi-sticky me-1"></i>{{ $session->notes }}</div>
            @endif
        </div>
        <div class="d-flex gap-2">
            @if(!$isOpen && abs($diff) >= 0.01 && $canAdjustCash)
            <button type="button" class="btn btn-sm btn-warning text-dark fw-semibold btn-adjust-cash"
                    data-session="{{ $session->id }}"
                    data-expected="{{ number_format($expected, 2, '.', '') }}"
                    data-counted="{{ number_format($session->closing_amount, 2, '.', '') }}"
                    data-difference="{{ number_format($diff, 2, '.', '') }}"
                    data-register="{{ $session->cashRegister?->name }}">
                <i class="bi bi-sliders me-1"></i>Resolver diferencia
            </button>
            @endif
            <a href="{{ route('cash.session.show', $session) }}" class="btn btn-sm btn-light border">
                <i class="bi bi-box-arrow-up-right me-1"></i>Ver detalle completo
            </a>
        </div>
    </div>

    {{-- Movimientos --}}
    <div class="sd-movs-wrap">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="fw-semibold small text-muted text-uppercase" style="letter-spacing:.04em;">
                <i class="bi bi-list-ul me-1"></i>Movimientos
            </span>
            <span class="text-muted small">{{ $movements->count() }} registros</span>
        </div>
        <div class="table-responsive" style="max-height:340px;overflow-y:auto;">
            <table class="table table-sm table-hover align-middle mb-0" style="font-size:.8rem;">
                <thead class="table-light" style="position:sticky;top:0;z-index:1;">
                    <tr>
                        <th class="py-2">Fecha</th>
                        <th class="py-2">Categoría</th>
                        <th class="py-2">Tipo</th>
                        <th class="py-2 text-end">Monto</th>
                        <th class="py-2">Descripción</th>
                        <th class="py-2">Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $mov)
                    <tr>
                        <td class="py-2">
                            <div>{{ $mov->movement_date->format('d/m/Y') }}</div>
                            <small class="text-muted">{{ $mov->movement_date->format('H:i') }}</small>
                        </td>
                        <td class="py-2">{{ $mov->category_label }}</td>
                        <td class="py-2">
                            @if($mov->type === 'income')
                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-arrow-down-short"></i> Ingreso</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-arrow-up-short"></i> Egreso</span>
                            @endif
                        </td>
                        <td class="py-2 text-end fw-semibold {{ $mov->type === 'income' ? 'text-success' : 'text-danger' }}">
                            {{ $mov->type === 'income' ? '+' : '−' }}{{ number_format($mov->amount, 2) }}
                        </td>
                        <td class="py-2 text-muted">{{ $mov->description ?: '—' }}</td>
                        <td class="py-2">{{ $mov->user?->name ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox d-block fs-3 mb-1 opacity-25"></i>
                            <span class="small">No hay movimientos en esta sesión.</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
