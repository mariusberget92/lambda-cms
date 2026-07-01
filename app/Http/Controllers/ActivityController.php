<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'subject_type' => ['required', 'in:contact,company,deal'],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', 'in:'.implode(',', Activity::$types)],
            'description' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['required', 'date'],
        ]);

        $modelMap = [
            'contact' => Contact::class,
            'company' => Company::class,
            'deal' => Deal::class,
        ];

        $subjectClass = $modelMap[$request->input('subject_type')];
        $subject = $subjectClass::findOrFail($request->input('subject_id'));

        $permissionMap = [
            'contact' => 'manage contacts',
            'company' => 'manage companies',
            'deal' => 'manage deals',
        ];

        abort_unless($request->user()->can($permissionMap[$request->input('subject_type')]), 403);

        Activity::create([
            'user_id' => $request->user()->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'type' => $request->input('type'),
            'description' => $request->input('description'),
            'occurred_at' => $request->input('occurred_at'),
        ]);

        return back()->with('status', 'Activity logged.');
    }

    public function destroy(Request $request, Activity $activity)
    {
        $permissionMap = [
            Contact::class => 'manage contacts',
            Company::class => 'manage companies',
            Deal::class => 'manage deals',
        ];

        $permission = $permissionMap[$activity->subject_type] ?? null;
        abort_unless($permission && $request->user()->can($permission), 403);

        $activity->delete();

        return back()->with('status', 'Activity removed.');
    }
}
