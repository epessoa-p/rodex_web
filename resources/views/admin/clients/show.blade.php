@extends('layouts.app')

@section('title', 'Cliente: ' . $client->full_name)

@section('page')
@php
    $u = auth()->user();
    $company = $u->getCurrentCompany();
    $can = fn ($perm) => $u->is_super_admin || $u->hasPermissionInCompany($perm, $company);

    // Definición de tabs (clave => [label, icon, permiso, conteo])
    $tabDefs = [
        'ventas'       => ['Ventas',        'bi-receipt',            'sales.view',     $client->sales_count],
        'servicios'    => ['Servicios',     'bi-tools',              'workshop.view',  $client->work_orders_count],
        'vehiculos'    => ['Vehículos',     'bi-bicycle',            'vehicles.view',  $client->vehicles_count],
        'alquileres'   => ['Alquileres',    'bi-calendar2-week',     'rentals.view',   $client->rental_contracts_count],
        'cotizaciones' => ['Cotizaciones',  'bi-file-earmark-text',  'quotes.view',    $client->quotes_count],
        'garantias'    => ['Garantías',     'bi-shield-check',       'warranties.view',$client->warranties_count],
    ];
    $tabs = array_filter($tabDefs, fn ($t) => $can($t[2]));
    $firstTab = array_key_first($tabs);
