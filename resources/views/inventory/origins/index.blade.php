@extends('layouts.app')
@section('title', 'Orígenes')
@section('page')
@php
    $u = auth()->user();
    $cc = $u->getCurrentCompany();
    $canCreate = $u->is_super_admin || $u->hasPermissionInCompany('product-origins.create', $cc);
    $canEdit   = $u->is_super_admin || $u->hasPermissionInCompany('product-origins.edit', $cc);
    $canDelete = $u->is_super_admin || $u->hasPermissionInCompany('product-origins.delete', $cc);
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-globe-americas me-2 text-danger"></i>Orígenes</h1>
            <p class="text-muted mb-0 small">País de procedencia de los productos (Brasil, China, Japón…).</p>
        </div>
        @if($canCreate)
        <button type="button" class="btn btn-primary" onclick="openOriginModal()">
            <i class="bi bi-plus-lg me-1"></i> Nuevo origen
        </button>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                <thead>
                    <tr class="table-light border-bottom">
                        <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Origen</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Productos</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="originsBody">
                    @forelse($origins as $o)
                    <tr id="origin-row-{{ $o->id }}" data-id="{{ $o->id }}" data-name="{{ $o->name }}" data-active="{{ $o->active ? 1 : 0 }}" class="border-bottom border-light">
                        <td class="ps-3 py-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;">
                                    <i class="bi bi-globe-americas" style="font-size:.72rem;"></i>
                                </div>
                                <span class="fw-semibold o-name">{{ $o->name }}</span>
                            </div>
                        </td>
                        <td class="py-2 text-center">
                            <span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">{{ $counts[$o->id] ?? 0 }}</span>
                        </td>
                        <td class="py-2 o-state">
                            @if($o->active)
                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activo</span>
                            @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactivo</span>
                            @endif
                        </td>
                        <td class="py-2 text-end pe-3">
                            @if($canEdit)
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;"
                                    onclick="openOriginModal({{ $o->id }})"><i class="bi bi-pencil"></i></button>
                            @endif
                            @if($canDelete)
                            <button type="button" class="btn btn-sm btn-light border text-danger py-0 px-2" title="Eliminar" style="font-size:.8rem;"
                                    onclick="deleteOrigin({{ $o->id }})"><i class="bi bi-trash"></i></button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr id="originsEmpty">
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-globe-americas fs-1 d-block mb-2 opacity-25"></i>
                            <p class="mb-0">No hay orígenes registrados.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal alta/edición ─────────────────────────────────────────── --}}
@if($canCreate || $canEdit)
<div class="modal fade" id="originModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-globe-americas me-2 text-primary"></i><span id="omTitle">Nuevo origen</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="omAlert" class="alert alert-danger d-none py-2 small"></div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="om_name">Nombre <span class="text-danger">*</span></label>
                    <input type="text" id="om_name" class="form-control" maxlength="80" placeholder="Ej: China, Brasil, Nacional…">
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="om_active" checked>
                    <label class="form-check-label fw-semibold" for="om_active">Activo</label>
                    <div class="form-text">Los orígenes inactivos no aparecen al asignar en productos.</div>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4" id="om_save" onclick="saveOrigin()">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
