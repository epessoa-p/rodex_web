@extends('layouts.app')
@section('title', 'Taller')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-tools me-2 text-danger"></i>Taller</h1>
            <p class="text-muted mb-0 small">Panel de control del módulo de taller.</p>
        </div>
        <a href="{{ route('workshop.reception') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>Nueva recepción
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(13,110,253,.08)">
                            <i class="bi bi-wrench-adjustable fs-5 text-primary"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Activas</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1">{{ $activeCount }}</div>
                    <div class="text-muted small">Órdenes activas</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(25,135,84,.08)">
                            <i class="bi bi-check2-circle fs-5 text-success"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">Este mes</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1">{{ $deliveredThisMonth }}</div>
                    <div class="text-muted small">Entregadas del mes</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(230,57,70,.08)">
                            <i class="bi bi-cash-coin fs-5 text-danger"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle small">Este mes</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1">${{ number_format($incomeThisMonth, 2) }}</div>
                    <div class="text-muted small">Ingresos del mes</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #dc3545 !important;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                             style="width:44px;height:44px;background:rgba(220,53,69,.08)">
                            <i class="bi bi-hourglass-split fs-5" style="color:#dc3545"></i>
                        </div>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle small">Por cobrar</span>
                    </div>
                    <div class="fw-bold fs-4 mb-1 text-danger">${{ number_format($receivable, 2) }}</div>
                    <div class="text-muted small">Por cobrar</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status pills --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        @php
            $statusLabels = [
                'recibida'     => 'Recibida',
                'diagnosticada'=> 'Diagnosticada',
                'en_proceso'   => 'En proceso',
                'terminada'    => 'Terminada',
                'entregada'    => 'Entregada',
                'anulada'      => 'Anulada',
            ];
            $statusColors = [
                'recibida'      => 'secondary',
                'diagnosticada' => 'info',
                'en_proceso'    => 'warning',
                'terminada'     => 'primary',
                'entregada'     => 'success',
                'anulada'       => 'danger',
            ];
        @endphp
        @foreach($statusCounts as $status => $count)
        <a href="{{ route('workshop.orders.index', ['status' => $status]) }}"
           class="badge text-decoration-none bg-{{ $statusColors[$status] ?? 'secondary' }}-subtle text-{{ $statusColors[$status] ?? 'secondary' }} border border-{{ $statusColors[$status] ?? 'secondary' }}-subtle"
           style="font-size:.8rem;padding:.45em .75em;">
            {{ $statusLabels[$status] ?? $status }}: <strong>{{ $count }}</strong>
        </a>
        @endforeach
    </div>

    <div class="row g-4">

        {{-- OT recientes --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-clipboard2-pulse me-2 text-muted"></i>OT recientes</h6>
                    <a href="{{ route('workshop.orders.index') }}" class="btn btn-sm btn-light border">Ver todas</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Código</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Cliente</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Vehículo</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Mecánico</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Estado</th>
                                    <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-2">
                                        <a href="{{ route('workshop.orders.show', $order) }}"
                                           class="text-decoration-none fw-semibold text-dark">
                                            {{ $order->code }}
                                        </a>
                                    </td>
                                    <td class="py-2 small">{{ $order->client?->full_name ?? '—' }}</td>
                                    <td class="py-2 small text-muted">{{ $order->vehicle?->display_name ?? '—' }}</td>
                                    <td class="py-2 small text-muted">{{ $order->mechanic?->name ?? '—' }}</td>
                                    <td class="py-2">
                                        <span class="badge bg-{{ $order->status_color }}-subtle text-{{ $order->status_color }} border border-{{ $order->status_color }}-subtle" style="font-size:.7rem;">
                                            {{ $order->status_label }}
                                        </span>
                                    </td>
                                    <td class="py-2 text-end fw-semibold small pe-4">${{ number_format($order->total, 2) }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-clipboard2 fs-1 d-block mb-2 opacity-25"></i>
                                        Sin órdenes recientes.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="col-lg-4">
            {{-- Top mecánicos --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-trophy me-2 text-muted"></i>Top mecánicos</h6>
                </div>
                <div class="card-body p-3">
                    @forelse($topMechanics as $i => $mechanic)
                    <div class="d-flex align-items-center gap-3 mb-3 {{ $loop->last ? 'mb-0' : '' }}">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                             style="width:32px;height:32px;font-size:.78rem;background:{{ $i === 0 ? '#f59e0b' : ($i === 1 ? '#9ca3af' : ($i === 2 ? '#b45309' : '#6b7280')) }}">
                            {{ $i + 1 }}
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-semibold text-truncate small">{{ $mechanic->name }}</div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle flex-shrink-0">
                            {{ $mechanic->work_orders_count }} OT
                        </span>
                    </div>
                    @empty
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-person-gear d-block fs-3 mb-1 opacity-25"></i>
                        Sin datos de mecánicos.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
