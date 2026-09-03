<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\IncomeStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class IncomeStatementController extends Controller
{
    public function __construct(private IncomeStatementService $service) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to'   => ['nullable', 'date'],
        ]);

        $from = isset($data['from'])
            ? Carbon::parse($data['from']) : Carbon::today()->startOfMonth();
        $to = isset($data['to'])
            ? Carbon::parse($data['to']) : Carbon::today();

        $report = $this->service->build($from, $to);

        return view('reports.income-statement', compact('report'));
    }
}
