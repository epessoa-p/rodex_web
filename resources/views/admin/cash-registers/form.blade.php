@php
    $isEdit = isset($cashRegister);
    $action = $isEdit ? route('cash-registers.update', $cashRegister) : route('cash-registers.store');
    $method = $isEdit ? 'PUT' : 'POST';
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ $action }}" method="POST">
    @csrf
    @method($method)

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-3">

                @if(isset($companies) && $companies->count() > 1)
                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="company_id">Empresa</label>
                    <select id="company_id" name="company_id" class="form-select">
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}"
                                {{ (string) old('company_id', $cashRegister->company_id ?? '') === (string) $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="branch_id">
                        Sucursal <span class="text-danger">*</span>
                    </label>
                    <select id="branch_id" name="branch_id"
                            class="form-select @error('branch_id') is-invalid @enderror" required>
                        <option value="">— Seleccionar sucursal —</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ (string) old('branch_id', $cashRegister->branch_id ?? '') === (string) $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="name">
                        Nombre <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="name" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $cashRegister->name ?? '') }}"
                           placeholder="Ej: Caja Principal"
                           required maxlength="255">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold" for="assigned_personal_id">
                        Cajero asignado <span class="text-danger">*</span>
                    </label>
                    <select id="assigned_personal_id" name="assigned_personal_id"
                            class="form-select @error('assigned_personal_id') is-invalid @enderror" required>
                        <option value="">— Seleccionar cajero —</option>
                        @foreach($personals as $personal)
                            <option value="{{ $personal->id }}"
                                {{ (string) old('assigned_personal_id', $cashRegister->assigned_personal_id ?? '') === (string) $personal->id ? 'selected' : '' }}>
                                {{ $personal->full_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('assigned_personal_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold" for="description">
                        Descripción
                        <span class="text-muted fw-normal">(opcional)</span>
                    </label>
                    <textarea id="description" name="description"
                              class="form-control @error('description') is-invalid @enderror"
                              rows="2" maxlength="500"
                              placeholder="Notas sobre esta caja...">{{ old('description', $cashRegister->description ?? '') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                               {{ old('active', $cashRegister->active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Caja habilitada</label>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('cash-registers.index') }}" class="btn btn-light border">Cancelar</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i>
            {{ $isEdit ? 'Actualizar caja' : 'Crear caja' }}
        </button>
    </div>
</form>
