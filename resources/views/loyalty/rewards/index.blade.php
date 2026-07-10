@extends('layouts.app')
@section('title', 'Fidelización · Recompensas')
@section('page')
@php
    $u  = auth()->user();
    $co = $u->getCurrentCompany();
    $canManage = $u->is_super_admin
        || $u->hasPermissionInCompany('loyalty-rewards.edit', $co)
        || $u->hasPermissionInCompany('loyalty-rewards.create', $co);

    $rewardsPayload = $rewards->map(fn ($r) => [
        'id'          => $r->id,
        'product_id'  => $r->product_id,
        'name'        => $r->name,
        'image_url'   => $r->image_url,
        'points_cost' => $r->points_cost,
        'stock'       => $r->stock,
        'active'      => (bool) $r->active,
    ])->values();
@endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gift me-2 text-danger"></i>Recompensas</h1>
            <p class="text-muted mb-0 small">Arma tu catálogo eligiendo productos: el nombre y la imagen se toman del producto.</p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
    @endif

    @if($catalogUrl)
    {{-- Catálogo público (link para enviar + PDF) --}}
    <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #7c3aed !important;">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <div class="flex-grow-1" style="min-width:240px;">
                    <div class="fw-semibold small mb-1"><i class="bi bi-link-45deg me-1" style="color:#7c3aed;"></i>Catálogo público</div>
                    <div class="input-group input-group-sm">
                        <input type="text" id="catalogLink" class="form-control" value="{{ $catalogUrl }}" readonly onclick="this.select()">
                        <button class="btn btn-light border" type="button" onclick="copyCatalogLink(this)"><i class="bi bi-clipboard"></i> Copiar</button>
                    </div>
                </div>
                <a href="{{ $catalogUrl }}" target="_blank" class="btn btn-sm btn-light border"><i class="bi bi-box-arrow-up-right me-1"></i>Ver catálogo</a>
                <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#qrModal"><i class="bi bi-qr-code me-1"></i>QR</button>
                <a href="{{ $catalogUrl }}?print=1" target="_blank" class="btn btn-sm btn-primary"><i class="bi bi-filetype-pdf me-1"></i>Descargar PDF</a>
            </div>
            <div class="text-muted mt-2" style="font-size:.72rem;"><i class="bi bi-info-circle me-1"></i>Página pública (sin inicio de sesión) con las recompensas activas. Compártela con tus clientes.</div>
        </div>
    </div>
    @endif

    {{-- ── Panel doble: productos ↔ catálogo ─────────────────────────── --}}
    <div class="row g-3">

        {{-- Izquierda: productos disponibles --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #0ea5e9 !important;">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-box-seam me-2" style="color:#0ea5e9;"></i>Productos disponibles</h6>
                </div>
                <div class="card-body p-3">
                    <div class="input-group input-group-sm mb-2">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="prodSearch" class="form-control border-start-0" placeholder="Buscar producto…" autocomplete="off">
                    </div>
                    <div id="availableList" class="lr-list">
                        @forelse($products as $p)
                        @continue(in_array($p['id'], $rewardProductIds))
                        <div class="lr-item prod-item" role="button"
                             data-id="{{ $p['id'] }}"
                             data-name="{{ $p['name'] }}"
                             data-image="{{ $p['image'] }}"
                             data-stock="{{ $p['stock'] }}">
                            <div class="lr-thumb">
                                @if($p['image'])<img src="{{ $p['image'] }}" alt="">@else<i class="bi bi-box-seam"></i>@endif
                            </div>
                            <div class="lr-info">
                                <div class="lr-name">{{ $p['name'] }}</div>
                                <div class="lr-sub">Stock: {{ (int) $p['stock'] }}</div>
                            </div>
                            <i class="bi bi-arrow-right-circle lr-add-ic"></i>
                        </div>
                        @empty
                        @endforelse
                    </div>
                    <div id="availableEmpty" class="text-center text-muted py-4 d-none">
                        <i class="bi bi-check2-circle d-block fs-3 mb-1 opacity-25"></i>
                        <span class="small">No hay productos disponibles para agregar.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Derecha: catálogo de recompensas --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #7c3aed !important;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-gift me-2" style="color:#7c3aed;"></i>En el catálogo</h6>
                    <span class="badge bg-light text-muted border" id="catalogCount">0</span>
                </div>
                <div class="card-body p-3">
                    <div id="catalogList" class="lr-list"></div>
                    <div id="catalogEmpty" class="text-center text-muted py-4 d-none">
                        <i class="bi bi-gift d-block fs-3 mb-1 opacity-25"></i>
                        <span class="small">Aún no hay recompensas. Elige un producto de la izquierda.</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@if($catalogUrl)
{{-- Modal QR del catálogo --}}
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold"><i class="bi bi-qr-code me-2 text-muted"></i>QR del catálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="qrBox" data-url="{{ $catalogUrl }}" class="d-inline-block p-2 border rounded-3 bg-white"></div>
                <div class="text-muted mt-2" style="font-size:.74rem;">Escanéalo para abrir el catálogo público.</div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="downloadQr()"><i class="bi bi-download me-1"></i>Descargar PNG</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .lr-list { max-height: 62vh; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; }
    .lr-item {
        display: flex; align-items: center; gap: .7rem;
        border: 1px solid #eceef2; border-radius: 12px; padding: .55rem .7rem; background: #fff; transition: all .15s ease;
    }
    .prod-item { cursor: pointer; }
    .prod-item:hover { border-color: #0ea5e9; background: #f0f9ff; transform: translateX(2px); }
    .lr-thumb {
        width: 46px; height: 46px; border-radius: 9px; flex-shrink: 0; overflow: hidden;
        background: #f2f4f7; display: flex; align-items: center; justify-content: center; color: #b6bdc7; font-size: 1.2rem;
    }
    .lr-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .lr-info { flex: 1; min-width: 0; }
    .lr-name { font-weight: 600; font-size: .84rem; line-height: 1.15; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lr-sub { font-size: .72rem; color: #8a929c; }
    .lr-add-ic { color: #0ea5e9; font-size: 1.15rem; flex-shrink: 0; }
    .reward-row.is-draft { border-color: #7c3aed; background: #faf5ff; }
    .rw-fields { display: flex; align-items: flex-end; gap: .4rem; flex-wrap: wrap; }
    .rw-fields .fld { display: flex; flex-direction: column; }
    .rw-fields .fld label { font-size: .62rem; text-transform: uppercase; letter-spacing: .03em; color: #8a929c; margin-bottom: 1px; }
    .rw-fields input[type=number] { width: 82px; }
    .reward-row.inactive { opacity: .6; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
function copyCatalogLink(btn) {
    const inp = document.getElementById('catalogLink');
    inp.select();
    navigator.clipboard.writeText(inp.value).then(() => {
        const html = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Copiado';
        setTimeout(() => btn.innerHTML = html, 1800);
    }).catch(() => { document.execCommand('copy'); });
}

(function () {
    const modal = document.getElementById('qrModal');
    if (!modal) return;
    let built = false;
    modal.addEventListener('shown.bs.modal', function () {
        if (built || typeof QRCode === 'undefined') return;
        const box = document.getElementById('qrBox');
        new QRCode(box, { text: box.dataset.url, width: 220, height: 220, correctLevel: QRCode.CorrectLevel.M });
        built = true;
    });
})();

function downloadQr() {
    const box = document.getElementById('qrBox');
    const canvas = box.querySelector('canvas');
    const img = box.querySelector('img');
    const data = canvas ? canvas.toDataURL('image/png') : (img ? img.src : null);
    if (!data) return;
    const a = document.createElement('a');
    a.href = data; a.download = 'catalogo-qr.png';
    document.body.appendChild(a); a.click(); a.remove();
}

// ── Panel doble: productos ↔ catálogo de recompensas ─────────────────
(function () {
    'use strict';
    const CSRF      = '{{ csrf_token() }}';
    const canManage = @json($canManage);
    const REWARDS   = @json($rewardsPayload);
    const URL_STORE   = '{{ route('loyalty.rewards.catalog.store') }}';
    const URL_UPDATE  = '{{ url('loyalty/rewards') }}';   // + /{id}/catalog
    const URL_DESTROY = '{{ url('loyalty/rewards') }}';   // + /{id}

    const listAvail = document.getElementById('availableList');
    const listCat   = document.getElementById('catalogList');
    const emptyAvail = document.getElementById('availableEmpty');
    const emptyCat   = document.getElementById('catalogEmpty');
    const countBadge = document.getElementById('catalogCount');

    const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    function toast(msg, ok) {
        let c = document.getElementById('lrToast');
        if (!c) { c = document.createElement('div'); c.id = 'lrToast';
            c.style.cssText = 'position:fixed;top:1rem;right:1rem;z-index:1090;display:flex;flex-direction:column;gap:.5rem;';
            document.body.appendChild(c); }
        const color = ok === false ? '#e11d48' : '#16a34a';
        const icon  = ok === false ? 'bi-x-octagon' : 'bi-check-circle';
        const el = document.createElement('div');
        el.style.cssText = 'background:#fff;border-left:4px solid '+color+';box-shadow:0 6px 22px rgba(0,0,0,.16);border-radius:8px;padding:.7rem .9rem;font-size:.85rem;max-width:320px;display:flex;align-items:center;gap:.5rem;';
        el.innerHTML = '<i class="bi '+icon+'" style="color:'+color+';"></i><span>'+esc(msg)+'</span>';
        c.appendChild(el);
        setTimeout(() => { el.style.transition='opacity .3s'; el.style.opacity='0'; setTimeout(()=>el.remove(),300); }, 3000);
    }

    function thumb(url) {
        return url ? '<div class="lr-thumb"><img src="'+esc(url)+'" alt=""></div>'
                   : '<div class="lr-thumb"><i class="bi bi-gift"></i></div>';
    }

    // Construye una fila del catálogo (recompensa). d: {id?, product_id, name, image_url, points_cost, stock, active}
    function buildRewardRow(d) {
        const row = document.createElement('div');
        row.className = 'lr-item reward-row' + (d.id ? '' : ' is-draft') + (d.active === false ? ' inactive' : '');
        if (d.id) row.dataset.rewardId = d.id;
        row.dataset.productId = d.product_id;
        row.dataset.name  = d.name || '';
        row.dataset.image = d.image_url || '';
        row.innerHTML =
            thumb(d.image_url) +
            '<div class="lr-info">' +
                '<div class="lr-name">' + esc(d.name) + '</div>' +
                '<div class="rw-fields mt-1">' +
                    '<div class="fld"><label>Puntos</label>' +
                        '<input type="number" class="form-control form-control-sm rw-points" min="1" step="1" value="' + (d.points_cost ?? '') + '" placeholder="0"></div>' +
                    '<div class="fld"><label>Stock</label>' +
                        '<input type="number" class="form-control form-control-sm rw-stock" min="0" step="1" value="' + (d.stock ?? '') + '" placeholder="∞"></div>' +
                    '<div class="fld"><label>Activo</label>' +
                        '<div class="form-check form-switch mt-1"><input class="form-check-input rw-active" type="checkbox" ' + (d.active === false ? '' : 'checked') + '></div></div>' +
                '</div>' +
            '</div>' +
            '<div class="d-flex flex-column gap-1">' +
                '<button type="button" class="btn btn-sm btn-primary rw-save" title="Guardar"><i class="bi bi-check-lg"></i></button>' +
                '<button type="button" class="btn btn-sm btn-light border text-danger rw-remove" title="Quitar"><i class="bi bi-x-lg"></i></button>' +
            '</div>';
        return row;
    }

    function refreshEmptyStates() {
        const availCount = listAvail.querySelectorAll('.prod-item').length;
        emptyAvail.classList.toggle('d-none', availCount > 0);
        const catCount = listCat.querySelectorAll('.reward-row').length;
        emptyCat.classList.toggle('d-none', catCount > 0);
        countBadge.textContent = catCount;
    }

    // Devuelve un producto al panel izquierdo (tras quitarlo del catálogo).
    function restoreProduct(row) {
        const item = document.createElement('div');
        item.className = 'lr-item prod-item';
        item.setAttribute('role', 'button');
        item.dataset.id = row.dataset.productId;
        item.dataset.name = row.dataset.name;
        item.dataset.image = row.dataset.image;
        item.dataset.stock = '';
        item.innerHTML = thumb(row.dataset.image) +
            '<div class="lr-info"><div class="lr-name">' + esc(row.dataset.name) + '</div><div class="lr-sub">En inventario</div></div>' +
            '<i class="bi bi-arrow-right-circle lr-add-ic"></i>';
        listAvail.appendChild(item);
    }

    // Mover producto → catálogo (borrador)
    listAvail.addEventListener('click', function (e) {
        if (!canManage) return;
        const item = e.target.closest('.prod-item');
        if (!item) return;
        const row = buildRewardRow({
            product_id: item.dataset.id, name: item.dataset.name,
            image_url: item.dataset.image, points_cost: '', stock: '', active: true,
        });
        listCat.appendChild(row);
        item.remove();
        refreshEmptyStates();
        row.querySelector('.rw-points').focus();
    });

    // Guardar / Quitar (delegado)
    listCat.addEventListener('click', function (e) {
        const saveBtn = e.target.closest('.rw-save');
        const remBtn  = e.target.closest('.rw-remove');
        if (!saveBtn && !remBtn) return;
        const row = e.target.closest('.reward-row');
        if (!row) return;

        if (saveBtn) saveRow(row, saveBtn);
        else removeRow(row);
    });

    function saveRow(row, btn) {
        const points = parseInt(row.querySelector('.rw-points').value, 10);
        const stockRaw = row.querySelector('.rw-stock').value.trim();
        const active = row.querySelector('.rw-active').checked;
        if (isNaN(points) || points < 1) { toast('Ingresa un costo en puntos válido (≥ 1).', false); row.querySelector('.rw-points').focus(); return; }

        const id = row.dataset.rewardId;
        const isNew = !id;
        const url = isNew ? URL_STORE : (URL_UPDATE + '/' + id + '/catalog');
        const payload = { points_cost: points, stock: stockRaw === '' ? null : parseInt(stockRaw, 10), active: active ? 1 : 0 };
        if (isNew) payload.product_id = row.dataset.productId;

        const orig = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        fetch(url, {
            method: isNew ? 'POST' : 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        })
        .then(r => r.json().then(d => ({ ok: r.ok, d })))
        .then(({ ok, d }) => {
            btn.disabled = false; btn.innerHTML = orig;
            if (!ok || !d.ok) { toast(d.message || (d.errors ? Object.values(d.errors).flat().join(' ') : 'No se pudo guardar.'), false); return; }
            row.dataset.rewardId = d.reward.id;
            row.classList.remove('is-draft');
            row.classList.toggle('inactive', !d.reward.active);
            toast(d.message || 'Guardado.');
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = orig; toast('Error de conexión.', false); });
    }

    function removeRow(row) {
        const id = row.dataset.rewardId;
        if (!id) { // borrador sin guardar
            restoreProduct(row); row.remove(); refreshEmptyStates(); return;
        }
        window.appConfirm({
            title: 'Quitar recompensa',
            message: '¿Quitar esta recompensa del catálogo?',
            confirmText: 'Quitar',
        }).then(function (okConfirm) {
            if (!okConfirm) return;
            fetch(URL_DESTROY + '/' + id, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json().then(d => ({ ok: r.ok, d })))
            .then(({ ok, d }) => {
                if (!ok || !d.ok) { toast(d.message || 'No se pudo quitar.', false); return; }
                restoreProduct(row); row.remove(); refreshEmptyStates();
                toast(d.message || 'Quitada.');
            })
            .catch(() => toast('Error de conexión.', false));
        });
    }

    // Buscador de productos
    document.getElementById('prodSearch').addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        listAvail.querySelectorAll('.prod-item').forEach(it => {
            const match = (it.dataset.name || '').toLowerCase().includes(q);
            it.style.display = match ? '' : 'none';
        });
    });

    // Cargar recompensas existentes en el panel derecho
    REWARDS.forEach(r => listCat.appendChild(buildRewardRow(r)));
    refreshEmptyStates();
})();
</script>
@endpush
@endsection
