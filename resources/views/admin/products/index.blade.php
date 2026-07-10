@extends('layouts.app')

@section('title', 'Productos')

@section('page')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h1 class="mb-1"><i class="bi bi-box-seam"></i> Productos</h1><p class="text-muted mb-0">Catálogo de productos de la tienda.</p></div>
        <a href="{{ route('products.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuevo producto</a>
    </div>
    <div class="card border-0 shadow-sm"><div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-light"><tr><th>Producto</th><th>SKU</th><th>Empresa</th><th>Unidad</th><th class="text-end">Costo</th><th class="text-end">Precio</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>@forelse($products as $product)<tr><td>{{ $product->name }}</td><td>{{ $product->sku }}</td><td>{{ $product->company?->name ?: '-' }}</td><td>{{ $product->unit }}</td><td class="text-end">${{ number_format($product->cost, 2) }}</td><td class="text-end">${{ number_format($product->price, 2) }}</td><td>@if($product->active)<span class="badge bg-success">Activo</span>@else<span class="badge bg-secondary">Inactivo</span>@endif</td><td class="text-end"><a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a><form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar producto?')"><i class="bi bi-trash"></i></button></form></td></tr>@empty<tr><td colspan="8" class="text-center py-5 text-muted">No hay productos registrados.</td></tr>@endforelse</tbody></table></div></div></div>
    <div class="mt-4 d-flex justify-content-center">{{ $products->links() }}</div>
</div>
@endsection
