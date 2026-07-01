<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage companies'), 403);

        $companies = Company::withCount('contacts', 'deals')
            ->search($request->input('search'))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'domain' => $company->domain,
                'phone' => $company->phone,
                'contacts_count' => $company->contacts_count,
                'deals_count' => $company->deals_count,
                'created_at' => $company->created_at->toISOString(),
            ]);

        return Inertia::render('Companies/Index', [
            'companies' => $companies,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage companies'), 403);

        return Inertia::render('Companies/Form');
    }

    public function store(StoreCompanyRequest $request)
    {
        Company::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('companies.index')
            ->with('status', 'Company created.');
    }

    public function edit(Request $request, Company $company)
    {
        abort_unless($request->user()->can('manage companies'), 403);

        return Inertia::render('Companies/Form', [
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'domain' => $company->domain,
                'phone' => $company->phone,
                'address' => $company->address,
                'notes' => $company->notes,
            ],
            'activities' => $company->activities()
                ->with('creator:id,name')
                ->latest('occurred_at')
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'type' => $a->type,
                    'description' => $a->description,
                    'occurred_at' => $a->occurred_at->toISOString(),
                    'creator_name' => $a->creator->name,
                ]),
        ]);
    }

    public function update(UpdateCompanyRequest $request, Company $company)
    {
        $company->update($request->validated());

        return redirect()
            ->route('companies.index')
            ->with('status', 'Company updated.');
    }

    public function destroy(Request $request, Company $company)
    {
        abort_unless($request->user()->can('manage companies'), 403);

        $company->delete();

        return redirect()
            ->route('companies.index')
            ->with('status', 'Company deleted.');
    }

    public function bulk(Request $request)
    {
        abort_unless($request->user()->can('manage companies'), 403);

        $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:companies,id'],
        ]);

        Company::whereIn('id', $request->input('ids'))
            ->each(fn (Company $c) => $c->delete());

        return back()->with('status', count($request->input('ids')).' company(ies) deleted.');
    }
}
