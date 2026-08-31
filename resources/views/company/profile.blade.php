@extends('layouts.app')
@section('title', 'Mi empresa')
@section('page')
@php
    $u = auth()->user(); $cc = $u->getCurrentCompany();
    $canEdit = $u->is_super_admin || $u->hasPermissionInCompany('company-profile.edit', $cc);
@endphp

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-building me-2 text-primary"></i>Mi empresa</h1>
            <p class="text-muted mb-0 small">Datos de <strong>{{ $company->name }}</strong> que se muestran a tus clientes.</p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul><button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    <form method="POST" action="{{ route('company-profile.update') }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="row g-4">
            {{-- Logo --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <p class="text-muted small text-uppercase fw-semibold mb-3">Foto / Logo</p>
                        <img src="{{ $company->logo_url }}" alt="Logo" id="logoPreview"
                             class="rounded border mb-3" style="width:150px;height:150px;object-fit:contain;background:#fff;">
                        @if($canEdit)
                        <input type="file" name="logo" id="logoInput" accept="image/*" class="form-control form-control-sm">
                        <div class="form-text">PNG o JPG, hasta 4 MB.</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Datos --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Teléfono</label>
                                <input type="text" name="phone" class="form-control" maxlength="30" value="{{ old('phone', $company->phone) }}" {{ $canEdit ? '' : 'disabled' }}>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Dirección</label>
                                <textarea name="address" class="form-control" rows="2" maxlength="500" {{ $canEdit ? '' : 'disabled' }}>{{ old('address', $company->address) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Vigencia del enlace de seguimiento (días)</label>
                                <input type="number" name="tracking_link_days" class="form-control" min="0" max="365" value="{{ old('tracking_link_days', $company->tracking_link_days ?? 1) }}" {{ $canEdit ? '' : 'disabled' }}>
                                <div class="form-text">Días que el enlace sigue activo <strong>después de entregar</strong> la OT. <strong>0 = sin caducidad</strong>.</div>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Orden de los tabs del dashboard (móvil)</label>
                                @php
                                    $labels = ['ventas' => 'Ventas', 'taller' => 'Taller', 'compras' => 'Compras'];
                                    $order = \App\Support\DashboardOrder::sanitize($company->dashboard_order);
                                    $ordered = explode(',', $order);
                                @endphp
                                <input type="hidden" name="dashboard_order" id="dashboardOrder" value="{{ $order }}">
                                <ul class="list-group" id="orderList" style="max-width:360px;">
                                    @foreach($ordered as $mod)
                                    <li class="list-group-item d-flex justify-content-between align-items-center" data-mod="{{ $mod }}">
                                        <span>{{ $labels[$mod] ?? $mod }}</span>
                                        @if($canEdit)
                                        <span class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-light border btn-up"><i class="bi bi-arrow-up"></i></button>
                                            <button type="button" class="btn btn-light border btn-down"><i class="bi bi-arrow-down"></i></button>
                                        </span>
                                        @endif
                                    </li>
                                    @endforeach
                                </ul>
                                <div class="form-text">El primero es el tab que se muestra primero.</div>
                            </div>
                        </div>
                    </div>
                    @if($canEdit)
                    <div class="card-footer bg-white border-0 text-end pb-4 pe-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cambios</button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('logoInput')?.addEventListener('change', function () {
        const f = this.files?.[0];
        if (f) document.getElementById('logoPreview').src = URL.createObjectURL(f);
    });

    // Orden de tabs del dashboard
    const list = document.getElementById('orderList');
    function syncOrder() {
        const order = Array.from(list.querySelectorAll('li')).map(li => li.dataset.mod).join(',');
        document.getElementById('dashboardOrder').value = order;
    }
    list?.addEventListener('click', function (ev) {
        const btn = ev.target.closest('.btn-up, .btn-down');
        if (!btn) return;
        const li = btn.closest('li');
        if (btn.classList.contains('btn-up') && li.previousElementSibling) {
            li.parentNode.insertBefore(li, li.previousElementSibling);
        } else if (btn.classList.contains('btn-down') && li.nextElementSibling) {
            li.parentNode.insertBefore(li.nextElementSibling, li);
        }
        syncOrder();
    });
</script>
@endpush
