@extends('layouts.app')
@section('title', 'Fidelización · ' . ($reward->exists ? 'Editar recompensa' : 'Nueva recompensa'))
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-gift me-2 text-danger"></i>{{ $reward->exists ? 'Editar recompensa' : 'Nueva recompensa' }}</h1>
        </div>
        <a href="{{ route('loyalty.rewards.index') }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form action="{{ $reward->exists ? route('loyalty.rewards.update', $reward) : route('loyalty.rewards.store') }}"
          method="POST" enctype="multipart/form-data">
        @csrf
        @if($reward->exists) @method('PUT') @endif
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $reward->name) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Descripción</label>
                                <textarea name="description" class="form-control" rows="2">{{ old('description', $reward->description) }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Costo en puntos <span class="text-danger">*</span></label>
                                <input type="number" name="points_cost" class="form-control" min="1" step="1"
                                       value="{{ old('points_cost', $reward->points_cost) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Stock <span class="text-muted">(vacío = ilimitado)</span></label>
                                <input type="number" name="stock" class="form-control" min="0" step="1"
                                       value="{{ old('stock', $reward->stock) }}" placeholder="Ilimitado">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Producto enlazado <span class="text-muted">(opcional — descuenta stock al canjear)</span></label>
                                <select name="product_id" class="form-select">
                                    <option value="">— Ninguno —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ (string) old('product_id', $reward->product_id) === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" value="1"
                                           {{ old('active', $reward->active ?? true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="active">Activa (disponible para canje)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <x-media-upload name="image" label="Imagen de la recompensa" :current="$reward->image_url" accent="#e10600" />
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('loyalty.rewards.index') }}" class="btn btn-light border">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Guardar</button>
        </div>
    </form>
</div>
@endsection
