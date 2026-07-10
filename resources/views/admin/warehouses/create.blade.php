@extends('layouts.app')

@section('title', 'Nuevo almacén')

@section('page')
@include('admin.warehouses.form', ['action' => route('warehouses.store'), 'method' => 'POST', 'warehouse' => null])
@endsection