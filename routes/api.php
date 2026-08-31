<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashRegisterController;
use App\Http\Controllers\Api\CashSessionController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MechanicController;
use App\Http\Controllers\Api\MechanicPaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\TreasuryController;
use App\Http\Controllers\Api\WorkOrderController;
use App\Http\Controllers\Api\WorkshopMetaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API (app móvil) — autenticación por token (Sanctum), sin sesión.
|--------------------------------------------------------------------------
| Prefijo /api. El tenant se resuelve por el header X-Company-Id (api.tenant).
*/

// Público
Route::post('login', [AuthController::class, 'login']);

// Autenticado (token), sin empresa aún: cerrar sesión.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    // Todo lo que opera sobre una empresa: token + tenant (header) + suscripción.
    Route::middleware(['api.tenant', 'api.subscription'])->group(function () {

        Route::get('me', [AuthController::class, 'me']);

        // ── Mi empresa (administrativo, sin plan) ──────────────────
        Route::get('company-profile', [\App\Http\Controllers\Api\CompanyProfileController::class, 'show'])
            ->middleware('api.permission:company-profile.view');
        Route::post('company-profile', [\App\Http\Controllers\Api\CompanyProfileController::class, 'update'])
            ->middleware('api.permission:company-profile.edit');

        // ── Módulo Ventas / POS (plan: sales) ──────────────────────
        Route::middleware('api.plan:sales')->group(function () {

            Route::get('dashboard/sales', [DashboardController::class, 'sales'])
                ->middleware('api.permission:sales-dashboard.view');

            Route::get('products', [ProductController::class, 'index'])
                ->middleware('api.permission:products.view,pos.access,sales.view');
            Route::get('products/{product}', [ProductController::class, 'show'])
                ->middleware('api.permission:products.view,pos.access,sales.view');

            Route::get('clients', [ClientController::class, 'index'])
                ->middleware('api.permission:clients.view,pos.access,sales.view');
            Route::post('clients', [ClientController::class, 'store'])
                ->middleware('api.permission:clients.create');

            // Caja
            Route::get('cash/current-session', [CashSessionController::class, 'current'])
                ->middleware('api.permission:cash.operate,pos.access');
            Route::get('cash/registers', [CashSessionController::class, 'registers'])
                ->middleware('api.permission:cash.operate,pos.access');
            Route::post('cash/open', [CashSessionController::class, 'open'])
                ->middleware('api.permission:cash.operate');
            Route::post('cash/close', [CashSessionController::class, 'close'])
                ->middleware('api.permission:cash.operate');
            Route::get('cash/movements', [CashSessionController::class, 'movements'])
                ->middleware('api.permission:cash.operate,pos.access');
            Route::post('cash/expense', [CashSessionController::class, 'storeExpense'])
                ->middleware('api.permission:cash.operate');

            // Ventas
            Route::get('sales', [SaleController::class, 'index'])
                ->middleware('api.permission:sales.view,pos.access');
            Route::get('sales/summary', [SaleController::class, 'summary'])
                ->middleware('api.permission:sales.view,pos.access');
            Route::get('sales/{sale}', [SaleController::class, 'show'])
                ->middleware('api.permission:sales.view,pos.access');
            Route::post('sales', [SaleController::class, 'store'])
                ->middleware('api.permission:pos.access,sales.create');
        });

        // ── Módulo Taller (plan: workshop) ─────────────────────────
        Route::middleware('api.plan:workshop')->group(function () {
            Route::get('dashboard/workshop', [DashboardController::class, 'workshop'])
                ->middleware('api.permission:workshop-dashboard.view');
            // Catálogos de apoyo
            Route::get('mechanics', [WorkshopMetaController::class, 'mechanics'])
                ->middleware('api.permission:workshop.view,mechanics.view');
            // Gestión de mecánicos (listado completo + alta/edición)
            Route::get('mechanics/all', [MechanicController::class, 'index'])
                ->middleware('api.permission:mechanics.view');
            Route::post('mechanics', [MechanicController::class, 'store'])
                ->middleware('api.permission:mechanics.create');
            Route::put('mechanics/{mechanic}', [MechanicController::class, 'update'])
                ->middleware('api.permission:mechanics.edit');
            Route::get('vehicles', [WorkshopMetaController::class, 'vehicles'])
                ->middleware('api.permission:workshop.view,vehicles.view');

            Route::get('work-orders/summary', [WorkOrderController::class, 'todaySummary'])
                ->middleware('api.permission:workshop.view');
            Route::get('work-orders', [WorkOrderController::class, 'index'])
                ->middleware('api.permission:workshop.view');
            Route::get('work-orders/{order}', [WorkOrderController::class, 'show'])
                ->middleware('api.permission:workshop.view');
            Route::post('work-orders', [WorkOrderController::class, 'store'])
                ->middleware('api.permission:workshop.create');

            Route::post('work-orders/{order}/services', [WorkOrderController::class, 'addService'])
                ->middleware('api.permission:workshop.edit');
            Route::delete('work-orders/{order}/services/{service}', [WorkOrderController::class, 'removeService'])
                ->middleware('api.permission:workshop.edit');
            Route::post('work-orders/{order}/parts', [WorkOrderController::class, 'addPart'])
                ->middleware('api.permission:workshop.edit');
            Route::delete('work-orders/{order}/parts/{part}', [WorkOrderController::class, 'removePart'])
                ->middleware('api.permission:workshop.edit');
            Route::post('work-orders/{order}/diagnosis', [WorkOrderController::class, 'diagnosis'])
                ->middleware('api.permission:workshop.edit');
            Route::post('work-orders/{order}/mechanic', [WorkOrderController::class, 'assignMechanic'])
                ->middleware('api.permission:workshop.edit');
            Route::post('work-orders/{order}/status', [WorkOrderController::class, 'changeStatus'])
                ->middleware('api.permission:workshop.edit');
            Route::post('work-orders/{order}/deliver', [WorkOrderController::class, 'deliver'])
                ->middleware('api.permission:workshop.deliver');

            // Enlace público de seguimiento
            Route::get('work-orders/{order}/share', [WorkOrderController::class, 'share'])
                ->middleware('api.permission:workshop.view');

            // Fotos de la OT
            Route::get('work-orders/{order}/photos', [WorkOrderController::class, 'photos'])
                ->middleware('api.permission:workshop.view');
            Route::post('work-orders/{order}/photos', [WorkOrderController::class, 'addPhotos'])
                ->middleware('api.permission:workshop.edit');
            Route::delete('work-orders/{order}/photos/{photo}', [WorkOrderController::class, 'deletePhoto'])
                ->middleware('api.permission:workshop.edit');

            // Pago a mecánicos
            Route::get('mechanic-payments', [MechanicPaymentController::class, 'summary'])
                ->middleware('api.permission:mechanic-payments.view');
            Route::get('mechanic-payments/{mechanic}', [MechanicPaymentController::class, 'show'])
                ->middleware('api.permission:mechanic-payments.view');
            Route::post('mechanic-payments', [MechanicPaymentController::class, 'store'])
                ->middleware('api.permission:mechanic-payments.pay');

            // Agenda / Citas
            Route::get('appointments/meta', [AppointmentController::class, 'meta'])
                ->middleware('api.permission:appointments.view');
            Route::get('appointments/range', [AppointmentController::class, 'range'])
                ->middleware('api.permission:appointments.view');
            Route::get('appointments', [AppointmentController::class, 'index'])
                ->middleware('api.permission:appointments.view');
            Route::post('appointments', [AppointmentController::class, 'store'])
                ->middleware('api.permission:appointments.create');
            Route::put('appointments/{appointment}', [AppointmentController::class, 'update'])
                ->middleware('api.permission:appointments.edit');
            Route::post('appointments/{appointment}/status', [AppointmentController::class, 'changeStatus'])
                ->middleware('api.permission:appointments.edit');
            Route::post('appointments/{appointment}/convert', [AppointmentController::class, 'convertToWorkOrder'])
                ->middleware('api.permission:workshop.create');
            Route::delete('appointments/{appointment}', [AppointmentController::class, 'destroy'])
                ->middleware('api.permission:appointments.delete');
        });

        // ── Cajas: gestión y asignación a personal (plan: cash) ────
        Route::middleware('api.plan:cash')->group(function () {
            Route::get('cash-registers', [CashRegisterController::class, 'index'])
                ->middleware('api.permission:cash-registers.view');
            Route::get('cash-registers/form-data', [CashRegisterController::class, 'formData'])
                ->middleware('api.permission:cash-registers.create,cash-registers.edit');
            Route::post('cash-registers', [CashRegisterController::class, 'store'])
                ->middleware('api.permission:cash-registers.create');
            Route::put('cash-registers/{cashRegister}', [CashRegisterController::class, 'update'])
                ->middleware('api.permission:cash-registers.edit');
        });

        // ── Módulo Inventario (plan: inventory) ────────────────────
        Route::middleware('api.plan:inventory')->group(function () {
            Route::post('products/{product}/stock-adjust', [ProductController::class, 'adjustStock'])
                ->middleware('api.permission:products.edit');
            Route::get('product-form-data', [ProductController::class, 'formData'])
                ->middleware('api.permission:products.create');
            Route::post('products', [ProductController::class, 'store'])
                ->middleware('api.permission:products.create');
        });

        // ── Módulo Compras (plan: purchases) ───────────────────────
        Route::middleware('api.plan:purchases')->group(function () {
            Route::get('dashboard/purchases', [DashboardController::class, 'purchases'])
                ->middleware('api.permission:purchases-dashboard.view');
            Route::get('suppliers', [SupplierController::class, 'index'])
                ->middleware('api.permission:suppliers.view,purchase-orders.view');
            Route::post('suppliers', [SupplierController::class, 'store'])
                ->middleware('api.permission:suppliers.create');

            Route::get('purchase-orders', [PurchaseOrderController::class, 'index'])
                ->middleware('api.permission:purchase-orders.view,goods-receipts.view');
            Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
                ->middleware('api.permission:purchase-orders.create');
            Route::post('purchases/direct', [PurchaseOrderController::class, 'directPurchase'])
                ->middleware('api.permission:purchases.create');
            Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])
                ->middleware('api.permission:purchase-orders.view,goods-receipts.view');
            Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])
                ->middleware('api.permission:goods-receipts.create');

            // Tesorería (Finanzas): cuentas + ingresos/gastos
            Route::get('treasury/accounts', [TreasuryController::class, 'index'])
                ->middleware('api.permission:treasury.view');
            Route::post('treasury/accounts', [TreasuryController::class, 'storeAccount'])
                ->middleware('api.permission:treasury.manage');
            Route::get('treasury/accounts/{account}', [TreasuryController::class, 'show'])
                ->middleware('api.permission:treasury.view');
            Route::post('treasury/accounts/{account}/movements', [TreasuryController::class, 'storeMovement'])
                ->middleware('api.permission:treasury.manage');
        });
    });
});
