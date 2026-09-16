<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashRegister extends Model
{
    use BelongsToCompany;

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'code',
        'description',
        'assigned_personal_id',
        'active',
        'created_by',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedPersonal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'assigned_personal_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashRegisterSession::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function activeSession(): ?CashRegisterSession
    {
        return $this->sessions()->where('status', 'open')->latest()->first();
    }

    /**
     * ¿Ya tiene registros (sesiones o movimientos)? Entonces la caja queda
     * "congelada": no se edita, para no reescribir la historia de esos
     * registros (sucursal / personal / nombre con los que se hicieron).
     */
    public function hasRecords(): bool
    {
        return $this->sessions()->exists() || $this->movements()->exists();
    }

    /**
     * Regla de unicidad: UNA caja por sucursal POR PERSONAL. Un personal puede
     * tener varias cajas, pero en sucursales distintas. Devuelve la caja que
     * choca (misma sucursal + mismo personal), o null. Las eliminadas (soft
     * delete) no cuentan.
     */
    public static function conflict(int $branchId, int $personalId, ?int $exceptId = null): ?self
    {
        return static::where('branch_id', $branchId)
            ->where('assigned_personal_id', $personalId)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->first();
    }
}
