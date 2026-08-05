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
                            <label class="form-label small fw-semibold">Correo electrónico</label>
                            <input type="email" name="email" class="form-control bg-light" value="{{ $user->email }}" readonly tabindex="-1">
                            <div class="text-muted mt-1" style="font-size:.75rem;"><i class="bi bi-lock me-1"></i>El correo no se puede modificar desde aquí.</div>
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
