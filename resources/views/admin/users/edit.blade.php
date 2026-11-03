@extends('layouts.app')
@section('title', 'Editar Usuario')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-pencil-square me-2 text-danger"></i>Editar Usuario</h1>
            <p class="text-muted mb-0 small">Modifica los datos del usuario <strong>{{ $user->name }}</strong>.</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('users.update', $user) }}" method="POST" id="editUserForm">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-7">

                {{-- Nombre de usuario --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-person me-2 text-muted"></i>Nombre de usuario</h6>
                    </div>
                    <div class="card-body p-4">
                        <label for="name" class="form-label small fw-semibold">
                            Nombre de usuario <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-at"></i></span>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   autocomplete="username"
                                   data-user-id="{{ $user->id }}"
                                   data-check-url="{{ route('users.check-username') }}"
                                   required>
                            <span class="input-group-text bg-white border-start-0" id="usernameStatus" style="min-width:38px;justify-content:center;"></span>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div id="usernameFeedback" class="mt-1" style="font-size:.78rem;min-height:1.2em;"></div>
                        <ul class="text-muted mt-2 mb-0 ps-3" style="font-size:.75rem;line-height:1.6;">
                            <li>Solo letras, números, guiones y puntos — <strong>sin espacios</strong>.</li>
                            <li>Mínimo 3 caracteres, máximo 255.</li>
                            <li>Debe ser único: ningún otro usuario puede tener el mismo nombre.</li>
                        </ul>
                    </div>
                </div>

                {{-- Datos de contacto --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-envelope me-2 text-muted"></i>Datos de contacto</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold">Correo electrónico <span class="text-danger">*</span></label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   autocomplete="email" required>
                            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="text-muted mt-1" style="font-size:.75rem;"><i class="bi bi-info-circle me-1"></i>También sirve para iniciar sesión (con el correo o el nombre de usuario).</div>
                        </div>
                        <div>
                            <label for="phone" class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" id="phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $user->phone) }}"
                                   placeholder="Ej. +505 8888-0000">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                {{-- Contraseña (opcional: vacía = se mantiene la actual) --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-key me-2 text-muted"></i>Cambiar contraseña</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label small fw-semibold">Nueva contraseña</label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password"
                                           class="form-control @error('password') is-invalid @enderror"
                                           autocomplete="new-password" minlength="8" placeholder="Mínimo 8 caracteres">
                                    <button type="button" class="btn btn-light border" data-toggle-pass="password" title="Mostrar/ocultar" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label small fw-semibold">Repetir contraseña</label>
                                <div class="input-group">
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                           class="form-control" autocomplete="new-password" minlength="8">
                                    <button type="button" class="btn btn-light border" data-toggle-pass="password_confirmation" title="Mostrar/ocultar" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                                </div>
                                <div id="passMatch" class="mt-1" style="font-size:.78rem;min-height:1.2em;"></div>
                            </div>
                        </div>
                        <div class="text-muted mt-2" style="font-size:.75rem;">
                            <i class="bi bi-info-circle me-1"></i>Déjala vacía para mantener la contraseña actual.
                            Al cambiarla se cierra la sesión de este usuario en la app móvil.
                        </div>
                    </div>
                </div>

            </div>

            {{-- Panel lateral: vista previa --}}
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm" style="border-left:4px solid var(--brand-red,#e63946) !important;">
                    <div class="card-body p-4">
                        <div class="text-muted small mb-1">Nombre de usuario (vista previa)</div>
                        <div class="fw-bold fs-5 font-monospace text-break" id="previewName">{{ $user->name }}</div>
                        <hr>
                        <div class="text-muted small">
                            <div class="mb-1"><i class="bi bi-envelope me-1"></i>{{ $user->email }}</div>
                            <div><i class="bi bi-calendar3 me-1"></i>Creado el {{ $user->created_at->format('d/m/Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-light border"><i class="bi bi-x-lg me-1"></i>Cancelar</a>
            <button type="submit" id="submitBtn" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Guardar cambios
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const inp       = document.getElementById('name');
    const statusEl  = document.getElementById('usernameStatus');
    const feedback  = document.getElementById('usernameFeedback');
    const preview   = document.getElementById('previewName');
    const submitBtn = document.getElementById('submitBtn');
    const checkUrl  = inp.dataset.checkUrl;
    const userId    = inp.dataset.userId;
    const original  = inp.value.trim();

    let timer = null;
    let pendingCheck = false;

    function setState(state, msg) {
        const icons = {
            loading : '<span class="spinner-border spinner-border-sm text-secondary"></span>',
            ok      : '<i class="bi bi-check-circle-fill text-success"></i>',
            error   : '<i class="bi bi-x-circle-fill text-danger"></i>',
            warn    : '<i class="bi bi-exclamation-circle-fill text-warning"></i>',
            ''      : '',
        };
        const colors = { ok:'text-success', error:'text-danger', warn:'text-warning', loading:'text-muted', '':'' };
        statusEl.innerHTML  = icons[state] ?? '';
        feedback.innerHTML  = msg ? `<span class="${colors[state]}">${msg}</span>` : '';
        // Solo bloquear en error confirmado; loading no bloquea (servidor valida igual).
        const blocked = (state === 'error');
        submitBtn.disabled = blocked;
        submitBtn.dataset.blocked = blocked ? '1' : '0';
    }

    let lastChecked = null;

    function check(val) {
        if (val === lastChecked) return;
        lastChecked = val;

        // Si no cambió del original, sin marca
        if (val === original) { setState('', ''); return; }

        // Validaciones locales antes de llamar al servidor
        if (val.length === 0)  { setState('', ''); return; }
        if (val.length < 3)    { setState('warn',  'Mínimo 3 caracteres.'); return; }
        if (/\s/.test(val))    { setState('error', 'No se permiten espacios.'); return; }

        setState('loading', 'Verificando disponibilidad…');

        fetch(`${checkUrl}?name=${encodeURIComponent(val)}&ignore=${userId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => setState(data.available ? 'ok' : 'error', data.message))
        .catch(() => setState('', ''));
    }

    inp.addEventListener('input', function () {
        preview.textContent = this.value.trim() || original;
        clearTimeout(timer);
        timer = setTimeout(() => check(this.value.trim()), 450);
    });

    inp.addEventListener('blur', function () {
        clearTimeout(timer);
        check(this.value.trim());
    });

    // ── Contraseña: mostrar/ocultar y aviso de coincidencia ──
    const pass  = document.getElementById('password');
    const pass2 = document.getElementById('password_confirmation');
    const match = document.getElementById('passMatch');
    document.querySelectorAll('[data-toggle-pass]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const el = document.getElementById(this.dataset.togglePass);
            const show = el.type === 'password';
            el.type = show ? 'text' : 'password';
            this.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    });
    // La validación nativa frena el envío antes del submit (así el spinner global no queda trabado).
    function checkMatch() {
        const mismatch = (pass.value || pass2.value) && pass.value !== pass2.value;
        pass2.setCustomValidity(mismatch ? 'Las contraseñas no coinciden.' : '');
        if (!pass.value && !pass2.value) { match.innerHTML = ''; pass2.required = false; return; }
        pass2.required = true;   // si escriben una nueva, hay que repetirla
        match.innerHTML = !pass2.value ? ''
            : pass.value === pass2.value
                ? '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Coinciden.</span>'
                : '<span class="text-danger"><i class="bi bi-x-circle me-1"></i>No coinciden.</span>';
    }
    pass.addEventListener('input', checkMatch);
    pass2.addEventListener('input', checkMatch);

    document.getElementById('editUserForm').addEventListener('submit', function (e) {
        // Solo bloquear si hay un error explícito de validación de nombre de usuario.
        // (No mirar `submitBtn.disabled` a secas: el spinner global lo deshabilita en captura
        //  antes de este handler, y eso cancelaría el envío dejando el botón en "Procesando…").
        if (submitBtn.disabled && submitBtn.dataset.blocked === '1') e.preventDefault();
    });
})();
</script>
@endpush
@endsection
