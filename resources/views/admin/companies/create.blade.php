@extends('layouts.app')

@section('page')
<h1><i class="bi bi-plus-circle"></i> Nueva Empresa</h1>

<div class="card mt-4">
    <div class="card-body">
        <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="form-group mb-3">
                <label for="logo" class="form-label">Logo</label>
                <input type="file" id="logo" name="logo" accept="image/*"
                       class="form-control @error('logo') is-invalid @enderror">
                <div class="form-text">Se muestra en el menú, los recibos y las impresiones de esta empresa.</div>
                @error('logo')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            </div>

            <div class="form-group mb-3">
                <label for="ruc" class="form-label">RUC</label>
                <input type="text" id="ruc" name="ruc" class="form-control @error('ruc') is-invalid @enderror" value="{{ old('ruc') }}">
            </div>

            <div class="form-group mb-3">
                <label for="currency" class="form-label">Moneda</label>
                <input type="text" id="currency" name="currency" maxlength="8"
                       class="form-control @error('currency') is-invalid @enderror"
                       value="{{ old('currency', 'Bs') }}" placeholder="Bs, $, S/, Gs…">
                <small class="text-muted">Símbolo que se mostrará en precios y totales de esta empresa.</small>
                @error('currency')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
            </div>

            <div class="form-group mb-3">
                <label for="phone" class="form-label">Teléfono</label>
                <input type="text" id="phone" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone') }}">
            </div>

            <div class="form-group mb-3">
                <label for="address" class="form-label">Dirección</label>
                <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address') }}">
            </div>

            <div class="form-group mb-3">
                <label for="description" class="form-label">Descripción</label>
                <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
            </div>

            @include('admin.companies._theme')

            {{-- ═══ Alta lista para usar: plan · sucursal · cargo · personal + acceso + caja ═══ --}}
            <hr class="my-4">
            <h5 class="fw-bold mb-1"><i class="bi bi-rocket-takeoff me-2 text-danger"></i>Dejarla lista para usar</h5>
            <p class="text-muted small mb-3">Todo opcional. Si llenas el personal, la empresa queda operativa al instante y abajo verás las credenciales para entregar al cliente.</p>

            @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm py-2 small"><i class="bi bi-exclamation-circle me-1"></i>{{ $errors->first() }}</div>
            @endif

            <div class="row g-3">
                {{-- Plan --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #4f46e5 !important;">
                        <div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold"><i class="bi bi-credit-card me-2" style="color:#4f46e5;"></i>Plan</h6></div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-8">
                                    <label class="form-label small fw-semibold">Plan</label>
                                    <select name="plan_id" class="form-select">
                                        <option value="">— Sin plan por ahora —</option>
                                        @foreach($plans as $p)
                                        <option value="{{ $p->id }}" {{ (string) old('plan_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }} · {{ money($p->price) }}/{{ $p->billing_period === 'yearly' ? 'año' : 'mes' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Inicia como</label>
                                    <select name="subscription_status" class="form-select">
                                        <option value="trial" {{ old('subscription_status', 'trial') === 'trial' ? 'selected' : '' }}>Prueba</option>
                                        <option value="active" {{ old('subscription_status') === 'active' ? 'selected' : '' }}>Activa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-text">Prueba: dura los días de prueba del plan. Activa: un periodo pagado desde hoy. Se puede ajustar luego en Suscripciones.</div>
                        </div>
                    </div>
                </div>

                {{-- Sucursal --}}
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #00c853 !important;">
                        <div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold"><i class="bi bi-shop me-2" style="color:#00c853;"></i>Primera sucursal</h6></div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Nombre</label>
                                    <input type="text" name="branch_name" class="form-control @error('branch_name') is-invalid @enderror" value="{{ old('branch_name') }}" placeholder="Ej: CASA MATRIZ">
                                    @error('branch_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-8">
                                    <label class="form-label small fw-semibold">Dirección</label>
                                    <input type="text" name="branch_address" class="form-control" value="{{ old('branch_address') }}">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-semibold">Teléfono</label>
                                    <input type="text" name="branch_phone" class="form-control" value="{{ old('branch_phone') }}">
                                </div>
                            </div>
                            <div class="form-text"><i class="bi bi-magic me-1"></i>Crea también su <strong>almacén</strong> con el mismo nombre y dirección (código automático).</div>
                        </div>
                    </div>
                </div>

                {{-- Cargo --}}
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #e91e63 !important;">
                        <div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold"><i class="bi bi-person-badge me-2" style="color:#e91e63;"></i>Cargo</h6></div>
                        <div class="card-body p-3">
                            <label class="form-label small fw-semibold">Nombre del cargo</label>
                            <input type="text" name="cargo_name" class="form-control @error('cargo_name') is-invalid @enderror" value="{{ old('cargo_name', 'ADMINISTRADOR') }}">
                            @error('cargo_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Crea el rol con <strong>todos los permisos</strong> (los módulos siguen limitados por el plan). Puedes afinarlo luego en Cargos.</div>
                        </div>
                    </div>
                </div>

                {{-- Personal + acceso + caja --}}
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #2563eb !important;">
                        <div class="card-header bg-white border-bottom py-2 px-3"><h6 class="mb-0 fw-semibold"><i class="bi bi-person-vcard me-2" style="color:#2563eb;"></i>Personal con acceso al sistema</h6></div>
                        <div class="card-body p-3">
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <label class="form-label small fw-semibold">Nombre completo</label>
                                    <input type="text" name="personal_name" class="form-control @error('personal_name') is-invalid @enderror" value="{{ old('personal_name') }}">
                                    @error('personal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Teléfono</label>
                                    <input type="text" name="personal_phone" class="form-control" value="{{ old('personal_phone') }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold">Email de acceso</label>
                                    <input type="email" name="user_email" class="form-control @error('user_email') is-invalid @enderror" value="{{ old('user_email') }}" autocomplete="off">
                                    @error('user_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Contraseña</label>
                                    <input type="password" name="user_password" class="form-control @error('user_password') is-invalid @enderror" autocomplete="new-password" minlength="8">
                                    @error('user_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Repetir contraseña</label>
                                    <input type="password" name="user_password_confirmation" class="form-control" autocomplete="new-password">
                                </div>
                                <div class="col-md-12 d-flex align-items-center gap-2 flex-wrap mt-2">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="create_register" value="1" id="create_register" {{ old('create_register', '1') ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-semibold" for="create_register">Crear su caja en la sucursal</label>
                                    </div>
                                    <input type="text" name="register_name" class="form-control form-control-sm" style="max-width:260px" value="{{ old('register_name') }}" placeholder="Nombre de la caja (auto: CAJA + sucursal)">
                                </div>
                            </div>
                            <div class="form-text">Se crea el usuario (hereda el rol del cargo) y el personal en la sucursal. Requiere sucursal y cargo.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Crear Empresa
                </button>
                <a href="{{ route('companies.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
