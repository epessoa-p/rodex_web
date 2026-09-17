<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Reports\IncomeStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Estado de resultados (por movimientos de caja + tesorería) para el móvil.
 */
class IncomeStatementController extends Controller
{
    public function __construct(private IncomeStatementService $service) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
            'all'  => ['nullable', 'boolean'],
        ]);

        // ?all=1 → todo el historial: desde el PRIMER movimiento registrado
        // (así la app puede mostrar "Desde dd/mm/yyyy" con la fecha real).
        if ($request->boolean('all')) {
            [$from, $to] = $this->service->presetRange('all');

            return response()->json(['data' => $this->service->build($from, $to)]);
        }

        $from = isset($data['from'])
            ? Carbon::parse($data['from']) : Carbon::today()->startOfMonth();
        $to = isset($data['to'])
            ? Carbon::parse($data['to']) : Carbon::today();

        return response()->json(['data' => $this->service->build($from, $to)]);
    }
}
