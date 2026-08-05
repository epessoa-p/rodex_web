{{-- Campos de colores base (white-label): menú de navegación y cabecera.
     $company puede ser null (crear) o el modelo (editar). --}}
@php $company = $company ?? null; @endphp

<div class="form-group mb-3">
    <label class="form-label">Colores base <span class="text-muted fw-normal">(menú de navegación y cabecera)</span></label>

    <div class="row g-3">
        <div class="col-md-6">
            <label for="theme_primary" class="form-label small text-muted">Color principal (fondo del menú y cabecera)</label>
            <div class="input-group">
                <input type="color" class="form-control form-control-color theme-picker"
                       data-target="theme_primary"
                       value="{{ old('theme_primary', $company?->theme_primary ?: '#22242e') }}"
                       title="Elegir color principal">
                <input type="text" id="theme_primary" name="theme_primary"
                       class="form-control theme-hex @error('theme_primary') is-invalid @enderror"
                       placeholder="#22242e (por defecto)"
                       value="{{ old('theme_primary', $company?->theme_primary) }}">
            </div>
            @error('theme_primary')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="col-md-6">
            <label for="theme_accent" class="form-label small text-muted">Color de acento (item activo y botones)</label>
            <div class="input-group">
                <input type="color" class="form-control form-control-color theme-picker"
                       data-target="theme_accent"
                       value="{{ old('theme_accent', $company?->theme_accent ?: '#e63946') }}"
                       title="Elegir color de acento">
                <input type="text" id="theme_accent" name="theme_accent"
                       class="form-control theme-hex @error('theme_accent') is-invalid @enderror"
                       placeholder="#e63946 (por defecto)"
                       value="{{ old('theme_accent', $company?->theme_accent) }}">
            </div>
            @error('theme_accent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mt-3 flex-wrap">
        <span class="form-text mb-0">Deja ambos vacíos para usar los colores por defecto del sistema.</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="themeReset">
            <i class="bi bi-arrow-counterclockwise"></i> Restablecer por defecto
        </button>

        {{-- Vista previa en miniatura --}}
        <div class="ms-auto d-flex align-items-stretch rounded overflow-hidden border" id="themePreview"
             style="height:40px; min-width:220px;">
            <div id="themePreviewMenu" style="width:70px; display:flex; align-items:center; justify-content:center;">
                <span id="themePreviewDot" style="width:14px;height:14px;border-radius:50%;display:inline-block;"></span>
            </div>
            <div id="themePreviewBar" style="flex:1; display:flex; align-items:center; padding:0 10px; gap:6px;">
                <span style="color:#fff;font-size:.72rem;">Cabecera</span>
                <span id="themePreviewBtn" style="margin-left:auto;font-size:.68rem;color:#fff;padding:2px 8px;border-radius:6px;">Botón</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const DEFAULT_PRIMARY = '#22242e';
    const DEFAULT_ACCENT  = '#e63946';
    const hexRe = /^#([0-9a-fA-F]{6})$/;

    const pickers = document.querySelectorAll('.theme-picker');
    const primaryHex = document.getElementById('theme_primary');
    const accentHex  = document.getElementById('theme_accent');
    if (!primaryHex || !accentHex) return;

    const preview    = {
        menu: document.getElementById('themePreviewMenu'),
        bar:  document.getElementById('themePreviewBar'),
        dot:  document.getElementById('themePreviewDot'),
        btn:  document.getElementById('themePreviewBtn'),
    };

    function currentPrimary() { return hexRe.test(primaryHex.value.trim()) ? primaryHex.value.trim() : DEFAULT_PRIMARY; }
    function currentAccent()  { return hexRe.test(accentHex.value.trim())  ? accentHex.value.trim()  : DEFAULT_ACCENT;  }

    function renderPreview() {
        const p = currentPrimary(), a = currentAccent();
        preview.menu.style.background = p;
        preview.bar.style.background  = p;
        preview.dot.style.background  = a;
        preview.btn.style.background  = a;
    }

    // Picker → campo de texto
    pickers.forEach(function (pk) {
        pk.addEventListener('input', function () {
            const target = document.getElementById(pk.dataset.target);
            target.value = pk.value.toUpperCase();
            renderPreview();
        });
    });

    // Campo de texto → picker (si es hex válido)
    [primaryHex, accentHex].forEach(function (inp) {
        inp.addEventListener('input', function () {
            const pk = document.querySelector('.theme-picker[data-target="' + inp.id + '"]');
            if (hexRe.test(inp.value.trim())) pk.value = inp.value.trim();
            renderPreview();
        });
    });

    // Restablecer: vacía los campos (=> el sistema usa su paleta por defecto)
    document.getElementById('themeReset').addEventListener('click', function () {
        primaryHex.value = '';
        accentHex.value  = '';
        document.querySelector('.theme-picker[data-target="theme_primary"]').value = DEFAULT_PRIMARY;
        document.querySelector('.theme-picker[data-target="theme_accent"]').value  = DEFAULT_ACCENT;
        renderPreview();
    });

    renderPreview();
})();
</script>
@endpush
