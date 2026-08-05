@extends('layouts.app')

@section('page')
<h1><i class="bi bi-pencil"></i> Editar Empresa</h1>

<div class="card mt-4">
    <div class="card-body">
        <form action="{{ route('companies.update', $company) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group mb-3">
                <label for="logo" class="form-label">Logo</label>
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ $company->logo_url }}" alt="{{ $company->name }}"
                         style="width:64px;height:64px;object-fit:contain;background:#111;border-radius:8px;padding:4px;">
                    <div class="flex-grow-1">
                        <input type="file" id="logo" name="logo" accept="image/*"
                               class="form-control @error('logo') is-invalid @enderror">
                        <div class="form-text">
                            Se muestra en el menú, los recibos y las impresiones.
                            {{ $company->logo ? 'Sube uno nuevo para reemplazarlo.' : 'Ahora se usa el logo por defecto.' }}
                        </div>
                        @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>

            <div class="form-group mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $company->name) }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="ruc" class="form-label">RUC</label>
                <input type="text" id="ruc" name="ruc" class="form-control @error('ruc') is-invalid @enderror" value="{{ old('ruc', $company->ruc) }}">
            </div>

            <div class="form-group mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $company->email) }}">
            </div>

            <div class="form-group mb-3">
                <label for="phone" class="form-label">Teléfono</label>
                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $company->phone) }}">
            </div>

            <div class="form-group mb-3">
                <label for="address" class="form-label">Dirección</label>
                <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $company->address) }}">
            </div>

            <div class="form-group mb-3">
                <label for="description" class="form-label">Descripción</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $company->description) }}</textarea>
            </div>

            @include('admin.companies._theme', ['company' => $company])

            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Actualizar
                </button>
                <a href="{{ route('companies.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