(function () {
    const CSRF     = '{{ csrf_token() }}';
    const BASE     = '{{ url('inventory/origins') }}';
    const canEdit  = @json($canEdit);
    const canDel   = @json($canDelete);
    let editingId  = null;

    const stateHtml = (active) => active
        ? '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.75rem;">Activo</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.75rem;">Inactivo</span>';

    function toast(msg) {
        let c = document.getElementById('oToast');
        if (!c) { c = document.createElement('div'); c.id = 'oToast'; c.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;'; document.body.appendChild(c); }
        const el = document.createElement('div');
        el.style.cssText = 'background:#fff;border-left:4px solid #16a34a;box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.7rem .9rem;font-size:.85rem;max-width:320px;display:flex;align-items:center;gap:.5rem;';
        el.innerHTML = '<i class="bi bi-check-circle" style="color:#16a34a;"></i><span>' + msg + '</span>';
        c.appendChild(el);
        setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3000);
    }

    function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&quot;').replace(/"/g,'&quot;'); }

    window.openOriginModal = function (id) {
        editingId = id || null;
        const modalEl = document.getElementById('originModal');
        document.getElementById('omAlert').classList.add('d-none');
        document.getElementById('omTitle').textContent = editingId ? 'Editar origen' : 'Nuevo origen';
        if (editingId) {
            const row = document.getElementById('origin-row-' + editingId);
            document.getElementById('om_name').value   = row.dataset.name;
            document.getElementById('om_active').checked = row.dataset.active === '1';
        } else {
            document.getElementById('om_name').value = '';
            document.getElementById('om_active').checked = true;
        }
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        setTimeout(() => document.getElementById('om_name').focus(), 300);
    };

    window.saveOrigin = function () {
        const name = document.getElementById('om_name').value.trim();
        const active = document.getElementById('om_active').checked;
        const alertBox = document.getElementById('omAlert');
        if (!name) { alertBox.textContent = 'El nombre es obligatorio.'; alertBox.classList.remove('d-none'); return; }

        const btn = document.getElementById('om_save');
        const original = btn.innerHTML;
        alertBox.classList.add('d-none');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';

        const url    = editingId ? (BASE + '/' + editingId) : BASE;
        const method = editingId ? 'PUT' : 'POST';

        fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ name: name, active: active ? 1 : 0 }),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (!ok || !d.ok) {
                const msg = d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo guardar.');
                alertBox.textContent = msg; alertBox.classList.remove('d-none');
                return;
            }
            upsertRow(d.origin);
            bootstrap.Modal.getInstance(document.getElementById('originModal'))?.hide();
            toast('Origen «' + d.origin.name + '» guardado.');
        })
        .catch(() => { alertBox.textContent = 'Error de conexión.'; alertBox.classList.remove('d-none'); })
        .finally(() => { btn.disabled = false; btn.innerHTML = original; });
    };

    window.deleteOrigin = function (id) {
        const row = document.getElementById('origin-row-' + id);
        if (!confirm('¿Eliminar el origen «' + (row?.dataset.name || '') + '»? Los productos conservan su registro (quedan sin origen).')) return;

        fetch(BASE + '/' + id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            if (!ok || !d.ok) { alert(d.message || 'No se pudo eliminar.'); return; }
            row?.remove();
            toast('Origen eliminado.');
        })
        .catch(() => alert('Error de conexión.'));
    };

    // Crea la fila si es nueva, o actualiza la existente.
    function upsertRow(o) {
        const empty = document.getElementById('originsEmpty');
        if (empty) empty.remove();

        let row = document.getElementById('origin-row-' + o.id);
        if (row) {
            row.dataset.name = o.name;
            row.dataset.active = o.active ? 1 : 0;
            row.querySelector('.o-name').textContent = o.name;
            row.querySelector('.o-state').innerHTML = stateHtml(o.active);
            return;
        }

        const editBtn = canEdit ? '<button type="button" class="btn btn-sm btn-light border py-0 px-2" title="Editar" style="font-size:.8rem;" onclick="openOriginModal(' + o.id + ')"><i class="bi bi-pencil"></i></button>' : '';
        const delBtn  = canDel ? '<button type="button" class="btn btn-sm btn-light border text-danger py-0 px-2" title="Eliminar" style="font-size:.8rem;" onclick="deleteOrigin(' + o.id + ')"><i class="bi bi-trash"></i></button>' : '';

        row = document.createElement('tr');
        row.id = 'origin-row-' + o.id;
        row.className = 'border-bottom border-light';
        row.dataset.id = o.id; row.dataset.name = o.name; row.dataset.active = o.active ? 1 : 0;
        row.innerHTML =
            '<td class="ps-3 py-2"><div class="d-flex align-items-center gap-2">' +
                '<div class="bg-danger bg-opacity-10 text-danger rounded-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width:26px;height:26px;"><i class="bi bi-globe-americas" style="font-size:.72rem;"></i></div>' +
                '<span class="fw-semibold o-name">' + esc(o.name) + '</span></div></td>' +
            '<td class="py-2 text-center"><span class="badge bg-light text-dark border fw-normal" style="font-size:.75rem;">' + (o.count || 0) + '</span></td>' +
            '<td class="py-2 o-state">' + stateHtml(o.active) + '</td>' +
            '<td class="py-2 text-end pe-3">' + editBtn + ' ' + delBtn + '</td>';
        document.getElementById('originsBody').prepend(row);
    }
})();
</script>
@endpush
@endsection
