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
                @if($companies->count() > 1)
                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" class="form-select">
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (string) old('company_id', $branch?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control" value="{{ old('name', $branch?->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Código</label><input type="text" name="code" class="form-control" value="{{ old('code', $branch?->code) }}"></div>
                <div class="col-md-6">
                    <label class="form-label">Almacén principal <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">Seleccionar almacén</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ (string) old('warehouse_id', $branch?->warehouse_id) === (string) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }} ({{ $warehouse->code }})</option>
                        @endforeach
                    </select>
                    @if($warehouses->isEmpty())
                        <small class="text-danger">No hay almacenes disponibles para esta empresa. Crea primero un almacén.</small>
                    @endif
                </div>
                <div class="col-md-6"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-control" value="{{ old('phone', $branch?->phone) }}"></div>
                <div class="col-md-6"><label class="form-label">Correo</label><input type="email" name="email" class="form-control" value="{{ old('email', $branch?->email) }}"></div>
                <div class="col-md-6"><label class="form-label">Encargado</label><input type="text" name="manager_name" class="form-control" value="{{ old('manager_name', $branch?->manager_name) }}"></div>
                <div class="col-md-6">
                    <label class="form-label">Color de la sucursal</label>
                    @php $branchColor = old('color', $branch?->color ?: '#e10600'); @endphp
                    <div class="input-group">
                        <input type="color" id="branch_color_picker" class="form-control form-control-color" value="{{ $branchColor }}"
                               style="max-width:52px;" title="Elegir color">
                        <input type="text" name="color" id="branch_color_hex" class="form-control" value="{{ $branchColor }}"
                               maxlength="7" placeholder="#e10600" data-no-search>
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