<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanyRequest;
use App\Models\Company;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('check-role:super_admin');
    }

    public function index()
    {
        $companies = Company::paginate(15);
        return view('admin.companies.index', compact('companies'));
    }

    public function create()
    {
        return view('admin.companies.create');
    }

    public function store(StoreCompanyRequest $request)
    {
        Company::create($request->validated());

        return redirect()->route('companies.index')->with('success', 'Empresa creada exitosamente');
    }

    public function show(Company $company)
    {
        $users = $company->users()->paginate(10);
        return view('admin.companies.show', compact('company', 'users'));
    }

    public function edit(Company $company)
    {
        return view('admin.companies.edit', compact('company'));
    }

    public function update(StoreCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return redirect()->route('companies.show', $company)->with('success', 'Empresa actualizada exitosamente');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return redirect()->route('companies.index')->with('success', 'Empresa eliminada exitosamente');
    }
}
