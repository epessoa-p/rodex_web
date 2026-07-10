<div class="container-fluid" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1">{{ $personal ? 'Editar personal' : 'Nuevo personal' }}</h1>
            <p class="text-muted mb-0">Al crear personal se genera usuario automáticamente y se asigna rol según el cargo.</p>
        </div>
        <a href="{{ route('personal.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show d-flex gap-2 align-items-start" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
            <div>
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ $action }}" method="POST" class="row g-4">
        @csrf
        @if($method !== 'POST') @method($method) @endif

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person-badge"></i> Datos del personal</h6>
                    <div class="row g-3">
                        @if($companies->count() > 1)
                            <div class="col-md-6">
                                <label class="form-label">Empresa</label>
                                <select name="company_id" id="company_id" class="form-select" required>
                                    <option value="">Seleccionar empresa</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ (string) old('company_id', $personal?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Cargo <span class="text-danger">*</span></label>
                            <select name="cargo_id" class="form-select" required>
                                <option value="">Seleccionar cargo</option>
                                @foreach($cargos as $cargo)
                                    <option value="{{ $cargo->id }}" {{ (string) old('cargo_id', $personal?->cargo_id) === (string) $cargo->id ? 'selected' : '' }}>
                                        {{ $cargo->name }} · Rol: {{ $cargo->role?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Nombre completo <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $personal?->full_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Documento</label>
                            <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $personal?->id_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $personal?->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de ingreso</label>
                            <input type="date" name="hire_date" class="form-control" value="{{ old('hire_date', optional($personal?->hire_date)->toDateString()) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $personal?->address) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $personal?->notes) }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $personal?->active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person-gear"></i> Datos del usuario</h6>

                    {{-- Nombre de usuario --}}
                    @if($personal)
                    {{-- Edición: campo editable con verificación en tiempo real --}}
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">
                            Nombre de usuario <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                            <input type="text" id="personalUsername" name="username"
                                   class="form-control @error('username') is-invalid @enderror"
                                   value="{{ old('username', $personal->user?->name) }}"
                                   autocomplete="username"
                                   data-user-id="{{ $personal->user_id }}"
                                   data-check-url="{{ route('users.check-username') }}"
                                   required>
                            <span class="input-group-text bg-white border-start-0" id="personalUsernameStatus" style="min-width:38px;justify-content:center;"></span>
                            @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div id="personalUsernameFeedback" class="mt-1" style="font-size:.78rem;min-height:1.2em;"></div>
                        <ul class="text-muted mt-1 mb-0 ps-3" style="font-size:.72rem;line-height:1.6;">
                            <li>Sin espacios · mínimo 3 caracteres · debe ser único.</li>
                        </ul>
                    </div>
                    @else
                    {{-- Creación: informativo --}}
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>El nombre de usuario se genera automáticamente con base en el nombre completo.
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email de acceso <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $personal?->user?->email ?? $personal?->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contraseña {{ $personal ? '(opcional para cambiar)' : '' }} <span class="text-danger">{{ $personal ? '' : '*' }}</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ $personal ? '' : 'required' }}>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Confirmar contraseña {{ $personal ? '(si cambiaste)' : '' }}</label>
                        <input type="password" name="password_confirmation" class="form-control" {{ $personal ? '' : 'required' }}>
                    </div>

                    <button class="btn btn-primary w-100" type="submit" id="personalSubmitBtn">
                        <i class="bi bi-save"></i> {{ $personal ? 'Guardar cambios' : 'Crear personal y usuario' }}
                    </button>
                    <a href="{{ route('personal.index') }}" class="btn btn-light border w-100 mt-2">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

@if($personal)
@push('scripts')
<script>
(function () {
    const inp       = document.getElementById('personalUsername');
    if (!inp) return;
    const statusEl  = document.getElementById('personalUsernameStatus');
    const feedback  = document.getElementById('personalUsernameFeedback');
    const submitBtn = document.getElementById('personalSubmitBtn');
    const checkUrl  = inp.dataset.checkUrl;
    const userId    = inp.dataset.userId;
    const original  = inp.value.trim();
    let timer = null, lastChecked = null, pendingCheck = false;

    function setState(state, msg) {
        const icons  = { loading:'<span class="spinner-border spinner-border-sm text-secondary"></span>', ok:'<i class="bi bi-check-circle-fill text-success"></i>', error:'<i class="bi bi-x-circle-fill text-danger"></i>', warn:'<i class="bi bi-exclamation-circle-fill text-warning"></i>', '':'' };
        const colors = { ok:'text-success', error:'text-danger', warn:'text-warning', loading:'text-muted', '':'' };
        statusEl.innerHTML = icons[state] ?? '';
        feedback.innerHTML = msg ? `<span class="${colors[state]}">${msg}</span>` : '';
        // Solo bloquear el envío cuando hay un error confirmado; loading no bloquea
        // (el servidor valida de todas formas).
        const blocked = (state === 'error');
        submitBtn.disabled = blocked;
        submitBtn.dataset.blocked = blocked ? '1' : '0';
    }

    function check(val) {
        if (val === lastChecked) return;
        lastChecked = val;
        if (val === original)  { setState('', ''); return; }
        if (val.length === 0)  { setState('', ''); return; }
        if (val.length < 3)    { setState('warn',  'Mínimo 3 caracteres.'); return; }
        if (/\s/.test(val))    { setState('error', 'No se permiten espacios.'); return; }
        setState('loading', 'Verificando…');
        fetch(`${checkUrl}?name=${encodeURIComponent(val)}&ignore=${userId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => setState(data.available ? 'ok' : 'error', data.message))
        .catch(() => setState('', ''));
    }

    inp.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => check(this.value.trim()), 450);
    });
    inp.addEventListener('blur', function () {
        clearTimeout(timer);
        check(this.value.trim());
    });
    inp.closest('form').addEventListener('submit', function (e) {
        // Solo bloquear si hay un error explícito; el estado loading/vacío no impide guardar.
        if (submitBtn.disabled && submitBtn.dataset.blocked === '1') e.preventDefault();
    });
})();
</script>
@endpush
@endif
