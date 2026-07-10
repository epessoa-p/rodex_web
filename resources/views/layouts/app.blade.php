@extends('layouts.base')

@section('content')
@php
    $currentCompany = auth()->user()->getCurrentCompany();
    $activeCompanies = auth()->user()->activeCompanies()->get();
@endphp

<div class="app-shell d-flex">
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <button class="btn btn-link p-0 me-2 d-lg-none text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarMobile" aria-controls="appSidebarMobile">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="brand-icon"><img src="{{ asset('images/logo_blanco_sm.png') }}" alt="VR Motors"></div>
            <div>
                <div class="brand-title">VR <span class="brand-accent">MOTORS</span></div>
                <small class="brand-subtitle">Repuestos &amp; Accesorios</small>
            </div>
        </div>

        <div class="sidebar-section-title">General</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('statistics.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.index') }}">
                    <i class="bi bi-bar-chart-line"></i> Estadísticas
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('document-templates.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('document-templates.*') ? 'active' : '' }}" href="{{ route('document-templates.index') }}">
                    <i class="bi bi-file-earmark-ruled"></i> Plantillas
                </a>
            </li>
            @endif
        </ul>

        @if(auth()->user()->is_super_admin)
        <div class="sidebar-section-title mt-4">Sistema</div>
        <ul class="nav flex-column gap-1">
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}">
                    <i class="bi bi-building"></i> Empresas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                    <i class="bi bi-shield-lock"></i> Roles
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('system.reset') ? 'active' : '' }}" href="{{ route('system.reset') }}">
                    <i class="bi bi-exclamation-octagon"></i> Reiniciar sistema
                </a>
            </li>
        </ul>
        @endif

        <div class="sidebar-section-title mt-4">Administración</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('users.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <i class="bi bi-person-gear"></i> Usuarios
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('branches.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}">
                    <i class="bi bi-diagram-2"></i> Sucursales
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('cargos.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('cargos.*') ? 'active' : '' }}" href="{{ route('cargos.index') }}">
                    <i class="bi bi-briefcase"></i> Cargos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('personal.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('personal.*') ? 'active' : '' }}" href="{{ route('personal.index') }}">
                    <i class="bi bi-person-vcard"></i> Personal
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('cash-registers.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('cash-registers.*') ? 'active' : '' }}" href="{{ route('cash-registers.index') }}">
                    <i class="bi bi-safe2"></i> Cajas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('cash.movements') ? 'active' : '' }}" href="{{ route('cash.movements') }}">
                    <i class="bi bi-arrow-left-right"></i> Movimientos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('expense-services.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('expense-services.*') ? 'active' : '' }}" href="{{ route('expense-services.index') }}">
                    <i class="bi bi-receipt-cutoff"></i> Servicios de gasto
                </a>
            </li>
            @endif
        </ul>

        <div class="sidebar-section-title mt-4">Inventario</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                    <i class="bi bi-box-seam"></i> Productos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('inventory.stock*') ? 'active' : '' }}" href="{{ route('inventory.stock') }}">
                    <i class="bi bi-clipboard-data"></i> Inventario
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                    <i class="bi bi-building-add"></i> Almacenes
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('product-categories.*') ? 'active' : '' }}" href="{{ route('product-categories.index') }}">
                    <i class="bi bi-tags"></i> Categorías
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-brands.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('product-brands.*') ? 'active' : '' }}" href="{{ route('product-brands.index') }}">
                    <i class="bi bi-award"></i> Marcas
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('inventory.kardex', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('inventory.kardex') ? 'active' : '' }}" href="{{ route('inventory.kardex') }}">
                    <i class="bi bi-journal-text"></i> Kardex
                </a>
            </li>
            @endif
        </ul>

        @php
            $canSales = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('pos.access', $currentCompany)
                || auth()->user()->hasPermissionInCompany('sales.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('quotes.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('sale-returns.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('clients.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('vehicles.view', $currentCompany);
            $canCredit = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('credit.collect', $currentCompany)
                || auth()->user()->hasPermissionInCompany('credit-applications.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('payment-plans.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('credit-reports.view', $currentCompany);
        @endphp
        @if($canSales)
        <div class="sidebar-section-title mt-4">Ventas</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales-dashboard.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}" href="{{ route('sales.dashboard') }}">
                    <i class="bi bi-graph-up-arrow"></i> Dashboard
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('clients.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">
                    <i class="bi bi-people"></i> Clientes
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('pos.access', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}">
                    <i class="bi bi-bag-check"></i> Punto de Venta
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('sales.index') || request()->routeIs('sales.create') || request()->routeIs('sales.show') ? 'active' : '' }}" href="{{ route('sales.index') }}">
                    <i class="bi bi-cart"></i> Ventas
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.create', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('quotes.pos') ? 'active' : '' }}" href="{{ route('quotes.pos') }}">
                    <i class="bi bi-calculator"></i> Punto de Cotización
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ (request()->routeIs('quotes.*') && !request()->routeIs('quotes.pos')) ? 'active' : '' }}" href="{{ route('quotes.index') }}">
                    <i class="bi bi-file-earmark-text"></i> Cotizaciones
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sale-returns.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('sale-returns.*') ? 'active' : '' }}" href="{{ route('sale-returns.index') }}">
                    <i class="bi bi-arrow-return-left"></i> Devoluciones
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}">
                    <i class="bi bi-bicycle"></i> Vehículos
                </a>
            </li>
            @endif
        </ul>
        @endif

        @if($canCredit)
        <div class="sidebar-section-title mt-4">Créditos</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit-applications.*') ? 'active' : '' }}" href="{{ route('credit-applications.index') }}">
                    <i class="bi bi-file-earmark-medical"></i> Solicitudes
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit.sales') ? 'active' : '' }}" href="{{ route('credit.sales') }}">
                    <i class="bi bi-credit-card"></i> Ventas a Crédito
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('payment-plans.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('payment-plans.*') ? 'active' : '' }}" href="{{ route('payment-plans.index') }}">
                    <i class="bi bi-list-check"></i> Planes de Pago
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit.cuotas') ? 'active' : '' }}" href="{{ route('credit.cuotas') }}">
                    <i class="bi bi-calendar-check"></i> Cuotas
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit.cobranza') ? 'active' : '' }}" href="{{ route('credit.cobranza') }}">
                    <i class="bi bi-cash-coin"></i> Cobranza
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit.morosos') ? 'active' : '' }}" href="{{ route('credit.morosos') }}">
                    <i class="bi bi-exclamation-octagon"></i> Morosos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-reports.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('credit.reports') ? 'active' : '' }}" href="{{ route('credit.reports') }}">
                    <i class="bi bi-graph-up"></i> Reportes
                </a>
            </li>
            @endif
        </ul>
        @endif

        @php
            $canWorkshop = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('workshop.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('services.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('mechanics.view', $currentCompany);
        @endphp
        @if($canWorkshop)
        <div class="sidebar-section-title mt-4">Taller</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop-dashboard.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('workshop.dashboard') ? 'active' : '' }}" href="{{ route('workshop.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.create', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('workshop.reception') ? 'active' : '' }}" href="{{ route('workshop.reception') }}">
                    <i class="bi bi-box-arrow-in-down"></i> Recepción
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('workshop.orders.*') ? 'active' : '' }}" href="{{ route('workshop.orders.index') }}">
                    <i class="bi bi-tools"></i> Órdenes de Trabajo
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('services.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}">
                    <i class="bi bi-wrench-adjustable"></i> Servicios
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('mechanics.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('mechanics.*') ? 'active' : '' }}" href="{{ route('mechanics.index') }}">
                    <i class="bi bi-person-gear"></i> Mecánicos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('workshop.deliveries.*') ? 'active' : '' }}" href="{{ route('workshop.deliveries.index') }}">
                    <i class="bi bi-truck"></i> Entregas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('workshop.history') ? 'active' : '' }}" href="{{ route('workshop.history') }}">
                    <i class="bi bi-clock-history"></i> Historial
                </a>
            </li>
            @endif
        </ul>
        @endif

        @php
            $canMotos = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('moto-units.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('moto-sales.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('moto-brands.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('moto-models.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('warranties.view', $currentCompany);
        @endphp
        @if($canMotos)
        <div class="sidebar-section-title mt-4">Motos</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-brands.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('moto-brands.*') ? 'active' : '' }}" href="{{ route('moto-brands.index') }}">
                    <i class="bi bi-tag"></i> Marcas
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-models.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('moto-models.*') ? 'active' : '' }}" href="{{ route('moto-models.index') }}">
                    <i class="bi bi-bicycle"></i> Modelos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('moto-units.*') ? 'active' : '' }}" href="{{ route('moto-units.index') }}">
                    <i class="bi bi-box-seam"></i> Inventario de Motos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-sales.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('moto-sales.*') ? 'active' : '' }}" href="{{ route('moto-sales.index') }}">
                    <i class="bi bi-cart-check"></i> Ventas de Motos
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-deliveries.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('moto-deliveries.*') ? 'active' : '' }}" href="{{ route('moto-deliveries.index') }}">
                    <i class="bi bi-truck"></i> Entregas
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warranties.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('warranties.*') ? 'active' : '' }}" href="{{ route('warranties.index') }}">
                    <i class="bi bi-shield-check"></i> Garantías
                </a>
            </li>
            @endif
        </ul>
        @endif

        @php
            $canRentals = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('rentals.view', $currentCompany);
        @endphp
        @if($canRentals)
        <div class="sidebar-section-title mt-4">Alquileres</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals-dashboard.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.dashboard') ? 'active' : '' }}" href="{{ route('rentals.dashboard') }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            @endif
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.calendar') ? 'active' : '' }}" href="{{ route('rentals.calendar') }}">
                    <i class="bi bi-calendar3"></i> Calendario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.reservations') || request()->routeIs('rentals.create') ? 'active' : '' }}" href="{{ route('rentals.reservations') }}">
                    <i class="bi bi-bookmark-plus"></i> Reservas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.contracts') ? 'active' : '' }}" href="{{ route('rentals.contracts') }}">
                    <i class="bi bi-file-earmark-text"></i> Contratos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.deliveries') ? 'active' : '' }}" href="{{ route('rentals.deliveries') }}">
                    <i class="bi bi-box-arrow-up"></i> Entregas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.active') ? 'active' : '' }}" href="{{ route('rentals.active') }}">
                    <i class="bi bi-bicycle"></i> En curso
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.returns') ? 'active' : '' }}" href="{{ route('rentals.returns') }}">
                    <i class="bi bi-box-arrow-in-down"></i> Devoluciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.collections') ? 'active' : '' }}" href="{{ route('rentals.collections') }}">
                    <i class="bi bi-cash-stack"></i> Cobros
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.payments') ? 'active' : '' }}" href="{{ route('rentals.payments') }}">
                    <i class="bi bi-cash-coin"></i> Pagos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.penalties') ? 'active' : '' }}" href="{{ route('rentals.penalties') }}">
                    <i class="bi bi-exclamation-triangle"></i> Penalizaciones
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('rentals.history') ? 'active' : '' }}" href="{{ route('rentals.history') }}">
                    <i class="bi bi-clock-history"></i> Historial
                </a>
            </li>
        </ul>
        @endif

        @php
            $canLoyalty = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('loyalty-dashboard.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('loyalty-rewards.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('loyalty-redemptions.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('loyalty-movements.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('loyalty-campaigns.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('loyalty-reports.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('loyalty-settings.view', $currentCompany);
        @endphp
        @if($canLoyalty)
        <div class="sidebar-section-title mt-4">Fidelización</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-dashboard.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.dashboard') ? 'active' : '' }}" href="{{ route('loyalty.dashboard') }}"><i class="bi bi-award"></i> Dashboard</a></li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-settings.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.settings.*') ? 'active' : '' }}" href="{{ route('loyalty.settings.edit') }}"><i class="bi bi-gear"></i> Configuración</a></li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.rewards.*') ? 'active' : '' }}" href="{{ route('loyalty.rewards.index') }}"><i class="bi bi-gift"></i> Recompensas</a></li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-redemptions.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.redemptions.*') ? 'active' : '' }}" href="{{ route('loyalty.redemptions.index') }}"><i class="bi bi-bag-check"></i> Canjes</a></li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-movements.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.movements.*') ? 'active' : '' }}" href="{{ route('loyalty.movements.index') }}"><i class="bi bi-arrow-left-right"></i> Movimientos de Puntos</a></li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-campaigns.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.campaigns.*') ? 'active' : '' }}" href="{{ route('loyalty.campaigns.index') }}"><i class="bi bi-megaphone"></i> Campañas</a></li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-reports.view', $currentCompany))
            <li class="nav-item"><a class="nav-link app-link {{ request()->routeIs('loyalty.reports.*') ? 'active' : '' }}" href="{{ route('loyalty.reports.index') }}"><i class="bi bi-bar-chart-line"></i> Reportes</a></li>
            @endif
        </ul>
        @endif

        @php
            $canPurchases = auth()->user()->is_super_admin
                || auth()->user()->hasPermissionInCompany('purchases-dashboard.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('suppliers.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('purchase-orders.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('goods-receipts.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('purchases.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('accounts-payable.view', $currentCompany)
                || auth()->user()->hasPermissionInCompany('treasury.view', $currentCompany);
        @endphp
        @if($canPurchases)
        <div class="sidebar-section-title mt-4">Compras</div>
        <ul class="nav flex-column gap-1">
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases-dashboard.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('purchases-dashboard.*') ? 'active' : '' }}" href="{{ route('purchases-dashboard.index') }}">
                    <i class="bi bi-graph-up-arrow"></i> Dashboard
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                    <i class="bi bi-truck"></i> Proveedores
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}">
                    <i class="bi bi-file-earmark-text"></i> Órdenes de Compra
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('goods-receipts.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('goods-receipts.*') ? 'active' : '' }}" href="{{ route('goods-receipts.index') }}">
                    <i class="bi bi-box-arrow-in-down"></i> Recepciones
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}">
                    <i class="bi bi-receipt"></i> Compras
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('accounts-payable.*') ? 'active' : '' }}" href="{{ route('accounts-payable.index') }}">
                    <i class="bi bi-cash-stack"></i> Cuentas por Pagar
                </a>
            </li>
            @endif
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.view', $currentCompany))
            <li class="nav-item">
                <a class="nav-link app-link {{ request()->routeIs('treasury.*') ? 'active' : '' }}" href="{{ route('treasury.index') }}">
                    <i class="bi bi-bank"></i> Tesorería
                </a>
            </li>
            @endif
        </ul>
        @endif

    </aside>

    <main class="app-main">
        <nav class="navbar navbar-expand-lg app-topbar mb-2">
            <div class="container-fluid px-0">
                <div class="d-flex align-items-center gap-3">
                    {{-- Hamburguesa: escritorio (colapsa el sidebar) --}}
                    <button class="btn btn-icon d-none d-lg-inline-flex" type="button" id="sidebarToggle" title="Mostrar/ocultar menú" aria-label="Mostrar/ocultar menú">
                        <i class="bi bi-list" style="font-size:1.5rem;line-height:1;"></i>
                    </button>
                    {{-- Hamburguesa: móvil (abre el offcanvas) --}}
                    <button class="btn btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebarMobile" aria-controls="appSidebarMobile">
                        <i class="bi bi-list" style="font-size:1.5rem;line-height:1;"></i>
                    </button>

                    <span class="topbar-separator">|</span>
                    <span class="text-muted small">{{ auth()->user()->is_super_admin ? 'Modo Global' : ($currentCompany?->name ?? 'Sin empresa activa') }}</span>

                    @if(!auth()->user()->is_super_admin && $activeCompanies->count() > 1)
                        <div class="dropdown">
                            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-buildings"></i> Empresa
                            </button>
                            <ul class="dropdown-menu shadow border-0">
                                @foreach($activeCompanies as $company)
                                    <li>
                                        <form action="{{ route('set-company', $company->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center">
                                                <span>{{ $company->name }}</span>
                                                @if($currentCompany && $currentCompany->id === $company->id)
                                                    <i class="bi bi-check-lg text-success"></i>
                                                @endif
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="d-flex align-items-center gap-2">
                    @include('partials.expense-btn')
                    @include('partials.cash-register-btn')
                    <div class="dropdown">
                        <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><span class="dropdown-item-text text-muted">{{ auth()->user()->name }}</span></li>
                            <li><span class="dropdown-item-text text-muted small">{{ auth()->user()->email }}</span></li>
                        </ul>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="m-0">
                        @csrf
                        <button class="btn btn-logout" type="submit">
                            <i class="bi bi-box-arrow-right"></i> Cerrar sesion
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        @if(isset($breadcrumbs))
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $label => $url)
                        @if($loop->last)
                            <li class="breadcrumb-item active">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ $url }}" class="text-decoration-none">{{ $label }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        @endif

        @if($message = session('success'))
            <div class="app-toast app-toast-success" id="appFlashToast" role="status" data-delay="4000">
                <i class="bi bi-check-circle-fill"></i>
                <span class="flex-grow-1">{{ $message }}</span>
                <button type="button" class="app-toast-close" aria-label="Cerrar">&times;</button>
            </div>
        @endif

        @if($message = session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('page')
    </main>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="appSidebarMobile" aria-labelledby="appSidebarMobileLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title d-flex align-items-center gap-2" id="appSidebarMobileLabel">
            <span class="brand-icon"><img src="{{ asset('images/logo_blanco_sm.png') }}" alt="VR Motors"></span>
            VR <span class="brand-accent">MOTORS</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <nav class="p-3">
            <div class="sidebar-section-title">General</div>
            <ul class="nav flex-column gap-1 mb-3">
                <li><a class="nav-link app-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('statistics.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('statistics.*') ? 'active' : '' }}" href="{{ route('statistics.index') }}"><i class="bi bi-bar-chart-line me-2"></i>Estadísticas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('document-templates.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('document-templates.*') ? 'active' : '' }}" href="{{ route('document-templates.index') }}"><i class="bi bi-file-earmark-ruled me-2"></i>Plantillas</a></li>
                @endif
            </ul>

            @if(auth()->user()->is_super_admin)
            <div class="sidebar-section-title">Sistema</div>
            <ul class="nav flex-column gap-1 mb-3">
                <li><a class="nav-link app-link {{ request()->routeIs('companies.*') ? 'active' : '' }}" href="{{ route('companies.index') }}"><i class="bi bi-building me-2"></i>Empresas</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="bi bi-shield-lock me-2"></i>Roles</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('system.reset') ? 'active' : '' }}" href="{{ route('system.reset') }}"><i class="bi bi-exclamation-octagon me-2"></i>Reiniciar sistema</a></li>
            </ul>
            @endif

            <div class="sidebar-section-title">Administración</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('users.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-person-gear me-2"></i>Usuarios</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('branches.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('branches.*') ? 'active' : '' }}" href="{{ route('branches.index') }}"><i class="bi bi-diagram-2 me-2"></i>Sucursales</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('cargos.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('cargos.*') ? 'active' : '' }}" href="{{ route('cargos.index') }}"><i class="bi bi-briefcase me-2"></i>Cargos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('personal.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('personal.*') ? 'active' : '' }}" href="{{ route('personal.index') }}"><i class="bi bi-person-vcard me-2"></i>Personal</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('cash-registers.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('cash-registers.*') ? 'active' : '' }}" href="{{ route('cash-registers.index') }}"><i class="bi bi-safe2 me-2"></i>Cajas</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('cash.movements') ? 'active' : '' }}" href="{{ route('cash.movements') }}"><i class="bi bi-arrow-left-right me-2"></i>Movimientos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('expense-services.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('expense-services.*') ? 'active' : '' }}" href="{{ route('expense-services.index') }}"><i class="bi bi-receipt-cutoff me-2"></i>Servicios de gasto</a></li>
                @endif
            </ul>

            <div class="sidebar-section-title">Inventario</div>
            <ul class="nav flex-column gap-1">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('products.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}"><i class="bi bi-box-seam me-2"></i>Productos</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('inventory.stock*') ? 'active' : '' }}" href="{{ route('inventory.stock') }}"><i class="bi bi-clipboard-data me-2"></i>Inventario</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warehouses.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}"><i class="bi bi-building-add me-2"></i>Almacenes</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-categories.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('product-categories.*') ? 'active' : '' }}" href="{{ route('product-categories.index') }}"><i class="bi bi-tags me-2"></i>Categorías</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('product-brands.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('product-brands.*') ? 'active' : '' }}" href="{{ route('product-brands.index') }}"><i class="bi bi-award me-2"></i>Marcas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('inventory.kardex', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('inventory.kardex') ? 'active' : '' }}" href="{{ route('inventory.kardex') }}"><i class="bi bi-journal-text me-2"></i>Kardex</a></li>
                @endif
            </ul>

            @if($canSales)
            <div class="sidebar-section-title">Ventas</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales-dashboard.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('sales.dashboard') ? 'active' : '' }}" href="{{ route('sales.dashboard') }}"><i class="bi bi-graph-up-arrow me-2"></i>Dashboard</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('clients.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}"><i class="bi bi-people me-2"></i>Clientes</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('pos.access', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('pos.*') ? 'active' : '' }}" href="{{ route('pos.index') }}"><i class="bi bi-bag-check me-2"></i>Punto de Venta</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sales.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('sales.index') || request()->routeIs('sales.create') || request()->routeIs('sales.show') ? 'active' : '' }}" href="{{ route('sales.index') }}"><i class="bi bi-cart me-2"></i>Ventas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.view', $currentCompany))
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('quotes.create', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('quotes.pos') ? 'active' : '' }}" href="{{ route('quotes.pos') }}"><i class="bi bi-calculator me-2"></i>Punto de Cotización</a></li>
                @endif
                <li><a class="nav-link app-link {{ (request()->routeIs('quotes.*') && !request()->routeIs('quotes.pos')) ? 'active' : '' }}" href="{{ route('quotes.index') }}"><i class="bi bi-file-earmark-text me-2"></i>Cotizaciones</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('sale-returns.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('sale-returns.*') ? 'active' : '' }}" href="{{ route('sale-returns.index') }}"><i class="bi bi-arrow-return-left me-2"></i>Devoluciones</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('vehicles.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}" href="{{ route('vehicles.index') }}"><i class="bi bi-bicycle me-2"></i>Vehículos</a></li>
                @endif
            </ul>
            @endif

            @if($canCredit)
            <div class="sidebar-section-title">Créditos</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-applications.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('credit-applications.*') ? 'active' : '' }}" href="{{ route('credit-applications.index') }}"><i class="bi bi-file-earmark-medical me-2"></i>Solicitudes</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('credit.sales') ? 'active' : '' }}" href="{{ route('credit.sales') }}"><i class="bi bi-credit-card me-2"></i>Ventas a Crédito</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('payment-plans.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('payment-plans.*') ? 'active' : '' }}" href="{{ route('payment-plans.index') }}"><i class="bi bi-list-check me-2"></i>Planes de Pago</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('credit.cuotas') ? 'active' : '' }}" href="{{ route('credit.cuotas') }}"><i class="bi bi-calendar-check me-2"></i>Cuotas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.collect', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('credit.cobranza') ? 'active' : '' }}" href="{{ route('credit.cobranza') }}"><i class="bi bi-cash-coin me-2"></i>Cobranza</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('credit.morosos') ? 'active' : '' }}" href="{{ route('credit.morosos') }}"><i class="bi bi-exclamation-octagon me-2"></i>Morosos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('credit-reports.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('credit.reports') ? 'active' : '' }}" href="{{ route('credit.reports') }}"><i class="bi bi-graph-up me-2"></i>Reportes</a></li>
                @endif
            </ul>
            @endif

            @if($canWorkshop)
            <div class="sidebar-section-title">Taller</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop-dashboard.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('workshop.dashboard') ? 'active' : '' }}" href="{{ route('workshop.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.create', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('workshop.reception') ? 'active' : '' }}" href="{{ route('workshop.reception') }}"><i class="bi bi-box-arrow-in-down me-2"></i>Recepción</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('workshop.orders.*') ? 'active' : '' }}" href="{{ route('workshop.orders.index') }}"><i class="bi bi-tools me-2"></i>Órdenes de Trabajo</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('services.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('services.*') ? 'active' : '' }}" href="{{ route('services.index') }}"><i class="bi bi-wrench-adjustable me-2"></i>Servicios</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('mechanics.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('mechanics.*') ? 'active' : '' }}" href="{{ route('mechanics.index') }}"><i class="bi bi-person-gear me-2"></i>Mecánicos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('workshop.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('workshop.deliveries.*') ? 'active' : '' }}" href="{{ route('workshop.deliveries.index') }}"><i class="bi bi-truck me-2"></i>Entregas</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('workshop.history') ? 'active' : '' }}" href="{{ route('workshop.history') }}"><i class="bi bi-clock-history me-2"></i>Historial</a></li>
                @endif
            </ul>
            @endif

            @if($canMotos)
            <div class="sidebar-section-title">Motos</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-brands.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('moto-brands.*') ? 'active' : '' }}" href="{{ route('moto-brands.index') }}"><i class="bi bi-tag me-2"></i>Marcas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-models.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('moto-models.*') ? 'active' : '' }}" href="{{ route('moto-models.index') }}"><i class="bi bi-bicycle me-2"></i>Modelos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-units.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('moto-units.*') ? 'active' : '' }}" href="{{ route('moto-units.index') }}"><i class="bi bi-box-seam me-2"></i>Inventario de Motos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-sales.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('moto-sales.*') ? 'active' : '' }}" href="{{ route('moto-sales.index') }}"><i class="bi bi-cart-check me-2"></i>Ventas de Motos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('moto-deliveries.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('moto-deliveries.*') ? 'active' : '' }}" href="{{ route('moto-deliveries.index') }}"><i class="bi bi-truck me-2"></i>Entregas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warranties.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('warranties.*') ? 'active' : '' }}" href="{{ route('warranties.index') }}"><i class="bi bi-shield-check me-2"></i>Garantías</a></li>
                @endif
            </ul>
            @endif

            @if($canRentals)
            <div class="sidebar-section-title">Alquileres</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals-dashboard.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.dashboard') ? 'active' : '' }}" href="{{ route('rentals.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                @endif
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.calendar') ? 'active' : '' }}" href="{{ route('rentals.calendar') }}"><i class="bi bi-calendar3 me-2"></i>Calendario</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.reservations') || request()->routeIs('rentals.create') ? 'active' : '' }}" href="{{ route('rentals.reservations') }}"><i class="bi bi-bookmark-plus me-2"></i>Reservas</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.contracts') ? 'active' : '' }}" href="{{ route('rentals.contracts') }}"><i class="bi bi-file-earmark-text me-2"></i>Contratos</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.deliveries') ? 'active' : '' }}" href="{{ route('rentals.deliveries') }}"><i class="bi bi-box-arrow-up me-2"></i>Entregas</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.active') ? 'active' : '' }}" href="{{ route('rentals.active') }}"><i class="bi bi-bicycle me-2"></i>En curso</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.returns') ? 'active' : '' }}" href="{{ route('rentals.returns') }}"><i class="bi bi-box-arrow-in-down me-2"></i>Devoluciones</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.collections') ? 'active' : '' }}" href="{{ route('rentals.collections') }}"><i class="bi bi-cash-stack me-2"></i>Cobros</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.payments') ? 'active' : '' }}" href="{{ route('rentals.payments') }}"><i class="bi bi-cash-coin me-2"></i>Pagos</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.penalties') ? 'active' : '' }}" href="{{ route('rentals.penalties') }}"><i class="bi bi-exclamation-triangle me-2"></i>Penalizaciones</a></li>
                <li><a class="nav-link app-link {{ request()->routeIs('rentals.history') ? 'active' : '' }}" href="{{ route('rentals.history') }}"><i class="bi bi-clock-history me-2"></i>Historial</a></li>
            </ul>
            @endif

            @if($canLoyalty)
            <div class="sidebar-section-title">Fidelización</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-dashboard.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.dashboard') ? 'active' : '' }}" href="{{ route('loyalty.dashboard') }}"><i class="bi bi-award me-2"></i>Dashboard</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-settings.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.settings.*') ? 'active' : '' }}" href="{{ route('loyalty.settings.edit') }}"><i class="bi bi-gear me-2"></i>Configuración</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-rewards.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.rewards.*') ? 'active' : '' }}" href="{{ route('loyalty.rewards.index') }}"><i class="bi bi-gift me-2"></i>Recompensas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-redemptions.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.redemptions.*') ? 'active' : '' }}" href="{{ route('loyalty.redemptions.index') }}"><i class="bi bi-bag-check me-2"></i>Canjes</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-movements.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.movements.*') ? 'active' : '' }}" href="{{ route('loyalty.movements.index') }}"><i class="bi bi-arrow-left-right me-2"></i>Movimientos de Puntos</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-campaigns.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.campaigns.*') ? 'active' : '' }}" href="{{ route('loyalty.campaigns.index') }}"><i class="bi bi-megaphone me-2"></i>Campañas</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('loyalty-reports.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('loyalty.reports.*') ? 'active' : '' }}" href="{{ route('loyalty.reports.index') }}"><i class="bi bi-bar-chart-line me-2"></i>Reportes</a></li>
                @endif
            </ul>
            @endif

            @if($canPurchases)
            <div class="sidebar-section-title">Compras</div>
            <ul class="nav flex-column gap-1 mb-3">
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases-dashboard.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('purchases-dashboard.*') ? 'active' : '' }}" href="{{ route('purchases-dashboard.index') }}"><i class="bi bi-graph-up-arrow me-2"></i>Dashboard</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('suppliers.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}"><i class="bi bi-truck me-2"></i>Proveedores</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchase-orders.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" href="{{ route('purchase-orders.index') }}"><i class="bi bi-file-earmark-text me-2"></i>Órdenes de Compra</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('goods-receipts.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('goods-receipts.*') ? 'active' : '' }}" href="{{ route('goods-receipts.index') }}"><i class="bi bi-box-arrow-in-down me-2"></i>Recepciones</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('purchases.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}" href="{{ route('purchases.index') }}"><i class="bi bi-receipt me-2"></i>Compras</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('accounts-payable.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('accounts-payable.*') ? 'active' : '' }}" href="{{ route('accounts-payable.index') }}"><i class="bi bi-cash-stack me-2"></i>Cuentas por Pagar</a></li>
                @endif
                @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('treasury.view', $currentCompany))
                <li><a class="nav-link app-link {{ request()->routeIs('treasury.*') ? 'active' : '' }}" href="{{ route('treasury.index') }}"><i class="bi bi-bank me-2"></i>Tesorería</a></li>
                @endif
            </ul>
            @endif

        </nav>
    </div>
