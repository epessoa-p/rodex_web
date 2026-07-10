<?php

namespace App\Http\Controllers\Sales\Concerns;

use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashRegisterSession;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sales\Sale;
use App\Models\Sales\SaleInstallment;
use App\Models\Sales\SaleItem;
use App\Models\Sales\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait HandlesSaleCreation
{
    use ResolvesCashSession;

    /**
     * Crea una venta completa (contado o crédito) dentro de una transacción:
     * descuenta stock, genera movimiento de inventario y, si aplica, registra
     * el ingreso en la caja abierta y/o el cronograma de cuotas.
     *
     * $data esperado:
     *   company_id, branch_id (nullable), client_id (nullable),
     *   sale_type ('cash'|'credit'), sale_date, discount, tax, notes,
     *   items: [ ['product_id','quantity','unit_price','discount'(opt)], ... ],
     *   installments: [ ['due_date','amount'], ... ]  (solo crédito)
     *   down_payment: float (opcional, solo crédito)
     */
    protected function confirmSale(array $data, ?CashRegisterSession $session): Sale
    {
        return DB::transaction(function () use ($data, $session) {
            $companyId = $data['company_id'];
            $items     = $data['items'];
            $saleType  = $data['sale_type'] ?? 'cash';

            // Resolver almacén de salida desde la sucursal
            $branch      = $data['branch_id'] ? Branch::find($data['branch_id']) : null;
            $warehouseId = $branch?->warehouse_id;

            if (!$warehouseId) {
                throw ValidationException::withMessages([
                    'branch_id' => 'La sucursal seleccionada no tiene un almacén asignado. No se puede descontar stock.',
                ]);
            }

            // 1. Resolver ítems (normales y de "venta rápida"), validar stock y calcular subtotal
            $subtotal = 0;
            $resolved = [];
            foreach ($items as $i) {
                $qty          = (float) $i['quantity'];
                $unitPrice    = (float) $i['unit_price'];
                $lineDiscount = (float) ($i['discount'] ?? 0);
                $isDirect     = !empty($i['direct']);
                $name         = isset($i['name']) ? trim((string) $i['name']) : null;

                if ($isDirect) {
                    // Venta rápida: enlazar a un producto existente por nombre (normalizado).
                    // Si coincide se descuenta stock aunque quede negativo (no se valida).
                    $product = $name
                        ? Product::where('company_id', $companyId)
                            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                            ->lockForUpdate()
                            ->first()
                        : null;
                } else {
                    $product = Product::lockForUpdate()->find($i['product_id']);

                    if (!$product) {
                        throw ValidationException::withMessages(['items' => 'Producto no encontrado.']);
                    }
                    if ((float) $product->current_stock < $qty) {
                        throw ValidationException::withMessages([
                            'items' => "Stock insuficiente de «{$product->name}» (disponible: {$product->current_stock}, solicitado: {$qty}).",
                        ]);
                    }
                }

                $subtotal += ($qty * $unitPrice) - $lineDiscount;

                $resolved[] = [
                    'product'    => $product,
                    'qty'        => $qty,
                    'unit_price' => $unitPrice,
                    'discount'   => $lineDiscount,
                    'name'       => $name,
                    'direct'     => $isDirect,
                ];
            }

            $discount = (float) ($data['discount'] ?? 0);
            $tax      = (float) ($data['tax'] ?? 0);
            $interest = (float) ($data['interest'] ?? 0);
            $total    = max(0, $subtotal - $discount + $tax + $interest);

            // Fecha/hora de la venta: si llega solo una fecha (sin hora) se le
            // adjunta la hora actual; si no llega nada, se usa el momento exacto.
            $saleDate = now();
            if (!empty($data['sale_date'])) {
                $parsed = \Illuminate\Support\Carbon::parse($data['sale_date']);
                if ($parsed->format('H:i:s') === '00:00:00') {
                    $parsed->setTimeFrom(now());
                }
                $saleDate = $parsed;
            }

            // 2. Crear la venta
            $sale = Sale::create([
                'company_id'               => $companyId,
                'branch_id'                => $data['branch_id'] ?? null,
                'client_id'                => $data['client_id'] ?? null,
                'cash_register_session_id' => $session?->id,
                'code'                     => $this->nextSaleCode($companyId, $data['branch_id'] ?? null),
                'sale_type'                => $saleType,
                'sale_category'            => $data['sale_category'] ?? 'producto',
                'payment_plan_id'          => $data['payment_plan_id'] ?? null,
                'credit_application_id'    => $data['credit_application_id'] ?? null,
                'sale_date'                => $saleDate,
                'subtotal'                 => $subtotal,
                'discount'                 => $discount,
                'tax'                      => $tax,
                'interest'                 => $interest,
                'total'                    => $total,
                'paid_amount'              => 0,
                'payment_status'           => 'pending',
                'status'                   => 'completed',
                'notes'                    => $data['notes'] ?? null,
                'created_by'               => auth()->id(),
            ]);

            // 3. Items + salida de inventario + descuento de stock
            foreach ($resolved as $r) {
                $product = $r['product'];

                SaleItem::create([
                    'sale_id'     => $sale->id,
                    'product_id'  => $product?->id,
                    'description' => $r['direct'] ? $r['name'] : null,
                    'quantity'    => $r['qty'],
                    'unit_price'  => $r['unit_price'],
                    'discount'    => $r['discount'],
                    'subtotal'    => ($r['qty'] * $r['unit_price']) - $r['discount'],
                ]);

                // Solo afecta inventario si hay producto enlazado (los ítems de venta
                // rápida sin coincidencia no tocan stock).
                if ($product) {
                    InventoryMovement::create([
                        'company_id'    => $companyId,
                        'warehouse_id'  => $warehouseId,
                        'branch_id'     => $data['branch_id'] ?? null,
                        'product_id'    => $product->id,
                        'user_id'       => auth()->id(),
                        'type'          => 'out',
                        'quantity'      => $r['qty'],
                        'unit_cost'     => $r['unit_price'],
                        'reference'     => $sale->code,
                        'notes'         => 'Venta ' . $sale->code,
                        'movement_date' => $sale->sale_date,
                    ]);

                    Product::where('id', $product->id)->decrement('current_stock', $r['qty']);
                }
            }

            // 4. Cobro / cuotas
            if ($saleType === 'cash') {
                // Venta al contado: cobro total inmediato a la caja
                $this->registerSalePayment($sale, null, $total, $session, [
                    'method'    => $data['method'] ?? 'efectivo',
                    'reference' => null,
                    'notes'     => 'Pago de contado',
                ]);
            } else {
                // Crédito: generar cronograma de cuotas
                $installments = $data['installments'] ?? [];
                $number = 1;
                foreach ($installments as $inst) {
                    SaleInstallment::create([
                        'company_id'  => $companyId,
                        'sale_id'     => $sale->id,
                        'number'      => $number++,
                        'due_date'    => $inst['due_date'],
                        'amount'      => (float) $inst['amount'],
                        'paid_amount' => 0,
                        'status'      => 'pending',
                    ]);
                }

                // Pago inicial / enganche (opcional):
                // se registra como pago independiente, NO se aplica a las cuotas
                // (las cuotas ya representan el saldo después del enganche).
                $downPayment = (float) ($data['down_payment'] ?? 0);
                if ($downPayment > 0) {
                    $this->registerSalePayment($sale, null, $downPayment, $session, [
                        'method'    => $data['method'] ?? 'efectivo',
                        'reference' => null,
                        'notes'     => 'Pago inicial',
                    ]);
                }
            }

            $sale->refresh()->recalcPaymentStatus();

            // Fidelización: acreditar puntos por la compra (idempotente; ignora "Cliente general")
            app(\App\Services\Loyalty\LoyaltyService::class)->award($sale);

            return $sale;
        });
    }

    /**
     * Registra un pago directo (contado) o un abono genérico contra la venta,
     * creando el SalePayment y el CashMovement en la caja (si hay sesión).
     */
    protected function registerSalePayment(Sale $sale, ?SaleInstallment $installment, float $amount, ?CashRegisterSession $session, array $meta = []): SalePayment
    {
        $payment = SalePayment::create([
            'company_id'               => $sale->company_id,
            'sale_id'                  => $sale->id,
            'sale_installment_id'      => $installment?->id,
            'cash_register_session_id' => $session?->id,
            'amount'                   => $amount,
            'payment_date'             => $meta['payment_date'] ?? now()->toDateString(),
            'method'                   => $meta['method'] ?? 'efectivo',
            'reference'                => $meta['reference'] ?? null,
            'notes'                    => $meta['notes'] ?? null,
            'user_id'                  => auth()->id(),
        ]);

        // Ingreso a la caja abierta
        if ($session) {
            CashMovement::create([
                'company_id'               => $sale->company_id,
                'cash_register_id'         => $session->cash_register_id,
                'cash_register_session_id' => $session->id,
                'user_id'                  => auth()->id(),
                'type'                     => 'income',
                'category'                 => 'sale',
                'amount'                   => $amount,
                'method'                   => $meta['method'] ?? 'efectivo',
                'reference_type'           => Sale::class,
                'reference_id'             => $sale->id,
                'description'              => 'Venta ' . $sale->code . ($sale->client ? ' — ' . $sale->client->full_name : ''),
                'movement_date'            => now(),
            ]);
        }

        // Actualizar montos
        $sale->increment('paid_amount', $amount);
        if ($installment) {
            $installment->increment('paid_amount', $amount);
            $installment->refresh()->recalcStatus();
        }

        return $payment;
    }

    /**
     * Aplica un abono de crédito distribuyéndolo entre las cuotas pendientes
     * (de la más antigua a la más nueva).
     */
    protected function applyCreditPayment(Sale $sale, float $amount, ?CashRegisterSession $session, array $meta = []): void
    {
        $remaining = $amount;

        $pending = $sale->installments()
            ->whereIn('status', ['pending', 'partial'])
            ->orderBy('number')
            ->get();

        foreach ($pending as $installment) {
            if ($remaining <= 0) {
                break;
            }
            $balance = (float) $installment->amount - (float) $installment->paid_amount;
            $apply   = min($remaining, $balance);

            if ($apply > 0) {
                $this->registerSalePayment($sale, $installment, $apply, $session, $meta);
                $remaining -= $apply;
            }
        }

        // Si sobra (pago mayor al saldo de cuotas), registrar como pago sin cuota
        if ($remaining > 0.001) {
            $this->registerSalePayment($sale, null, $remaining, $session, $meta);
        }
    }

    protected function nextSaleCode(int $companyId, ?int $branchId = null): string
    {
        // Correlativo independiente por sucursal
        $count = Sale::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->count() + 1;
        return 'VEN-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}

