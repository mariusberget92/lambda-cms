<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage contacts'), 403);

        $contacts = Contact::with('company')
            ->search($request->input('search'))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Contact $contact) => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'full_name' => $contact->full_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'position' => $contact->position,
                'status' => $contact->status,
                'company' => $contact->company ? [
                    'id' => $contact->company->id,
                    'name' => $contact->company->name,
                ] : null,
                'deals_count' => $contact->deals()->count(),
                'created_at' => $contact->created_at->toISOString(),
            ]);

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage contacts'), 403);

        return Inertia::render('Contacts/Form', [
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreContactRequest $request)
    {
        Contact::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('contacts.index')
            ->with('status', 'Contact created.');
    }

    public function edit(Request $request, Contact $contact)
    {
        abort_unless($request->user()->can('manage contacts'), 403);

        return Inertia::render('Contacts/Form', [
            'contact' => [
                'id' => $contact->id,
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'position' => $contact->position,
                'company_id' => $contact->company_id,
                'status' => $contact->status,
                'notes' => $contact->notes,
            ],
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'activities' => $contact->activities()
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

    public function update(UpdateContactRequest $request, Contact $contact)
    {
        $contact->update($request->validated());

        return redirect()
            ->route('contacts.index')
            ->with('status', 'Contact updated.');
    }

    public function destroy(Request $request, Contact $contact)
    {
        abort_unless($request->user()->can('manage contacts'), 403);

        $contact->delete();

        return redirect()
            ->route('contacts.index')
            ->with('status', 'Contact deleted.');
    }

    public function bulk(Request $request)
    {
        abort_unless($request->user()->can('manage contacts'), 403);

        $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:contacts,id'],
        ]);

        Contact::whereIn('id', $request->input('ids'))
            ->each(fn (Contact $c) => $c->delete());

        return back()->with('status', count($request->input('ids')).' contact(s) deleted.');
    }
}
