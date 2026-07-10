<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="mb-1">{{ $product ? 'Editar producto' : 'Nuevo producto' }}</h1>
            <p class="text-muted mb-0">Define catálogo y costos del producto.</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">Volver</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ $action }}" method="POST" class="row g-3">
                @csrf
                @if($method !== 'POST') @method($method) @endif

                @if($companies->count() > 1)
                    <div class="col-md-6">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" class="form-select">
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (string) old('company_id', $product?->company_id) === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $product?->name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">SKU</label>
                    <input type="text" name="sku" class="form-control" value="{{ old('sku', $product?->sku) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Unidad</label>
                    <input type="text" name="unit" class="form-control" value="{{ old('unit', $product?->unit ?? 'unidad') }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Costo</label>
                    <input type="number" step="0.01" name="cost" class="form-control" value="{{ old('cost', $product?->cost) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Precio</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product?->price) }}" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $product?->description) }}</textarea>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="active" value="1" {{ old('active', $product?->active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Activo</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary" type="submit">Guardar</button>
                    <a href="{{ route('products.index') }}" class="btn btn-light border">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>
