@extends('layouts.app')
@php
    $isEdit = isset($account);
    $action = $isEdit ? route('treasury.update', $account) : route('treasury.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp
@section('title', $isEdit ? 'Editar cuenta: ' . $account->name : 'Nueva cuenta de tesorería')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4">
                <i class="bi bi-bank me-2 text-danger"></i>
                {{ $isEdit ? 'Editar cuenta' : 'Nueva cuenta de tesorería' }}
            </h1>
            <p class="text-muted mb-0 small">
                {{ $isEdit ? $account->name : 'Configure una nueva cuenta de efectivo o banco.' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            @if($isEdit)
            <a href="{{ route('treasury.show', $account) }}" class="btn btn-light border">
                <i class="bi bi-eye me-1"></i>Ver cuenta
            </a>
            @endif
            <a href="{{ route('treasury.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form action="{{ $action }}" method="POST">
        @csrf
        @method($method)

        <div class="row g-4">

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Datos de la cuenta</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="name">
                                    Nombre de la cuenta <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="name" name="name"
                                       class="form-control form-control-lg @error('name') is-invalid @enderror"
                                       value="{{ old('name', $isEdit ? $account->name : '') }}"
                                       required maxlength="255"
                                       placeholder="Ej: Caja principal, Cuenta BNB...">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="type">
                                    Tipo de cuenta <span class="text-danger">*</span>
                                </label>
                                <select id="type" name="type"
                                        class="form-select @error('type') is-invalid @enderror"
                                        required onchange="toggleBankFields(this.value)">
                                    <option value="">— Seleccionar tipo —</option>
                                    @foreach(\App\Models\Purchases\TreasuryAccount::TYPES as $key => $label)
                                    <option value="{{ $key }}"
                                        {{ old('type', $isEdit ? $account->type : '') === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if(!$isEdit)
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="opening_balance">Saldo inicial</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">{{ currency_symbol() }}</span>
                                    <input type="number" id="opening_balance" name="opening_balance"
                                           step="0.01" min="0"
                                           class="form-control @error('opening_balance') is-invalid @enderror"
                                           value="{{ old('opening_balance', '0.00') }}"
                                           placeholder="0.00">
                                </div>
                                @error('opening_balance')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text text-muted">Solo se establece al crear la cuenta.</div>
                            </div>
                            @endif

                            <div id="bankFields" class="{{ old('type', $isEdit ? $account->type : '') !== 'bank' ? 'd-none' : '' }} col-12">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="bank_name">Nombre del banco</label>
                                        <input type="text" id="bank_name" name="bank_name"
                                               class="form-control @error('bank_name') is-invalid @enderror"
                                               value="{{ old('bank_name', $isEdit ? $account->bank_name : '') }}"
                                               maxlength="255"
                                               placeholder="BNB, Banco Unión...">
                                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold" for="account_number">Número de cuenta</label>
                                        <input type="text" id="account_number" name="account_number"
                                               class="form-control @error('account_number') is-invalid @enderror"
                                               value="{{ old('account_number', $isEdit ? $account->account_number : '') }}"
                                               maxlength="100"
                                               placeholder="0000-00-000000-0">
                                        @error('account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top:20px">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-sliders me-2 text-muted"></i>Estado</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                   {{ old('active', $isEdit ? $account->active : true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="active">Cuenta activa</label>
                        </div>
                        <p class="text-muted small mb-0">Las cuentas inactivas no aparecen como opción de pago.</p>
                    </div>
                </div>
            </div>

        </div>

        <div class="d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('treasury.index') }}" class="btn btn-light border px-4">
                <i class="bi bi-x-lg me-1"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-primary px-5">
                <i class="bi bi-check-lg me-1"></i>
                {{ $isEdit ? 'Guardar cambios' : 'Crear cuenta' }}
            </button>
        </div>

    </form>

</div>

@push('scripts')
<script>
function toggleBankFields(type) {
    const bankFields = document.getElementById('bankFields');
    if (type === 'bank') {
        bankFields.classList.remove('d-none');
    } else {
        bankFields.classList.add('d-none');
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('type');
    if (typeSelect) toggleBankFields(typeSelect.value);
});
</script>
@endpush
@endsection
