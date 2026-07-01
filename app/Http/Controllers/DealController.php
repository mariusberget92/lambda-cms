<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DealController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage deals'), 403);

        $deals = Deal::with('contact:id,first_name,last_name', 'company:id,name')
            ->search($request->input('search'))
            ->when($request->input('stage'), fn ($q, $stage) => $q->where('stage', $stage))
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'value' => $deal->value,
                'stage' => $deal->stage,
                'expected_close_date' => $deal->expected_close_date?->toDateString(),
                'closed_at' => $deal->closed_at?->toISOString(),
                'contact' => $deal->contact ? [
                    'id' => $deal->contact->id,
                    'full_name' => $deal->contact->full_name,
                ] : null,
                'company' => $deal->company ? [
                    'id' => $deal->company->id,
                    'name' => $deal->company->name,
                ] : null,
                'created_at' => $deal->created_at->toISOString(),
            ]);

        return Inertia::render('Deals/Index', [
            'deals' => $deals,
            'filters' => $request->only('search', 'stage'),
            'stages' => Deal::$stages,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage deals'), 403);

        return Inertia::render('Deals/Form', [
            'contacts' => Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'stages' => Deal::$stages,
        ]);
    }

    public function store(StoreDealRequest $request)
    {
        $data = $request->validated();

        if (in_array($data['stage'], ['won', 'lost'])) {
            $data['closed_at'] = now();
        }

        Deal::create([
            ...$data,
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('deals.index')
            ->with('status', 'Deal created.');
    }

    public function edit(Request $request, Deal $deal)
    {
        abort_unless($request->user()->can('manage deals'), 403);

        return Inertia::render('Deals/Form', [
            'deal' => [
                'id' => $deal->id,
                'name' => $deal->name,
                'value' => $deal->value,
                'stage' => $deal->stage,
                'expected_close_date' => $deal->expected_close_date?->toDateString(),
                'contact_id' => $deal->contact_id,
                'company_id' => $deal->company_id,
                'notes' => $deal->notes,
                'closed_at' => $deal->closed_at?->toISOString(),
            ],
            'contacts' => Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'stages' => Deal::$stages,
            'activities' => $deal->activities()
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

    public function update(UpdateDealRequest $request, Deal $deal)
    {
        $data = $request->validated();

        if (in_array($data['stage'], ['won', 'lost']) && ! $deal->closed_at) {
            $data['closed_at'] = now();
        } elseif (! in_array($data['stage'], ['won', 'lost'])) {
            $data['closed_at'] = null;
        }

        $deal->update($data);

        return redirect()
            ->route('deals.index')
            ->with('status', 'Deal updated.');
    }

    public function destroy(Request $request, Deal $deal)
    {
        abort_unless($request->user()->can('manage deals'), 403);

        $deal->delete();

        return redirect()
            ->route('deals.index')
            ->with('status', 'Deal deleted.');
    }

    public function bulk(Request $request)
    {
        abort_unless($request->user()->can('manage deals'), 403);

        $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:deals,id'],
        ]);

        Deal::whereIn('id', $request->input('ids'))
            ->each(fn (Deal $d) => $d->delete());

        return back()->with('status', count($request->input('ids')).' deal(s) deleted.');
    }
}
