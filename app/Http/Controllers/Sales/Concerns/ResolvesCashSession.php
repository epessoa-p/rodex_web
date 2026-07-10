<?php

namespace App\Http\Controllers\Sales\Concerns;

use App\Models\CashRegisterSession;
use App\Models\Personal;

trait ResolvesCashSession
{
    /**
     * Sesión de caja abierta del usuario actual (vía Personal → CashRegister asignada).
     */
    protected function currentOpenSession(): ?CashRegisterSession
    {
        $personal = Personal::where('user_id', auth()->id())->first();
        if (!$personal) {
            return null;
        }

        return CashRegisterSession::where('status', 'open')
            ->whereHas('cashRegister', fn ($q) => $q->where('assigned_personal_id', $personal->id))
            ->with('cashRegister.branch')
            ->latest('opened_at')
            ->first();
    }
}
