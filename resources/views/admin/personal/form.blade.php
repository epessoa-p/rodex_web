<div class="container-fluid" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1">{{ $personal ? 'Editar personal' : 'Nuevo personal' }}</h1>
            <p class="text-muted mb-0">Registra al personal. El acceso al sistema (usuario) es opcional: actívalo solo si necesita iniciar sesión.</p>
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
                            <select name="cargo_id" id="cargo_id" class="form-select" required
                                    data-cargos-url="{{ route('personal.cargos') }}"
                                    data-selected="{{ old('cargo_id', $personal?->cargo_id) }}">
                                @if($companies->count() > 1 && !old('company_id', $personal?->company_id))
                                    <option value="">Selecciona una empresa primero</option>
                                @else
                                    <option value="">Seleccionar cargo</option>
                                    @foreach($cargos as $cargo)
                                        <option value="{{ $cargo->id }}" {{ (string) old('cargo_id', $personal?->cargo_id) === (string) $cargo->id ? 'selected' : '' }}>
                                            {{ $cargo->name }} · Rol: {{ $cargo->role?->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sucursal <span class="text-muted small">(opcional)</span></label>
                            <select name="branch_id" id="branch_id" class="form-select"
                                    data-branches-url="{{ route('personal.branches') }}"
                                    data-selected="{{ old('branch_id', $personal?->branch_id) }}">
                                @if($companies->count() > 1 && !old('company_id', $personal?->company_id))
                                    <option value="">Selecciona una empresa primero</option>
                                @else
                                    <option value="">Sin sucursal</option>
                                    @foreach(($branches ?? collect()) as $branch)
                                        <option value="{{ $branch->id }}" {{ (string) old('branch_id', $personal?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="form-text">Sucursal base del empleado. No cambia la sucursal de sus ventas (esa viene de su caja).</div>
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

        @php
            $hasUser = $personal && $personal->user;
            // Cupo de usuarios del plan agotado (solo aplica al crear acceso nuevo).
            $userLimitReached = !$hasUser && ($userLimitStatus['reached'] ?? false);
            // Estado del toggle: siempre ON si ya tiene usuario; al crear, ON por defecto;
            // al editar un personal sin usuario, OFF salvo que se marque. Si no hay cupo, OFF forzado.
            $userOn = $hasUser ? true : (!$userLimitReached && (bool) old('create_user', $personal ? '' : '1'));
        @endphp
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-person-gear"></i> Acceso al sistema</h6>

                    {{-- Cupos de usuario del plan (con override de empresa): siempre visible.
                         Usa $userCupoStatus (cupos reales de la empresa) para que el super_admin
                         también los vea; $userLimitStatus se reserva para el bloqueo del toggle. --}}
                    @php $cupo = $userCupoStatus ?? $userLimitStatus; @endphp
                    @if($cupo['unlimited'] ?? false)
                        <div class="d-flex align-items-center gap-2 small text-muted mb-3">
                            <i class="bi bi-infinity"></i>
                            <span>Usuarios <strong>ilimitados</strong> en este plan.</span>
                        </div>
                    @else
                        @php
                            $uMax  = (int) $cupo['max'];
                            $uUsed = (int) $cupo['usage'];
                            $uLeft = max(0, $uMax - $uUsed);
                            $uPct  = $uMax > 0 ? min(100, round($uUsed / $uMax * 100)) : 100;
                            $uColor = $uLeft === 0 ? 'danger' : ($uLeft <= 1 ? 'warning' : 'success');
                        @endphp
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span class="text-muted"><i class="bi bi-people me-1"></i>Usuarios del plan</span>
                                <span class="fw-semibold text-{{ $uColor }}">{{ $uUsed }} / {{ $uMax }}</span>
                            </div>
                            <div class="progress" style="height:6px;" role="progressbar"
                                 aria-valuenow="{{ $uUsed }}" aria-valuemin="0" aria-valuemax="{{ $uMax }}">
                                <div class="progress-bar bg-{{ $uColor }}" style="width: {{ $uPct }}%"></div>
                            </div>
                            <div class="form-text mt-1">
                                @if($uLeft === 0)
                                    <span class="text-danger fw-semibold">Sin cupos disponibles.</span>
                                @else
                                    Quedan <strong>{{ $uLeft }}</strong> {{ $uLeft === 1 ? 'cupo' : 'cupos' }} de usuario.
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($hasUser)
                        {{-- Edición de un personal que YA tiene usuario: siempre editable. --}}
                        <input type="hidden" name="create_user" value="1">
                    @elseif($userLimitReached)
                        {{-- Sin cupo de usuarios en el plan: se puede registrar personal, pero no crear acceso. --}}
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="createUserToggle" disabled>
                            <label class="form-check-label fw-semibold text-muted" for="createUserToggle">
                                Crear acceso al sistema (usuario)
                            </label>
                        </div>
                        <div class="alert alert-warning small mb-3">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Alcanzaste el límite de {{ $userLimitStatus['max'] }} usuarios de tu plan.
                            Puedes registrar al personal <strong>sin acceso al sistema</strong>, o ampliar
                            tu plan para crear más usuarios.
                        </div>
                    @else
                        {{-- Toggle: crear o no un usuario para este personal. --}}
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="createUserToggle" name="create_user" value="1" {{ $userOn ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="createUserToggle">
                                Crear acceso al sistema (usuario)
                            </label>
                        </div>
                        <p class="text-muted small mb-3">
                            Actívalo solo si esta persona necesita iniciar sesión. Un cargo operativo
                            (p. ej. un mecánico) puede registrarse sin usuario.
                        </p>
                    @endif

                    <div id="user-fields" class="{{ $userOn ? '' : 'd-none' }}">
                        @if($hasUser)
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nombre de usuario <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                                <input type="text" id="personalUsername" name="username"
                                       class="form-control @error('username') is-invalid @enderror"
                                       value="{{ old('username', $personal->user?->name) }}"
                                       autocomplete="username"
                                       data-user-id="{{ $personal->user_id }}"
                                       data-check-url="{{ route('users.check-username') }}">
                                <span class="input-group-text bg-white border-start-0" id="personalUsernameStatus" style="min-width:38px;justify-content:center;"></span>
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div id="personalUsernameFeedback" class="mt-1" style="font-size:.78rem;min-height:1.2em;"></div>
                        </div>
                        @else
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-info-circle me-1"></i>El nombre de usuario se genera automáticamente con base en el nombre completo.
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email de acceso <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $personal?->user?->email ?? $personal?->email) }}">
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Contraseña {{ $hasUser ? '(opcional para cambiar)' : '' }} <span class="text-danger">{{ $hasUser ? '' : '*' }}</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                    </div>

                    <button class="btn btn-primary w-100" type="submit" id="personalSubmitBtn">
                        <i class="bi bi-save"></i> {{ $personal ? 'Guardar cambios' : 'Crear personal' }}
                    </button>
                    <a href="{{ route('personal.index') }}" class="btn btn-light border w-100 mt-2">Cancelar</a>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Muestra/oculta los campos de acceso y ajusta los required según el toggle
// "Crear acceso al sistema". Sin toggle (edición con usuario) los campos siempre
// se muestran y el email es obligatorio; la contraseña solo al crear un usuario.
(function () {
    const toggle = document.getElementById('createUserToggle');
    const fields = document.getElementById('user-fields');
    if (!fields) return;

    const email = fields.querySelector('input[name="email"]');
    const pass  = fields.querySelector('input[name="password"]');
    const passC = fields.querySelector('input[name="password_confirmation"]');
    const isEditUser = !toggle; // sin toggle => el personal ya tiene usuario

    function apply() {
        const on = toggle ? toggle.checked : true;
        fields.classList.toggle('d-none', !on);
        if (email) email.required = on;
        const needPass = on && !isEditUser; // contraseña obligatoria solo al crear usuario
        if (pass)  pass.required = needPass;
        if (passC) passC.required = needPass;
        if (!on) { [email, pass, passC].forEach(el => { if (el) el.value = ''; }); }
    }

    if (toggle) toggle.addEventListener('change', apply);
    apply();
})();
</script>
@endpush

@if($companies->count() > 1)
@push('scripts')
<script>
// Carga dinámica de cargos según la empresa elegida (solo para super_admin).
// Se usa jQuery porque los <select> están envueltos por select2: hay que
// escuchar el 'change' de jQuery y refrescar el widget con 'change.select2'.
jQuery(function ($) {
    const $company = $('#company_id');
    const $cargo   = $('#cargo_id');
    const $branch  = $('#branch_id');
    if (!$company.length || !$cargo.length) return;

    const url       = $cargo.data('cargosUrl');
    const branchUrl = $branch.data('branchesUrl');

    // Sucursales (opcional): recarga el select según la empresa elegida.
    function loadBranches(companyId, selectBranch) {
        if (!$branch.length) return;
        if (!companyId) {
            $branch.empty().append($('<option>', { value: '', text: 'Selecciona una empresa primero' }))
                   .prop('disabled', true).trigger('change.select2');
            return;
        }
        $.ajax({ url: branchUrl, data: { company_id: companyId }, dataType: 'json', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .done(function (data) {
                $branch.empty().append($('<option>', { value: '', text: 'Sin sucursal' }));
                data.forEach(function (b) {
                    const $opt = $('<option>', { value: b.id, text: b.name });
                    if (String(b.id) === String(selectBranch)) $opt.prop('selected', true);
                    $branch.append($opt);
                });
                $branch.prop('disabled', false).trigger('change.select2');
            })
            .fail(function () {
                $branch.empty().append($('<option>', { value: '', text: 'Sin sucursal' }))
                       .prop('disabled', false).trigger('change.select2');
            });
    }

    function setPlaceholder(text, disabled) {
        $cargo.empty()
              .append($('<option>', { value: '', text: text }))
              .prop('disabled', !!disabled)
              .trigger('change.select2');
    }

    function loadCargos(companyId, selectCargo) {
        if (!companyId) { setPlaceholder('Selecciona una empresa primero', true); return; }

        setPlaceholder('Cargando cargos…', true);
        $.ajax({
            url: url,
            data: { company_id: companyId },
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).done(function (data) {
            if (!data.length) { setPlaceholder('Esta empresa no tiene cargos activos', false); return; }

            $cargo.empty().append($('<option>', { value: '', text: 'Seleccionar cargo' }));
            data.forEach(function (c) {
                const label = c.name + (c.role_name ? ' · Rol: ' + c.role_name : '');
                const $opt = $('<option>', { value: c.id, text: label });
                if (String(c.id) === String(selectCargo)) $opt.prop('selected', true);
                $cargo.append($opt);
            });
            $cargo.prop('disabled', false).trigger('change.select2');
        }).fail(function () {
            setPlaceholder('No se pudieron cargar los cargos', false);
        });
    }

    // Carga inicial: si ya hay empresa elegida (edición o reintento tras error),
    // trae sus cargos y preselecciona el que estaba.
    if ($company.val()) {
        loadCargos($company.val(), $cargo.data('selected') || '');
        loadBranches($company.val(), $branch.data('selected') || '');
    } else {
        setPlaceholder('Selecciona una empresa primero', true);
    }

    // Al cambiar de empresa, recargar cargos y sucursales.
    $company.on('change', function () {
        loadCargos($(this).val(), '');
        loadBranches($(this).val(), '');
    });
});
</script>
@endpush
@endif

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
