<?php

namespace App\Models\Scopes;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope de aislamiento multi-empresa.
 *
 * Si hay una empresa activa (Tenancy::id() !== null) añade
 * "where {tabla}.company_id = :id" a TODAS las consultas del modelo.
 * Si no hay empresa activa (super_admin / contexto público que no fijó tenant)
 * no filtra, replicando el antiguo patrón ->when($cid, ...).
 *
 * La columna se cualifica con el nombre de la tabla para evitar ambigüedad
 * cuando la consulta hace JOINs.
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(Tenancy::class)->id();

        if ($companyId === null) {
            return;
        }

        $builder->where($model->getTable().'.'.$model->getCompanyColumn(), $companyId);
    }
}
