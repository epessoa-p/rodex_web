@extends('layouts.app')
@section('title', 'Garantía ' . $warranty->code)
@section('page')
<div class="container-fluid">

    {{-- Header --}}
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div style="height:5px;background:linear-gradient(90deg,var(--brand-red) 0%,#ff6b6b 50%,transparent 100%);"></div>
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="mb-0 fw-bold fs-4 font-monospace" style="font-size:1.2rem!important;">{{ $warranty->code }}</h1>
                        <span class="badge bg-{{ $warranty->status_color }}-subtle text-{{ $warranty->status_color }} border border-{{ $warranty->status_color }}-subtle">
                            {{ $warranty->status_label }}
                        </span>
                    </div>
                    <div class="d-flex flex-wrap gap-3 text-muted small mt-1">
                        @if($warranty->motoUnit)
                        <span><i class="bi bi-bicycle me-1"></i>{{ $warranty->motoUnit->display_name }}</span>
                        @endif
                        @if($warranty->client)
                        <span><i class="bi bi-person me-1"></i>{{ $warranty->client->full_name }}</span>
                        @endif
                        <span><i class="bi bi-calendar3 me-1"></i>{{ $warranty->start_date->format('d/m/Y') }} → {{ $warranty->end_date->format('d/m/Y') }}</span>
                    </div>
                </div>
                <a href="{{ route('warranties.index') }}" class="btn btn-light border btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Volver
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Detalle --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-shield-check me-2 text-muted"></i>Datos de la garantía</h6>
                </div>
                <div class="card-body p-4">
                    <dl class="row g-3 small mb-0">
                        <dt class="col-5 text-muted fw-normal">Código</dt>
                        <dd class="col-7 mb-0 fw-semibold font-monospace">{{ $warranty->code }}</dd>

                        <dt class="col-5 text-muted fw-normal">Moto</dt>
                        <dd class="col-7 mb-0">
                            @if($warranty->motoUnit)
                            <a href="{{ route('moto-units.show', $warranty->motoUnit) }}" class="text-decoration-none fw-semibold">
                                {{ $warranty->motoUnit->display_name }}
                            </a>
                            @else
                            —
                            @endif
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Cliente</dt>
                        <dd class="col-7 mb-0 fw-semibold">{{ $warranty->client?->full_name ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal">Fecha inicio</dt>
                        <dd class="col-7 mb-0">{{ $warranty->start_date->format('d/m/Y') }}</dd>

                        <dt class="col-5 text-muted fw-normal">Duración</dt>
                        <dd class="col-7 mb-0">{{ $warranty->months }} meses</dd>

                        <dt class="col-5 text-muted fw-normal">Fecha vence</dt>
                        <dd class="col-7 mb-0 fw-semibold {{ $warranty->is_active ? 'text-success' : 'text-muted' }}">
                            {{ $warranty->end_date->format('d/m/Y') }}
                        </dd>

                        <dt class="col-5 text-muted fw-normal">Estado</dt>
                        <dd class="col-7 mb-0">
                            <span class="badge bg-{{ $warranty->status_color }}-subtle text-{{ $warranty->status_color }} border border-{{ $warranty->status_color }}-subtle">
                                {{ $warranty->status_label }}
                            </span>
                        </dd>

                        @if($warranty->coverage)
                        <dt class="col-5 text-muted fw-normal">Cobertura</dt>
                        <dd class="col-7 mb-0" style="white-space:pre-line;">{{ $warranty->coverage }}</dd>
                        @endif

                        @if($warranty->notes)
                        <dt class="col-5 text-muted fw-normal">Notas</dt>
                        <dd class="col-7 mb-0 text-muted" style="white-space:pre-line;">{{ $warranty->notes }}</dd>
                        @endif

                        @if($warranty->creator)
                        <dt class="col-5 text-muted fw-normal">Registrado por</dt>
                        <dd class="col-7 mb-0 text-muted small">{{ $warranty->creator->name }}</dd>
                        @endif

                        <dt class="col-5 text-muted fw-normal">Creado</dt>
                        <dd class="col-7 mb-0 text-muted small">{{ $warranty->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Gestión --}}
        @if(auth()->user()->is_super_admin || auth()->user()->hasPermissionInCompany('warranties.manage', auth()->user()->getCurrentCompany()))
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h6 class="mb-0 fw-semibold"><i class="bi bi-pencil-square me-2 text-muted"></i>Gestionar garantía</h6>
                </div>
                <div class="card-body p-4">

                    @if($errors->any())
                    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show mb-3" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <form action="{{ route('warranties.update', $warranty) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="edit_status">Estado</label>
                                <select id="edit_status" name="status"
                                        class="form-select @error('status') is-invalid @enderror">
                                    <option value="vigente" {{ $warranty->status === 'vigente' ? 'selected' : '' }}>Vigente</option>
                                    <option value="vencida" {{ $warranty->status === 'vencida' ? 'selected' : '' }}>Vencida</option>
                                    <option value="anulada" {{ $warranty->status === 'anulada' ? 'selected' : '' }}>Anulada</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="edit_coverage">Cobertura</label>
                                <textarea id="edit_coverage" name="coverage"
                                          class="form-control @error('coverage') is-invalid @enderror"
                                          rows="3"
                                          placeholder="Describe qué cubre la garantía...">{{ old('coverage', $warranty->coverage) }}</textarea>
                                @error('coverage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold" for="edit_notes">Notas</label>
                                <textarea id="edit_notes" name="notes"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          rows="2"
                                          placeholder="Observaciones...">{{ old('notes', $warranty->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg me-1"></i>Guardar cambios
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </div>
        </div>
        @endif

    </div>

</div>
@endsection
