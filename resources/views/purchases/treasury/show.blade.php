@extends('layouts.app')
@section('title', 'Cuenta: ' . $account->name)
@section('page')
<div class="container-fluid">

    {{-- Hero header cuenta --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:6px;background:{{ $account->type === 'bank' ? 'linear-gradient(90deg,#0d6efd,#60a5fa)' : 'linear-gradient(90deg,#198754,#4ade80)' }};"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-4 flex-wrap">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:72px;height:72px;background:{{ $account->type === 'bank' ? 'rgba(13,110,253,.1)' : 'rgba(25,135,84,.1)' }}">
                    <i class="bi {{ $account->type === 'bank' ? 'bi-bank fs-2 text-primary' : 'bi-cash-coin fs-2 text-success' }}"></i>
                </div>
                <div class="flex-grow-1">
                    <h1 class="mb-1 fw-bold fs-4">{{ $account->name }}</h1>
                    <div class="d-flex flex-wrap gap-3 text-muted small">
                        <span><i class="bi bi-tag me-1"></i>{{ $account->type_label }}</span>
                        @if($account->bank_name)
                        <span><i class="bi bi-building me-1"></i>{{ $account->bank_name }}</span>
                        @endif
                        @if($account->account_number)
                        <span><i class="bi bi-hash me-1"></i>{{ $account->account_number }}</span>
                        @endif
                        @if($account->active)
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Activa</span>
                        @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactiva</span>
                        @endif
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-muted small mb-1">Saldo actual</div>
                    <div class="fw-bold {{ $account->balance >= 0 ? 'text-success' : 'text-danger' }}" style="font-size:2rem">
                        ${{ number_format($account->balance, 2) }}
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.manage', auth()->user()->getCurrentCompany()))
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#movModal">
                        <i class="bi bi-plus-lg me-1"></i>Registrar movimiento
                    </button>
                    <a href="{{ route('treasury.edit', $account) }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-pencil me-1"></i>Editar
                    </a>
                    @endif
                    <a href="{{ route('treasury.index') }}" class="btn btn-light border btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Movimientos --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history me-2 text-muted"></i>Historial de movimientos</h6>
            <span class="badge bg-light text-muted border fw-normal">{{ $movements->total() }} movimientos</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4 py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Fecha</th>
                            <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Categoría</th>
                            <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Tipo</th>
                            <th class="py-3 small fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em">Monto</th>
                            <th class="py-3 small fw-semibold text-muted text-uppercase" style="letter-spacing:.04em">Descripción</th>
                            <th class="py-3 small fw-semibold text-muted text-uppercase pe-4" style="letter-spacing:.04em">Usuario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $mov)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-3">
                                <div class="small fw-semibold">{{ $mov->movement_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $mov->movement_date->format('H:i') }}</small>
                            </td>
                            <td class="py-3 small">{{ $mov->category_label }}</td>
                            <td class="py-3">
                                @if($mov->type === 'in')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-arrow-down-circle me-1"></i>Entrada
                                </span>
                                @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="bi bi-arrow-up-circle me-1"></i>Salida
                                </span>
                                @endif
                            </td>
                            <td class="py-3 text-end fw-semibold {{ $mov->type === 'in' ? 'text-success' : 'text-danger' }}">
                                {{ $mov->type === 'in' ? '+' : '-' }}${{ number_format($mov->amount, 2) }}
                            </td>
                            <td class="py-3 small text-muted">{{ $mov->description ?: '—' }}</td>
                            <td class="py-3 small text-muted pe-4">{{ $mov->user?->name ?: '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                Sin movimientos registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $movements->links() }}</div>

</div>

{{-- Modal: Registrar movimiento --}}
@if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.manage', auth()->user()->getCurrentCompany()))
<div class="modal fade" id="movModal" tabindex="-1" aria-labelledby="movModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold" id="movModalLabel">
                    <i class="bi bi-plus-slash-minus me-2 text-muted"></i>Registrar movimiento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="{{ route('treasury.capital', $account) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border mb-3 py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Saldo actual de <strong>{{ $account->name }}</strong></span>
                            <span class="fw-bold {{ $account->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                ${{ number_format($account->balance, 2) }}
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="cat_category">
                            Categoría <span class="text-danger">*</span>
                        </label>
                        <select id="cat_category" name="category"
                                class="form-select @error('category') is-invalid @enderror" required>
                            <option value="">— Seleccionar categoría —</option>
                            @foreach(\App\Models\Purchases\TreasuryMovement::CATEGORIES as $key => $data)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>
                                {{ $data['label'] }}
                                ({{ $data['type'] === 'in' ? 'Entrada' : 'Salida' }})
                            </option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="cat_amount">
                            Monto <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" id="cat_amount" name="amount"
                                   step="0.01" min="0.01"
                                   class="form-control @error('amount') is-invalid @enderror"
                                   value="{{ old('amount') }}"
                                   required placeholder="0.00">
                        </div>
                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="cat_description">Descripción</label>
                        <textarea id="cat_description" name="description"
                                  class="form-control @error('description') is-invalid @enderror"
                                  rows="2"
                                  placeholder="Detalle del movimiento...">{{ old('description') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
// Auto-open modal if there are form errors after submission
@if($errors->any())
document.addEventListener('DOMContentLoaded', function () {
    var modal = new bootstrap.Modal(document.getElementById('movModal'));
    modal.show();
});
@endif
</script>
@endpush
@endsection
