@php
    $u = auth()->user(); $cc = $u->getCurrentCompany();
    $canEdit      = $u->is_super_admin || $u->hasPermissionInCompany('appointments.edit', $cc);
    $canDelete    = $u->is_super_admin || $u->hasPermissionInCompany('appointments.delete', $cc);
    $canWorkOrder = $u->is_super_admin || $u->hasPermissionInCompany('workshop.create', $cc);
@endphp
@if($canEdit || $canDelete)
<div class="dropdown">
    <button class="btn btn-sm btn-light border" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
    <ul class="dropdown-menu dropdown-menu-end shadow">
        @if($canEdit)
        <li><button type="button" class="dropdown-item btn-edit-appt" data-appt="{{ $appt->id }}"><i class="bi bi-pencil me-2"></i>Editar / reprogramar</button></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Cambiar estado</h6></li>
        @foreach(\App\Models\Workshop\Appointment::STATUSES as $key => $meta)
            @if($key !== $appt->status)
            <li>
                <form action="{{ route('workshop.agenda.status', $appt->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="status" value="{{ $key }}">
                    <input type="hidden" name="_view" value="{{ request('view', 'day') }}">
                    <button type="submit" class="dropdown-item"><i class="bi bi-circle-fill me-2 small text-{{ $meta['color'] }}"></i>{{ $meta['label'] }}</button>
                </form>
            </li>
            @endif
        @endforeach
        @endif
        @if($canWorkOrder && !$appt->work_order_id)
        <li><hr class="dropdown-divider"></li>
        <li>
            <form action="{{ route('workshop.agenda.convert', $appt->id) }}" method="POST" onsubmit="return confirm('¿Crear una Orden de Trabajo a partir de esta cita?');">
                @csrf
                <button type="submit" class="dropdown-item text-primary" @if(!$appt->client_id || !$appt->vehicle_id) disabled title="Requiere cliente registrado y vehículo" @endif><i class="bi bi-clipboard2-plus me-2"></i>Crear Orden de Trabajo</button>
            </form>
        </li>
        @endif
        @if($canDelete)
        <li><hr class="dropdown-divider"></li>
        <li>
            <form action="{{ route('workshop.agenda.destroy', $appt->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta cita?');">
                @csrf @method('DELETE')
                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-2"></i>Eliminar</button>
            </form>
        </li>
        @endif
    </ul>
</div>
@endif
