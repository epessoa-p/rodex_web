<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SystemResetController extends Controller
{
    /**
     * Tablas OPERATIVAS que se vacían al reiniciar.
     * Se CONSERVAN: usuarios, roles, permisos, cargos, personal, promotores,
     * mecánicos, sucursales, cajas, almacenes, empresas y los catálogos
     * (categorías, marcas, modelos, proveedores, servicios, planes, plantillas,
     * cuentas de tesorería — a estas se les pone el saldo en 0).
     */
    private array $wipeTables = [
        // Clientes
        'client_documents', 'clients',
        // Ventas
        'sale_return_items', 'sale_returns',
        'sale_payments', 'sale_installments', 'sale_items', 'sale_details', 'sales',
        'commissions',
        // Cotizaciones / crédito
        'quote_items', 'quotes', 'credit_applications',
        // Compras
        'supplier_payments', 'goods_receipt_items', 'goods_receipts',
        'purchase_order_items', 'purchase_orders', 'purchase_items', 'purchases',
        'treasury_movements',
        // Inventario
        'product_photos', 'moto_model_product', 'inventory_movements', 'products',
        // Motos (unidades) y garantías
        'warranties', 'moto_units',
        // Taller
        'work_order_payments', 'work_order_installments', 'work_order_parts',
        'work_order_services', 'work_orders',
        // Alquileres
        'rental_payments', 'rental_penalties', 'rental_installments',
        'rental_inspection_photos', 'rental_inspections', 'rental_contracts',
        // Caja
        'cash_movements', 'cash_register_sessions',
        'petty_cash_movements', 'petty_cashes',
        // Otros operativos
        'vehicles', 'trackings',
    ];

    public function index()
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        // Conteos informativos de lo que se borrará
        $countDefs = [
            'clients'         => 'Clientes',
            'sales'           => 'Ventas',
            'quotes'          => 'Cotizaciones',
            'purchases'       => 'Compras',
            'products'        => 'Productos',
            'inventory_movements' => 'Movimientos de inventario',
            'work_orders'     => 'Órdenes de taller',
            'rental_contracts'=> 'Contratos de alquiler',
            'moto_units'      => 'Unidades de moto',
            'cash_movements'  => 'Movimientos de caja',
        ];
        $counts = [];
        foreach ($countDefs as $tbl => $label) {
            if (Schema::hasTable($tbl)) {
                $counts[$label] = DB::table($tbl)->count();
            }
        }

        return view('admin.system.reset', compact('counts'));
    }

    public function run(Request $request)
    {
        abort_unless(auth()->user()->is_super_admin, 403);

        $request->validate(['confirmation' => 'required|string']);
        if (mb_strtoupper(trim($request->input('confirmation'))) !== 'REINICIAR') {
            return back()->withErrors(['confirmation' => 'Debes escribir REINICIAR (en mayúsculas) para confirmar.']);
        }

        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            foreach ($this->wipeTables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            // Conservamos las cuentas de tesorería pero ponemos su saldo en 0
            if (Schema::hasTable('treasury_accounts') && Schema::hasColumn('treasury_accounts', 'balance')) {
                DB::table('treasury_accounts')->update(['balance' => 0]);
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Throwable $e) {
            try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $ignore) {}
            Log::error('Error al reiniciar la base de datos', ['msg' => $e->getMessage()]);
            return back()->withErrors(['error' => 'No se pudo reiniciar la base de datos: ' . $e->getMessage()]);
        }

        Log::warning('Base de datos reiniciada por super admin', ['user_id' => auth()->id()]);

        return redirect()->route('system.reset')
            ->with('success', 'Base de datos reiniciada. Se borraron los datos operativos y se conservaron usuarios, roles, cargos, personal, sucursales, cajas, almacenes y catálogos.');
    }
}
