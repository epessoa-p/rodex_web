@extends('layouts.app')
@section('title', 'Órdenes de Trabajo')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-clipboard2-pulse me-2 text-danger"></i>Órdenes de Trabajo
                @unless($canAllRecords)
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle align-middle ms-1" style="font-size:.6rem;"><i class="bi bi-person-check me-1"></i>Solo las mías</span>
                @endunless
            </h1>
            <p class="text-muted mb-0 small">Gestión de órdenes de trabajo del taller.</p>
        </div>
        @php
            $authUser  = auth()->user();
            $canCreate = $authUser->is_super_admin || $authUser->hasPermissionInCompany('workshop.create', $authUser->getCurrentCompany());
        @endphp
        <div class="d-flex gap-2 flex-wrap">
            @if($canCreate)
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#quickModal">
                <i class="bi bi-lightning-charge me-1"></i>Servicio rápido
            </button>
            @endif
            <a href="{{ route('workshop.reception') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Nueva recepción
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            @php
                $hasFilters = request('mechanic_id') || request('status') || request('client_id')
                    || request('payment_status') || request('date_from') || request('date_to') || request('q');
            @endphp
            <form method="GET">
                {{-- Conserva el estado activo (elegido por las píldoras) al enviar el resto de filtros --}}
                <input type="hidden" name="status" value="{{ request('status') }}">

                {{-- Status pills --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small mb-1 d-block">Estado</label>
                    <div class="d-flex flex-wrap gap-1">
                        <a href="{{ route('workshop.orders.index', array_merge(request()->except(['status', 'page']), [])) }}"
                           class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                            Todos
                        </a>
                        @foreach(['recibida' => 'Recibida', 'diagnosticada' => 'Diagnosticada', 'en_proceso' => 'En proceso', 'terminada' => 'Terminada', 'entregada' => 'Entregada', 'anulada' => 'Anulada'] as $val => $label)
                        <a href="{{ route('workshop.orders.index', array_merge(request()->except(['status', 'page']), ['status' => $val])) }}"
                           class="btn btn-sm {{ request('status') === $val ? 'btn-primary' : 'btn-light border' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>

                {{-- Filtros --}}
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">Buscar</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                                   placeholder="Código OT, placa o cliente…">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small mb-1">Cliente</label>
                        <select name="client_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($clients as $c)
                            <option value="{{ $c->id }}" {{ request('client_id') == $c->id ? 'selected' : '' }}>{{ $c->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small mb-1">Mecánico</label>
                        <select name="mechanic_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($mechanics as $m)
                            <option value="{{ $m->id }}" {{ request('mechanic_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold small mb-1">Pago</label>
                        <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach(\App\Models\Workshop\WorkOrder::PAYMENT_STATUSES as $val => $ps)
                            <option value="{{ $val }}" {{ request('payment_status') === $val ? 'selected' : '' }}>{{ $ps['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold small mb-1">Recepción (desde / hasta)</label>
                        <div class="d-flex gap-1">
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm flex-grow-1" style="min-width:0;" onchange="this.form.submit()">
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm flex-grow-1" style="min-width:0;" onchange="this.form.submit()">
                        </div>
                    </div>
                    <div class="col-12 d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel me-1"></i>Filtrar</button>
                        @if($hasFilters)
                        <a href="{{ route('workshop.orders.index') }}" class="btn btn-light border btn-sm">
                            <i class="bi bi-x me-1"></i>Limpiar
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Vehículo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Mecánico</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Recepción</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;letter-spacing:.04em;">Pago</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;letter-spacing:.04em;">Total</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;letter-spacing:.04em;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $canDeliver = auth()->user()->is_super_admin
                                || auth()->user()->hasPermissionInCompany('workshop.deliver', auth()->user()->getCurrentCompany());
                        @endphp
                        @forelse($orders as $order)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('workshop.orders.show', $order) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $order->code }}
                                </a>
                            </td>
                            <td class="py-2 small">{{ $order->client_display }}@if($order->is_quick) <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size:.6rem;"><i class="bi bi-lightning-charge"></i> Rápida</span>@endif</td>
                            <td class="py-2 small text-muted">{{ $order->vehicle_display ?? '—' }}</td>
                            <td class="py-2 small text-muted">{{ $order->mechanic?->name ?? '—' }}</td>
                            <td class="py-2 small text-muted">
                                {{ $order->reception_date ? \Carbon\Carbon::parse($order->reception_date)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="py-2">
                                <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle" style="font-size:.7rem;">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="py-2">
                                @if($order->status === 'entregada' || $order->paid_amount > 0)
                                    <span class="badge bg-{{ $order->payment_status_color }}-subtle text-{{ $order->payment_status_color }} border border-{{ $order->payment_status_color }}-subtle" style="font-size:.7rem;">
                                        {{ $order->payment_status_label }}
                                    </span>
                                    @if($order->delivered_at)
                                    <div class="text-muted" style="font-size:.65rem;"><i class="bi bi-box-arrow-right me-1"></i>{{ $order->delivered_at->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-end fw-semibold small">{{ money($order->total, null, 2) }}</td>
                            <td class="py-2 text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    @if($order->status === 'terminada' && $canDeliver)
                                    <a href="{{ route('workshop.deliveries.create', $order) }}"
                                       class="btn btn-sm btn-primary py-0 px-2" title="Entregar / Cobrar" style="font-size:.8rem;">
                                        <i class="bi bi-box-arrow-right"></i>
                                    </a>
                                    @endif
                                    <a href="{{ route('workshop.orders.show', $order) }}"
                                       class="btn btn-sm btn-light border py-0 px-2" title="Ver" style="font-size:.8rem;">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-clipboard2 fs-1 d-block mb-2 opacity-25"></i>
                                <p class="mb-0">No hay órdenes de trabajo registradas.</p>
                                <a href="{{ route('workshop.reception') }}" class="btn btn-sm btn-primary mt-3">
                                    <i class="bi bi-plus-lg me-1"></i>Nueva recepción
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $orders->withQueryString()->links() }}</div>

</div>

{{-- ── Modal: Servicio rápido (OT creada, entregada y cobrada en un paso) ── --}}
@if($canCreate)
<div class="modal fade" id="quickModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content border-0 shadow" method="POST" action="{{ route('workshop.orders.quick') }}" id="quickForm">
      @csrf
      <div class="modal-header border-bottom">
        <h5 class="modal-title fw-semibold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Servicio rápido</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small mb-3">
          Para trabajos al paso (ajuste de cadena, cambio de aceite…): se crea la OT, se entrega y se cobra
          <strong>en un solo paso</strong>. Cliente y vehículo son opcionales.
        </p>

        @if($quickSession)
          <div class="alert alert-success border-0 py-2 small mb-3">
            <i class="bi bi-safe2 me-1"></i>Caja abierta: <strong>{{ $quickSession->cashRegister?->name }}</strong>
            · disponible <strong>{{ money($quickSession->expectedBalance()) }}</strong>
          </div>
        @else
          <div class="alert alert-danger border-0 py-2 small mb-3">
            <i class="bi bi-lock me-1"></i>No tienes una caja abierta: ábrela para poder cobrar el servicio rápido.
          </div>
        @endif

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label fw-semibold" for="qs_services">Servicios <span class="text-danger">*</span></label>
            <select id="qs_services" name="service_ids[]" class="form-select" multiple data-placeholder="Buscar y elegir servicios…">
              @foreach($services as $s)
                <option value="{{ $s->id }}" data-price="{{ $s->price }}">{{ $s->name }} · {{ money($s->price) }}</option>
              @endforeach
            </select>
            <div class="form-text">Puedes elegir varios. Ajusta cantidad y precio abajo si hace falta.</div>
          </div>

          <div class="col-12">
            <table class="table table-sm align-middle mb-0 d-none" id="qs_lines" style="font-size:.85rem;">
              <thead class="table-light"><tr><th>Servicio</th><th style="width:90px;">Cant.</th><th style="width:130px;">Precio</th><th class="text-end" style="width:110px;">Subtotal</th></tr></thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="qs_mechanic">Mecánico <span class="text-muted fw-normal small">(opcional)</span></label>
            <select id="qs_mechanic" name="mechanic_id" class="form-select" data-placeholder="Sin mecánico">
              <option value="">— Sin mecánico —</option>
              @foreach($mechanics as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Forma de pago</label>
            <select name="method" class="form-select" data-no-search>
              <option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="tarjeta">Tarjeta</option><option value="qr">QR</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold" for="qs_client">Cliente <span class="text-muted fw-normal small">(opcional)</span></label>
            <select id="qs_client" name="client_id" class="form-select" data-placeholder="Cliente de paso">
              <option value="">— Cliente de paso —</option>
              @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->full_name }}</option>@endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="qs_vehicle">Vehículo <span class="text-muted fw-normal small">(opcional)</span></label>
            <select id="qs_vehicle" name="vehicle_id" class="form-select d-none" data-no-search>
              <option value="">— Elegir del cliente —</option>
            </select>
            <input type="text" id="qs_quick_vehicle" name="quick_vehicle" class="form-control" maxlength="80" placeholder="Ej: CG 150 ROJA (texto libre)">
          </div>

          <div class="col-md-4">
            <label class="form-label fw-semibold">Descuento</label>
            <div class="input-group"><span class="input-group-text bg-light">{{ currency_symbol() }}</span>
              <input type="number" name="discount" id="qs_discount" class="form-control" step="0.01" min="0" value="0"></div>
          </div>
          <div class="col-md-8">
            <label class="form-label fw-semibold">Notas</label>
            <input type="text" name="notes" class="form-control" maxlength="1000" placeholder="Opcional">
          </div>
        </div>
      </div>
      <div class="modal-footer border-top d-flex justify-content-between align-items-center">
        <div class="fw-bold fs-5">Total: <span id="qs_total">{{ money(0) }}</span></div>
        <div>
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary px-4" id="qs_submit" {{ $quickSession ? '' : 'disabled' }}>
            <i class="bi bi-cash-coin me-1"></i>Cobrar y cerrar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('quickModal');
    if (!modal) return;
    const sel      = document.getElementById('qs_services');
    const table    = document.getElementById('qs_lines');
    const tbody    = table.querySelector('tbody');
    const totalEl  = document.getElementById('qs_total');
    const discount = document.getElementById('qs_discount');
    const clientEl = document.getElementById('qs_client');
    const vehSel   = document.getElementById('qs_vehicle');
    const vehText  = document.getElementById('qs_quick_vehicle');
    const SYMBOL   = @json(currency_symbol());
    const VEHICLES = @json($vehicles);
    const fmt = n => SYMBOL + ' ' + (Number(n) || 0).toFixed(2);

    // Filas de cantidad/precio por servicio elegido (conserva lo ya editado).
    function rebuildLines() {
        const chosen = Array.from(sel.selectedOptions);
        const keep = {};
        tbody.querySelectorAll('tr').forEach(tr => {
            keep[tr.dataset.id] = { q: tr.querySelector('.qs-qty').value, p: tr.querySelector('.qs-price').value };
        });
        tbody.innerHTML = '';
        chosen.forEach(o => {
            const id = o.value, prev = keep[id] || {};
            const name = o.textContent.split(' · ')[0];
            const tr = document.createElement('tr');
            tr.dataset.id = id;
            tr.innerHTML = '<td>' + name + '</td>'
                + '<td><input type="number" class="form-control form-control-sm qs-qty" name="quantities[' + id + ']" min="1" step="1" value="' + (prev.q || 1) + '"></td>'
                + '<td><input type="number" class="form-control form-control-sm qs-price" name="prices[' + id + ']" min="0" step="0.01" value="' + (prev.p ?? Number(o.dataset.price || 0).toFixed(2)) + '"></td>'
                + '<td class="text-end fw-semibold qs-sub"></td>';
            tbody.appendChild(tr);
        });
        table.classList.toggle('d-none', chosen.length === 0);
        recalc();
    }

    function recalc() {
        let total = 0;
        tbody.querySelectorAll('tr').forEach(tr => {
            const q = parseFloat(tr.querySelector('.qs-qty').value) || 0;
            const p = parseFloat(tr.querySelector('.qs-price').value) || 0;
            const sub = q * p;
            tr.querySelector('.qs-sub').textContent = fmt(sub);
            total += sub;
        });
        total = Math.max(0, total - (parseFloat(discount.value) || 0));
        totalEl.textContent = fmt(total);
    }

    // Vehículo: si hay cliente, lista sus vehículos; si no, texto libre.
    function refreshVehicles() {
        const cid = clientEl.value;
        const list = cid ? VEHICLES.filter(v => String(v.client_id) === String(cid)) : [];
        vehSel.innerHTML = '<option value="">— ' + (list.length ? 'Elegir del cliente' : 'Sin vehículos registrados') + ' —</option>';
        list.forEach(v => vehSel.appendChild(new Option(v.label, v.id)));
        const useList = list.length > 0;
        vehSel.classList.toggle('d-none', !useList);
        vehText.classList.toggle('d-none', useList);
        if (useList) { vehText.value = ''; } else { vehSel.value = ''; }
    }

    const on = (el, ev, fn) => window.jQuery ? jQuery(el).on(ev, fn) : el.addEventListener(ev, fn);
    on(sel, 'change', rebuildLines);
    on(clientEl, 'change', refreshVehicles);
    tbody.addEventListener('input', recalc);
    discount.addEventListener('input', recalc);

    document.getElementById('quickForm').addEventListener('submit', function (e) {
        if (!sel.selectedOptions.length) { e.preventDefault(); alert('Elige al menos un servicio.'); return; }
        const btn = document.getElementById('qs_submit');
        btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Cobrando…';
    });

    // Reabrir con errores de validación: mostrar el modal.
    @if($errors->has('error') && old('service_ids'))
    bootstrap.Modal.getOrCreateInstance(modal).show();
    @endif
})();
</script>
@endpush