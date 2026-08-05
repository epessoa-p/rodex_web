<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marca de la PLATAFORMA
    |--------------------------------------------------------------------------
    |
    | Es la marca del producto SaaS (la tuya, la que vende el sistema). Se usa
    | solo donde todavía no hay una empresa concreta: la pantalla de login, los
    | títulos por defecto y como respaldo cuando una empresa no subió su logo.
    |
    | Dentro del sistema, cada empresa-cliente ve SU nombre y SU logo
    | (ver Company::$logo_url y Company::$name), no estos valores.
    |
    */

    'name' => env('BRAND_NAME', 'SCZ SOFT'),

    // Rutas relativas a public/
    // 'logo' es la firma/logo completo (con texto) que se muestra en el login y
    // en el modo global del super_admin. 'logo_sm' es el ícono pequeño de respaldo.
    'logo'    => env('BRAND_LOGO', 'images/brand-scz.png'),
    'logo_sm' => env('BRAND_LOGO_SM', 'images/brand-scz.png'),

];
