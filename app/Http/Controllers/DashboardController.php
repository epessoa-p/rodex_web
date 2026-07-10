<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Warehouse;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = $user->getCurrentCompany();

        if ($user->is_super_admin) {
            $totalProducts = Product::count();
            $totalBranches = Branch::count();
            $totalWarehouses = Warehouse::count();
        } else {
            $companyId = $company?->id;
            $totalProducts = Product::where('company_id', $companyId)->count();
            $totalBranches = Branch::where('company_id', $companyId)->count();
            $totalWarehouses = Warehouse::where('company_id', $companyId)->count();
        }

        return view('dashboard.index', compact(
            'totalProducts',
            'totalBranches',
            'totalWarehouses',
            'company'
        ));
    }
}
