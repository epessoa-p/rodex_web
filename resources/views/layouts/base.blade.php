<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset(config('brand.logo_sm')) }}">
    {{-- Con sesión, el título por defecto es el de la empresa activa (white-label). --}}
    <title>@yield('title', auth()->check()
        ? (auth()->user()->getCurrentCompany()?->name ?? config('brand.name'))
        : config('brand.name'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Chakra+Petch:wght@600;700&display=swap" rel="stylesheet">
    <style>
        /* ════════════════════════════════════════════════════════════
           Sistema de diseño (negro + rojo)
           ════════════════════════════════════════════════════════════ */
        :root {
            /* Brand */
            --brand-red:        #e63946;
            --brand-red-dark:   #c1121f;
            --brand-red-soft:   #fee2e2;
            --brand-red-tint:   #fef2f2;
            --brand-black:      #22242e;
            --brand-black-2:    #2d2f3a;
            --brand-black-3:    #3a3d4a;

            /* Surface */
            --surface-bg:       #f5f6f8;
            --surface-card:     #ffffff;
            --surface-muted:    #fafafa;

            /* Text */
            --text-primary:     #22242e;
            --text-secondary:   #4a4a52;
            --text-muted:       #8a8a92;
            --text-inverse:     #ffffff;

            /* Borders / dividers */
            --border-soft:      #ececef;
            --border-medium:    #dcdce0;

            /* Status */
            --color-success:    #16a34a;
            --color-warning:    #f59e0b;
            --color-info:       #2563eb;
            --color-danger:     var(--brand-red);

            /* Bootstrap override — primary = NEGRO (acciones neutras).
               El rojo se reserva para acentos de marca y .danger */
            --bs-primary:       var(--brand-black);
            --bs-primary-rgb:   34, 36, 46;
            --bs-link-color:    var(--brand-red);
            --bs-link-color-rgb:230, 57, 70;
            --bs-link-hover-color: var(--brand-red-dark);

            /* Radii & shadows */
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --shadow-sm: 0 1px 2px rgba(15,15,16,.04), 0 1px 1px rgba(15,15,16,.03);
            --shadow-md: 0 4px 12px rgba(15,15,16,.06), 0 2px 4px rgba(15,15,16,.04);
            --shadow-lg: 0 12px 32px rgba(15,15,16,.10), 0 4px 8px rgba(15,15,16,.05);
        }

        * { box-sizing: border-box; }

        html, body { height: auto; }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--surface-bg);
            color: var(--text-primary);
            font-size: 0.92rem;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary);
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        h1 { font-size: 1.6rem; }
        h2 { font-size: 1.35rem; }
        h3 { font-size: 1.15rem; }

        a { color: var(--brand-red); text-decoration: none; }
        a:hover { color: var(--brand-red-dark); }

        /* ── Cards ──────────────────────────────────────────────── */
        .card {
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            background: var(--surface-card);
        }
        .card .card-header {
            background: transparent;
            border-bottom: 1px solid var(--border-soft);
            padding: 14px 18px;
            font-weight: 600;
        }
        .card .card-body { padding: 18px; }

        /* ── Buttons ────────────────────────────────────────────── */
        .btn {
            font-weight: 500;
            letter-spacing: 0.005em;
            border-radius: var(--radius-sm);
            padding: 7px 14px;
            font-size: 0.88rem;
            transition: all .16s ease;
            border-width: 1px;
        }
        .btn:focus, .btn:focus-visible { box-shadow: 0 0 0 3px rgba(34,36,46,.12) !important; }

        /* Primary = NEGRO (acciones neutras: guardar, crear, confirmar) */
        .btn-primary {
            background: var(--brand-black);
            border-color: var(--brand-black);
            color: #fff;
        }
        .btn-primary:hover, .btn-primary:focus {
            background: var(--brand-black-2);
            border-color: var(--brand-black-2);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(34,36,46,.20);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-outline-primary {
            color: var(--brand-black);
            border-color: var(--brand-black);
            background: transparent;
        }
        .btn-outline-primary:hover {
            background: var(--brand-black);
            border-color: var(--brand-black);
            color: #fff;
        }

        /* Dark = igual que primary (mismo negro de marca) */
        .btn-dark {
            background: var(--brand-black);
            border-color: var(--brand-black);
            color: #fff;
        }
        .btn-dark:hover {
            background: var(--brand-black-2);
            border-color: var(--brand-black-2);
            color: #fff;
        }

        /* Danger = ROJO (eliminar, cancelar, acción destructiva) */
        .btn-danger {
            background: var(--brand-red);
            border-color: var(--brand-red);
            color: #fff;
        }
        .btn-danger:hover, .btn-danger:focus {
            background: var(--brand-red-dark);
            border-color: var(--brand-red-dark);
            color: #fff;
            box-shadow: 0 4px 10px rgba(230,57,70,.25);
        }

        .btn-outline-danger {
            color: var(--brand-red);
            border-color: var(--brand-red);
            background: transparent;
        }
        .btn-outline-danger:hover {
            background: var(--brand-red);
            border-color: var(--brand-red);
            color: #fff;
        }

        .btn-light {
            background: #fff;
            border-color: var(--border-medium);
            color: var(--text-primary);
        }
        .btn-light:hover {
            background: var(--surface-muted);
            border-color: var(--border-medium);
        }

        /* ── Forms ──────────────────────────────────────────────── */
        .form-control, .form-select {
            border: 1px solid var(--border-medium);
            border-radius: var(--radius-sm);
            padding: 8px 12px;
            font-size: 0.9rem;
            background-color: #fff;
            color: var(--text-primary);
            transition: border-color .15s ease, box-shadow .15s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--brand-black);
            box-shadow: 0 0 0 3px rgba(34,36,46,.10);
        }
        .form-label {
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }
        .input-group-text {
            background: var(--surface-muted);
            border-color: var(--border-medium);
            color: var(--text-secondary);
        }
        .form-check-input:checked {
            background-color: var(--brand-black);
            border-color: var(--brand-black);
        }
        .form-check-input:focus {
            border-color: var(--brand-black);
            box-shadow: 0 0 0 3px rgba(34,36,46,.10);
        }
        .form-switch .form-check-input:checked {
            background-color: var(--brand-black);
            border-color: var(--brand-black);
        }

        /* ── Tables ─────────────────────────────────────────────── */
        .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }
        .table > :not(caption) > * > * { padding: 12px 14px; }
        .table thead th {
            background-color: var(--surface-muted);
            border-bottom: 1px solid var(--border-soft);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .table tbody tr { border-bottom: 1px solid var(--border-soft); }
        .table-hover tbody tr:hover { background-color: var(--surface-muted); }
        .table-light, .table-light > th, .table-light > td {
            background-color: var(--surface-muted) !important;
        }

        /* ── Badges ─────────────────────────────────────────────── */
        .badge {
            font-weight: 500;
            font-size: 0.72rem;
            padding: 4px 9px;
            border-radius: 999px;
            letter-spacing: 0.01em;
        }
        .badge.bg-primary { background-color: var(--brand-black) !important; color: #fff !important; }
        .badge.bg-success-subtle { background-color: #dcfce7 !important; }
        .badge.bg-danger-subtle  { background-color: var(--brand-red-soft) !important; color: var(--brand-red-dark) !important; }
        .badge.bg-warning-subtle { background-color: #fef3c7 !important; }

        /* ── Alerts ─────────────────────────────────────────────── */
        .alert {
            border: 1px solid transparent;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            font-size: 0.88rem;
        }
        .alert-success { background: #ecfdf5; border-color: #d1fae5; color: #065f46; }
        .alert-danger  { background: var(--brand-red-tint); border-color: var(--brand-red-soft); color: var(--brand-red-dark); }
        .alert-warning { background: #fffbeb; border-color: #fef3c7; color: #92400e; }
        .alert-info    { background: #eff6ff; border-color: #dbeafe; color: #1e40af; }
        .alert-light   { background: #fafafa; border-color: var(--border-soft); color: var(--text-secondary); }

        /* ── Modal ──────────────────────────────────────────────── */
        .modal-content {
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        .modal-header { border-bottom: 1px solid var(--border-soft); padding: 18px 20px; }
        .modal-footer { border-top: 1px solid var(--border-soft); padding: 14px 20px; }
        .modal-title { font-weight: 700; }

        /* ── Dropdown ───────────────────────────────────────────── */
        .dropdown-menu {
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            padding: 6px;
            font-size: 0.88rem;
        }
        .dropdown-item {
            border-radius: var(--radius-sm);
            padding: 8px 12px;
        }
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: var(--surface-muted);
            color: var(--text-primary);
        }
        .dropdown-item.text-danger:hover {
            background-color: var(--brand-red-tint);
            color: var(--brand-red-dark);
        }

        /* ── Pagination ─────────────────────────────────────────── */
        .page-link {
            color: var(--text-secondary);
            border-color: var(--border-soft);
        }
        .page-link:hover {
            color: var(--brand-black);
            background-color: var(--surface-muted);
            border-color: var(--border-medium);
        }
        .page-item.active .page-link {
            background-color: var(--brand-black);
            border-color: var(--brand-black);
            color: #fff;
        }

        /* ── Utility — text colors override ─────────────────────── */
        .text-primary { color: var(--brand-black) !important; }
        .text-danger  { color: var(--brand-red) !important; }
        .text-muted   { color: var(--text-muted) !important; }
        .bg-primary   { background-color: var(--brand-black) !important; }
        .bg-dark      { background-color: var(--brand-black) !important; }
        .border-primary { border-color: var(--brand-black) !important; }
        /* Acento de marca para casos explícitos */
        .text-brand   { color: var(--brand-red) !important; }
        .bg-brand     { background-color: var(--brand-red) !important; color: #fff !important; }

        /* ── Scrollbar (sutil) ──────────────────────────────────── */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d1d4; border-radius: 5px; }
        ::-webkit-scrollbar-thumb:hover { background: #b1b1b4; }
        /* ── Select2 (tema acorde a la marca) ───────────────────── */
        .select2-container { width: 100% !important; }
        .select2-container--bootstrap-5 .select2-selection {
            border-color: var(--border-soft);
            border-radius: 8px;
            min-height: 38px;
            font-size: .9rem;
        }
        .select2-container--bootstrap-5 .select2-selection--single { padding: 4px 8px; }
        .select2-container--bootstrap-5.select2-container--focus .select2-selection,
        .select2-container--bootstrap-5.select2-container--open  .select2-selection {
            border-color: var(--brand-black);
            box-shadow: 0 0 0 .2rem rgba(34,36,46,.08);
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: var(--brand-black);
        }
        .form-select-sm + .select2-container--bootstrap-5 .select2-selection,
        .select2-sm + .select2-container--bootstrap-5 .select2-selection { min-height: 31px; font-size: .82rem; }
    </style>

    {{-- Select2 (búsqueda en selects) --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">

    {{-- Colores base por empresa (white-label): recolorea el menú de navegación y
         la cabecera. Solo se inyecta si la empresa activa definió sus colores;
         de lo contrario se usa la paleta por defecto del sistema. --}}
    @php $themeCompany = auth()->check() ? auth()->user()->getCurrentCompany() : null; @endphp
    @if($themeCompany?->hasTheme())
        @php
            $tPrimary = $themeCompany->theme_primary;
            $tAccent  = $themeCompany->theme_accent;
        @endphp
        {{-- Los selectores van prefijados con `body` para ganar en especificidad a
             los estilos base del layout (que se inyectan después vía @stack). --}}
        <style>
            :root {
                --company-primary: {{ $tPrimary }};
                --company-accent:  {{ $tAccent }};
            }

            /* ── Menú de navegación ─────────────────────────────── */
            body .app-sidebar { background: var(--company-primary); }
            body .app-sidebar::before {
                background: linear-gradient(180deg, var(--company-accent) 0%, transparent 100%);
            }
            body .app-link:hover i { color: var(--company-accent); }
            body .app-link.active {
                background: linear-gradient(90deg, color-mix(in srgb, var(--company-accent) 16%, transparent) 0%, transparent 100%);
                border-color: color-mix(in srgb, var(--company-accent) 30%, transparent);
            }
            body .app-link.active::before { background: var(--company-accent); }
            body .app-link.active i { color: var(--company-accent); }
            body .sidebar-section-title:has(+ ul .app-link.active) {
                background: color-mix(in srgb, var(--company-accent) 14%, transparent);
                border-color: color-mix(in srgb, var(--company-accent) 40%, transparent);
            }
            body .app-sidebar .nav.flex-column:has(.app-link.active),
            body .offcanvas .nav.flex-column:has(.app-link.active) {
                border-color: color-mix(in srgb, var(--company-accent) 40%, transparent);
            }
            body .offcanvas.offcanvas-start { background: var(--company-primary); }

            /* ── Cabecera (topbar: nombre de empresa + botones usuario/cerrar sesión) ── */
            body .app-topbar {
                background: var(--company-primary);
                border-bottom-color: color-mix(in srgb, #fff 12%, transparent);
            }
            body .app-topbar .topbar-label,
            body .app-topbar .text-muted { color: #fff !important; }
            body .app-topbar .topbar-separator { color: color-mix(in srgb, #fff 40%, transparent); }
            body .app-topbar .btn-icon,
            body .app-topbar .btn-logout {
                background: color-mix(in srgb, #fff 8%, transparent);
                color: #fff;
                border-color: color-mix(in srgb, #fff 20%, transparent);
            }
            body .app-topbar .btn-icon:hover,
            body .app-topbar .btn-logout:hover {
                background: var(--company-accent);
                border-color: var(--company-accent);
                color: #fff;
            }
        </style>
    @endif

    @stack('styles')
</head>
<body>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
    // ── Moneda (por empresa) para formateo en el cliente ─────────────
    window.APP_CURRENCY = @json(currency_symbol());
    // Formato con punto decimal (compatible con parseFloat en los subtotales).
    window.money = function (n, decimals) {
        decimals = (decimals === undefined) ? 2 : decimals;
        return window.APP_CURRENCY + ' ' + Number(n || 0).toFixed(decimals);
    };
    // Extrae el número de un texto formateado con money() (quita símbolo/separadores).
    window.parseMoney = function (text) {
        return parseFloat(String(text).replace(/[^\d.-]/g, '')) || 0;
    };
    </script>

    <script>
    // ── Select2 global: búsqueda en todos los <select> ───────────────
    window.initSelect2 = function (scope) {
        if (!window.jQuery || !jQuery.fn.select2) return;
        const $scope = scope ? jQuery(scope) : jQuery(document);
        $scope.find('select:not([data-no-search]):not([data-select2-done])').each(function () {
            const $el = jQuery(this);
            const inModal = $el.closest('.modal').length ? $el.closest('.modal') : null;
            $el.attr('data-select2-done', '1').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $el.data('placeholder') || 'Seleccionar…',
                allowClear: !$el.prop('required') && $el.find('option[value=""]').length > 0,
                dropdownParent: inModal || undefined,
            });
        });
    };

    jQuery(function () { window.initSelect2(document); });

    // Re-inicializar selects cuando se abre un modal (selects creados/ocultos)
    document.addEventListener('shown.bs.modal', function (e) { window.initSelect2(e.target); });

    // ── Confirmación bonita (reemplaza el confirm() nativo del sistema) ──
    (function () {
        let resolver = null, modal = null, modalEl = null;

        function ensureModal() {
            if (modalEl) return;
            modalEl = document.createElement('div');
            modalEl.className = 'modal fade';
            modalEl.id = 'appConfirmModal';
            modalEl.tabIndex = -1;
            modalEl.setAttribute('aria-hidden', 'true');
            modalEl.innerHTML =
                '<div class="modal-dialog modal-dialog-centered modal-sm">'
              +   '<div class="modal-content border-0 shadow">'
              +     '<div class="modal-body p-4 text-center">'
              +       '<div id="acIcon" style="width:56px;height:56px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:rgba(230,57,70,.1);color:#e63946;font-size:1.6rem;margin-bottom:.75rem;"><i class="bi bi-exclamation-triangle"></i></div>'
              +       '<h6 class="fw-bold mb-1" id="acTitle">¿Confirmar acción?</h6>'
              +       '<p class="text-muted small mb-4" id="acMsg"></p>'
              +       '<div class="d-flex gap-2">'
              +         '<button type="button" class="btn btn-light border flex-fill" id="acCancel">Cancelar</button>'
              +         '<button type="button" class="btn btn-danger flex-fill" id="acOk">Confirmar</button>'
              +       '</div>'
              +     '</div>'
              +   '</div>'
              + '</div>';
            document.body.appendChild(modalEl);
            modal = bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: 'static' });
            modalEl.querySelector('#acOk').addEventListener('click', function () { settle(true); });
            modalEl.querySelector('#acCancel').addEventListener('click', function () { settle(false); });
            modalEl.addEventListener('hidden.bs.modal', function () { settle(false); });
        }

        function settle(val) {
            const r = resolver; resolver = null;
            if (modal) modal.hide();
            if (r) r(val);
        }

        // API pública: window.appConfirm({message, title, confirmText, variant}) => Promise<bool>
        window.appConfirm = function (opts) {
            opts = opts || {};
            ensureModal();
            modalEl.querySelector('#acTitle').textContent = opts.title || '¿Confirmar acción?';
            modalEl.querySelector('#acMsg').textContent   = opts.message || '';
            const ok = modalEl.querySelector('#acOk');
            ok.textContent = opts.confirmText || 'Confirmar';
            ok.className = 'btn flex-fill btn-' + (opts.variant || 'danger');
            return new Promise(function (resolve) {
                resolver = resolve;
                modal.show();
                setTimeout(function () { ok.focus(); }, 250);
            });
        };

        function extractMsg(attr) {
            if (!attr) return null;
            const m = attr.match(/confirm\(\s*(['"`])([\s\S]*?)\1\s*\)/);
            return m ? m[2] : null;
        }

        // Interceptar envíos de formularios con confirm(...) o data-confirm
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement) || form.dataset.acConfirmed === '1') return;
            const msg = form.getAttribute('data-confirm') || extractMsg(form.getAttribute('onsubmit'));
            if (!msg) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            window.appConfirm({ message: msg }).then(function (ok) {
                if (!ok) return;
                form.removeAttribute('onsubmit');
                form.dataset.acConfirmed = '1';
                if (form.requestSubmit) form.requestSubmit(); else form.submit();
            });
        }, true);

        // Interceptar clics con confirm(...) o data-confirm (botones submit y enlaces)
        document.addEventListener('click', function (e) {
            const el = e.target.closest('[onclick], [data-confirm]');
            if (!el || el.dataset.acConfirmed === '1') return;
            const msg = el.getAttribute('data-confirm') || extractMsg(el.getAttribute('onclick'));
            if (!msg) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            window.appConfirm({ message: msg }).then(function (ok) {
                if (!ok) return;
                el.removeAttribute('onclick');
                el.dataset.acConfirmed = '1';
                const form = el.form || el.closest('form');
                if (form && (el.type === 'submit' || el.tagName === 'BUTTON')) {
                    if (form.requestSubmit) form.requestSubmit(el.type === 'submit' ? el : undefined); else form.submit();
                } else if (el.tagName === 'A' && el.getAttribute('href')) {
                    window.location = el.href;
                } else {
                    el.click();
                }
            });
        }, true);
    })();

    // ── Spinner global + deshabilitar botón al enviar formularios ────
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-no-spinner')) return;
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) return; // HTML5 inválido

        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function (btn) {
            if (btn.dataset.spinnerBound) return;
            btn.dataset.spinnerBound = '1';
            btn.dataset.originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Procesando…';
        });
    }, true);

    // Restaurar botones si se vuelve atrás (bfcache)
    window.addEventListener('pageshow', function (ev) {
        if (ev.persisted) {
            document.querySelectorAll('button[data-spinner-bound]').forEach(function (btn) {
                btn.disabled = false;
                if (btn.dataset.originalHtml) btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.spinnerBound;
            });
        }
    });
    // ── Mayúsculas automáticas en campos de texto ───────────────────
    //  Aplica a inputs de texto y textareas (nombres, descripciones,
    //  detalles, direcciones…). NO aplica a: email, usuario, contraseña,
    //  buscadores, URLs ni campos marcados con data-no-uppercase.
    (function () {
        const SKIP_RE = /(email|correo|e-?mail|username|usuario|user_name|login|password|contrase|\burl\b|slug|token|search|buscar|website)/i;

        function shouldUppercase(el) {
            if (!el) return false;
            const tag = el.tagName;
            if (tag === 'INPUT') {
                const type = (el.getAttribute('type') || 'text').toLowerCase();
                if (type !== 'text') return false;   // excluye email, password, number, search, tel, url, date…
            } else if (tag !== 'TEXTAREA') {
                return false;
            }
            if (el.readOnly || el.disabled) return false;
            if (el.hasAttribute('data-no-uppercase')) return false;

            const ac = (el.getAttribute('autocomplete') || '').toLowerCase();
            if (ac.includes('username') || ac.includes('email') || ac.includes('password') || ac.includes('current-password') || ac.includes('new-password')) return false;

            const key = ((el.name || '') + ' ' + (el.id || '')).toLowerCase();
            if (SKIP_RE.test(key)) return false;

            const ph = (el.getAttribute('placeholder') || '').toLowerCase();
            if (/buscar|search/.test(ph)) return false;   // buscadores por placeholder

            return true;
        }

        document.addEventListener('input', function (e) {
            const el = e.target;
            if (!shouldUppercase(el)) return;
            const up = el.value.toUpperCase();
            if (up === el.value) return;
            const s = el.selectionStart, en = el.selectionEnd;
            el.value = up;
            try { el.setSelectionRange(s, en); } catch (_) {}
        }, true);
    })();
    </script>

    @stack('scripts')
</body>
</html>
