@extends('layouts.app')
@section('title', 'Servicios de gasto')
@section('page')
@php $company = auth()->user()->getCurrentCompany(); $canManage = auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('expense-services.manage', $company); @endphp
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-receipt-cutoff me-2 text-danger"></i>Servicios de gasto</h1>
            <p class="text-muted mb-0 small">Conceptos recurrentes (luz, agua, internet, externos…) usados al registrar gastos.</p>
        </div>
        @if($canManage)
        <a href="{{ route('expense-services.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nuevo servicio</a>
        @endif
    </div>

    @if(session('success'))
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead>
                        <tr class="table-light border-bottom">
                            <th class="ps-4 py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Servicio</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Tipo</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end" style="font-size:.72rem;">Monto sugerido</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase" style="font-size:.72rem;">Estado</th>
                            <th class="py-3 fw-semibold text-muted text-uppercase text-end pe-4" style="font-size:.72rem;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $s)
                        <tr class="border-bottom border-light">
                            <td class="ps-4 py-2 fw-semibold">{{ $s->name }}@if($s->notes)<div class="text-muted small fw-normal">{{ $s->notes }}</div>@endif</td>
                            <td class="py-2"><span class="badge bg-light text-dark border" style="font-size:.7rem;">{{ $s->type_label }}</span></td>
                            <td class="py-2 text-end">{{ $s->default_amount ? money($s->default_amount) : '—' }}</td>
                            <td class="py-2"><span class="badge bg-{{ $s->active ? 'success' : 'secondary' }}-subtle text-{{ $s->active ? 'success' : 'secondary' }} border border-{{ $s->active ? 'success' : 'secondary' }}-subtle" style="font-size:.7rem;">{{ $s->active ? 'Activo' : 'Inactivo' }}</span></td>
                            <td class="py-2 text-end pe-4">
                                @if($canManage)
                                <div class="btn-group">
                                    <a href="{{ route('expense-services.edit', $s) }}" class="btn btn-sm btn-light border" title="Editar"><i class="bi bi-pencil"></i></a>
                                    <form action="{{ route('expense-services.destroy', $s) }}" method="POST" onsubmit="return confirm('¿Eliminar «{{ addslashes($s->name) }}»?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-light border text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-receipt-cutoff fs-2 opacity-25 d-block mb-2"></i>Sin servicios registrados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $services->links() }}</div>
</div>
@endsection
