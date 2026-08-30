@extends('layouts.app')
@section('title', 'Agenda')
@section('page')
@php
    use Illuminate\Support\Carbon;
    $authUser       = auth()->user();
    $currentCompany = $authUser->getCurrentCompany();
    $canCreate = $authUser->is_super_admin || $authUser->hasPermissionInCompany('appointments.create', $currentCompany);
    $canEdit   = $authUser->is_super_admin || $authUser->hasPermissionInCompany('appointments.edit', $currentCompany);
    $canDelete = $authUser->is_super_admin || $authUser->hasPermissionInCompany('appointments.delete', $currentCompany);
    $canWorkOrder = $authUser->is_super_admin || $authUser->hasPermissionInCompany('workshop.create', $currentCompany);
    $dias = ['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    $prevDate = match($view) {
        'week'  => $date->copy()->subWeek(),
        'month' => $date->copy()->subMonthNoOverflow(),
        default => $date->copy()->subDay(),
    };
    $nextDate = match($view) {
        'week'  => $date->copy()->addWeek(),
        'month' => $date->copy()->addMonthNoOverflow(),
        default => $date->copy()->addDay(),
    };
    $prev = $prevDate->toDateString();
    $next = $nextDate->toDateString();
    // Etiqueta del rango según la vista
    $rangeLabel = match($view) {
        'week'  => $weekStart->isoFormat('D MMM') . ' — ' . $weekEnd->isoFormat('D MMM, YYYY'),
        'month' => \Illuminate\Support\Str::ucfirst($date->isoFormat('MMMM YYYY')),
        default => \Illuminate\Support\Str::ucfirst($date->isoFormat('dddd D [de] MMMM, YYYY')),
    };
@endphp

