<?php

namespace App\Services\Workshop;

use App\Http\Controllers\Workshop\Concerns\HandlesWorkOrderCharge;
use App\Models\CashRegisterSession;
use App\Models\Client;
use App\Models\Personal;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Workshop\Mechanic;
use App\Models\Workshop\Service;
use App\Models\Workshop\WorkOrder;
use App\Models\Workshop\WorkOrderPart;
use App\Models\Workshop\WorkOrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * "Servicio rápido": crea una OT con sus servicios y la deja entregada y
 * cobrada en un solo paso (cliente y vehículo opcionales). Compartido por la
 * web y la API. Reutiliza HandlesWorkOrderCharge::deliverWorkOrder, así el
 * cobro a caja, la comisión del mecánico y la fidelización son los de siempre.
 */
class QuickServiceService
{
    use HandlesWorkOrderCharge;

    public const METHODS = ['efectivo', 'transferencia', 'tarjeta', 'qr'];

    /** Reglas de validación (mismas para web y API). */
    public static function rules(int $companyId): array
    {
        return [
            // Servicios y/o repuestos: al menos una línea entre los dos.
            'services'                => ['nullable', 'array'],
            'services.*.service_id'   => ['nullable', 'integer'],
            'services.*.description'  => ['nullable', 'string', 'max:255'],
            'services.*.price'        => ['nullable', 'numeric', 'min:0'],
            'services.*.quantity'     => ['nullable', 'integer', 'min:1'],
            'parts'                   => ['nullable', 'array'],
            'parts.*.product_id'      => ['required_with:parts', 'integer'],
            'parts.*.quantity'        => ['nullable', 'integer', 'min:1'],
            'parts.*.unit_price'      => ['nullable', 'numeric', 'min:0'],
            'mechanic_id'             => ['nullable', 'integer'],
            'client_id'               => ['nullable', 'integer'],
            'vehicle_id'              => ['nullable', 'integer'],
            'quick_vehicle'           => ['nullable', 'string', 'max:80'],
            'method'                  => ['nullable', 'in:' . implode(',', self::METHODS)],
            'discount'                => ['nullable', 'numeric', 'min:0'],
            'notes'                   => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Crea, entrega y cobra. Lanza ValidationException con mensajes de negocio
     * (sin caja abierta, servicio sin nombre, vehículo de otro cliente…).
     */
    public function run(int $companyId, int $userId, array $data, ?CashRegisterSession $session): WorkOrder
    {
        if (! $session) {
            throw ValidationException::withMessages([
                'cash' => 'Necesitas tu caja abierta para cobrar el servicio rápido.',
            ]);
        }

        // Cliente / vehículo opcionales, pero coherentes entre sí y de la empresa.
        $clientId = ! empty($data['client_id'])
            ? Client::where('company_id', $companyId)->whereKey($data['client_id'])->value('id')
            : null;
        $vehicleId = null;
        if (! empty($data['vehicle_id'])) {
            $vehicle = Vehicle::where('company_id', $companyId)->find($data['vehicle_id']);
            if ($vehicle) {
                $vehicleId = $vehicle->id;
                $clientId  = $clientId ?: $vehicle->client_id;
            }
        }
        $mechanicId = ! empty($data['mechanic_id'])
            ? Mechanic::where('company_id', $companyId)->whereKey($data['mechanic_id'])->value('id')
            : null;

        $lines = $this->resolveLines($companyId, $data['services'] ?? []);
        $parts = $this->resolveParts($companyId, $data['parts'] ?? []);
        if (empty($lines) && empty($parts)) {
            throw ValidationException::withMessages([
                'services' => 'Agrega al menos un servicio o un repuesto.',
            ]);
        }
        $branchId = Personal::where('user_id', $userId)->value('branch_id')
            ?? $session->cashRegister?->branch_id;

        return DB::transaction(function () use ($companyId, $userId, $data, $session, $clientId, $vehicleId, $mechanicId, $lines, $parts, $branchId) {
            $order = WorkOrder::create([
                'company_id'     => $companyId,
                'branch_id'      => $branchId,
                'client_id'      => $clientId,
                'vehicle_id'     => $vehicleId,
                'quick_vehicle'  => $vehicleId ? null : (trim((string) ($data['quick_vehicle'] ?? '')) ?: null),
                'mechanic_id'    => $mechanicId,
                'is_quick'       => true,
                'reception_date' => now()->toDateTimeString(),
                'reported_issue' => 'Servicio rápido',
                'notes'          => $data['notes'] ?? null,
                'code'           => $this->nextQuickCode($companyId, $branchId),
                'status'         => 'recibida',
                'payment_status' => 'pendiente',
                'created_by'     => $userId,
            ]);

            foreach ($lines as $l) {
                WorkOrderService::create([
                    'work_order_id' => $order->id,
                    'service_id'    => $l['service_id'],
                    'mechanic_id'   => $mechanicId,
                    'description'   => $l['description'],
                    'price'         => $l['price'],
                    'quantity'      => $l['quantity'],
                    'subtotal'      => $l['price'] * $l['quantity'],
                ]);
            }
            foreach ($parts as $p) {
                WorkOrderPart::create([
                    'work_order_id' => $order->id,
                    'product_id'    => $p['product_id'],
                    'quantity'      => $p['quantity'],
                    'unit_price'    => $p['unit_price'],
                    'subtotal'      => $p['unit_price'] * $p['quantity'],
                ]);
            }
            $order->recalcTotals();

            // deliverWorkOrder descuenta el stock de los repuestos y registra el kardex.
            $this->deliverWorkOrder($order->fresh(), [
                'payment_type'   => 'contado',
                'discount'       => (float) ($data['discount'] ?? 0),
                'tax'            => 0,
                'delivered_to'   => $order->fresh()->client_display,
                'delivery_notes' => 'Servicio rápido',
                'method'         => $data['method'] ?? 'efectivo',
                'installments'   => [],
                'down_payment'   => 0,
            ], $session);

            return $order->fresh();
        });
    }

    /**
     * Normaliza las líneas: con service_id toma nombre (y precio si no viene)
     * del catálogo; con solo descripción crea/reutiliza el servicio por nombre.
     */
    private function resolveLines(int $companyId, array $services): array
    {
        $out = [];
        foreach ($services as $i => $row) {
            $service = null;
            if (! empty($row['service_id'])) {
                $service = Service::where('company_id', $companyId)->find($row['service_id']);
            }
            $name = trim((string) ($row['description'] ?? $service?->name ?? ''));
            if ($name === '') {
                throw ValidationException::withMessages([
                    "services.$i" => 'Cada servicio necesita un nombre.',
                ]);
            }
            if (! $service) {
                $service = Service::where('company_id', $companyId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->first()
                    ?? Service::create([
                        'company_id' => $companyId,
                        'name'       => $name,
                        'price'      => (float) ($row['price'] ?? 0),
                        'active'     => true,
                    ]);
            }
            $price = isset($row['price']) && $row['price'] !== '' && $row['price'] !== null
                ? (float) $row['price']
                : (float) $service->price;

            $out[] = [
                'service_id'  => $service->id,
                'description' => $name,
                'price'       => $price,
                'quantity'    => max(1, (int) ($row['quantity'] ?? 1)),
            ];
        }

        return $out;
    }

    /**
     * Normaliza los repuestos: valida que el producto sea de la empresa y toma
     * su precio de venta cuando no se envía uno.
     */
    private function resolveParts(int $companyId, array $parts): array
    {
        $out = [];
        foreach ($parts as $i => $row) {
            $product = Product::where('company_id', $companyId)->find($row['product_id'] ?? null);
            if (! $product) {
                throw ValidationException::withMessages([
                    "parts.$i" => 'Uno de los repuestos ya no existe.',
                ]);
            }
            $price = isset($row['unit_price']) && $row['unit_price'] !== '' && $row['unit_price'] !== null
                ? (float) $row['unit_price']
                : (float) $product->price;

            $out[] = [
                'product_id' => $product->id,
                'quantity'   => max(1, (int) ($row['quantity'] ?? 1)),
                'unit_price' => $price,
            ];
        }

        return $out;
    }

    /** Correlativo por empresa/sucursal (misma serie OT-#####). */
    private function nextQuickCode(int $companyId, ?int $branchId): string
    {
        $count = WorkOrder::withTrashed()
            ->where('company_id', $companyId)
            ->where('branch_id', $branchId)
            ->count() + 1;

        return 'OT-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
