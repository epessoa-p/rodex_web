<?php

namespace App\Support;

/**
 * Resolvedor central de la empresa (tenant) activa para la petición actual.
 *
 * La fuente de verdad sigue siendo session('current_company_id'); el middleware
 * SetTenant fija aquí ese valor al inicio de la petición autenticada. Si nadie lo
 * fijó explícitamente, se hace fallback perezoso a la sesión.
 *
 * Semántica (idéntica al antiguo patrón ->when($cid, ...)):
 *   - id() === null  => sin filtro de empresa (super_admin ve todo / contexto público)
 *   - id() === int   => se filtra por esa empresa
 */
class Tenancy
{
    protected ?int $companyId = null;

    protected bool $resolved = false;

    /**
     * Fija explícitamente la empresa activa (o null para "ver todo").
     */
    public function set(?int $companyId): void
    {
        $this->companyId = $companyId !== null ? (int) $companyId : null;
        $this->resolved = true;
    }

    /**
     * Id de la empresa activa, con fallback perezoso a la sesión.
     */
    public function id(): ?int
    {
        if (! $this->resolved) {
            $sessionValue = session('current_company_id');
            $this->companyId = $sessionValue !== null ? (int) $sessionValue : null;
            $this->resolved = true;
        }

        return $this->companyId;
    }

    /**
     * ¿Hay una empresa activa concreta? (false = ver todo)
     */
    public function has(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Ejecuta un callback forzando temporalmente una empresa (o null = ver todo).
     * Útil para tareas de sistema, comandos, o el catálogo público por token.
     */
    public function runAs(?int $companyId, callable $callback)
    {
        $previousId = $this->companyId;
        $previousResolved = $this->resolved;

        $this->set($companyId);

        try {
            return $callback();
        } finally {
            $this->companyId = $previousId;
            $this->resolved = $previousResolved;
        }
    }

    /**
     * Olvida el tenant resuelto (vuelve a fallback perezoso en el próximo id()).
     */
    public function forget(): void
    {
        $this->companyId = null;
        $this->resolved = false;
    }
}
