<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Módulos exclusivos de la PLATAFORMA (super_admin): ninguna empresa los ve
     * ni puede otorgarlos a sus cargos. Los usuarios de una empresa se crean
     * desde Personal; las plantillas de documentos las gestiona el operador.
     */
    public const PLATFORM_ONLY_MODULES = ['users', 'document_templates'];

    protected $fillable = ['name', 'slug', 'module', 'description'];

    /** Permisos que una empresa puede asignar a sus cargos/roles (sin los de plataforma). */
    public function scopeForCompanies($query)
    {
        return $query->whereNotIn('module', self::PLATFORM_ONLY_MODULES);
    }

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }
}
