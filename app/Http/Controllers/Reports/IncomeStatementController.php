<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\IncomeStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class IncomeStatementController extends Controller
{
    public function __construct(private IncomeStatementService $service) {}

    /**
     * ?preset=this_month|last_month|all (como en el móvil) o ?from=&to= (rango).
     * Sin parámetros: este mes.
     */
    public function index(Request $request)
    {
        $data = $request->validate([
            'preset' => ['nullable', 'in:this_month,last_month,all,custom'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
        ]);

        $preset = $data['preset'] ?? (isset($data['from']) || isset($data['to']) ? 'custom' : 'this_month');
        $range  = $this->service->presetRange($preset);

        if ($range) {
            [$from, $to] = $range;
        } else {
            $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::today()->startOfMonth();
            $to   = isset($data['to']) ? Carbon::parse($data['to']) : Carbon::today();
        }

        $report = $this->service->build($from, $to);

        return view('reports.income-statement', compact('report', 'preset'));
    }
}
