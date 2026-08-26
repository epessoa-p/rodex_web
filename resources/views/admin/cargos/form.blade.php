<div class="container-fluid" style="max-width: 1300px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1">{{ $cargo ? 'Editar cargo' : 'Nuevo cargo' }}</h1>
            <p class="text-muted mb-0">
                {{ $cargo
                    ? 'Ajusta el cargo y los permisos heredados por el personal con este cargo.'
                    : 'Al crear un cargo se genera un rol nuevo con el mismo nombre y los permisos que elijas.' }}
            </p>
        </div>
        <a href="{{ route('cargos.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Volver</a>
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

        {{-- ── Datos del cargo: ancho completo, arriba ──────────────────── --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-briefcase"></i> Datos del cargo</h6>
                    <div class="row g-3">
                        @if($companies->count() > 1)
                            <div class="col-md-5">
                                <label class="form-label">Empresa</label>
                                <select name="company_id" class="form-select" required>
                                    <option value="">Seleccionar empresa</option>
                                    @foreach($companies as $company)
                                        <option value="{{ $company->id }}" {{ (string) old('company_id', $cargo?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nombre del cargo <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cargo?->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $cargo?->active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Activo</label>
                                </div>
                            </div>
                        @else
                            <div class="col-md-9">
                                <label class="form-label">Nombre del cargo <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $cargo?->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $cargo?->active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label">Activo</label>
                                </div>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" rows="2" class="form-control" placeholder="Funciones principales del cargo...">{{ old('description', $cargo?->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Rol asociado: solo en EDICIÓN (al crear siempre es un rol nuevo) ── --}}
        @if($cargo)
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock"></i> Rol asociado</h6>

                    <div class="btn-group mb-3" role="group">
                        <input type="radio" class="btn-check" name="role_mode" id="mode_existing" value="existing"
                            {{ old('role_mode', 'existing') === 'existing' ? 'checked' : '' }} autocomplete="off">
                        <label class="btn btn-outline-primary" for="mode_existing">
                            <i class="bi bi-list-check me-1"></i> Rol existente
                        </label>

                        <input type="radio" class="btn-check" name="role_mode" id="mode_new" value="new"
                            {{ old('role_mode') === 'new' ? 'checked' : '' }} autocomplete="off">
                        <label class="btn btn-outline-primary" for="mode_new">
                            <i class="bi bi-plus-circle me-1"></i> Crear nuevo rol
                        </label>
                    </div>

                    <div class="row g-3">
                        {{-- Existing role --}}
                        <div id="existing-role-section" class="col-md-6">
                            <label class="form-label">Seleccionar rol</label>
                            <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror">
                                <option value="">Seleccionar rol...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ (string) old('role_id', $cargo?->role_id) === (string) $role->id ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ $role->slug }})
                                    </option>
                                @endforeach
                            </select>
                            @error('role_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Los permisos del rol serán heredados por el personal con este cargo.</small>
                        </div>

                        {{-- New role --}}
                        <div id="new-role-section" class="col-md-6 d-none">
                            <label class="form-label">Nombre del nuevo rol <span class="text-danger">*</span></label>
                            <input type="text" name="new_role_name" id="new_role_name" class="form-control @error('new_role_name') is-invalid @enderror"
                                value="{{ old('new_role_name') }}" placeholder="Ej: Supervisor">
                            @error('new_role_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">El slug se generará automáticamente.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- ── Permisos del rol: ancho completo, 3 columnas ─────────────── --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0"><i class="bi bi-key"></i> Permisos del rol</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-select-all">
                                <i class="bi bi-check-all"></i> Todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-deselect-all">
                                <i class="bi bi-x-lg"></i> Ninguno
                            </button>
                        </div>
                    </div>
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="permissions-info">{{ $cargo ? 'Selecciona un rol o crea uno nuevo para configurar permisos.' : 'Marca los permisos que tendrá el nuevo rol de este cargo.' }}</span>
                    </div>

                    @php
                        $planFeatures = $planFeatures ?? null; // null = no gatear (super_admin sin empresa elegida)
                        $checkedPerms = old('permissions', $cargo?->role?->permissions?->pluck('id')->toArray() ?? []);
                    @endphp
                    <div class="row g-3" id="permissions-container">
                        @foreach($permissions as $module => $modulePerms)
                            @php
                                // Feature de plan que habilita este módulo (null = administrativo/compartido, siempre disponible).
                                $feature = \App\Models\Plan::featureForPermissionModule($module);
                                $modLocked = $planFeatures !== null && $feature && !in_array($feature, $planFeatures, true);
                            @endphp
                            <div class="col-md-4 perm-module" data-feature="{{ $feature }}">
                                <div class="border rounded-3 p-3 h-100 module-box {{ $modLocked ? 'module-locked' : '' }}">
                                    {{-- Encabezado del módulo con checkbox "marcar todo el módulo" --}}
                                    <div class="form-check mb-2 pb-2 border-bottom d-flex align-items-center gap-2">
                                        <input class="form-check-input mod-check mt-0" type="checkbox"
                                            id="modcheck_{{ $loop->index }}" data-mod="{{ $loop->index }}" {{ $modLocked ? 'disabled' : '' }}>
                                        <label class="form-check-label text-uppercase text-muted small fw-bold flex-grow-1" for="modcheck_{{ $loop->index }}">
                                            <i class="bi bi-folder me-1"></i> {{ ucfirst($module) }}
                                        </label>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle module-lock-badge {{ $modLocked ? '' : 'd-none' }}"
                                              title="Este módulo no está incluido en el plan de la empresa">
                                            <i class="bi bi-lock-fill"></i> Fuera del plan
                                        </span>
                                    </div>
                                    @foreach($modulePerms as $permission)
                                        @php $isChecked = in_array($permission->id, $checkedPerms); @endphp
                                        <div class="form-check mb-1">
                                            <input class="form-check-input perm-check" type="checkbox" name="permissions[]"
                                                value="{{ $permission->id }}" id="perm_{{ $permission->id }}"
                                                data-mod="{{ $loop->parent->index }}"
                                                {{ $isChecked ? 'checked' : '' }} {{ $modLocked ? 'disabled' : '' }}>
                                            <label class="form-check-label small" for="perm_{{ $permission->id }}">
                                                {{ $permission->name }}
                                            </label>
                                        </div>
                                        {{-- Si el módulo quedó fuera del plan pero el cargo YA tenía este permiso,
                                             lo preservamos (el checkbox disabled no se envía). Evita perder permisos
                                             al guardar sólo por editar otra cosa; no permite agregar nuevos. --}}
                                        @if($modLocked && $isChecked)
                                            <input type="hidden" name="permissions[]" value="{{ $permission->id }}" class="perm-preserve" data-mod="{{ $loop->parent->index }}">
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 text-muted small">
                        <i class="bi bi-shield-check me-1"></i>
                        <span id="perm-count">{{ count(old('permissions', $cargo?->role?->permissions?->pluck('id')->toArray() ?? [])) }}</span> permisos seleccionados
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Acciones ─────────────────────────────────────────────────── --}}
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-primary px-4" type="submit"><i class="bi bi-save"></i> {{ $cargo ? 'Guardar cambios' : 'Crear cargo' }}</button>
            <a href="{{ route('cargos.index') }}" class="btn btn-light border">Cancelar</a>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Recuadro de un módulo que el plan de la empresa no incluye. */
    .module-box.module-locked { opacity: .6; background: #f8f9fa; }
    .module-box.module-locked .form-check-input { cursor: not-allowed; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const $permChecks = $('.perm-check');
    const $modChecks  = $('.mod-check');
    const $permCount  = $('#perm-count');
    const $permInfo   = $('#permissions-info');

    function updateCount() {
        $permCount.text($permChecks.filter(':checked').length);
    }

    // Sincroniza el checkbox de un módulo según sus permisos:
    // marcado (todos), indeterminado (algunos) o vacío (ninguno).
    function syncModuleHeader(mod) {
        const $group  = $('.perm-check[data-mod="' + mod + '"]');
        const total   = $group.length;
        const checked = $group.filter(':checked').length;
        const $mod    = $('#modcheck_' + mod);
        $mod.prop('checked', total > 0 && checked === total);
        $mod.prop('indeterminate', checked > 0 && checked < total);
    }
    function syncAllModuleHeaders() {
        $modChecks.each(function () { syncModuleHeader($(this).data('mod')); });
    }

    // Check del módulo → marca/desmarca todos sus permisos.
    $modChecks.on('change', function () {
        const mod = $(this).data('mod');
        $('.perm-check[data-mod="' + mod + '"]').prop('checked', $(this).prop('checked'));
        $(this).prop('indeterminate', false);
        updateCount();
    });

    // Permiso individual → actualiza el total y el encabezado de su módulo.
    $permChecks.on('change', function () {
        updateCount();
        syncModuleHeader($(this).data('mod'));
    });

    $('#btn-select-all').on('click', function () {
        $permChecks.not(':disabled').prop('checked', true);
        updateCount();
        syncAllModuleHeaders();
    });
    $('#btn-deselect-all').on('click', function () {
        $permChecks.not(':disabled').prop('checked', false);
        updateCount();
        syncAllModuleHeaders();
    });

    updateCount();
    syncAllModuleHeaders();

    // ── Gateo de módulos por el plan de la empresa ────────────────────────
    // Los recuadros de módulos que el plan de la empresa no incluye se
    // deshabilitan. En server ya vienen gateados; aquí se re-evalúa cuando el
    // super_admin cambia de empresa en el select.
    const $companySelect  = $('select[name="company_id"]');
    const planFeaturesUrl = '{{ route('cargos.plan-features') }}';

    function applyPlanGating(features) {
        // features: array de claves de plan; null => no gatear (habilitar todo).
        $('.perm-module').each(function () {
            const $mod      = $(this);
            const feature   = ($mod.data('feature') || '').toString();
            const $box      = $mod.find('.module-box');
            const $badge    = $mod.find('.module-lock-badge');
            const $inputs   = $mod.find('.perm-check');
            const $modCheck = $mod.find('.mod-check');
            // Sin feature => módulo administrativo/compartido, siempre disponible.
            const locked = feature && features !== null && features.indexOf(feature) === -1;

            if (locked) {
                $inputs.prop('checked', false).prop('disabled', true);
                $modCheck.prop('checked', false).prop('indeterminate', false).prop('disabled', true);
                $box.addClass('module-locked');
                $badge.removeClass('d-none');
                $mod.find('.perm-preserve').remove(); // al cambiar de empresa no se preservan permisos ajenos
            } else {
                $inputs.prop('disabled', false);
                $modCheck.prop('disabled', false);
                $box.removeClass('module-locked');
                $badge.addClass('d-none');
            }
        });
        updateCount();
        syncAllModuleHeaders();
    }

    if ($companySelect.length) {
        $companySelect.on('change', function () {
            const companyId = $(this).val();
            if (!companyId) { applyPlanGating(null); return; }
            $.getJSON(planFeaturesUrl, { company_id: companyId })
                .done(applyPlanGating)
                .fail(function () { applyPlanGating(null); });
        });
        // Si ya hay empresa elegida (reintento tras error de validación), gatear.
        if ($companySelect.val()) $companySelect.trigger('change');
    }

    // ── Selección/creación de rol: solo existe en el formulario de EDICIÓN ──
    const $existingSection = $('#existing-role-section');
    if ($existingSection.length) {
        const $roleSelect = $('#role_id');
        const $newSection = $('#new-role-section');
        const $modeNew    = $('#mode_new');
        const permissionsUrl = '{{ route("cargos.role-permissions", ":id") }}';

        function toggleMode() {
            const isNew = $modeNew.is(':checked');
            if (isNew) {
                $existingSection.addClass('d-none');
                $newSection.removeClass('d-none');
                $permInfo.text('Configura los permisos para el nuevo rol.');
            } else {
                $existingSection.removeClass('d-none');
                $newSection.addClass('d-none');
                const roleId = $roleSelect.val();
                $permInfo.text(roleId ? 'Permisos del rol seleccionado (puedes modificarlos).' : 'Selecciona un rol para ver sus permisos.');
            }
        }

        $('input[name="role_mode"]').on('change', toggleMode);
        toggleMode();

        $roleSelect.on('change', function () {
            const roleId = $(this).val();
            if (!roleId) {
                $permInfo.text('Selecciona un rol para ver sus permisos.');
                return;
            }
            $permInfo.text('Cargando permisos...');
            $.getJSON(permissionsUrl.replace(':id', roleId))
                .done(function (permIds) {
                    $permChecks.not(':disabled').prop('checked', false);
                    permIds.forEach(function (id) { $('#perm_' + id).not(':disabled').prop('checked', true); });
                    updateCount();
                    syncAllModuleHeaders();
                    $permInfo.text('Permisos del rol seleccionado (puedes modificarlos).');
                })
                .fail(function () {
                    $permInfo.text('Error al cargar permisos.');
                });
        });
    }
});
</script>
@endpush
