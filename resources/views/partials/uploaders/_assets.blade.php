{{-- Assets compartidos para los uploaders (foto/imágenes y documentos). Se emiten una sola vez. --}}
@once
@push('styles')
<style>
.media-uploader { --mu-accent:#2563eb; }
.media-uploader.card-box {
    border:1px solid #eceef2; border-radius:16px; background:#fff; padding:18px;
    box-shadow:0 1px 2px rgba(16,24,40,.04);
}
.mu-head { display:flex; align-items:center; gap:12px; margin-bottom:14px; }
.mu-head-icon {
    width:40px; height:40px; border-radius:11px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:color-mix(in srgb, var(--mu-accent) 13%, #fff); color:var(--mu-accent); font-size:1.15rem;
}
.mu-head-title { font-weight:700; font-size:.98rem; color:#1f2937; line-height:1.1; }
.mu-head-sub { font-size:.78rem; color:#8a93a2; margin-top:2px; }

.mu-tabs { display:flex; gap:6px; background:#f1f3f6; border-radius:11px; padding:5px; margin-bottom:14px; }
.mu-tab {
    flex:1; border:0; background:transparent; border-radius:8px; padding:.5rem .25rem;
    font-size:.85rem; font-weight:600; color:#6b7280; cursor:pointer; transition:.15s;
    display:flex; align-items:center; justify-content:center; gap:.4rem;
}
.mu-tab.active { background:#fff; color:var(--mu-accent); box-shadow:0 1px 3px rgba(16,24,40,.12); }
.mu-tab:not(.active):hover { color:#374151; }

.mu-chips { display:flex; flex-wrap:wrap; gap:7px; margin-bottom:14px; }
.mu-chip {
    border:1px solid color-mix(in srgb, var(--mu-accent) 35%, #fff);
    color:var(--mu-accent); background:color-mix(in srgb, var(--mu-accent) 7%, #fff);
    border-radius:50rem; padding:.3rem .72rem; font-size:.76rem; font-weight:600; cursor:pointer;
    display:inline-flex; align-items:center; gap:.35rem; transition:.15s;
}
.mu-chip:hover { background:var(--mu-accent); color:#fff; }

.mu-drop {
    display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; gap:6px;
    border:2px dashed color-mix(in srgb, var(--mu-accent) 32%, #d7dbe2); border-radius:14px;
    background:color-mix(in srgb, var(--mu-accent) 4%, #fbfcff); padding:26px 16px; cursor:pointer; transition:.15s;
}
.mu-drop:hover, .mu-drop.drag { border-color:var(--mu-accent); background:color-mix(in srgb, var(--mu-accent) 9%, #fff); }
.mu-drop-icon {
    width:46px; height:46px; border-radius:50%; background:#fff;
    border:1px solid color-mix(in srgb, var(--mu-accent) 22%, #e9ecf2); color:var(--mu-accent);
    display:flex; align-items:center; justify-content:center; font-size:1.25rem; box-shadow:0 1px 2px rgba(16,24,40,.05);
}
.mu-drop-text { font-weight:700; color:#374151; font-size:.9rem; }
.mu-drop-hint { font-size:.74rem; color:#9aa2af; }

.mu-cam-frame {
    position:relative; border-radius:14px; overflow:hidden; background:#0f1115;
    aspect-ratio:4/3; display:flex; align-items:center; justify-content:center;
}
.mu-video { width:100%; height:100%; object-fit:cover; display:none; }
.mu-video.on { display:block; }
.mu-cam-off { position:absolute; color:#6b7280; display:flex; flex-direction:column; align-items:center; gap:6px; font-size:.8rem; }
.mu-cam-off i { font-size:1.8rem; }
.mu-cam-actions { display:flex; gap:8px; justify-content:center; margin-top:10px; }
.mu-btn { border:0; border-radius:9px; padding:.45rem .95rem; font-size:.82rem; font-weight:600; cursor:pointer; }
.mu-btn-light { background:#eef0f4; color:#374151; }
.mu-btn-accent { background:var(--mu-accent); color:#fff; }

.mu-previews { display:flex; flex-wrap:wrap; gap:10px; }
.mu-previews:not(:empty) { margin-top:14px; }
.mu-prev-item { position:relative; width:84px; height:84px; border-radius:11px; overflow:hidden; border:1px solid #e7e9ee; background:#f6f7f9; }
.mu-prev-item img { width:100%; height:100%; object-fit:cover; }
.mu-prev-del {
    position:absolute; top:3px; right:3px; width:20px; height:20px; border-radius:50%; border:0;
    background:rgba(17,18,20,.6); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.7rem; cursor:pointer;
}
.mu-prev-cur { position:absolute; left:0; right:0; bottom:0; background:var(--mu-accent); color:#fff; font-size:.6rem; text-align:center; padding:1px 0; }

.mu-doc-list { display:flex; flex-direction:column; gap:8px; }
.mu-doc-list:not(:empty) { margin-top:14px; }
.mu-doc-item { display:flex; align-items:center; gap:10px; border:1px solid #e9ebf0; border-radius:11px; padding:8px 10px; background:#fff; }
.mu-doc-item > img, .mu-doc-ic { width:42px; height:42px; border-radius:8px; object-fit:cover; flex-shrink:0; }
.mu-doc-ic { background:color-mix(in srgb, var(--mu-accent) 10%, #fff); color:var(--mu-accent); display:flex; align-items:center; justify-content:center; font-size:1.2rem; }
.mu-doc-meta { flex:1; min-width:0; }
.mu-doc-type { font-weight:700; font-size:.8rem; color:#374151; }
.mu-doc-name { font-size:.72rem; color:#9aa2af; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.mu-doc-del { border:0; background:transparent; color:#b9c0cb; cursor:pointer; padding:4px; }
.mu-doc-del:hover { color:#e11d48; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    if (window.__muInit) return;
    window.__muInit = true;

    const isImage = f => f.type && f.type.startsWith('image/');
    const sizeOk  = (f, maxMb) => f.size <= maxMb * 1024 * 1024;

    // ── Cámara compartida ────────────────────────────────────────
    function setupCamera(root, onCapture) {
        const video = root.querySelector('.mu-video');
        if (!video) return null;
        const canvas  = root.querySelector('.mu-canvas');
        const camOff  = root.querySelector('.mu-cam-off');
        const startB  = root.querySelector('[data-cam="start"]');
        const snapB   = root.querySelector('[data-cam="snap"]');
        const stopB   = root.querySelector('[data-cam="stop"]');
        let stream = null;

        function start() {
            if (stream) return; // ya está encendida
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Tu navegador no permite usar la cámara aquí.');
                return;
            }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
                .then(s => {
                    stream = s; video.srcObject = s; video.classList.add('on');
                    camOff && camOff.classList.add('d-none');
                    startB && startB.classList.add('d-none');
                    snapB && snapB.classList.remove('d-none');
                    stopB && stopB.classList.remove('d-none');
                })
                .catch(() => alert('No se pudo acceder a la cámara. Revisa los permisos del navegador.'));
        }
        function stop() {
            if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
            video.classList.remove('on');
            camOff && camOff.classList.remove('d-none');
            startB && startB.classList.remove('d-none');
            snapB && snapB.classList.add('d-none');
            stopB && stopB.classList.add('d-none');
        }
        function snap() {
            if (!stream) return;
            const w = video.videoWidth, h = video.videoHeight;
            canvas.width = w; canvas.height = h;
            canvas.getContext('2d').drawImage(video, 0, 0, w, h);
            canvas.toBlob(b => {
                if (!b) return;
                onCapture(new File([b], 'captura_' + Date.now() + '.jpg', { type: 'image/jpeg' }));
            }, 'image/jpeg', 0.9);
        }
        startB && startB.addEventListener('click', start);
        stopB  && stopB.addEventListener('click', stop);
        snapB  && snapB.addEventListener('click', snap);
        return { start, stop };
    }

    function setupTabs(root, onSwitch) {
        root.querySelectorAll('.mu-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                root.querySelectorAll('.mu-tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                root.querySelectorAll('.mu-pane').forEach(p => p.classList.add('d-none'));
                const pane = tab.dataset.pane;
                const el = root.querySelector('.mu-pane-' + pane);
                el && el.classList.remove('d-none');
                onSwitch && onSwitch(pane);
            });
        });
    }

    function setupDrop(zone, onFiles) {
        ['dragover', 'dragenter'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag'); }));
        ['dragleave', 'dragend'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('drag')));
        zone.addEventListener('drop', e => {
            e.preventDefault(); zone.classList.remove('drag');
            if (e.dataTransfer.files.length) onFiles(e.dataTransfer.files);
        });
    }

    // ════════════ UPLOADER DE IMÁGENES ════════════
    function initImage(root) {
        const input    = root.querySelector('.mu-input');
        const multiple = root.dataset.multiple === '1';
        const maxMb    = parseFloat(root.dataset.maxMb || '5');
        const maxFiles = root.dataset.maxFiles ? parseInt(root.dataset.maxFiles) : null;
        const current  = root.dataset.current || '';
        const prev     = root.querySelector('.mu-previews');
        const dt       = new DataTransfer();

        function sync() { input.files = dt.files; render(); }

        function addFiles(list) {
            const files = Array.from(list);
            if (!multiple) { while (dt.items.length) dt.items.remove(0); }
            for (const f of files) {
                if (!isImage(f)) { alert('“' + f.name + '” no es una imagen.'); continue; }
                if (!sizeOk(f, maxMb)) { alert('“' + f.name + '” supera ' + maxMb + ' MB.'); continue; }
                if (maxFiles && dt.items.length >= maxFiles) { alert('Máximo ' + maxFiles + ' imágenes.'); break; }
                dt.items.add(f);
                if (!multiple) break;
            }
            sync();
        }
        function removeAt(i) {
            const keep = Array.from(dt.files).filter((_, x) => x !== i);
            while (dt.items.length) dt.items.remove(0);
            keep.forEach(f => dt.items.add(f));
            sync();
        }
        function render() {
            prev.innerHTML = '';
            const files = Array.from(dt.files);
            if (files.length === 0 && current && !multiple) {
                prev.insertAdjacentHTML('beforeend',
                    '<div class="mu-prev-item"><img src="' + current + '"><span class="mu-prev-cur">Actual</span></div>');
                return;
            }
            files.forEach((f, i) => {
                const item = document.createElement('div');
                item.className = 'mu-prev-item';
                item.innerHTML =
                    '<img src="' + URL.createObjectURL(f) + '">' +
                    '<button type="button" class="mu-prev-del" title="Quitar"><i class="bi bi-x"></i></button>' +
                    (multiple && i === 0 ? '<span class="mu-prev-cur">Principal</span>' : '');
                item.querySelector('.mu-prev-del').addEventListener('click', () => removeAt(i));
                prev.appendChild(item);
            });
        }

        input.addEventListener('change', () => addFiles(input.files));
        const drop = root.querySelector('.mu-drop');
        if (drop) setupDrop(drop, addFiles);
        let cam;
        setupTabs(root, pane => {
            if (pane === 'camera') { cam && cam.start(); }   // abre la cámara directo
            else if (cam) cam.stop();
        });
        cam = setupCamera(root, file => addFiles([file]));
        render();
    }

    // ════════════ UPLOADER DE DOCUMENTOS ════════════
    function initDocs(root) {
        const list    = root.querySelector('.mu-doc-list');
        const types   = JSON.parse(root.dataset.types || '{}');
        const maxMb   = parseFloat(root.dataset.maxMb || '5');
        const picker  = root.querySelector('.mu-doc-picker');
        const defType = root.dataset.defaultType || 'other';
        let pendingType = defType;
        let idx = parseInt(root.dataset.startIndex || '0');

        function addEntry(file, type) {
            const t      = type || pendingType;
            const i      = idx++;
            const label  = types[t] || 'Documento';
            const isOther = t === 'other';
            const wrap = document.createElement('div');
            wrap.className = 'mu-doc-item';
            const thumb = isImage(file)
                ? '<img src="' + URL.createObjectURL(file) + '">'
                : '<span class="mu-doc-ic"><i class="bi bi-file-earmark-pdf"></i></span>';
            wrap.innerHTML =
                thumb +
                '<div class="mu-doc-meta">' +
                    '<div class="mu-doc-type">' + label + '</div>' +
                    '<div class="mu-doc-name">' + file.name + '</div>' +
                    '<input type="text" name="doc_label[' + i + ']" class="form-control form-control-sm mt-1" ' +
                        'placeholder="Nombre / referencia" ' + (isOther ? '' : 'style="display:none"') + '>' +
                '</div>' +
                '<input type="hidden" name="doc_type[' + i + ']" value="' + t + '">' +
                '<button type="button" class="mu-doc-del" title="Quitar"><i class="bi bi-x-lg"></i></button>';
            const fi = document.createElement('input');
            fi.type = 'file'; fi.name = 'doc_file[' + i + ']'; fi.className = 'd-none';
            const d = new DataTransfer(); d.items.add(file); fi.files = d.files;
            wrap.appendChild(fi);
            wrap.querySelector('.mu-doc-del').addEventListener('click', () => wrap.remove());
            list.appendChild(wrap);
        }

        function handleFiles(files, type) {
            Array.from(files).forEach(f => {
                if (!sizeOk(f, maxMb)) { alert('“' + f.name + '” supera ' + maxMb + ' MB.'); return; }
                addEntry(f, type);
            });
        }

        root.querySelectorAll('[data-doc-chip]').forEach(chip => {
            chip.addEventListener('click', () => { pendingType = chip.dataset.type || defType; picker.click(); });
        });
        const drop = root.querySelector('.mu-drop');
        if (drop) {
            drop.addEventListener('click', () => { pendingType = defType; picker.click(); });
            setupDrop(drop, files => handleFiles(files, defType));
        }
        picker.addEventListener('change', () => { handleFiles(picker.files, pendingType); picker.value = ''; });

        let cam;
        setupTabs(root, pane => {
            if (pane === 'camera') { cam && cam.start(); }   // abre la cámara directo
            else if (cam) cam.stop();
        });
        cam = setupCamera(root, file => handleFiles([file], pendingType));
    }

    function boot(scope) {
        (scope || document).querySelectorAll('[data-uploader]:not([data-mu-done])').forEach(el => {
            el.setAttribute('data-mu-done', '1'); initImage(el);
        });
        (scope || document).querySelectorAll('[data-doc-uploader]:not([data-mu-done])').forEach(el => {
            el.setAttribute('data-mu-done', '1'); initDocs(el);
        });
    }

    document.addEventListener('DOMContentLoaded', () => boot(document));
    window.muBoot = boot;
})();
</script>
@endpush
@endonce