@endphp
<div class="container-fluid">

    {{-- ── Header compacto ──────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3 overflow-hidden">
        <div style="height:4px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff4d4d 50%,transparent 100%);"></div>
        <div class="card-body p-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                {{-- Avatar --}}
                <div class="position-relative flex-shrink-0">
                    @if($client->photo_url)
                        <img src="{{ $client->photo_url }}" alt="{{ $client->full_name }}" class="rounded-3 object-fit-cover border" style="width:56px;height:56px;">
                    @else
                        <div class="rounded-3 border d-flex align-items-center justify-content-center" style="width:56px;height:56px;background:#f4f4f5;font-size:1.4rem;font-weight:800;color:#ccc;">
                            {{ strtoupper(substr($client->full_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                {{-- Info principal --}}
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-5">{{ $client->full_name }}</h1>
                        <span class="badge {{ $client->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}" style="font-size:.68rem;">
                            {{ $client->active ? 'Activo' : 'Inactivo' }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted mt-1" style="font-size:.8rem;">
                        @if($client->id_number)<span><i class="bi bi-card-text me-1"></i>{{ $client->id_number }}</span>@endif
                        @if($client->phone)<a href="tel:{{ $client->phone }}" class="text-muted text-decoration-none"><i class="bi bi-telephone me-1"></i>{{ $client->phone }}</a>@endif
                        @if($client->email)<a href="mailto:{{ $client->email }}" class="text-muted text-decoration-none"><i class="bi bi-envelope me-1"></i>{{ $client->email }}</a>@endif
                        @if($client->location_link)<a href="{{ $client->location_link }}" target="_blank" class="text-danger text-decoration-none"><i class="bi bi-geo-alt-fill me-1"></i>Ubicación</a>@endif
                        @if($client->address)<span><i class="bi bi-pin-map me-1"></i>{{ $client->address }}</span>@endif
                    </div>
                </div>

                {{-- Acciones --}}
                <div class="d-flex gap-2 flex-wrap">
                    @if($can('clients.edit'))
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Editar</a>
                    @endif
                    @if($can('clients.delete'))
                    <form action="{{ route('clients.destroy', $client) }}" method="POST" onsubmit="return confirm('¿Eliminar al cliente «{{ addslashes($client->full_name) }}»?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                    </form>
                    @endif
                    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left"></i> Volver</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        {{-- ── Columna principal: TABS de actividad ─────────────────── --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                @if(count($tabs))
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs card-header-tabs mx-2 mt-2 border-0" role="tablist" style="font-size:.85rem;">
                        @foreach($tabs as $key => $t)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $key === $firstTab ? 'active' : '' }}" id="tab-{{ $key }}" data-bs-toggle="tab" data-bs-target="#pane-{{ $key }}" type="button" role="tab">
                                <i class="bi {{ $t[1] }} me-1"></i>{{ $t[0] }}
                                <span class="badge rounded-pill bg-light text-muted border ms-1">{{ $t[3] }}</span>
                            </button>
                        </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-body p-0">
                    <div class="tab-content">

                        {{-- VENTAS --}}
                        @if(isset($tabs['ventas']))
                        <div class="tab-pane fade {{ $firstTab === 'ventas' ? 'show active' : '' }}" id="pane-ventas" role="tabpanel">
                            @include('admin.clients.partials.activity-table', [
                                'rows' => $client->sales, 'empty' => 'Sin ventas registradas.', 'icon' => 'bi-receipt',
                                'head' => ['Código','Fecha','Tipo','Total','Pago'],
                                'render' => fn($s) => [
                                    ['link' => route('sales.show', $s), 'text' => $s->code, 'mono' => true],
                                    ['text' => optional($s->sale_date)->format('d/m/Y'), 'muted' => true],
                                    ['badge' => $s->sale_type_label, 'color' => $s->sale_type_color],
                                    ['text' => '$'.number_format($s->total, 2), 'end' => true, 'bold' => true],
                                    ['badge' => $s->payment_status_label, 'color' => $s->payment_status_color],
                                ],
                            ])
                        </div>
                        @endif

                        {{-- SERVICIOS (Taller) --}}
                        @if(isset($tabs['servicios']))
                        <div class="tab-pane fade {{ $firstTab === 'servicios' ? 'show active' : '' }}" id="pane-servicios" role="tabpanel">
                            @include('admin.clients.partials.activity-table', [
                                'rows' => $client->workOrders, 'empty' => 'Sin órdenes de taller.', 'icon' => 'bi-tools',
                                'head' => ['Código','Recepción','Estado','Total','Pago'],
                                'render' => fn($o) => [
                                    ['link' => route('workshop.orders.show', $o), 'text' => $o->code, 'mono' => true],
                                    ['text' => optional($o->reception_date)->format('d/m/Y'), 'muted' => true],
                                    ['badge' => $o->status_label, 'color' => $o->status_color],
                                    ['text' => '$'.number_format($o->total, 2), 'end' => true, 'bold' => true],
                                    ['badge' => $o->payment_status_label, 'color' => $o->payment_status_color],
                                ],
                            ])
                        </div>
                        @endif

                        {{-- VEHÍCULOS --}}
                        @if(isset($tabs['vehiculos']))
                        <div class="tab-pane fade {{ $firstTab === 'vehiculos' ? 'show active' : '' }}" id="pane-vehiculos" role="tabpanel">
                            @include('admin.clients.partials.activity-table', [
                                'rows' => $client->vehicles, 'empty' => 'Sin vehículos registrados.', 'icon' => 'bi-bicycle',
                                'head' => ['Placa','Marca / Modelo','Año','Color',''],
                                'render' => fn($v) => [
                                    ['link' => route('vehicles.show', $v), 'text' => $v->plate ?: '—', 'mono' => true],
                                    ['text' => trim(($v->brand ?? '').' '.($v->model ?? '')) ?: '—'],
                                    ['text' => $v->year ?: '—', 'muted' => true],
                                    ['text' => $v->color ?: '—', 'muted' => true],
                                    ['text' => ''],
                                ],
                            ])
                        </div>
                        @endif

                        {{-- ALQUILERES --}}
                        @if(isset($tabs['alquileres']))
                        <div class="tab-pane fade {{ $firstTab === 'alquileres' ? 'show active' : '' }}" id="pane-alquileres" role="tabpanel">
                            @include('admin.clients.partials.activity-table', [
                                'rows' => $client->rentalContracts, 'empty' => 'Sin alquileres.', 'icon' => 'bi-calendar2-week',
                                'head' => ['Código','Moto','Período','Total','Estado'],
                                'render' => fn($r) => [
                                    ['link' => route('rentals.show', $r), 'text' => $r->code, 'mono' => true],
                                    ['text' => $r->motoUnit?->display_name ?? '—'],
                                    ['text' => optional($r->start_date)->format('d/m/Y').' → '.optional($r->end_date)->format('d/m/Y'), 'muted' => true],
                                    ['text' => 'Bs. '.number_format($r->total, 2), 'end' => true, 'bold' => true],
                                    ['badge' => $r->status_label, 'color' => $r->status_color],
                                ],
                            ])
                        </div>
                        @endif

                        {{-- COTIZACIONES --}}
                        @if(isset($tabs['cotizaciones']))
                        <div class="tab-pane fade {{ $firstTab === 'cotizaciones' ? 'show active' : '' }}" id="pane-cotizaciones" role="tabpanel">
                            @include('admin.clients.partials.activity-table', [
                                'rows' => $client->quotes, 'empty' => 'Sin cotizaciones.', 'icon' => 'bi-file-earmark-text',
                                'head' => ['Código','Fecha','Total','Estado',''],
                                'render' => fn($q) => [
                                    ['link' => route('quotes.show', $q), 'text' => $q->code, 'mono' => true],
                                    ['text' => optional($q->quote_date)->format('d/m/Y'), 'muted' => true],
                                    ['text' => '$'.number_format($q->total, 2), 'end' => true, 'bold' => true],
                                    ['badge' => $q->status_label, 'color' => $q->status_color],
                                    ['text' => ''],
                                ],
                            ])
                        </div>
                        @endif

                        {{-- GARANTÍAS --}}
                        @if(isset($tabs['garantias']))
                        <div class="tab-pane fade {{ $firstTab === 'garantias' ? 'show active' : '' }}" id="pane-garantias" role="tabpanel">
                            @include('admin.clients.partials.activity-table', [
                                'rows' => $client->warranties, 'empty' => 'Sin garantías.', 'icon' => 'bi-shield-check',
                                'head' => ['Código','Moto','Inicio','Meses','Estado'],
                                'render' => fn($w) => [
                                    ['link' => route('warranties.show', $w), 'text' => $w->code, 'mono' => true],
                                    ['text' => $w->motoUnit?->display_name ?? '—'],
                                    ['text' => optional($w->start_date)->format('d/m/Y'), 'muted' => true],
                                    ['text' => $w->months ? $w->months.' m' : '—', 'muted' => true],
                                    ['badge' => ucfirst($w->status ?? '—'), 'color' => 'secondary'],
                                ],
                            ])
                        </div>
                        @endif

                    </div>
                </div>
                @else
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-lock fs-2 opacity-25 d-block mb-2"></i>
                    No tienes permisos para ver la actividad de este cliente.
                </div>
                @endif
            </div>
        </div>

        {{-- ── Columna lateral ──────────────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- Puntos de fidelización --}}
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-dashboard.view', auth()->user()->getCurrentCompany()))
            <div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #7c3aed !important;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small mb-1"><i class="bi bi-award me-1 text-purple" style="color:#7c3aed;"></i>Puntos de fidelización</div>
                        <div class="fw-bold fs-4" style="color:#7c3aed;">{{ number_format($client->points_balance ?? 0, 0) }}</div>
                    </div>
                    @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-movements.view', auth()->user()->getCurrentCompany()))
                    <a href="{{ route('loyalty.movements.index', ['client_id' => $client->id]) }}" class="btn btn-sm btn-light border"><i class="bi bi-arrow-left-right me-1"></i>Movimientos</a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Detalles (notas + ubicación) --}}
            @if($client->notes || $client->location_link)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 small fw-semibold"><i class="bi bi-info-circle me-2 text-muted"></i>Detalles</h6></div>
                <div class="card-body p-3">
                    @if($client->notes)
                    <div class="text-muted small mb-1">Notas</div>
                    <div class="p-2 bg-light rounded-3 mb-3" style="font-size:.82rem;white-space:pre-wrap;">{{ $client->notes }}</div>
                    @endif
                    @if($client->location_link)
                        @php
                            preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $client->location_link, $matches);
                            $hasCoords = count($matches) >= 3;
                            $lat = $matches[1] ?? null;
                            $lng = $matches[2] ?? null;
                        @endphp
                        @if($hasCoords)
                            <iframe src="https://www.openstreetmap.org/export/embed.html?bbox={{ ($lng - 0.005) }},{{ ($lat - 0.005) }},{{ ($lng + 0.005) }},{{ ($lat + 0.005) }}&layer=mapnik&marker={{ $lat }},{{ $lng }}"
                                    style="width:100%;height:150px;border:0;border-radius:8px;" loading="lazy"></iframe>
                        @endif
                        <a href="{{ $client->location_link }}" target="_blank" class="btn btn-sm btn-light border w-100 mt-2"><i class="bi bi-geo-alt-fill text-danger me-1"></i>Abrir mapa</a>
                    @endif
                </div>
            </div>
            @endif

            {{-- Documentos --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 small fw-semibold"><i class="bi bi-folder2-open me-2 text-muted"></i>Documentos</h6>
                    <span class="badge bg-light text-muted border">{{ $client->documents->count() }}</span>
                </div>
                <div class="card-body p-3">
                    @forelse($client->documents as $doc)
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3 border mb-2 bg-light">
                        <div class="flex-shrink-0 rounded-2 d-flex align-items-center justify-content-center" style="width:34px;height:34px;background:#fff;border:1px solid #eee;">
                            <i class="bi {{ $doc->icon }} text-muted"></i>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate" style="font-size:.8rem;">{{ $doc->display_label }}</div>
                            <div class="text-muted text-truncate" style="font-size:.72rem;">{{ $doc->file_name }}</div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-sm btn-light border py-0 px-2" title="Ver"><i class="bi bi-eye"></i></a>
                            <a href="{{ $doc->file_url }}" download="{{ $doc->file_name }}" class="btn btn-sm btn-light border py-0 px-2" title="Descargar"><i class="bi bi-download"></i></a>
                            @if($can('clients.edit'))
                            <form action="{{ route('clients.documents.destroy', $doc) }}" method="POST" onsubmit="return confirm('¿Eliminar este documento?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-3 text-muted small"><i class="bi bi-folder2 d-block fs-3 mb-2 opacity-25"></i>Sin documentos adjuntos.</div>
                    @endforelse
                </div>
                <div class="card-footer bg-white border-top py-2 px-3 d-flex justify-content-between" style="font-size:.75rem;">
                    <span class="text-muted">Registrado por <strong>{{ $client->createdBy?->name ?: '—' }}</strong></span>
                    <span class="text-muted">{{ $client->created_at->format('d/m/Y') }}</span>
                </div>
            </div>

        </div>
    </div>

</div>

@push('styles')
<style>
.activity-row { cursor: pointer; }
.activity-row:hover { background: #faf7f7; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const row = e.target.closest('tr.activity-row');
    if (!row || !row.dataset.href) return;
    if (e.target.closest('a, button, form, input')) return; // respetar enlaces/acciones internas
    window.location = row.dataset.href;
});
</script>
@endpush
@endsection
