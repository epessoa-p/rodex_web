<?php

namespace App\Http\Controllers;

use App\Services\Admin\PlatformOverviewService;
use App\Services\Dashboard\OperationalOverviewService;

/**
 * Inicio de la web.
 *  - Empresas: dashboard OPERATIVO del día (el mismo que el móvil): ventas hoy,
 *    OTs, motos en taller, citas, stock, OTs por estado, próxima cita, top
 *    servicios del mes y OTs recientes. Cada bloque según plan + permiso.
 *  - Super admin (sin empresa activa): dashboard de la PLATAFORMA — quién usa
 *    el sistema, suscripciones por vencer/vencidas y empresas sin actividad.
 */
class DashboardController extends Controller
{
    public function __construct(
        private OperationalOverviewService $operational,
        private PlatformOverviewService $platform,
    ) {}

    public function index()
    {
        $user    = auth()->user();
        $company = $user->getCurrentCompany();

        if ($user->is_super_admin) {
            return view('dashboard.platform', ['overview' => $this->platform->build()]);
        }

        return view('dashboard.index', [
            'company'  => $company,
            'overview' => $this->operational->build($user, $company),
        ]);
    }
}
