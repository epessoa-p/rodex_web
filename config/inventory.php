<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración genérica de inventario (SaaS)
    |--------------------------------------------------------------------------
    |
    | Valores por defecto del inventario (repuestos de moto). Antes estaban
    | hardcodeados en vistas y controladores; aquí quedan ajustables por despliegue.
    |
    */

    // Símbolo de moneda mostrado en inventario (KPIs, precios, export).
    'currency' => env('INVENTORY_CURRENCY', 'Bs'),

    // Unidad por defecto al crear/importar productos sin unidad.
    'default_unit' => env('INVENTORY_DEFAULT_UNIT', 'Unidad'),

    // Prefijo del código/SKU autogenerado (ej. PRD-00001).
    'code_prefix' => env('INVENTORY_CODE_PREFIX', 'PRD'),

];
