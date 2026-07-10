@extends('layouts.app')

@section('title', 'Editar almacén')

@section('page')
@include('admin.warehouses.form', ['action' => route('warehouses.update', $warehouse), 'method' => 'PUT', 'warehouse' => $warehouse])
@endsection