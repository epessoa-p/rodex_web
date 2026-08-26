<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migra los "modelos compatibles" que estaban como TEXTO en products.compatible_models
 * a filas ESTRUCTURADAS en moto_models (por empresa, sin marca) enlazadas al producto
 * mediante el pivote moto_model_product. Al terminar limpia el texto para que
 * moto_models quede como única fuente.
 *
 * Idempotente: solo procesa productos con texto no vacío y no duplica modelos/enlaces.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'compatible_models')) {
            return;
        }

        $rows = DB::table('products')
            ->whereNotNull('compatible_models')
            ->where('compatible_models', '<>', '')
            ->get(['id', 'company_id', 'compatible_models']);

        $now = now();

        foreach ($rows as $p) {
            $tokens = collect(explode(',', (string) $p->compatible_models))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->unique(fn ($t) => mb_strtolower($t));

            foreach ($tokens as $name) {
                // Reutiliza el modelo si ya existe para la empresa (varchar = case-insensitive).
                $model = DB::table('moto_models')
                    ->where('company_id', $p->company_id)
                    ->where('name', $name)
                    ->whereNull('deleted_at')
                    ->first();

                $modelId = $model?->id ?? DB::table('moto_models')->insertGetId([
                    'company_id'      => $p->company_id,
                    'moto_brand_id'   => null,
                    'name'            => $name,
                    'suggested_price' => 0,
                    'daily_rate'      => 0,
                    'active'          => 1,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                $linked = DB::table('moto_model_product')
                    ->where('product_id', $p->id)
                    ->where('moto_model_id', $modelId)
                    ->exists();

                if (! $linked) {
                    DB::table('moto_model_product')->insert([
                        'product_id'    => $p->id,
                        'moto_model_id' => $modelId,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }

            // Ya migrado: limpia el texto (moto_models es la fuente de verdad).
            DB::table('products')->where('id', $p->id)->update(['compatible_models' => null]);
        }
    }

    public function down(): void
    {
        // No se revierte: los datos ya viven de forma estructurada en moto_models.
    }
};
