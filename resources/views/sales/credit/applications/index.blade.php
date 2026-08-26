@extends('layouts.app')
@section('title', 'Solicitudes de Crédito')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-file-earmark-medical me-2 text-danger"></i>Solicitudes de Crédito</h1>
            <p class="text-muted mb-0 small">Gestión de solicitudes de crédito de clientes.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('credit-applications.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva solicitud
        </a>
        @endif
    </div>

    {{-- Status filter pills --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 px-4">
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted small fw-semibold me-1">Estado:</span>
                <a href="{{ route('credit-applications.index') }}"
                   class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-light border' }}">
                    Todas
                </a>
                @foreach(['pendiente' => ['label' => 'Pendiente', 'color' => 'warning'], 'aprobada' => ['label' => 'Aprobada', 'color' => 'success'], 'rechazada' => ['label' => 'Rechazada', 'color' => 'danger'], 'convertida' => ['label' => 'Convertida', 'color' => 'secondary']] as $key => $meta)
                <a href="{{ route('credit-applications.index', ['status' => $key]) }}"
                   class="btn btn-sm {{ request('status') === $key ? 'btn-' . $meta['color'] : 'btn-light border' }}">
                    {{ $meta['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Código</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Monto solicitado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Plan</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Fecha</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="letter-spacing:.04em;font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2">
                                <a href="{{ route('credit-applications.show', $app) }}"
                                   class="text-decoration-none fw-semibold text-dark">
                                    {{ $app->code }}
                                </a>
                            </td>
                            <td class="py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                                         style="width:30px;height:30px;font-size:.72rem;font-weight:700;color:#555;">
                                        {{ strtoupper(substr($app->client?->full_name ?? 'C', 0, 1)) }}
                                    </div>
                                    <span class="small">{{ $app->client?->full_name ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="py-2 text-end small fw-semibold">{{ money($app->requested_amount, null, 2) }}</td>
                            <td class="py-2 small text-muted">{{ $app->paymentPlan?->name ?? '—' }}</td>
                            <td class="py-2">
                                <span class="badge bg-{{ $app->status_color }}-subtle text-{{ $app->status_color }} border border-{{ $app->status_color }}-subtle" style="font-size:.7rem;">
                                    {{ $app->status_label }}
                                </span>
                            </td>
                            <td class="py-2 small text-muted">{{ $app->created_at->format('d/m/Y') }}</td>
                            <td class="py-2 text-end pe-4">
                                <a href="{{ route('credit-applications.show', $app) }}"
                                   class="btn btn-sm btn-light border" title="Ver detalle">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3"
                                     style="width:64px;height:64px;">
                                    <i class="bi bi-file-earmark-medical fs-2 opacity-25"></i>
                                </div>
                                <p class="fw-semibold mb-1">Sin solicitudes</p>
                                <small>
                                    @if(request('status'))
                                        No hay solicitudes con estado "{{ request('status') }}".
                                        <a href="{{ route('credit-applications.index') }}" class="ms-1">Limpiar filtro</a>
                                    @else
                                        Aún no se han registrado solicitudes de crédito.
                                    @endif
                                </small>
                                @if(!request('status') && (auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.create', auth()->user()->getCurrentCompany())))
                                <div class="mt-3">
                                    <a href="{{ route('credit-applications.create') }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-plus-lg me-1"></i>Nueva solicitud
                                    </a>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $applications->withQueryString()->links() }}</div>

</div>
@endsection
