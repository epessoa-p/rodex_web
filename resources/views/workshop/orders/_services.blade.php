@php
    $editable = !in_array($order->status, ['entregada', 'anulada']);
    $canEdit  = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.edit', auth()->user()->getCurrentCompany());
    $colspan  = $editable && $canEdit ? 6 : 5;
@endphp
<div class="card border-0 shadow-sm mb-4 wo-card-services" style="border-left:4px solid #2563eb !important;">
    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center gap-2">
        <h6 class="mb-0 fw-semibold"><i class="bi bi-gear me-2" style="color:#2563eb;"></i>Servicios</h6>
        @if($editable && $canEdit)
        <button type="button" class="btn btn-primary btn-sm no-print" data-bs-toggle="modal" data-bs-target="#woServiceModal">
            <i class="bi bi-plus-lg me-1"></i>Agregar
        </button>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:.85rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Descripción</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Mecánico</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Precio</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase text-center" style="font-size:.72rem;">Cant.</th>
                        <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Subtotal</th>
                        @if($editable && $canEdit)
                        <th class="py-3 pe-3" style="width:44px;"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->services as $svc)
                    <tr class="border-bottom border-light">
                        <td class="ps-4 py-2 small">{{ $svc->description ?: $svc->service?->name }}</td>
                        <td class="py-2 small text-muted">{{ $svc->mechanic?->name ?? '—' }}</td>
                        <td class="py-2 text-end small">{{ money($svc->price, null, 2) }}</td>
                        <td class="py-2 text-center small">{{ $svc->quantity }}</td>
                        <td class="py-2 text-end fw-semibold small">{{ money($svc->subtotal, null, 2) }}</td>
                        @if($editable && $canEdit)
                        <td class="py-2 pe-3">
                            <form action="{{ route('workshop.orders.services.remove', [$order, $svc]) }}" method="POST" class="d-inline wo-ajax-form" data-no-spinner>
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-light border text-danger py-0 px-1" title="Quitar servicio">
                                    <i class="bi bi-x"></i>
                                </button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="text-center py-4 text-muted">
                            <i class="bi bi-gear d-block fs-3 mb-1 opacity-25"></i>
                            Sin servicios agregados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($editable && $canEdit)
    {{-- ── MODAL: Agregar servicio (si no existe en el catálogo, se crea) ── --}}
    <div class="modal fade" id="woServiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-gear me-2" style="color:#2563eb;"></i>Agregar servicio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('workshop.orders.services.add', $order) }}" method="POST" class="wo-ajax-form wo-add-form" data-no-spinner>
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label form-label-sm fw-semibold mb-1">Servicio</label>
                            <input type="text" name="description" class="form-control wo-svc-name"
                                   list="svcOptions" autocomplete="off" required maxlength="255"
                                   placeholder="Escribe o elige un servicio…">
                            <datalist id="svcOptions">
                                @foreach($services as $s)
                                <option value="{{ $s->name }}" data-price="{{ $s->price }}"></option>
                                @endforeach
                            </datalist>
                            <div class="form-text wo-svc-hint" style="font-size:.72rem;">Si no existe, se guarda automáticamente en el catálogo de servicios.</div>
                        </div>
                        <div class="row g-2">
                            <div class="col-7">
                                <label class="form-label form-label-sm fw-semibold mb-1">Precio</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white">{{ currency_symbol() }}</span>
                                    <input type="number" name="price" class="form-control wo-svc-price"
                                           step="0.01" min="0" required placeholder="0.00">
                                </div>
                            </div>
                            <div class="col-5">
                                <label class="form-label form-label-sm fw-semibold mb-1">Cant.</label>
                                <input type="number" name="quantity" class="form-control form-control-sm"
                                       min="1" step="1" inputmode="numeric" value="1" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label form-label-sm fw-semibold mb-1">Mecánico</label>
                                <select name="mechanic_id" class="form-select form-select-sm" data-no-search>
                                    <option value="">Sin asignar</option>
                                    @foreach($mechanics as $m)
                                    <option value="{{ $m->id }}" {{ $order->mechanic_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-light border btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-check-lg me-1"></i>Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
