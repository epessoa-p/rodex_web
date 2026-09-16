<?php

namespace App\Services\Admin;

use App\Models\Branch;
use App\Models\Scopes\CompanyScope;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Regla: cada sucursal tiene SU almacén, creado por el sistema junto con ella.
 *
 * Por qué: ventas del POS, recepción/entrega de OTs y compras descuentan o
 * ingresan stock en "el almacén de la sucursal" (personal.branch_id →
 * branch.warehouse_id). Si una sucursal nace sin almacén, o apunta a uno
 * compartido/equivocado, el stock termina en el lugar incorrecto y la
 * operación falla o se descuadra. Con el almacén automático:
 *  - no hay pasos previos ("crea primero un almacén") ni sucursales huérfanas;
 *  - el nombre/dirección del almacén siempre reflejan la sucursal;
 *  - el almacén de sucursal no se edita ni borra suelto desde Almacenes.
 */
class BranchWarehouseService
{
    /** Crea la sucursal y su almacén (nombre y ubicación tomados de la sucursal). */
    public function create(int $companyId, array $attrs): Branch
    {
        return DB::transaction(function () use ($companyId, $attrs) {
            $warehouse = Warehouse::create([
                'company_id'  => $companyId,
                'name'        => $attrs['name'],
                'code'        => self::generateCode($companyId),
                'location'    => $attrs['address'] ?? null,
                'description' => 'Almacén de la sucursal ' . $attrs['name'],
                'active'      => true,
            ]);

            return Branch::create([
                ...$attrs,
                'company_id'   => $companyId,
                'warehouse_id' => $warehouse->id,
            ]);
        });
    }

    /**
     * Tras editar la sucursal: su almacén copia nombre, dirección y estado.
     * Solo si el almacén es exclusivo de esta sucursal (datos antiguos pueden
     * compartir uno entre varias; ahí no se toca). Si la sucursal quedó sin
     * almacén (datos antiguos), se le crea uno.
     */
    public function sync(Branch $branch): void
    {
        $warehouse = $branch->warehouse_id
            ? Warehouse::withoutGlobalScope(CompanyScope::class)->find($branch->warehouse_id)
            : null;

        if (! $warehouse) {
            $warehouse = Warehouse::create([
                'company_id'  => $branch->company_id,
                'name'        => $branch->name,
                'code'        => self::generateCode($branch->company_id),
                'location'    => $branch->address,
                'description' => 'Almacén de la sucursal ' . $branch->name,
                'active'      => (bool) $branch->active,
            ]);
            $branch->forceFill(['warehouse_id' => $warehouse->id])->save();

            return;
        }

        if (! self::isExclusive($warehouse)) {
            return;
        }

        $warehouse->update([
            'name'     => $branch->name,
            'location' => $branch->address,
            'active'   => (bool) $branch->active,
        ]);
    }

    /** ¿Es el almacén de una sucursal? (no se edita ni borra desde Almacenes). */
    public static function belongsToBranch(Warehouse $warehouse): bool
    {
        return Branch::withoutGlobalScope(CompanyScope::class)->where('warehouse_id', $warehouse->id)->exists();
    }

    /** ¿Lo usa exactamente una sucursal? (entonces se sincroniza con ella). */
    public static function isExclusive(Warehouse $warehouse): bool
    {
        return Branch::withoutGlobalScope(CompanyScope::class)->where('warehouse_id', $warehouse->id)->count() === 1;
    }

    /** Código de almacén único POR EMPRESA: ALM-001, ALM-002, … */
    public static function generateCode(int $companyId): string
    {
        $seq = Warehouse::withoutGlobalScopes()->where('company_id', $companyId)->count() + 1;

        do {
            $code = 'ALM-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $seq++;
        } while (Warehouse::withoutGlobalScopes()->where('company_id', $companyId)->where('code', $code)->exists());

        return $code;
    }
}
