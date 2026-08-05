<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla el modelo por empresa (tenant).
 *
 * - Aplica el CompanyScope global: toda consulta se filtra por la empresa activa.
 * - Al crear un registro, auto-asigna company_id = empresa activa si viene vacío.
 * - Expone relación company() y un scope local ->forCompany($id) puntual.
 *
 * Para saltar el aislamiento (p. ej. super_admin explícito o tareas de sistema):
 *   Model::withoutGlobalScope(\App\Models\Scopes\CompanyScope::class)->...
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            $column = $model->getCompanyColumn();

            if (empty($model->{$column})) {
                $companyId = app(Tenancy::class)->id();

                if ($companyId !== null) {
                    $model->{$column} = $companyId;
                }
            }
        });
    }

    /**
     * Nombre de la columna discriminadora de empresa.
     */
    public function getCompanyColumn(): string
    {
        return 'company_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, $this->getCompanyColumn());
    }

    /**
     * Filtro puntual por empresa, saltando el global scope.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->withoutGlobalScope(CompanyScope::class)
            ->where($this->getTable().'.'.$this->getCompanyColumn(), $companyId);
    }
}