<style>
    .agenda-daychip { border:1px solid var(--bs-border-color); border-radius:14px; padding:.55rem .25rem; text-align:center; text-decoration:none; color:inherit; transition:.15s; display:block; position:relative; }
    .agenda-daychip:hover { background:var(--bs-tertiary-bg); transform:translateY(-2px); }
    .agenda-daychip.active { background:var(--bs-primary); border-color:var(--bs-primary); color:#fff; box-shadow:0 6px 16px rgba(var(--bs-primary-rgb),.35); }
    .agenda-daychip .dow { font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; opacity:.8; }
    .agenda-daychip .dnum { font-size:1.15rem; font-weight:700; line-height:1.1; }
    .agenda-daychip .cnt { position:absolute; top:6px; right:6px; font-size:.62rem; min-width:16px; height:16px; line-height:16px; border-radius:8px; background:var(--bs-danger); color:#fff; padding:0 4px; }
    .agenda-daychip.active .cnt { background:#fff; color:var(--bs-primary); }
    .agenda-daychip .today-dot { width:5px; height:5px; border-radius:50%; background:var(--bs-danger); margin:2px auto 0; }
    .agenda-daychip.active .today-dot { background:#fff; }
    .tl-item { display:flex; gap:.9rem; }
    .tl-time { flex:0 0 62px; text-align:right; padding-top:.15rem; }
    .tl-time .h { font-weight:700; font-size:.95rem; }
    .tl-time .dur { font-size:.7rem; color:var(--bs-secondary-color); }
    .tl-rail { flex:0 0 14px; position:relative; }
    .tl-rail::before { content:''; position:absolute; left:50%; top:0; bottom:-1rem; width:2px; background:var(--bs-border-color); transform:translateX(-50%); }
    .tl-dot { position:absolute; left:50%; top:.35rem; width:12px; height:12px; border-radius:50%; transform:translateX(-50%); border:2px solid var(--bs-body-bg); z-index:1; }
    .tl-card { flex:1 1 auto; margin-bottom:1rem; }
    .tl-card .card { border:1px solid var(--bs-border-color); border-radius:14px; }
    .agenda-stat { border-radius:14px; padding:.75rem 1rem; }
    /* Semana */
    .wk-col { border:1px solid var(--bs-border-color); border-radius:12px; overflow:hidden; height:100%; }
    .wk-col .wk-head { padding:.4rem .6rem; font-size:.8rem; font-weight:700; background:var(--bs-tertiary-bg); text-decoration:none; color:inherit; display:flex; justify-content:space-between; align-items:center; }
    .wk-col .wk-head.today { color:var(--bs-primary); }
    .wk-col .wk-body { padding:.4rem; min-height:60px; }
    .wk-appt { border-left:3px solid var(--bs-border-color); border-radius:8px; background:var(--bs-body-bg); padding:.3rem .45rem; margin-bottom:.4rem; }
    .wk-appt .wk-time { font-weight:700; font-size:.78rem; }
    .wk-appt .wk-name { font-size:.8rem; }
    /* Mes */
    .mo-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:5px; }
    .mo-dow { text-align:center; font-size:.72rem; color:var(--bs-secondary-color); font-weight:600; text-transform:uppercase; }
    .mo-cell { border:1px solid var(--bs-border-color); border-radius:10px; min-height:82px; padding:.35rem; text-decoration:none; color:inherit; display:flex; flex-direction:column; transition:.12s; }
    .mo-cell:hover { background:var(--bs-tertiary-bg); }
    .mo-cell.out { opacity:.4; }
    .mo-cell.today { border-color:var(--bs-primary); border-width:2px; }
    .mo-cell .mo-num { font-size:.85rem; font-weight:600; }
    .mo-cell.today .mo-num { color:var(--bs-primary); }
    .mo-cell .mo-count { margin-top:auto; font-size:.72rem; background:rgba(var(--bs-primary-rgb),.14); color:var(--bs-primary); border-radius:6px; text-align:center; padding:.05rem .2rem; font-weight:700; }
</style>

<div class="container-fluid">

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-calendar2-week me-2 text-primary"></i>Agenda</h1>
            <p class="text-muted mb-0 small">Revisa tu disponibilidad y agenda los servicios.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            {{-- Selector de vista --}}
            <div class="btn-group btn-group-sm" role="group">
                @foreach(['day' => 'Día', 'week' => 'Semana', 'month' => 'Mes'] as $vk => $vl)
                <a href="{{ route('workshop.agenda.index', ['view' => $vk, 'date' => $date->toDateString()]) }}"
                   class="btn {{ $view === $vk ? 'btn-primary' : 'btn-light border' }}">{{ $vl }}</a>
                @endforeach
            </div>
            <a href="{{ route('workshop.agenda.index', ['view' => $view, 'date' => now()->toDateString()]) }}" class="btn btn-light border btn-sm"><i class="bi bi-dot"></i> Hoy</a>
            <div class="btn-group">
                <a href="{{ route('workshop.agenda.index', ['view' => $view, 'date' => $prev]) }}" class="btn btn-light border btn-sm"><i class="bi bi-chevron-left"></i></a>
                <input type="date" id="dateJump" class="form-control form-control-sm border" style="width:150px" value="{{ $date->toDateString() }}">
                <a href="{{ route('workshop.agenda.index', ['view' => $view, 'date' => $next]) }}" class="btn btn-light border btn-sm"><i class="bi bi-chevron-right"></i></a>
            </div>
            @if($canCreate)
            <button type="button" class="btn btn-primary btn-sm" id="btnNewAppt"><i class="bi bi-plus-lg me-1"></i>Nueva cita</button>
            @endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if(session('info'))<div class="alert alert-info border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
    @if($errors->any())<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif

    @if($view === 'day')
    {{-- Tira de la semana --}}
    <div class="row g-2 mb-3">
        @foreach($week as $i => $d)
        <div class="col">
            <a href="{{ route('workshop.agenda.index', ['view' => 'day', 'date' => $d['date']->toDateString()]) }}"
               class="agenda-daychip {{ $d['isActive'] ? 'active' : '' }}">
                @if($d['count'] > 0)<span class="cnt">{{ $d['count'] }}</span>@endif
                <div class="dow">{{ $dias[$i] }}</div>
                <div class="dnum">{{ $d['date']->format('d') }}</div>
                @if($d['isToday'])<div class="today-dot"></div>@endif
            </a>
        </div>
        @endforeach
    </div>

    {{-- Resumen del día --}}
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3"><div class="agenda-stat bg-body-tertiary"><div class="small text-muted">Total del día</div><div class="fs-4 fw-bold">{{ $stats['total'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="agenda-stat" style="background:rgba(var(--bs-info-rgb),.12)"><div class="small text-muted">Programadas</div><div class="fs-4 fw-bold text-info-emphasis">{{ $stats['programada'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="agenda-stat" style="background:rgba(var(--bs-primary-rgb),.12)"><div class="small text-muted">Confirmadas</div><div class="fs-4 fw-bold text-primary-emphasis">{{ $stats['confirmada'] }}</div></div></div>
        <div class="col-6 col-md-3"><div class="agenda-stat" style="background:rgba(var(--bs-success-rgb),.12)"><div class="small text-muted">Completadas</div><div class="fs-4 fw-bold text-success-emphasis">{{ $stats['completada'] }}</div></div></div>
    </div>

    {{-- Línea de tiempo del día --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 pt-3">
            <h2 class="fs-6 fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>{{ $date->isoFormat('dddd D [de] MMMM, YYYY') }}</h2>
        </div>
        <div class="card-body">
            @forelse($appointments as $appt)
            @php $c = $appt->status_color; @endphp
            <div class="tl-item">
                <div class="tl-time">
                    <div class="h">{{ $appt->scheduled_at->format('H:i') }}</div>
                    <div class="dur">{{ $appt->ends_at?->format('H:i') }}</div>
                </div>
                <div class="tl-rail"><span class="tl-dot bg-{{ $c }}"></span></div>
                <div class="tl-card">
                    <div class="card">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div class="min-w-0">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold">{{ $appt->display_name }}</span>
                                        <span class="badge bg-{{ $c }}-subtle text-{{ $c }}-emphasis border border-{{ $c }}-subtle">{{ $appt->status_label }}</span>
                                        @if($appt->work_order_id)<a href="{{ route('workshop.orders.show', $appt->work_order_id) }}" class="badge text-bg-dark text-decoration-none"><i class="bi bi-clipboard2-check me-1"></i>OT creada</a>@endif
                                    </div>
                                    <div class="small text-muted mt-1">
                                        @if($appt->title)<span class="me-2"><i class="bi bi-wrench-adjustable me-1"></i>{{ $appt->title }}</span>@endif
                                        @if($appt->vehicle)<span class="me-2"><i class="bi bi-bicycle me-1"></i>{{ trim($appt->vehicle->brand.' '.$appt->vehicle->model) }}{{ $appt->vehicle->plate ? ' · '.$appt->vehicle->plate : '' }}</span>@endif
                                        @if($appt->mechanic)<span class="me-2"><i class="bi bi-person-gear me-1"></i>{{ $appt->mechanic->name }}</span>@endif
                                        @if($appt->display_phone)<span class="me-2"><i class="bi bi-telephone me-1"></i>{{ $appt->display_phone }}</span>@endif
                                        <span><i class="bi bi-hourglass-split me-1"></i>{{ $appt->duration_minutes }} min</span>
                                    </div>
                                    @if($appt->notes)<div class="small text-body-secondary mt-1 fst-italic">{{ $appt->notes }}</div>@endif
                                </div>

                                @include('workshop.agenda._appt-actions', ['appt' => $appt])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center text-muted py-5">
                <i class="bi bi-calendar2-x fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-2">No hay citas para este día.</p>
                @if($canCreate)<button type="button" class="btn btn-primary btn-sm" id="btnNewApptEmpty"><i class="bi bi-plus-lg me-1"></i>Agendar una cita</button>@endif
            </div>
            @endforelse
        </div>
    </div>

    @elseif($view === 'week')
    {{-- Vista semana --}}
    <div class="mb-2 fw-bold fs-6"><i class="bi bi-calendar3-week me-2 text-primary"></i>{{ $rangeLabel }}</div>
    <div class="row row-cols-2 row-cols-lg-7 g-2">
        @foreach($weekDays as $wd)
        <div class="col">
            <div class="wk-col">
                <a href="{{ route('workshop.agenda.index', ['view' => 'day', 'date' => $wd['date']->toDateString()]) }}" class="wk-head {{ $wd['isToday'] ? 'today' : '' }}">
                    <span>{{ $dias[$wd['date']->dayOfWeekIso - 1] }} {{ $wd['date']->format('d') }}</span>
                    @if($wd['items']->count())<span class="badge bg-primary-subtle text-primary-emphasis">{{ $wd['items']->count() }}</span>@endif
                </a>
                <div class="wk-body">
                    @forelse($wd['items'] as $appt)
                    @php $c = $appt->status_color; @endphp
                    <div class="wk-appt" style="border-left-color:var(--bs-{{ $c }})">
                        <div class="d-flex justify-content-between align-items-start gap-1">
                            <div class="min-w-0 flex-grow-1">
                                <div class="wk-time">{{ $appt->scheduled_at->format('H:i') }}</div>
                                <div class="wk-name text-truncate">{{ $appt->display_name }}</div>
                                @if($appt->title)<div class="small text-muted text-truncate">{{ $appt->title }}</div>@endif
                                @if($appt->work_order_id)<span class="badge text-bg-dark mt-1"><i class="bi bi-clipboard2-check"></i></span>@endif
                            </div>
                            @include('workshop.agenda._appt-actions', ['appt' => $appt])
                        </div>
                    </div>
                    @empty
                    <div class="small text-muted text-center py-2">—</div>
                    @endforelse
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @else
    {{-- Vista mes --}}
    <div class="mb-2 fw-bold fs-6"><i class="bi bi-calendar3 me-2 text-primary"></i>{{ $rangeLabel }}</div>
    <div class="mo-grid mb-2">
        @foreach($dias as $dn)<div class="mo-dow">{{ $dn }}</div>@endforeach
    </div>
    <div class="mo-grid">
        @foreach($monthCells as $cell)
        <a href="{{ route('workshop.agenda.index', ['view' => 'day', 'date' => $cell['date']->toDateString()]) }}"
           class="mo-cell {{ $cell['inMonth'] ? '' : 'out' }} {{ $cell['isToday'] ? 'today' : '' }}">
            <div class="mo-num">{{ $cell['date']->format('d') }}</div>
            @if($cell['count'] > 0)<div class="mo-count">{{ $cell['count'] }} {{ $cell['count'] == 1 ? 'cita' : 'citas' }}</div>@endif
        </a>
        @endforeach
    </div>
    @endif
</div>

@if($canCreate || $canEdit)
{{-- Modal Nueva / Editar cita --}}
<div class="modal fade" id="apptModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="apptForm" method="POST" action="{{ route('workshop.agenda.store') }}">
                @csrf
                <input type="hidden" name="_method" id="apptMethod" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="apptModalTitle"><i class="bi bi-calendar2-plus me-2 text-primary"></i>Nueva cita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Tipo de cliente --}}
                    <div class="btn-group btn-group-sm w-100 mb-3" role="group" id="clientMode">
                        <input type="radio" class="btn-check" name="__cmode" id="cmodeReg" value="registered" checked>
                        <label class="btn btn-outline-primary" for="cmodeReg"><i class="bi bi-person-check me-1"></i>Cliente registrado</label>
                        <input type="radio" class="btn-check" name="__cmode" id="cmodeQuick" value="quick">
                        <label class="btn btn-outline-primary" for="cmodeQuick"><i class="bi bi-person-plus me-1"></i>Rápido (nombre)</label>
                    </div>

                    {{-- Cliente registrado --}}
                    <div id="paneRegistered">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Cliente</label>
                                <select name="client_id" id="f_client" class="form-select">
                                    <option value="">— Seleccionar cliente —</option>
                                    @foreach($clients as $c)<option value="{{ $c->id }}" data-phone="{{ $c->phone }}">{{ $c->full_name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Vehículo</label>
                                <select name="vehicle_id" id="f_vehicle" class="form-select">
                                    <option value="">— Sin especificar —</option>
                                    @foreach($vehicles as $v)<option value="{{ $v->id }}" data-client="{{ $v->client_id }}">{{ trim($v->brand.' '.$v->model) }}{{ $v->plate ? ' · '.$v->plate : '' }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Cliente rápido --}}
                    <div id="paneQuick" class="d-none">
                        <div class="row g-3">
                            <div class="col-md-7">
                                <label class="form-label small fw-semibold">Nombre</label>
                                <input type="text" name="customer_name" id="f_cname" class="form-control" maxlength="255" placeholder="Nombre del cliente">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Teléfono</label>
                                <input type="text" name="customer_phone" id="f_cphone" class="form-control" maxlength="30" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Servicio</label>
                            <select name="service_id" id="f_service" class="form-select">
                                <option value="">— Sin especificar —</option>
                                @foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Mecánico</label>
                            <select name="mechanic_id" id="f_mechanic" class="form-select">
                                <option value="">Sin asignar</option>
                                @foreach($mechanics as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Motivo / detalle</label>
                            <input type="text" name="title" id="f_title" class="form-control" maxlength="255" placeholder="Ej: Cambio de aceite, revisión de frenos…">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Fecha</label>
                            <input type="date" name="__date" id="f_date" class="form-control" value="{{ $date->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Hora</label>
                            <input type="time" name="__time" id="f_time" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Duración</label>
                            <select name="duration_minutes" id="f_duration" class="form-select">
                                <option value="30">30 min</option>
                                <option value="60" selected>1 hora</option>
                                <option value="90">1 h 30 min</option>
                                <option value="120">2 horas</option>
                                <option value="180">3 horas</option>
                                <option value="240">4 horas</option>
                            </select>
                        </div>
                        <input type="hidden" name="scheduled_at" id="f_scheduled">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Notas</label>
                            <textarea name="notes" id="f_notes" class="form-control" rows="2" maxlength="1000" placeholder="Observaciones (opcional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar cita</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    const APPTS = @json($appointmentsJson ?? []);
    const storeUrl = "{{ route('workshop.agenda.store') }}";
    const updateBase = "{{ url('workshop/agenda') }}";

    // Navegación por fecha (preserva la vista actual)
    document.getElementById('dateJump')?.addEventListener('change', function () {
        if (this.value) window.location = "{{ route('workshop.agenda.index') }}?view={{ $view }}&date=" + this.value;
    });

    const apptModalEl = document.getElementById('apptModal');
    if (apptModalEl) {
        const apptModal = new bootstrap.Modal(apptModalEl);
        const form = document.getElementById('apptForm');

        function setClientMode(mode) {
            const reg = mode === 'registered';
            document.getElementById('paneRegistered').classList.toggle('d-none', !reg);
            document.getElementById('paneQuick').classList.toggle('d-none', reg);
            document.getElementById('cmodeReg').checked = reg;
            document.getElementById('cmodeQuick').checked = !reg;
            // Limpia el modo inactivo para no enviar datos cruzados
            if (reg) { document.getElementById('f_cname').value=''; document.getElementById('f_cphone').value=''; }
            else { document.getElementById('f_client').value=''; document.getElementById('f_vehicle').value=''; filterVehicles(); }
        }
        document.querySelectorAll('input[name="__cmode"]').forEach(r =>
            r.addEventListener('change', () => setClientMode(r.value)));

        // Filtra vehículos por cliente elegido
        function filterVehicles() {
            const cid = document.getElementById('f_client').value;
            document.querySelectorAll('#f_vehicle option').forEach(o => {
                if (!o.value) return;
                const show = !cid || o.dataset.client === cid;
                o.hidden = !show;
            });
            const sel = document.getElementById('f_vehicle');
            if (sel.selectedOptions[0]?.hidden) sel.value = '';
        }
        document.getElementById('f_client').addEventListener('change', filterVehicles);

        // Autorrellena el motivo con el servicio si está vacío
        document.getElementById('f_service').addEventListener('change', function () {
            const t = document.getElementById('f_title');
            if (!t.value && this.selectedOptions[0]) t.value = this.selectedOptions[0].text;
        });

        function openNew() {
            form.reset();
            form.action = storeUrl;
            document.getElementById('apptMethod').value = 'POST';
            document.getElementById('apptModalTitle').innerHTML = '<i class="bi bi-calendar2-plus me-2 text-primary"></i>Nueva cita';
            document.getElementById('f_date').value = "{{ $date->toDateString() }}";
            document.getElementById('f_time').value = '09:00';
            document.getElementById('f_duration').value = '60';
            setClientMode('registered');
            filterVehicles();
            apptModal.show();
        }
        document.getElementById('btnNewAppt')?.addEventListener('click', openNew);
        document.getElementById('btnNewApptEmpty')?.addEventListener('click', openNew);

        // Editar
        document.querySelectorAll('.btn-edit-appt').forEach(btn => btn.addEventListener('click', function () {
            const a = APPTS[this.dataset.appt];
            if (!a) return;
            form.reset();
            form.action = updateBase + '/' + a.id;
            document.getElementById('apptMethod').value = 'PUT';
            document.getElementById('apptModalTitle').innerHTML = '<i class="bi bi-pencil me-2 text-primary"></i>Editar cita';
            const quick = !a.client_id;
            setClientMode(quick ? 'quick' : 'registered');
            document.getElementById('f_client').value = a.client_id || '';
            filterVehicles();
            document.getElementById('f_vehicle').value = a.vehicle_id || '';
            document.getElementById('f_cname').value = a.customer_name || '';
            document.getElementById('f_cphone').value = a.customer_phone || '';
            document.getElementById('f_service').value = a.service_id || '';
            document.getElementById('f_mechanic').value = a.mechanic_id || '';
            document.getElementById('f_title').value = a.title || '';
            document.getElementById('f_date').value = a.date;
            document.getElementById('f_time').value = a.time;
            document.getElementById('f_duration').value = a.duration_minutes;
            document.getElementById('f_notes').value = a.notes || '';
            apptModal.show();
        }));

        // Combina fecha + hora en scheduled_at antes de enviar
        form.addEventListener('submit', function () {
            document.getElementById('f_scheduled').value =
                document.getElementById('f_date').value + ' ' + document.getElementById('f_time').value + ':00';
        });
    }
</script>
@endpush
