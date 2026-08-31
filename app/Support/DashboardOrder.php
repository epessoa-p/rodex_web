<?php

namespace App\Support;

/**
 * Normaliza el orden de los tabs del dashboard (CSV de módulos válidos).
 */
class DashboardOrder
{
    public const MODULES = ['ventas', 'taller', 'compras'];
    public const DEFAULT = 'ventas,taller,compras';

    /**
     * Devuelve un CSV con los 3 módulos: los indicados (en su orden, sin
     * repetir ni inválidos) primero, y los faltantes al final en orden por defecto.
     */
    public static function sanitize(?string $csv): string
    {
        $order = [];
        foreach (explode(',', (string) $csv) as $m) {
            $m = trim($m);
            if (in_array($m, self::MODULES, true) && ! in_array($m, $order, true)) {
                $order[] = $m;
            }
        }
        foreach (self::MODULES as $m) {
            if (! in_array($m, $order, true)) {
                $order[] = $m;
            }
        }

        return implode(',', $order);
    }
}
