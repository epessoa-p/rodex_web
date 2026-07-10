<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SystemResetController;
use App\Http\Controllers\Admin\CargoController;
use App\Http\Controllers\Admin\PersonalController;
use App\Http\Controllers\Clients\ClientController;
use App\Http\Controllers\DocumentTemplates\DocumentTemplateController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.store');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/select-company',             [LoginController::class, 'selectCompany'])->name('select-company');
    Route::post('/set-company/{companyId}',   [LoginController::class, 'setCompany'])->name('set-company');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Empresas (solo super_admin) ───────────────────────────────
    Route::middleware('check-role:super_admin')->prefix('admin/companies')->name('companies.')->group(function () {
        Route::get('/',               [CompanyController::class, 'index'])->name('index');
        Route::get('/create',         [CompanyController::class, 'create'])->name('create');
        Route::post('/',              [CompanyController::class, 'store'])->name('store');
        Route::get('/{company}',      [CompanyController::class, 'show'])->name('show');
        Route::get('/{company}/edit', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}',      [CompanyController::class, 'update'])->name('update');
        Route::delete('/{company}',   [CompanyController::class, 'destroy'])->name('destroy');
    });

    // ── Roles (solo super_admin) ──────────────────────────────────
    Route::middleware('check-role:super_admin')->prefix('admin/roles')->name('roles.')->group(function () {
        Route::get('/',           [RoleController::class, 'index'])->name('index');
        Route::get('/create',     [RoleController::class, 'create'])->name('create');
        Route::post('/',          [RoleController::class, 'store'])->name('store');
        Route::get('/{role}',     [RoleController::class, 'show'])->name('show');
        Route::get('/{role}/edit',[RoleController::class, 'edit'])->name('edit');
        Route::put('/{role}',     [RoleController::class, 'update'])->name('update');
        Route::delete('/{role}',  [RoleController::class, 'destroy'])->name('destroy');
    });

    // ── Reinicio del sistema (solo super_admin) ───────────────────
    Route::middleware('check-role:super_admin')->group(function () {
        Route::get('admin/system/reset',  [SystemResetController::class, 'index'])->name('system.reset');
        Route::post('admin/system/reset', [SystemResetController::class, 'run'])->name('system.reset.run');
    });

    // ── Usuarios ──────────────────────────────────────────────────
    // IMPORTANTE: /create debe registrarse ANTES de /{user} (wildcard)
    Route::prefix('admin/users')->name('users.')->group(function () {
        Route::get('/',               [UserController::class, 'index'])->name('index')->middleware('check-permission:users.view');
        Route::get('/create',         [UserController::class, 'create'])->name('create')->middleware('check-permission:users.create');
        Route::get('/check-username', [UserController::class, 'checkUsername'])->name('check-username');
        Route::post('/',              [UserController::class, 'store'])->name('store')->middleware('check-permission:users.create');
        Route::get('/{user}',         [UserController::class, 'show'])->name('show')->middleware('check-permission:users.view');
        Route::get('/{user}/edit',                           [UserController::class, 'edit'])->name('edit')->middleware('check-permission:users.edit');
        Route::put('/{user}',                                [UserController::class, 'update'])->name('update')->middleware('check-permission:users.edit');
        Route::post('/{user}/assign-role/{company}/{role}',  [UserController::class, 'assignRole'])->name('assign-role')->middleware('check-permission:users.edit');
        Route::delete('/{user}',                             [UserController::class, 'destroy'])->name('destroy')->middleware('check-permission:users.delete');
    });

    // ── Sucursales ────────────────────────────────────────────────
    Route::prefix('admin/branches')->name('branches.')->group(function () {
        Route::get('/',          [BranchController::class, 'index'])->name('index')->middleware('check-permission:branches.view');
        Route::get('/create',    [BranchController::class, 'create'])->name('create')->middleware('check-permission:branches.create');
        Route::post('/',         [BranchController::class, 'store'])->name('store')->middleware('check-permission:branches.create');
        Route::get('/{branch}',  [BranchController::class, 'show'])->name('show')->middleware('check-permission:branches.view');
        Route::get('/{branch}/edit', [BranchController::class, 'edit'])->name('edit')->middleware('check-permission:branches.edit');
        Route::put('/{branch}',      [BranchController::class, 'update'])->name('update')->middleware('check-permission:branches.edit');
        Route::delete('/{branch}',   [BranchController::class, 'destroy'])->name('destroy')->middleware('check-permission:branches.delete');
    });

    // ── Cargos ────────────────────────────────────────────────────
    Route::prefix('admin/cargos')->name('cargos.')->group(function () {
        Route::get('/',                        [CargoController::class, 'index'])->name('index')->middleware('check-permission:cargos.view');
        Route::get('/create',                  [CargoController::class, 'create'])->name('create')->middleware('check-permission:cargos.create');
        Route::post('/',                       [CargoController::class, 'store'])->name('store')->middleware('check-permission:cargos.create');
        Route::get('/role-permissions/{role}', [CargoController::class, 'rolePermissions'])->name('role-permissions')->middleware('check-permission:cargos.view');
        Route::get('/{cargo}/edit',            [CargoController::class, 'edit'])->name('edit')->middleware('check-permission:cargos.edit');
        Route::put('/{cargo}',                 [CargoController::class, 'update'])->name('update')->middleware('check-permission:cargos.edit');
        Route::delete('/{cargo}',              [CargoController::class, 'destroy'])->name('destroy')->middleware('check-permission:cargos.delete');
    });

    // ── Personal ──────────────────────────────────────────────────
    Route::prefix('admin/personal')->name('personal.')->group(function () {
        Route::get('/',               [PersonalController::class, 'index'])->name('index')->middleware('check-permission:personal.view');
        Route::get('/create',         [PersonalController::class, 'create'])->name('create')->middleware('check-permission:personal.create');
        Route::post('/',              [PersonalController::class, 'store'])->name('store')->middleware('check-permission:personal.create');
        Route::get('/{personal}/edit',[PersonalController::class, 'edit'])->name('edit')->middleware('check-permission:personal.edit');
        Route::put('/{personal}',     [PersonalController::class, 'update'])->name('update')->middleware('check-permission:personal.edit');
        Route::delete('/{personal}',  [PersonalController::class, 'destroy'])->name('destroy')->middleware('check-permission:personal.delete');
    });

    // ── Plantillas de documento ───────────────────────────────────
    Route::prefix('document-templates')->name('document-templates.')->group(function () {
        Route::get('/',       [DocumentTemplateController::class, 'index'])->name('index')->middleware('check-permission:document-templates.view');
        Route::get('/create', [DocumentTemplateController::class, 'create'])->name('create')->middleware('check-permission:document-templates.create');
        Route::post('/',      [DocumentTemplateController::class, 'store'])->name('store')->middleware('check-permission:document-templates.create');
        Route::get('/{documentTemplate}/download/word', [DocumentTemplateController::class, 'downloadWord'])->name('download.word')->middleware('check-permission:document-templates.view');
        Route::get('/{documentTemplate}/export/pdf',    [DocumentTemplateController::class, 'exportPdf'])->name('export.pdf')->middleware('check-permission:document-templates.view');
        Route::get('/{documentTemplate}',               [DocumentTemplateController::class, 'show'])->name('show')->middleware('check-permission:document-templates.view');
        Route::get('/{documentTemplate}/edit',          [DocumentTemplateController::class, 'edit'])->name('edit')->middleware('check-permission:document-templates.edit');
        Route::put('/{documentTemplate}',               [DocumentTemplateController::class, 'update'])->name('update')->middleware('check-permission:document-templates.edit');
        Route::delete('/{documentTemplate}',            [DocumentTemplateController::class, 'destroy'])->name('destroy')->middleware('check-permission:document-templates.delete');
    });
});

    // ── Clientes ──────────────────────────────────────────────────
    Route::prefix('admin/clients')->name('clients.')->group(function () {
        Route::get('/',               [ClientController::class, 'index'])->name('index')->middleware('check-permission:clients.view');
        Route::get('/create',         [ClientController::class, 'create'])->name('create')->middleware('check-permission:clients.create');
        Route::post('/quick',         [ClientController::class, 'quickStore'])->name('quick-store')->middleware('check-permission:clients.create');
        Route::post('/',              [ClientController::class, 'store'])->name('store')->middleware('check-permission:clients.create');
        Route::get('/{client}',       [ClientController::class, 'show'])->name('show')->middleware('check-permission:clients.view');
        Route::get('/{client}/edit',  [ClientController::class, 'edit'])->name('edit')->middleware('check-permission:clients.edit');
        Route::put('/{client}',       [ClientController::class, 'update'])->name('update')->middleware('check-permission:clients.edit');
        Route::delete('/{client}',    [ClientController::class, 'destroy'])->name('destroy')->middleware('check-permission:clients.delete');
        Route::delete('/documents/{document}', [ClientController::class, 'destroyDocument'])->name('documents.destroy')->middleware('check-permission:clients.edit');
    });

// Fallback
Route::redirect('/', '/dashboard');
