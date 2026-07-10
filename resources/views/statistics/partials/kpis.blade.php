{{-- KPI cards. Props: $kpis (array de ['label','value', opcional 'pct','invert']) --}}
<div class="row g-2 mb-3">
    @foreach($kpis as $k)
    <div class="col-6 col-lg">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body py-2 px-3">
                <div class="text-muted text-uppercase d-flex justify-content-between align-items-center" style="font-size:.62rem;letter-spacing:.04em;">
                    <span>{{ $k['label'] }}</span>
                    @if(isset($k['pct']))
                        @php $good = (($k['pct'] >= 0) xor !empty($k['invert'])); @endphp
                        <span class="badge bg-{{ $good ? 'success' : 'danger' }}-subtle text-{{ $good ? 'success' : 'danger' }}" style="font-size:.6rem;">
                            <i class="bi bi-arrow-{{ $k['pct'] >= 0 ? 'up' : 'down' }}"></i>{{ abs($k['pct']) }}%
                        </span>
                    @endif
                </div>
                <div class="fw-bold mt-1" style="font-size:1.02rem;">{{ $k['value'] }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>
