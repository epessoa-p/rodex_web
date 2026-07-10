@php
    $isEdit = isset($client);
    $action = $isEdit ? route('clients.update', $client) : route('clients.store');
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

<form action="{{ $action }}" method="POST" enctype="multipart/form-data" id="clientForm">
    @csrf
    @method($method)

    <div class="row g-4">

        {{-- ── Columna principal ───────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Datos personales --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-person me-2 text-muted"></i>Datos del cliente</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">

                        @if(isset($companies) && $companies->count() > 1)
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="company_id">Empresa</label>
                            <select id="company_id" name="company_id" class="form-select">
                                @foreach($companies as $company)
                                    <option value="{{ $company->id }}"
                                        {{ (string) old('company_id', $client->company_id ?? '') === (string) $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="col-md-8">
                            <label class="form-label fw-semibold" for="full_name">
                                Nombre completo <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name"
                                   class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name', $client->full_name ?? '') }}"
                                   placeholder="Ej: Juan Pérez García" required maxlength="255">
                            @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="id_number">
                                CI / Documento
                            </label>
                            <input type="text" id="id_number" name="id_number"
                                   class="form-control @error('id_number') is-invalid @enderror"
                                   value="{{ old('id_number', $client->id_number ?? '') }}"
                                   placeholder="Ej: 12345678" maxlength="50">
                            @error('id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="phone">Teléfono</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="tel" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $client->phone ?? '') }}"
                                       placeholder="+591 70000000" maxlength="20">
                            </div>
                            @error('phone')<div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="email">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $client->email ?? '') }}"
                                       placeholder="cliente@ejemplo.com" maxlength="255">
                            </div>
                            @error('email')<div class="text-danger" style="font-size:.8rem;">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="address">Dirección</label>
                            <input type="text" id="address" name="address"
                                   class="form-control @error('address') is-invalid @enderror"
                                   value="{{ old('address', $client->address ?? '') }}"
                                   placeholder="Calle, barrio, ciudad..." maxlength="500">
                            @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" for="notes">Notas</label>
                            <textarea id="notes" name="notes"
                                      class="form-control @error('notes') is-invalid @enderror"
                                      rows="3" placeholder="Observaciones, referencias de entrega, etc.">{{ old('notes', $client->notes ?? '') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                                       {{ old('active', $client->active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="active">Cliente activo</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Ubicación --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Ubicación</h6>
                </div>
                <div class="card-body">
                    <label class="form-label fw-semibold" for="location_link">Coordenadas / enlace guardado</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-geo"></i></span>
                        <input type="url" id="location_link" name="location_link"
                               class="form-control @error('location_link') is-invalid @enderror"
                               value="{{ old('location_link', $client->location_link ?? '') }}"
                               placeholder="Se completa al seleccionar en el mapa..."
                               readonly style="background:#fafafa;cursor:default;">
                        @if(old('location_link', $client->location_link ?? null))
                        <a href="{{ old('location_link', $client->location_link ?? '#') }}" target="_blank"
                           class="btn btn-light border" title="Abrir en Google Maps">
                            <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                        @endif
                    </div>
                    @error('location_link')<div class="text-danger mb-2" style="font-size:.8rem;">{{ $message }}</div>@enderror

                    {{-- Botones de acción --}}
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMapPicker">
                            <i class="bi bi-map me-2"></i>Seleccionar en mapa
                        </button>
                        <button type="button" class="btn btn-light border" id="btnGeolocate">
                            <i class="bi bi-crosshair2 me-1"></i>Mi ubicación actual
                        </button>
                        @if(old('location_link', $client->location_link ?? null))
                        <button type="button" class="btn btn-light border text-danger" id="btnClearLocation">
                            <i class="bi bi-x-lg me-1"></i>Limpiar
                        </button>
                        @endif
                    </div>

                    {{-- Preview del mapa --}}
                    <div id="mapPreview" class="rounded-3 overflow-hidden border mt-3" style="display:none;height:200px;">
                        <iframe id="mapIframe" src="" style="width:100%;height:100%;border:0;" loading="lazy"></iframe>
                    </div>
                    @if($isEdit && $client->location_link)
                        @php
                            preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $client->location_link, $m);
                        @endphp
                        @if(count($m) >= 3)
                        <div class="rounded-3 overflow-hidden border mt-2" id="existingMapPreview" style="height:200px;">
                            <iframe src="https://www.openstreetmap.org/export/embed.html?bbox={{ $m[2]-0.005 }},{{ $m[1]-0.005 }},{{ $m[2]+0.005 }},{{ $m[1]+0.005 }}&layer=mapnik&marker={{ $m[1] }},{{ $m[2] }}"
                                    style="width:100%;height:100%;border:0;" loading="lazy"></iframe>
                        </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>

        {{-- ── Columna lateral: foto + documentos ──────────────── --}}
        <div class="col-lg-4">

            {{-- Foto del cliente --}}
            <div class="mb-4" style="position:sticky;top:1rem;">
                <x-media-upload
                    name="photo"
                    label="Foto del cliente"
                    icon="bi-person-bounding-box"
                    :max-mb="2"
                    accent="#2563eb"
                    drop-text="Arrastra aquí"
                    hint="JPG, PNG, WebP · máx. 2MB"
                    :current="$isEdit && $client->photo_url ? $client->photo_url : null" />

                {{-- Documentos adjuntos --}}
                <div class="media-uploader card-box mt-4" data-doc-uploader
                     data-types='@json(\App\Models\ClientDocument::TYPES)'
                     data-default-type="other" data-max-mb="5" data-start-index="0"
                     style="--mu-accent:#7c3aed;">
                    <div class="mu-head">
                        <span class="mu-head-icon"><i class="bi bi-folder2-open"></i></span>
                        <div>
                            <div class="mu-head-title">Documentos adjuntos</div>
                            <div class="mu-head-sub">CI, facturas, contratos u otros archivos del cliente.</div>
                        </div>
                    </div>

                    {{-- Documentos existentes (edit) --}}
                    @if($isEdit && $client->documents->isNotEmpty())
                    <div class="mb-3">
                        <div class="text-muted small mb-2 fw-semibold">Documentos actuales</div>
                        @foreach($client->documents as $doc)
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 border mb-1 bg-light">
                            <i class="bi {{ $doc->icon }} text-muted"></i>
                            <span class="text-truncate flex-grow-1" style="font-size:.8rem;">{{ $doc->display_label }}</span>
                            <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                        @endforeach
                        <small class="text-muted d-block mt-1">Para eliminar documentos usa la vista de detalle.</small>
                    </div>
                    @endif

                    <div class="mu-tabs">
                        <button type="button" class="mu-tab active" data-pane="upload"><i class="bi bi-upload"></i> Archivo</button>
                        <button type="button" class="mu-tab" data-pane="camera"><i class="bi bi-camera-video"></i> Cámara</button>
                    </div>

                    <div class="mu-pane mu-pane-upload">
                        <div class="mu-chips">
                            <button type="button" class="mu-chip" data-doc-chip data-type="ci_front"><i class="bi bi-person-vcard"></i> CI Anverso</button>
                            <button type="button" class="mu-chip" data-doc-chip data-type="ci_back"><i class="bi bi-person-vcard-fill"></i> CI Reverso</button>
                            <button type="button" class="mu-chip" data-doc-chip data-type="invoice"><i class="bi bi-receipt"></i> Factura</button>
                            <button type="button" class="mu-chip" data-doc-chip data-type="other"><i class="bi bi-plus-lg"></i> Otro</button>
                        </div>
                        <div class="mu-drop">
                            <span class="mu-drop-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                            <span class="mu-drop-text">Arrastra archivos aquí</span>
                            <span class="mu-drop-hint">o usa los botones de arriba · JPG, PNG, PDF · máx. 5MB</span>
                        </div>
                        <input type="file" class="mu-doc-picker d-none" accept="image/*,.pdf" multiple>
                    </div>

                    <div class="mu-pane mu-pane-camera d-none">
                        <div class="mu-cam-frame">
                            <video class="mu-video" autoplay playsinline muted></video>
                            <div class="mu-cam-off"><i class="bi bi-camera-video-off"></i><span>Cámara apagada</span></div>
                        </div>
                        <div class="mu-cam-actions">
                            <button type="button" class="mu-btn mu-btn-light" data-cam="start"><i class="bi bi-camera-video me-1"></i>Iniciar</button>
                            <button type="button" class="mu-btn mu-btn-accent d-none" data-cam="snap"><i class="bi bi-camera me-1"></i>Capturar</button>
                            <button type="button" class="mu-btn mu-btn-light d-none" data-cam="stop">Detener</button>
                        </div>
                        <canvas class="mu-canvas d-none"></canvas>
                    </div>

                    <div class="mu-doc-list"></div>

                    @include('partials.uploaders._assets')
                </div>
            </div>

        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ $isEdit ? route('clients.show', $client) : route('clients.index') }}" class="btn btn-light border">
            Cancelar
        </a>
        <button type="submit" class="btn btn-primary" id="btnSubmitClient">
            <span id="btnSubmitIcon"><i class="bi bi-save me-1"></i></span>
            <span class="spinner-border spinner-border-sm me-1 d-none" id="btnSubmitSpinner" role="status"></span>
            <span id="btnSubmitText">{{ $isEdit ? 'Actualizar cliente' : 'Registrar cliente' }}</span>
        </button>
    </div>
</form>

{{-- ── Modal selector de mapa ──────────────────────────────── --}}
<div class="modal fade" id="modalMapPicker" tabindex="-1" aria-labelledby="modalMapPickerLabel">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:92vw;">
        <div class="modal-content border-0 shadow-lg" style="height:94vh;">
            <div class="modal-header border-0 pb-0 px-4 pt-3" style="background:#0a0a0a;color:#fff;border-radius:14px 14px 0 0;">
                <div class="d-flex align-items-center gap-3 flex-grow-1 me-3">
                    <i class="bi bi-geo-alt-fill text-danger fs-5"></i>
                    <h5 class="modal-title mb-0 fw-bold" id="modalMapPickerLabel" style="color:#fff;">
                        Seleccionar ubicación
                    </h5>
                    {{-- Buscador de dirección --}}
                    <div class="input-group ms-3" style="max-width:460px;">
                        <span class="input-group-text" style="background:#1f1f1f;border-color:#333;color:#aaa;">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" id="mapSearchInput" class="form-control"
                               placeholder="Buscar dirección o lugar..."
                               style="background:#1a1a1a;border-color:#333;color:#e8e8e8;">
                        <button type="button" class="btn" id="btnMapSearch"
                                style="background:#e10600;border-color:#e10600;color:#fff;">
                            Buscar
                        </button>
                    </div>
                    <button type="button" class="btn btn-sm" id="btnMapGeolocate"
                            style="background:#1f1f1f;border:1px solid #333;color:#ccc;white-space:nowrap;">
                        <i class="bi bi-crosshair2 me-1"></i>Mi ubicación
                    </button>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Instrucción --}}
            <div class="px-4 py-2 d-flex align-items-center gap-3 flex-wrap"
                 style="background:#161616;border-bottom:1px solid #2a2a2a;">
                <span style="color:#9a9a9a;font-size:.8rem;">
                    <i class="bi bi-hand-index me-1 text-danger"></i>
                    Haz clic en el mapa para colocar el marcador en la ubicación del cliente.
                </span>
                <div id="mapCoordsBadge" class="ms-auto rounded-pill px-3 py-1 d-none"
                     style="background:#1f1f1f;border:1px solid #333;color:#ccc;font-size:.78rem;font-family:monospace;">
                    <i class="bi bi-geo me-1 text-danger"></i>
                    <span id="mapCoordsText">—</span>
                </div>
            </div>

            {{-- Mapa --}}
            <div id="leafletMap" style="flex:1;min-height:0;"></div>

            {{-- Footer --}}
            <div class="modal-footer border-0 px-4 py-3 d-flex justify-content-between align-items-center"
                 style="background:#0f0f0f;border-radius:0 0 14px 14px;">
                <div style="color:#6a6a6a;font-size:.78rem;">
                    Mapa: <a href="https://www.openstreetmap.org" target="_blank"
                              style="color:#555;text-decoration:none;">© OpenStreetMap</a>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btnConfirmLocation" disabled>
                        <i class="bi bi-check-lg me-1"></i>Confirmar ubicación
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #leafletMap { flex: 1; min-height: 0; }
    #modalMapPicker .modal-content { display: flex; flex-direction: column; }
    .leaflet-container { background: #1a1a1a; }
    /* Crosshair cursor sobre el mapa */
    #leafletMap { cursor: crosshair !important; }
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    {{-- La foto y los documentos del cliente ahora usan el componente <x-media-upload> --}}

    // ── Preview de mapa estático (iframe OSM) ─────────────────
    window.previewMap = function (url) {
        const el  = document.getElementById('mapPreview');
        const ifr = document.getElementById('mapIframe');
        document.getElementById('existingMapPreview')?.remove();

        const m = url.match(/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
        if (m) {
            const lat = parseFloat(m[1]), lng = parseFloat(m[2]);
            ifr.src = `https://www.openstreetmap.org/export/embed.html?bbox=${lng-.008},${lat-.008},${lng+.008},${lat+.008}&layer=mapnik&marker=${lat},${lng}`;
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    };

    const locInput = document.getElementById('location_link');
    if (locInput.value) previewMap(locInput.value);

    // ── "Mi ubicación actual" (fuera del modal) ───────────────
    document.getElementById('btnGeolocate').addEventListener('click', function () {
        if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización.'); return; }
        const btn = this;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        btn.disabled  = true;
        navigator.geolocation.getCurrentPosition(
            pos => {
                setLocation(pos.coords.latitude, pos.coords.longitude);
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Listo';
                btn.disabled  = false;
            },
            () => {
                alert('No se pudo obtener la ubicación. Verifica los permisos.');
                btn.innerHTML = '<i class="bi bi-crosshair2 me-1"></i>Mi ubicación actual';
                btn.disabled  = false;
            },
            { timeout: 10000 }
        );
    });

    // Limpiar ubicación
    document.getElementById('btnClearLocation')?.addEventListener('click', () => {
        locInput.value = '';
        document.getElementById('mapPreview').style.display = 'none';
        document.getElementById('existingMapPreview')?.remove();
    });

    // ── Función compartida para guardar coordenadas ───────────
    function setLocation(lat, lng) {
        const link = `https://www.google.com/maps?q=${lat.toFixed(7)},${lng.toFixed(7)}`;
        locInput.value = link;
        previewMap(link);
        document.getElementById('existingMapPreview')?.remove();
    }

    // ══════════════════════════════════════════════════════════
    //  MAPA LEAFLET (modal)
    // ══════════════════════════════════════════════════════════
    let leafletMap   = null;
    let pickerMarker = null;
    let pickedLat    = null;
    let pickedLng    = null;

    const modalEl = document.getElementById('modalMapPicker');

    modalEl.addEventListener('shown.bs.modal', function () {
        // Inicializar solo una vez
        if (!leafletMap) {
            // Coordenadas iniciales: Santa Cruz de la Sierra, Bolivia
            const initLat = -17.7840, initLng = -63.1821, initZoom = 13;

            leafletMap = L.map('leafletMap', {
                center: [initLat, initLng],
                zoom:   initZoom,
                zoomControl: true,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(leafletMap);

            // Ícono personalizado (rojo)
            const redIcon = L.divIcon({
                className: '',
                html: `<div style="
                    width:32px;height:40px;
                    background:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23e10600%22><path d=%22M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z%22/></svg>') center/contain no-repeat;
                    margin-left:-16px;margin-top:-40px;">
                </div>`,
                iconSize: [32, 40],
                iconAnchor: [16, 40],
            });

            // Click en el mapa → colocar marcador
            leafletMap.on('click', function (e) {
                placeMarker(e.latlng.lat, e.latlng.lng, redIcon);
            });

            // Si ya hay coordenadas guardadas, centrar ahí
            if (locInput.value) {
                const m = locInput.value.match(/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/);
                if (m) {
                    const lat = parseFloat(m[1]), lng = parseFloat(m[2]);
                    leafletMap.setView([lat, lng], 16);
                    placeMarker(lat, lng, redIcon);
                }
            }
        } else {
            // Solo invalidar tamaño al reabrir
            setTimeout(() => leafletMap.invalidateSize(), 100);
        }
    });

    function placeMarker(lat, lng, icon) {
        pickedLat = lat;
        pickedLng = lng;

        if (pickerMarker) {
            pickerMarker.setLatLng([lat, lng]);
        } else {
            const redIcon = L.divIcon({
                className: '',
                html: `<div style="width:32px;height:40px;background:url('data:image/svg+xml;utf8,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22 fill=%22%23e10600%22><path d=%22M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z%22/></svg>') center/contain no-repeat;margin-left:-16px;margin-top:-40px;"></div>`,
                iconSize: [32, 40], iconAnchor: [16, 40],
            });
            pickerMarker = L.marker([lat, lng], { icon: redIcon, draggable: true }).addTo(leafletMap);

            // Marcador arrastrable
            pickerMarker.on('dragend', function (e) {
                const pos = e.target.getLatLng();
                pickedLat = pos.lat;
                pickedLng = pos.lng;
                updateCoordsDisplay(pos.lat, pos.lng);
            });
        }

        updateCoordsDisplay(lat, lng);
        document.getElementById('btnConfirmLocation').disabled = false;
    }

    function updateCoordsDisplay(lat, lng) {
        const badge = document.getElementById('mapCoordsBadge');
        document.getElementById('mapCoordsText').textContent =
            `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        badge.classList.remove('d-none');
    }

    // Buscar dirección (Nominatim)
    function searchAddress(query) {
        if (!query.trim()) return;
        const btn = document.getElementById('btnMapSearch');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled  = true;

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`, {
            headers: { 'Accept-Language': 'es' }
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lng = parseFloat(data[0].lon);
                leafletMap.setView([lat, lng], 16);
                placeMarker(lat, lng);
            } else {
                alert('No se encontró la dirección. Intenta con un término más específico.');
            }
        })
        .catch(() => alert('Error al buscar. Verifica tu conexión.'))
        .finally(() => {
            btn.innerHTML = 'Buscar';
            btn.disabled  = false;
        });
    }

    document.getElementById('btnMapSearch').addEventListener('click', () => {
        searchAddress(document.getElementById('mapSearchInput').value);
    });
    document.getElementById('mapSearchInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); searchAddress(e.target.value); }
    });

    // Mi ubicación dentro del modal
    document.getElementById('btnMapGeolocate').addEventListener('click', function () {
        if (!navigator.geolocation) { alert('Tu navegador no soporta geolocalización.'); return; }
        const btn = this;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        btn.disabled  = true;
        navigator.geolocation.getCurrentPosition(
            pos => {
                const lat = pos.coords.latitude, lng = pos.coords.longitude;
                leafletMap.setView([lat, lng], 17);
                placeMarker(lat, lng);
                btn.innerHTML = '<i class="bi bi-crosshair2 me-1"></i>Mi ubicación';
                btn.disabled  = false;
            },
            () => {
                alert('No se pudo obtener la ubicación.');
                btn.innerHTML = '<i class="bi bi-crosshair2 me-1"></i>Mi ubicación';
                btn.disabled  = false;
            },
            { timeout: 10000 }
        );
    });

    // Confirmar ubicación seleccionada
    document.getElementById('btnConfirmLocation').addEventListener('click', function () {
        if (pickedLat === null) return;
        setLocation(pickedLat, pickedLng);
        bootstrap.Modal.getInstance(modalEl).hide();
    });

    // Reset al cerrar el modal
    modalEl.addEventListener('hidden.bs.modal', function () {
        document.getElementById('mapSearchInput').value = '';
    });

    // ── Spinner en botón guardar ──────────────────────────────
    document.getElementById('clientForm').addEventListener('submit', function () {
        const btn     = document.getElementById('btnSubmitClient');
        const icon    = document.getElementById('btnSubmitIcon');
        const spinner = document.getElementById('btnSubmitSpinner');
        const text    = document.getElementById('btnSubmitText');

        btn.disabled        = true;
        icon.classList.add('d-none');
        spinner.classList.remove('d-none');
        text.textContent    = 'Guardando...';
    });
})();
</script>
@endpush
