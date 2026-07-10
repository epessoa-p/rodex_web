@extends('layouts.app')
@section('title', 'Alquileres · Calendario')
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-calendar3 me-2 text-danger"></i>Calendario de alquileres</h1>
            <p class="text-muted mb-0 small">Disponibilidad por moto. Cada barra es un alquiler en su rango de fechas.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-light border" id="btnFit"><i class="bi bi-arrows-fullscreen me-1"></i>Ajustar</button>
            @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('rentals.create', auth()->user()->getCurrentCompany()))
            <a href="{{ route('rentals.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Nueva reserva</a>
            @endif
        </div>
    </div>

    {{-- Leyenda --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-4 d-flex flex-wrap gap-3 small align-items-center">
            <span class="text-muted">Estados:</span>
            <span><span class="d-inline-block rounded" style="width:14px;height:14px;background:#0dcaf0;"></span> Reservada</span>
            <span><span class="d-inline-block rounded" style="width:14px;height:14px;background:#0d6efd;"></span> Contrato</span>
            <span><span class="d-inline-block rounded" style="width:14px;height:14px;background:#ffc107;"></span> En alquiler</span>
            <span><span class="d-inline-block rounded" style="width:14px;height:14px;background:#198754;"></span> Cerrada</span>
            <span><span class="d-inline-block rounded" style="width:14px;height:14px;background:#6c757d;"></span> Devuelta</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-2">
            <div id="rentalTimeline"></div>
            <div id="timelineEmpty" class="text-center py-5 text-muted d-none"><i class="bi bi-calendar-x fs-2 opacity-25 d-block mb-2"></i>No hay motos ni alquileres para mostrar.</div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/vis-timeline/7.7.3/vis-timeline-graph2d.min.css" rel="stylesheet">
<style>
    .vis-timeline { border:none; font-size:.8rem; }
    .vis-item { border-radius:4px; border-width:1px; }
    .vis-item .vis-item-content { padding:2px 6px; }
    .vis-labelset .vis-label { font-size:.8rem; color:#212529; }
    .vis-time-axis .vis-text { color:#6c757d; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/vis-timeline/7.7.3/vis-timeline-graph2d.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', async function () {
    const container = document.getElementById('rentalTimeline');
    let timeline = null;
    try {
        const res = await fetch('{{ route('rentals.calendar.data') }}', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.groups.length) {
            document.getElementById('timelineEmpty').classList.remove('d-none');
            return;
        }

        const groups = new vis.DataSet(data.groups);
        const items  = new vis.DataSet(data.items);
        const options = {
            stack: true,
            orientation: 'top',
            zoomMin: 1000 * 60 * 60 * 24 * 3,
            margin: { item: 6, axis: 8 },
            tooltip: { followMouse: true },
            locale: 'es',
        };
        timeline = new vis.Timeline(container, items, groups, options);
        timeline.fit();

        timeline.on('select', function (props) {
            if (!props.items.length) return;
            const item = items.get(props.items[0]);
            if (item && item.rentalId) {
                window.location.href = '{{ url('rentals') }}/' + item.rentalId;
            }
        });

        document.getElementById('btnFit').addEventListener('click', () => timeline && timeline.fit());
    } catch (e) {
        document.getElementById('timelineEmpty').classList.remove('d-none');
        console.error(e);
    }
});
</script>
@endpush
@endsection
