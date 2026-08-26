<?php

use App\Models\Company;

if (! function_exists('currency_symbol')) {
    /**
     * Símbolo de moneda a usar. Resuelve por prioridad:
     *  - una Company explícita (o su código de moneda como string), útil en PDFs
     *    y vistas públicas sin sesión;
     *  - la empresa activa del usuario autenticado;
     *  - el valor por defecto de config (respaldo).
     */
    function currency_symbol($context = null): string
    {
        if ($context instanceof Company) {
            return $context->currency ?: config('inventory.currency', 'Bs');
        }
        if (is_string($context) && $context !== '') {
            return $context;
        }

        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $company = auth()->check() ? auth()->user()->getCurrentCompany() : null;

        return $cached = ($company?->currency ?: config('inventory.currency', 'Bs'));
    }
}

if (! function_exists('money')) {
    /**
     * Formatea un monto con el símbolo de moneda: "Bs 1,234.50".
     *
     * @param  mixed  $amount
     * @param  Company|string|null  $currency  Company o símbolo explícito; si es
     *         null, usa la moneda de la empresa activa.
     */
    function money($amount, $currency = null, int $decimals = 2): string
    {
        return currency_symbol($currency) . ' ' . number_format((float) $amount, $decimals);
    }
}
