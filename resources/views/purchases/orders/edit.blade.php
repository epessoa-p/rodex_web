@extends('layouts.app')
@section('title', 'Editar orden: ' . $order->code)
@section('page')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-1 fw-bold fs-4"><i class="bi bi-file-earmark-text me-2 text-danger"></i>Editar orden</h1>
            <p class="text-muted mb-0 small">{{ $order->code }} &mdash; {{ $order->supplier->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchase-orders.show', $order) }}" class="btn btn-light border">
                <i class="bi bi-eye me-1"></i>Ver detalle
            </a>
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-light border">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>

    @include('purchases.orders.form')

</div>
@endsection
