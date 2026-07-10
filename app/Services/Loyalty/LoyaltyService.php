<?php

namespace App\Services\Loyalty;

use App\Models\Branch;
use App\Models\Client;
use App\Models\InventoryMovement;
use App\Models\Loyalty\LoyaltyCampaign;
use App\Models\Loyalty\LoyaltyPointMovement;
use App\Models\Loyalty\LoyaltyRedemption;
use App\Models\Loyalty\LoyaltyReward;
use App\Models\Loyalty\LoyaltySetting;
use App\Models\Product;
use App\Models\Sales\Sale;
use App\Models\Workshop\WorkOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoyaltyService
{
    /** Puntos otorgados por un monto, según las reglas de la empresa (por bloques). */
    public function pointsForAmount(float $amount, LoyaltySetting $s): int
    {
        $earnAmount = (float) $s->earn_amount;
        if ($earnAmount <= 0 || $s->earn_points <= 0 || $amount <= 0) {
            return 0;
        }
        $blocks = $amount / $earnAmount;
        $blocks = match ($s->rounding) {
            'up'      => ceil($blocks),
            'nearest' => round($blocks),
            default   => floor($blocks),
        };
        return (int) ($blocks * $s->earn_points);
    }

    /** Multiplicador de la campaña activa para una fecha (1.0 si no hay). */
    public function activeCampaignMultiplier(int $companyId, $date = null): float
    {
        $d = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();
        $max = LoyaltyCampaign::where('company_id', $companyId)
            ->where('active', true)
            ->whereDate('starts_at', '<=', $d)
            ->whereDate('ends_at', '>=', $d)
            ->max('multiplier');

        return $max ? (float) $max : 1.0;
    }

    /** Acredita puntos por una venta (idempotente). */
    public function award(Sale $sale): void
    {
        $this->awardForSource($sale->company_id, $sale->client_id, (float) $sale->total, $sale, $sale->code, 'Compra', $sale->sale_date);
    }

    /** Acredita puntos por una orden de taller (idempotente). */
    public function awardWorkOrder(WorkOrder $wo): void
    {
        $this->awardForSource($wo->company_id, $wo->client_id, (float) $wo->total, $wo, $wo->code, 'Taller', $wo->delivered_at ?? now());
    }

    /**
     * Acredita puntos por una fuente cualquiera (venta, orden de taller, etc.).
     * Aplica multiplicador de campaña y fecha de expiración. Idempotente por fuente.
     */
    public function awardForSource(int $companyId, ?int $clientId, float $total, Model $source, string $code, ?string $label = null, $date = null): void
    {
        if (!$clientId) {
            return;
        }

        $settings = LoyaltySetting::where('company_id', $companyId)->first();
        if (!$settings || !$settings->enabled) {
            return;
        }
        if ($total < (float) $settings->min_purchase) {
            return;
        }

        // Idempotencia: no acreditar dos veces la misma fuente
        $already = LoyaltyPointMovement::where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->where('type', 'earn')
            ->exists();
        if ($already) {
            return;
        }

        $base       = $this->pointsForAmount($total, $settings);
        $multiplier = $this->activeCampaignMultiplier($companyId, $date);
        $points     = (int) round($base * $multiplier);
        if ($points <= 0) {
            return;
        }

        $expiresAt = $settings->expiration_months
            ? now()->addMonths((int) $settings->expiration_months)->endOfDay()
            : null;

        $desc = trim(($label ? $label . ' ' : '') . $code);
        if ($multiplier > 1) {
            $desc .= ' (campaña x' . rtrim(rtrim(number_format($multiplier, 2), '0'), '.') . ')';
        }

        DB::transaction(function () use ($companyId, $clientId, $points, $source, $desc, $expiresAt) {
            LoyaltyPointMovement::create([
                'company_id'       => $companyId,
                'client_id'        => $clientId,
                'type'             => 'earn',
                'points'           => $points,
                'points_remaining' => $points,
                'expires_at'       => $expiresAt,
                'source_type'      => $source->getMorphClass(),
                'source_id'        => $source->getKey(),
                'description'      => $desc,
                'user_id'          => auth()->id(),
            ]);
            Client::where('id', $clientId)->increment('points_balance', $points);
        });
    }

    /** Revierte los puntos acreditados por una venta (al anularla). */
    public function reverse(Sale $sale): void
    {
        $this->reverseForSource($sale->getMorphClass(), $sale->id, $sale->company_id, $sale->client_id, $sale->code);
    }

    /** Revierte los puntos acreditados por una orden de taller. */
    public function reverseWorkOrder(WorkOrder $wo): void
    {
        $this->reverseForSource($wo->getMorphClass(), $wo->id, $wo->company_id, $wo->client_id, $wo->code);
    }

    /**
     * Revierte el saldo aún disponible de los lotes acreditados por una fuente.
     * Solo descuenta la parte NO gastada de esos lotes (lo ya canjeado no se reclama).
     */
    public function reverseForSource(string $sourceType, int $sourceId, int $companyId, ?int $clientId, string $code): void
    {
        if (!$clientId) {
            return;
        }

        $lots = LoyaltyPointMovement::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('type', 'earn')
            ->get();

        $remaining = (int) $lots->sum(fn ($l) => (int) ($l->points_remaining ?? 0));
        if ($remaining <= 0) {
            return;
        }

        DB::transaction(function () use ($lots, $companyId, $clientId, $remaining, $code) {
            foreach ($lots as $lot) {
                if ((int) $lot->points_remaining > 0) {
                    $lot->update(['points_remaining' => 0]);
                }
            }
            LoyaltyPointMovement::create([
                'company_id'  => $companyId,
                'client_id'   => $clientId,
                'type'        => 'adjust',
                'points'      => -$remaining,
                'description' => 'Reverso por anulación ' . $code,
                'user_id'     => auth()->id(),
            ]);
            Client::where('id', $clientId)->decrement('points_balance', $remaining);
        });
    }

    /**
     * Canjea una recompensa: valida saldo/stock, descuenta puntos (consumiendo
     * lotes FIFO), registra el canje y descuenta inventario si aplica.
     *
     * @throws ValidationException
     */
    public function redeem(Client $client, LoyaltyReward $reward, ?int $branchId = null, ?Sale $sale = null): LoyaltyRedemption
    {
        if ((int) $client->points_balance < (int) $reward->points_cost) {
            throw ValidationException::withMessages([
                'reward' => "Saldo insuficiente: el cliente tiene {$client->points_balance} y la recompensa cuesta {$reward->points_cost} puntos.",
            ]);
        }
        if ($reward->stock !== null && $reward->stock <= 0) {
            throw ValidationException::withMessages(['reward' => 'La recompensa no tiene stock disponible.']);
        }

        return DB::transaction(function () use ($client, $reward, $branchId, $sale) {
            $redemption = LoyaltyRedemption::create([
                'company_id'   => $client->company_id,
                'client_id'    => $client->id,
                'reward_id'    => $reward->id,
                'points_spent' => $reward->points_cost,
                'sale_id'      => $sale?->id,
                'status'       => 'completed',
                'user_id'      => auth()->id(),
                'redeemed_at'  => now(),
            ]);

            LoyaltyPointMovement::create([
                'company_id'  => $client->company_id,
                'client_id'   => $client->id,
                'type'        => 'redeem',
                'points'      => -1 * (int) $reward->points_cost,
                'source_type' => $redemption->getMorphClass(),
                'source_id'   => $redemption->id,
                'description' => 'Canje: ' . $reward->name,
                'user_id'     => auth()->id(),
            ]);

            $client->decrement('points_balance', (int) $reward->points_cost);
            $this->consumeLots($client->id, (int) $reward->points_cost);

            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }
            if ($reward->product_id) {
                $this->dischargeProductStock($reward, $branchId, $redemption);
            }

            return $redemption;
        });
    }

    /**
     * Expira los lotes vencidos con saldo disponible. Devuelve el total de
     * puntos expirados. Pensado para ejecutarse desde un comando programado.
     */
    public function expirePoints(): int
    {
        $lots = LoyaltyPointMovement::where('type', 'earn')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('points_remaining', '>', 0)
            ->get();

        $totalExpired = 0;
        foreach ($lots as $lot) {
            $rem = (int) $lot->points_remaining;
            DB::transaction(function () use ($lot, $rem) {
                LoyaltyPointMovement::create([
                    'company_id'  => $lot->company_id,
                    'client_id'   => $lot->client_id,
                    'type'        => 'expire',
                    'points'      => -$rem,
                    'description' => 'Vencimiento de puntos',
                    'user_id'     => null,
                ]);
                Client::where('id', $lot->client_id)->decrement('points_balance', $rem);
                $lot->update(['points_remaining' => 0]);
            });
            $totalExpired += $rem;
        }

        return $totalExpired;
    }

    /** Consume puntos de los lotes 'earn' del cliente, del más antiguo al más nuevo (FIFO). */
    private function consumeLots(int $clientId, int $points): void
    {
        $remaining = $points;
        $lots = LoyaltyPointMovement::where('client_id', $clientId)
            ->where('type', 'earn')
            ->where('points_remaining', '>', 0)
            ->orderBy('created_at')->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }
            $take = min($remaining, (int) $lot->points_remaining);
            $lot->decrement('points_remaining', $take);
            $remaining -= $take;
        }
    }

    /** Descuenta inventario del producto enlazado a la recompensa. */
    private function dischargeProductStock(LoyaltyReward $reward, ?int $branchId, LoyaltyRedemption $redemption): void
    {
        $warehouseId = null;
        if ($branchId) {
            $warehouseId = Branch::find($branchId)?->warehouse_id;
        }
        if (!$warehouseId) {
            $warehouseId = Branch::where('company_id', $reward->company_id)
                ->whereNotNull('warehouse_id')->value('warehouse_id');
        }

        if ($warehouseId) {
            InventoryMovement::create([
                'company_id'    => $reward->company_id,
                'warehouse_id'  => $warehouseId,
                'branch_id'     => $branchId,
                'product_id'    => $reward->product_id,
                'user_id'       => auth()->id(),
                'type'          => 'out',
                'quantity'      => 1,
                'unit_cost'     => 0,
                'reference'     => 'CANJE-' . $redemption->id,
                'notes'         => 'Canje de recompensa: ' . $reward->name,
                'movement_date' => now()->toDateString(),
            ]);
        }

        Product::where('id', $reward->product_id)->decrement('current_stock', 1);
    }
}
