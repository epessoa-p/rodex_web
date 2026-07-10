@extends('layouts.app')

@section('title', 'Dashboard - Multi-Store Repuestos')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h4 class="fw-bold mb-1">Dashboard</h4>
            <p class="text-muted mb-0">Resumen general de la tienda</p>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $totalProducts }}</div>
                        <div class="kpi-label">Productos</div>
                        <div class="kpi-trend text-muted"><i class="bi bi-box-seam"></i> Catálogo</div>
                    </div>
                    <div class="kpi-icon" style="background: #7c4dff;">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $totalBranches }}</div>
                        <div class="kpi-label">Sucursales</div>
                        <div class="kpi-trend text-muted"><i class="bi bi-diagram-2"></i> Activas</div>
                    </div>
                    <div class="kpi-icon" style="background: #00c853;">
                        <i class="bi bi-diagram-2"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="kpi-card">
                <div class="kpi-body">
                    <div>
                        <div class="kpi-value">{{ $totalWarehouses }}</div>
                        <div class="kpi-label">Almacenes</div>
                        <div class="kpi-trend text-muted"><i class="bi bi-building-add"></i> Operativos</div>
                    </div>
                    <div class="kpi-icon" style="background: #0288d1;">
                        <i class="bi bi-building-add"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Accesos Rápidos</h6>
            <div class="row g-3">
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('products.index') }}" class="text-decoration-none">
                        <div class="quick-link text-center p-3 rounded">
                            <i class="bi bi-box-seam fs-2 mb-2 d-block" style="color:#7c4dff;"></i>
                            <small class="text-dark fw-semibold">Productos</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('warehouses.index') }}" class="text-decoration-none">
                        <div class="quick-link text-center p-3 rounded">
                            <i class="bi bi-building-add fs-2 mb-2 d-block" style="color:#0288d1;"></i>
                            <small class="text-dark fw-semibold">Almacenes</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('branches.index') }}" class="text-decoration-none">
                        <div class="quick-link text-center p-3 rounded">
                            <i class="bi bi-diagram-2 fs-2 mb-2 d-block" style="color:#00c853;"></i>
                            <small class="text-dark fw-semibold">Sucursales</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('personal.index') }}" class="text-decoration-none">
                        <div class="quick-link text-center p-3 rounded">
                            <i class="bi bi-person-badge fs-2 mb-2 d-block" style="color:#e91e63;"></i>
                            <small class="text-dark fw-semibold">Personal</small>
                        </div>
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="{{ route('users.index') }}" class="text-decoration-none">
                        <div class="quick-link text-center p-3 rounded">
                            <i class="bi bi-shield-person fs-2 mb-2 d-block" style="color:#607d8b;"></i>
                            <small class="text-dark fw-semibold">Usuarios</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .kpi-card {
        background: #fff;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        border: 0;
    }
    .kpi-body {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .kpi-value {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 4px;
    }
    .kpi-label {
        font-size: 0.82rem;
        color: #777;
        margin-bottom: 6px;
    }
    .kpi-trend {
        font-size: 0.75rem;
    }
    .kpi-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #fff;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .quick-link {
        background: #f8f9fa;
        transition: background 0.15s;
    }
    .quick-link:hover {
        background: #e9ecef;
    }
</style>
@endpush
@endsection
