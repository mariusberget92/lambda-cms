<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallListRequest;
use App\Http\Requests\UpdateCallListRequest;
use App\Models\CallList;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CallListController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $callLists = CallList::withCount(['contacts', 'contacts as completed_count' => function ($q) {
            $q->wherePivot('call_status', 'completed');
        }])
            ->search($request->input('search'))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CallList $list) => [
                'id' => $list->id,
                'name' => $list->name,
                'status' => $list->status,
                'contacts_count' => $list->contacts_count,
                'completed_count' => $list->completed_count,
                'created_at' => $list->created_at->toISOString(),
            ]);

        return Inertia::render('CallLists/Index', [
            'callLists' => $callLists,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        return Inertia::render('CallLists/Form');
    }

    public function store(StoreCallListRequest $request)
    {
        CallList::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('call-lists.index')
            ->with('status', 'Call list created.');
    }

    public function edit(Request $request, CallList $callList)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $existingContactIds = $callList->contacts()->pluck('contacts.id')->toArray();

        return Inertia::render('CallLists/Form', [
            'callList' => [
                'id' => $callList->id,
                'name' => $callList->name,
                'description' => $callList->description,
                'status' => $callList->status,
            ],
            'contacts' => $callList->contacts()
                ->with('company:id,name')
                ->get()
                ->map(fn (Contact $c) => [
                    'id' => $c->id,
                    'full_name' => $c->full_name,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'company' => $c->company?->name,
                    'call_status' => $c->pivot->call_status,
                    'notes' => $c->pivot->notes,
                    'called_at' => $c->pivot->called_at,
                ]),
            'availableContacts' => Contact::whereNotIn('id', $existingContactIds)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (Contact $c) => [
                    'id' => $c->id,
                    'name' => $c->full_name,
                ]),
        ]);
    }

    public function update(UpdateCallListRequest $request, CallList $callList)
    {
        $callList->update($request->validated());

        return redirect()
            ->route('call-lists.index')
            ->with('status', 'Call list updated.');
    }

    public function destroy(Request $request, CallList $callList)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $callList->delete();

        return redirect()
            ->route('call-lists.index')
            ->with('status', 'Call list deleted.');
    }

    public function bulk(Request $request)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $request->validate([
            'action' => ['required', 'in:delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:call_lists,id'],
        ]);

        CallList::whereIn('id', $request->input('ids'))->delete();

        return back()->with('status', count($request->input('ids')).' call list(s) deleted.');
    }

    public function work(Request $request, CallList $callList)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $contacts = $callList->contacts()
            ->with('company:id,name')
            ->get()
            ->map(fn (Contact $c) => [
                'id' => $c->id,
                'full_name' => $c->full_name,
                'first_name' => $c->first_name,
                'last_name' => $c->last_name,
                'email' => $c->email,
                'phone' => $c->phone,
                'position' => $c->position,
                'company' => $c->company?->name,
                'call_status' => $c->pivot->call_status,
                'notes' => $c->pivot->notes,
                'called_at' => $c->pivot->called_at,
            ]);

        $currentContactId = $request->input('contact_id');
        if (! $currentContactId) {
            $first = $contacts->firstWhere('call_status', 'not_called');
            $currentContactId = $first ? $first['id'] : ($contacts->first()['id'] ?? null);
        }

        return Inertia::render('CallLists/Work', [
            'callList' => [
                'id' => $callList->id,
                'name' => $callList->name,
                'status' => $callList->status,
            ],
            'contacts' => $contacts,
            'currentContactId' => (int) $currentContactId,
        ]);
    }

    public function addContacts(Request $request, CallList $callList)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $request->validate([
            'contact_ids' => ['required', 'array'],
            'contact_ids.*' => ['exists:contacts,id'],
        ]);

        $maxOrder = $callList->contacts()->max('sort_order') ?? 0;

        $attach = [];
        foreach ($request->input('contact_ids') as $i => $contactId) {
            $attach[$contactId] = ['sort_order' => $maxOrder + $i + 1];
        }

        $callList->contacts()->syncWithoutDetaching($attach);

        return back()->with('status', count($request->input('contact_ids')).' contact(s) added.');
    }

    public function removeContacts(Request $request, CallList $callList)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $request->validate([
            'contact_ids' => ['required', 'array'],
            'contact_ids.*' => ['exists:contacts,id'],
        ]);

        $callList->contacts()->detach($request->input('contact_ids'));

        return back()->with('status', count($request->input('contact_ids')).' contact(s) removed.');
    }

    public function updateContactStatus(Request $request, CallList $callList, Contact $contact)
    {
        abort_unless($request->user()->can('manage call lists'), 403);

        $request->validate([
            'call_status' => ['required', 'in:not_called,called,no_answer,callback,completed'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $data = [
            'call_status' => $request->input('call_status'),
            'notes' => $request->input('notes'),
        ];

        if (in_array($request->input('call_status'), ['called', 'no_answer', 'callback', 'completed'])) {
            $data['called_at'] = now();
        }

        $callList->contacts()->updateExistingPivot($contact->id, $data);

        return back()->with('status', 'Contact status updated.');
    }
}
