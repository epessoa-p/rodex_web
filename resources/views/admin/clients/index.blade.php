@extends('layouts.app')

@section('title', 'Clientes')

@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1"><i class="bi bi-people"></i> Clientes</h1>
            <p class="text-muted mb-0">Registro de clientes de la empresa.</p>
        </div>
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('clients.create', auth()->user()->getCurrentCompany()))
        <a href="{{ route('clients.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo cliente
        </a>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> {{ $errors->first() }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Buscador rápido --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" class="d-flex align-items-center gap-2">
                <i class="bi bi-search text-muted"></i>
                <input type="text" name="q" class="form-control border-0 shadow-none"
                       placeholder="Buscar por nombre, CI o teléfono..."
                       value="{{ request('q') }}" style="max-width:380px;">
                @if(request('q'))
                    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-light border">Limpiar</a>
                @endif
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Cliente</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">CI / Doc.</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Contacto</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-center" style="letter-spacing:.04em;font-size:.72rem;">Ventas / Servicios</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end" style="letter-spacing:.04em;font-size:.72rem;">Por cobrar</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase" style="letter-spacing:.04em;font-size:.72rem;">Ubicación</th>
                            <th class="py-2 fw-semibold text-muted text-uppercase text-end pe-3" style="letter-spacing:.04em;font-size:.72rem;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr class="border-bottom border-light client-row" data-href="{{ route('clients.show', $client) }}" style="cursor:pointer;">
                            <td class="ps-3 py-2">
                                <div class="d-flex align-items-center gap-2">
                                    @if($client->photo_url)
                                        <img src="{{ $client->photo_url }}" alt="{{ $client->full_name }}"
                                             class="rounded-circle object-fit-cover flex-shrink-0"
                                             style="width:30px;height:30px;">
                                    @else
                                        <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:30px;height:30px;font-size:.75rem;font-weight:700;color:#9a9a9a;">
                                            {{ strtoupper(substr($client->full_name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="fw-semibold lh-sm">{{ $client->full_name }}</div>
                                        @if($client->email)
                                            <span class="text-muted" style="font-size:.75rem;">{{ $client->email }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-2">{{ $client->id_number ?: '—' }}</td>
                            <td class="py-2">{{ $client->phone ?: '—' }}</td>
                            <td class="py-2 text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1" title="Ventas">
                                    <i class="bi bi-cart me-1"></i>{{ $client->sales_count }}
                                </span>
                                <span class="badge bg-info-subtle text-info border border-info-subtle" title="Servicios de taller">
                                    <i class="bi bi-tools me-1"></i>{{ $client->work_orders_count }}
                                </span>
                            </td>
                            <td class="py-2 text-end">
                                @if((float) $client->credit_due > 0.001)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="Saldo pendiente por cobrar">
                                        <i class="bi bi-exclamation-circle me-1"></i>{{ money($client->credit_due, null, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2">
                                @if($client->location_link)
                                    <a href="{{ $client->location_link }}" target="_blank"
                                       class="btn btn-sm btn-light border py-0 px-2" title="Ver en mapa" style="font-size:.8rem;">
                                        <i class="bi bi-geo-alt-fill text-danger"></i>
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-end pe-3">
                                <span class="badge {{ $client->active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}" style="font-size:.75rem;">
                                    {{ $client->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-25"></i>
                                No hay clientes registrados.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">{{ $clients->links() }}</div>

</div>

@push('scripts')
<script>
document.querySelectorAll('tr.client-row').forEach(function (row) {
    row.addEventListener('click', function (e) {
        // No navegar si se hizo clic en un enlace, botón o formulario interno
        if (e.target.closest('a, button, form, input')) return;
        window.location = row.dataset.href;
    });
});
</script>
@endpush
@endsection
