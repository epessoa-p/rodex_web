<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\Reports\IncomeStatementService;
use App\Services\Reports\ProfitReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Ganancias (precio − costo) de ventas y taller: hoy, semana, mes o rango.
 */
class ProfitReportController extends Controller
{
    public function index(Request $request, IncomeStatementService $periods, ProfitReportService $profit)
    {
        $data = $request->validate([
            'preset'      => ['nullable', 'in:today,this_week,last_week,this_month,last_month,custom'],
            'from'        => ['nullable', 'date'],
            'to'          => ['nullable', 'date'],
            'branch_id'   => ['nullable', 'integer'],
            'scope'       => ['nullable', 'in:sales,workshop,all'],
            'merge_quick' => ['nullable', 'boolean'],
        ]);

        $company = auth()->user()->getCurrentCompany();
        abort_unless($company, 403, 'Elige una empresa para ver sus ganancias.');

        $preset = $data['preset'] ?? (isset($data['from']) || isset($data['to']) ? 'custom' : 'today');
        if ($range = $periods->presetRange($preset)) {
            [$from, $to] = $range;
        } else {
            $from = isset($data['from']) ? Carbon::parse($data['from']) : Carbon::today()->startOfMonth();
            $to   = isset($data['to']) ? Carbon::parse($data['to']) : Carbon::today();
            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
        }

        $branches = Branch::where('company_id', $company->id)->where('active', true)->orderBy('name')->get(['id', 'name']);
        $branchId = ! empty($data['branch_id']) ? $branches->firstWhere('id', (int) $data['branch_id'])?->id : null;
        $scope    = $data['scope'] ?? 'all';
        $merge    = (bool) ($data['merge_quick'] ?? false);

        $report = $profit->build($company->id, $from, $to, $branchId, $scope, $merge);

        return view('reports.profit', compact('report', 'preset', 'branches', 'branchId', 'scope', 'merge', 'company'));
    }
}
