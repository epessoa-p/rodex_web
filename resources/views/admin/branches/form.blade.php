<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">{{ $branch ? 'Editar sucursal' : 'Nueva sucursal' }}</h1>
            <p class="text-muted mb-0">Configura la información operativa de la sucursal.</p>
        </div>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ $action }}" method="POST" class="row g-3">
                @csrf
                @if($method !== 'POST') @method($method) @endif
                @if($isSuperAdmin ?? false)
                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" id="company_id" class="form-select">
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (string) old('company_id', $selectedCompanyId ?? $branch?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control" value="{{ old('name', $branch?->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Código</label><input type="text" name="code" class="form-control" value="{{ old('code', $branch?->code) }}"></div>
                <div class="col-md-6">
                    <label class="form-label mb-1">Almacén de la sucursal</label>
                    @if($branch?->warehouse)
                        <div class="form-control bg-light d-flex align-items-center gap-2">
                            <i class="bi bi-house-door text-primary"></i>
                            <span>{{ $branch->warehouse->name }}</span>
                            <span class="badge bg-secondary">{{ $branch->warehouse->code }}</span>
                        </div>
                        <small class="text-muted">Lo gestiona el sistema: toma el nombre y la dirección de la sucursal al guardar.</small>
                    @else
                        <div class="form-control bg-light text-muted d-flex align-items-center gap-2">
                            <i class="bi bi-magic text-primary"></i>
                            <span>Se creará automáticamente con el nombre y la dirección de la sucursal.</span>
                        </div>
                        <small class="text-muted">El código del almacén lo genera el sistema (ALM-001, ALM-002, …).</small>
                    @endif
                </div>
                <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $branch?->phone) }}"></div>
                <div class="col-md-6"><label class="form-label">Correo</label><input type="email" name="email" class="form-control" value="{{ old('email', $branch?->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Encargado</label><input type="text" name="manager_name" class="form-control" value="{{ old('manager_name', $branch?->manager_name) }}"></div>
                <div class="col-md-6">
                    <label class="form-label">Color de la sucursal</label>
                    @php $branchColor = old('color', $branch?->color ?: '#e63946'); @endphp
                    <div class="input-group">
                        <input type="color" id="branch_color_picker" class="form-control form-control-color" value="{{ $branchColor }}"
                               style="max-width:52px;" title="Elegir color">
                        <input type="text" name="color" id="branch_color_hex" class="form-control" value="{{ $branchColor }}"
                               maxlength="7" placeholder="#e63946" data-no-search>
                    </div>
                    <small class="text-muted">Se usará como color de referencia de la sucursal.</small>
                </div>
                <div class="col-12"><label class="form-label">Dirección</label><input type="text" name="address" class="form-control" value="{{ old('address', $branch?->address) }}"></div>
                <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $branch?->active ?? true) ? 'checked' : '' }}><label class="form-check-label">Activo</label></div></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit">Guardar</button><a href="{{ route('branches.index') }}" class="btn btn-light border">Cancelar</a></div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    const picker = document.getElementById('branch_color_picker');
    const hex    = document.getElementById('branch_color_hex');
    if (picker && hex) {
        picker.addEventListener('input', () => { hex.value = picker.value; });
        hex.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value;
        });
    }
})();

</script>
@endpush