</div>

@push('styles')
<style>
    /* ── Shell layout ───────────────────────────────────────────── */
    .app-shell {
        min-height: 100vh;
        background: var(--surface-bg);
    }

    .app-sidebar {
        width: 248px;
        background: var(--brand-black);
        color: #d8d8da;
        padding: 16px 12px;
        position: sticky;
        top: 0;
        height: 100vh;
        border-right: 1px solid #000;
        overflow-y: auto;
    }

    /* Acento rojo lateral sutil */
    .app-sidebar::before {
        content: '';
        position: fixed;
        top: 0; left: 0;
        width: 3px; height: 100vh;
        background: linear-gradient(180deg, var(--brand-red) 0%, transparent 100%);
        opacity: 0.7;
        z-index: 1;
    }

    /* ── Brand ──────────────────────────────────────────────────── */
    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
        padding: 6px 8px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }

    .brand-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        color: #fff;
        background: #000;
        font-size: 1.15rem;
        box-shadow: 0 4px 12px rgba(0,0,0,.4), inset 0 1px 0 rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.06);
        overflow: hidden;
        flex-shrink: 0;
    }
    .brand-icon img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 3px;
    }

    .brand-title {
        font-weight: 800;
        font-size: 1rem;
        letter-spacing: 0.05em;
        line-height: 1.1;
        color: #fff;
    }

    .brand-accent {
        color: var(--brand-red);
        font-weight: 800;
    }

    .brand-subtitle {
        color: #7d7d83;
        font-size: 0.7rem;
        letter-spacing: 0.04em;
    }

    /* ── Section titles ─────────────────────────────────────────── */
    .sidebar-section-title {
        color: #6a6a70;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        padding: 4px 12px 8px;
        margin-top: 6px;
    }

    /* ── Nav links ──────────────────────────────────────────────── */
    .app-link {
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 8px;
        padding: 9px 12px;
        color: #c0c0c5;
        border: 1px solid transparent;
        font-size: 0.86rem;
        font-weight: 500;
        transition: all 0.18s ease;
        position: relative;
    }

    .app-link i {
        font-size: 1rem;
        width: 18px;
        text-align: center;
        color: #8d8d93;
        transition: color 0.18s ease;
    }

    .app-link:hover {
        background: rgba(255,255,255,0.04);
        color: #fff;
    }
    .app-link:hover i { color: var(--brand-red); }

    .app-link.active {
        background: linear-gradient(90deg, rgba(225,6,0,0.15) 0%, rgba(225,6,0,0.03) 100%);
        color: #fff;
        font-weight: 600;
        border-color: rgba(225,6,0,0.25);
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }
    .app-link.active::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: var(--brand-red);
        border-radius: 0 3px 3px 0;
    }
    .app-link.active i { color: var(--brand-red); }

    /* ── Main column ────────────────────────────────────────────── */
    .app-main {
        flex: 1;
        padding: 0 14px 24px;
        min-width: 0;
    }

    /* Sidebar colapsado (toggle hamburguesa en escritorio) */
    .app-shell.sidebar-collapsed .app-sidebar { display: none; }
    .app-shell.sidebar-collapsed .app-sidebar::before { display: none; }

    /* ── Topbar (oscuro elegante con acento rojo) ───────────────── */
    .app-topbar {
        background: #fff;
        border: 0;
        border-bottom: 1px solid var(--border-soft);
        border-radius: 0;
        padding: 12px 20px;
        margin-left: -14px;
        margin-right: -14px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }

    .topbar-label {
        color: var(--text-primary);
        font-size: 0.92rem;
        font-weight: 700;
        letter-spacing: -0.01em;
    }

    .topbar-separator {
        color: var(--border-medium);
        font-size: 0.85rem;
    }

    /* ── Top-right buttons ──────────────────────────────────────── */
    .btn-icon {
        width: 36px; height: 36px;
        border-radius: 9px;
        border: 1px solid var(--border-soft);
        background: #fff;
        color: var(--text-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        transition: all .15s ease;
    }
    .btn-icon > i { line-height: 1; vertical-align: middle; }
    .btn-icon:hover {
        background: var(--brand-red-tint);
        color: var(--brand-red);
        border-color: var(--brand-red-soft);
    }

    .btn-logout {
        border: 1px solid var(--border-soft);
        background: #fff;
        color: var(--text-secondary);
        border-radius: 9px;
        padding: 7px 14px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all .15s ease;
    }
    .btn-logout:hover {
        background: var(--brand-red-tint);
        border-color: var(--brand-red-soft);
        color: var(--brand-red-dark);
    }

    /* ── Cash register button states ────────────────────────────── */
    /* Caja cerrada: neutro oscuro con indicador rojo (llama la
       atención sin parecer botón destructivo) */
    .btn-cash-closed {
        border: 1px solid var(--brand-black);
        background: var(--brand-black);
        color: #fff;
        border-radius: 9px;
        padding: 7px 14px;
        font-size: 0.85rem;
        font-weight: 500;
        white-space: nowrap;
        transition: all .15s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        position: relative;
    }
    .btn-cash-closed::before {
        content: '';
        width: 8px; height: 8px;
        border-radius: 50%;
        background: var(--brand-red);
        box-shadow: 0 0 0 2px rgba(225,6,0,.25);
        animation: pulse-red 2s ease-in-out infinite;
    }
    .btn-cash-closed:hover {
        background: var(--brand-black-2);
        border-color: var(--brand-black-2);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(10,10,10,.20);
    }

    @keyframes pulse-red {
        0%, 100% { box-shadow: 0 0 0 2px rgba(225,6,0,.25); }
        50%      { box-shadow: 0 0 0 4px rgba(225,6,0,.05); }
    }

    /* Caja abierta: verde suave (estado positivo activo) */
    .btn-cash-open {
        border: 1px solid #bbf7d0;
        background: #f0fdf4;
        color: #15803d;
        border-radius: 9px;
        padding: 7px 14px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        transition: all .15s ease;
    }
    .btn-cash-open:hover {
        background: #dcfce7;
        border-color: #86efac;
        color: #14532d;
    }

    /* ── Empresa selector en topbar ─────────────────────────────── */
    .app-topbar .btn-outline-light {
        border-color: var(--border-soft);
        background: #fff;
        color: var(--text-secondary);
        font-weight: 500;
    }
    .app-topbar .btn-outline-light:hover {
        background: var(--surface-muted);
        color: var(--text-primary);
        border-color: var(--border-medium);
    }

    /* ── Breadcrumb ─────────────────────────────────────────────── */
    .breadcrumb {
        background: transparent;
        padding: 0;
        font-size: 0.85rem;
    }
    .breadcrumb-item.active { color: var(--text-secondary); }

    /* ── Responsive ─────────────────────────────────────────────── */
    @media (max-width: 991.98px) {
        .app-sidebar { display: none; }
        .app-sidebar::before { display: none; }

        .app-main { padding: 0 10px 20px; }

        .app-topbar {
            margin-left: -10px;
            margin-right: -10px;
        }
    }

    /* ── Offcanvas mobile sidebar ───────────────────────────────── */
    .offcanvas {
        background: var(--brand-black);
        color: #d8d8da;
        max-width: 280px;
    }
    .offcanvas .offcanvas-header {
        border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .offcanvas .offcanvas-title { color: #fff; }
    .offcanvas .btn-close { filter: invert(1); opacity: 0.7; }
    .offcanvas .brand-icon { width: 38px; height: 38px; border-radius: 9px; }

    /* ── Secciones colapsables del menú ─────────────────────────── */
    .sidebar-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        transition: color .15s ease;
    }
    .sidebar-section-title:hover { color: #b9b9bf; }
    .sidebar-section-title .sec-chevron {
        font-size: 0.6rem;
        opacity: 0.55;
        transition: transform .2s ease;
    }
    .sidebar-section-title.collapsed .sec-chevron { transform: rotate(-90deg); }
    .nav-section-hidden { display: none !important; }

    /* ── Toast emergente (mensaje de éxito) ─────────────────────── */
    .app-toast {
        position: fixed;
        top: 1rem; right: 1rem;
        z-index: 1090;
        display: flex; align-items: center; gap: .6rem;
        max-width: 360px;
        background: #fff;
        border: 1px solid var(--border-soft);
        border-left: 4px solid #16a34a;
        border-radius: 10px;
        padding: .7rem .9rem;
        box-shadow: 0 8px 26px rgba(16,24,40,.16);
        font-size: .88rem;
        animation: app-toast-in .25s ease;
    }
    .app-toast-success i { color: #16a34a; font-size: 1.1rem; }
    .app-toast-close {
        border: 0; background: transparent; color: #9aa0a6;
        font-size: 1.2rem; line-height: 1; cursor: pointer; padding: 0 .1rem;
    }
    .app-toast-close:hover { color: #444; }
    .app-toast.hide { opacity: 0; transform: translateX(12px); transition: opacity .3s ease, transform .3s ease; }
    @keyframes app-toast-in { from { opacity: 0; transform: translateX(16px); } to { opacity: 1; transform: translateX(0); } }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // ── 4. Secciones colapsables (acordeón con memoria) ──────────
    function setupCollapsibleSections(scope) {
        scope.querySelectorAll('.sidebar-section-title').forEach(function (title) {
            const list = title.nextElementSibling;
            if (!list || list.tagName !== 'UL') return;

            // Chevron (idempotente)
            if (!title.querySelector('.sec-chevron')) {
                const chev = document.createElement('i');
                chev.className = 'bi bi-chevron-down sec-chevron';
                title.appendChild(chev);
            }

            const label    = (title.textContent || '').trim();
            const key       = 'vrSec:' + label;
            const hasActive = !!list.querySelector('.app-link.active');
            const saved     = localStorage.getItem(key);

            // Abierta si: contiene el activo, o el usuario la dejó abierta.
            // Por defecto (sin estado guardado) las que no tienen activo van colapsadas.
            let open = hasActive || saved === 'open';
            if (saved === null && !hasActive) open = false;
            if (saved === null && hasActive)  open = true;

            applyState(title, list, open);

            title.addEventListener('click', function () {
                const nowOpen = title.classList.contains('collapsed'); // si estaba colapsada → abrir
                applyState(title, list, nowOpen);
                localStorage.setItem(key, nowOpen ? 'open' : 'closed');
            });
        });
    }

    function applyState(title, list, open) {
        title.classList.toggle('collapsed', !open);
        list.classList.toggle('nav-section-hidden', !open);
    }

    // ── 3. Persistir scroll del sidebar / mantener visible el activo ──
    function setupSidebarScroll() {
        const sb = document.querySelector('.app-sidebar');
        if (!sb) return;
        const KEY = 'vrSidebarScroll';

        const saved = sessionStorage.getItem(KEY);
        if (saved !== null) {
            sb.scrollTop = parseInt(saved, 10) || 0;
        } else {
            // Centrar el item activo sin mover la página
            const active = sb.querySelector('.app-link.active');
            if (active) {
                const r  = active.getBoundingClientRect();
                const sr = sb.getBoundingClientRect();
                sb.scrollTop += (r.top - sr.top) - (sb.clientHeight / 2) + (r.height / 2);
            }
        }

        // Guardar al navegar
        sb.querySelectorAll('a.app-link').forEach(function (a) {
            a.addEventListener('click', function () {
                sessionStorage.setItem(KEY, sb.scrollTop);
            });
        });
        // Guardar al hacer scroll (con debounce)
        let t;
        sb.addEventListener('scroll', function () {
            clearTimeout(t);
            t = setTimeout(function () { sessionStorage.setItem(KEY, sb.scrollTop); }, 120);
        });
    }

    // ── Hamburguesa: colapsar/mostrar el sidebar en escritorio ───
    function setupSidebarToggle() {
        const shell  = document.querySelector('.app-shell');
        const toggle = document.getElementById('sidebarToggle');
        if (!shell || !toggle) return;
        const KEY = 'vrSidebarCollapsed';
        if (localStorage.getItem(KEY) === '1') shell.classList.add('sidebar-collapsed');
        toggle.addEventListener('click', function () {
            shell.classList.toggle('sidebar-collapsed');
            localStorage.setItem(KEY, shell.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
    }

    // ── Toast de éxito: auto-cierre ──────────────────────────────
    function setupFlashToast() {
        const toast = document.getElementById('appFlashToast');
        if (!toast) return;
        const delay = parseInt(toast.dataset.delay, 10) || 4000;
        const close = function () {
            toast.classList.add('hide');
            setTimeout(function () { toast.remove(); }, 320);
        };
        toast.querySelector('.app-toast-close')?.addEventListener('click', close);
        setTimeout(close, delay);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const desktop = document.querySelector('.app-sidebar');
        if (desktop) setupCollapsibleSections(desktop);
        const mobile = document.querySelector('#appSidebarMobile');
        if (mobile) setupCollapsibleSections(mobile);
        setupSidebarScroll();
        setupSidebarToggle();
        setupFlashToast();
    });
})();
</script>
@endpush
@endsection
