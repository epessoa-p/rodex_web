{{--
    Foto por fila en las tablas de importación (Excel y carga en tabla).
    En el celular abre la cámara (o la galería); en la PC, el explorador.
    La foto se reduce en el navegador (~1600 px, JPEG) y se sube enseguida;
    la fila solo guarda el token. Se pasa al producto al confirmar.

    Uso:  ImportPhoto.pick({ start(thumb), done(token), fail(msg) })
          ImportPhoto.cellHtml({ thumb, token, busy, error })
          ImportPhoto.pending()  → fotos que aún se están subiendo
--}}
@push('styles')
<style>
.ip-btn {
    width:34px; height:34px; border-radius:8px; border:1px dashed #ced4da; background:#fafbfc; color:#6c757d;
    display:inline-flex; align-items:center; justify-content:center; padding:0; font-size:1rem; cursor:pointer;
}
.ip-btn:hover { border-color:var(--bs-primary,#0d6efd); color:var(--bs-primary,#0d6efd); background:#f5f9ff; }
.ip-btn.ip-err { border-color:#dc3545; color:#dc3545; background:#fff5f5; }
.ip-thumb { position:relative; width:34px; height:34px; display:inline-block; }
.ip-thumb img { width:34px; height:34px; object-fit:cover; border-radius:8px; border:1px solid #dee2e6; cursor:pointer; }
.ip-thumb .ip-busy {
    position:absolute; inset:0; border-radius:8px; background:rgba(255,255,255,.7);
    display:flex; align-items:center; justify-content:center;
}
.ip-thumb .ip-del {
    position:absolute; top:-6px; right:-6px; width:18px; height:18px; border-radius:50%; border:0; padding:0;
    background:#dc3545; color:#fff; font-size:.6rem; line-height:18px; text-align:center; cursor:pointer;
}
</style>
@endpush

@push('scripts')
<script>
window.ImportPhoto = (function () {
    'use strict';
    const UPLOAD_URL = @json(route('inventory.stock.import.photo'));
    const CSRF       = @json(csrf_token());
    const MAX_SIDE   = 1600;   // suficiente para la ficha y el catálogo
    const QUALITY    = 0.82;
    const THUMB_SIDE = 96;     // vista previa en la fila (y en el borrador)
    let pending = 0, handlers = null;

    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.hidden = true;
    document.body.appendChild(input);
    input.addEventListener('change', function () {
        const file = input.files[0];
        input.value = '';
        if (file && handlers) handle(file, handlers);
    });

    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload  = function () { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('No se pudo leer la imagen. Usa una foto JPG o PNG.')); };
            img.src = url;
        });
    }

    /** Lienzo con la imagen reducida al lado mayor indicado (fondo blanco para PNG). */
    function scaled(img, side) {
        const s = Math.min(1, side / Math.max(img.naturalWidth, img.naturalHeight));
        const c = document.createElement('canvas');
        c.width = Math.max(1, Math.round(img.naturalWidth * s));
        c.height = Math.max(1, Math.round(img.naturalHeight * s));
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0, c.width, c.height);
        return c;
    }

    function toBlob(canvas) {
        return new Promise(function (resolve, reject) {
            canvas.toBlob(function (b) { b ? resolve(b) : reject(new Error('No se pudo procesar la foto.')); }, 'image/jpeg', QUALITY);
        });
    }

    async function handle(file, h) {
        pending++;
        try {
            const img   = await loadImage(file);
            const thumb = scaled(img, THUMB_SIDE).toDataURL('image/jpeg', 0.7);
            h.start && h.start(thumb);
            const blob  = await toBlob(scaled(img, MAX_SIDE));

            const fd = new FormData();
            fd.append('photo', blob, 'foto.jpg');
            const r = await fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd });
            let d = {};
            try { d = await r.json(); } catch (e) { /* 413 u otra respuesta sin JSON */ }
            if (!r.ok || !d.ok) {
                const msg = d.message || (d.errors && Object.values(d.errors)[0]?.[0]) ||
                    (r.status === 413 ? 'La foto es demasiado grande.' : 'No se pudo subir la foto.');
                throw new Error(msg);
            }
            h.done && h.done(d.token);
        } catch (e) {
            h.fail && h.fail(e.message || 'No se pudo subir la foto.');
            window.showToast?.(e.message || 'No se pudo subir la foto.', 'danger');
        } finally {
            pending--;
        }
    }

    return {
        /** Abre la cámara/galería; los handlers reciben el avance de esa foto. */
        pick: function (h) { handlers = h; input.click(); },
        pending: function () { return pending; },
        cellHtml: function (p) {
            p = p || {};
            if (p.thumb) {
                return '<span class="ip-thumb"><img src="' + p.thumb + '" alt="" data-photo title="Cambiar foto">' +
                    (p.busy ? '<span class="ip-busy"><span class="spinner-border spinner-border-sm text-primary"></span></span>'
                            : '<button type="button" class="ip-del" data-photo-del tabindex="-1" title="Quitar foto"><i class="bi bi-x-lg"></i></button>') +
                    '</span>';
            }
            return '<button type="button" class="ip-btn' + (p.error ? ' ip-err' : '') + '" data-photo tabindex="-1" title="' +
                (p.error ? String(p.error).replace(/"/g, '&quot;') + ' Toca para reintentar.' : 'Tomar o elegir foto') +
                '"><i class="bi bi-camera' + (p.error ? '-fill' : '') + '"></i></button>';
        },
    };
})();
</script>
@endpush
