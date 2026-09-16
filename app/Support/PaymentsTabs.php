<?php

namespace App\Support;

use App\Models\User;

/**
 * Tabs del hub "Pagos" (Finanzas): Mecánicos · Proveedores · Personal · Gastos.
 * Cada uno se muestra si el plan tiene el módulo y el usuario el permiso —
 * mismo criterio que el hub del móvil (`canSeePayments`).
 */
class PaymentsTabs
{
    /** @return array<int, array{key:string,label:string,icon:string,route:string,pattern:string}> */
    public static function visible(User $user): array
    {
        $company = $user->getCurrentCompany();
        if (! $company) {
            return [];
        }

        $can = fn (string $module, array $perms) => $company->planAllows($module)
            && ($user->is_super_admin || collect($perms)->contains(fn ($p) => $user->hasPermissionInCompany($p, $company)));

        $tabs = [];

        if ($can('workshop', ['mechanic-payments.view'])) {
            $tabs[] = ['key' => 'mechanics', 'label' => 'Mecánicos', 'icon' => 'bi-person-gear',
                       'route' => 'workshop.mechanic-payments.index', 'pattern' => 'workshop.mechanic-payments.*'];
        }
        if ($can('purchases', ['accounts-payable.view'])) {
            $tabs[] = ['key' => 'suppliers', 'label' => 'Proveedores', 'icon' => 'bi-truck',
                       'route' => 'accounts-payable.index', 'pattern' => 'accounts-payable.*'];
        }
        if ($can('cash', ['cash.operate', 'expense-services.view'])) {
            $tabs[] = ['key' => 'personal', 'label' => 'Personal', 'icon' => 'bi-people',
                       'route' => 'payments.personal', 'pattern' => 'payments.personal'];
            $tabs[] = ['key' => 'expenses', 'label' => 'Gastos', 'icon' => 'bi-wallet2',
                       'route' => 'payments.expenses', 'pattern' => 'payments.expenses'];
        }

        return $tabs;
    }

    public static function first(User $user): ?array
    {
        return self::visible($user)[0] ?? null;
    }

    public static function any(User $user): bool
    {
        return self::visible($user) !== [];
    }
}
