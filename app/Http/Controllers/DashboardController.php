<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\CallList;
use App\Models\Comment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Post;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $hasCrm = $user->canAny(['manage contacts', 'manage companies', 'manage deals', 'manage call lists']);

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total' => Post::count(),
                'published' => Post::published()->count(),
                'scheduled' => Post::scheduled()->count(),
                'drafts' => Post::draft()->count(),
                'pendingCommentsCount' => Comment::pending()->count(),
            ],

            'upcoming_scheduled' => Post::scheduled()
                ->where('published_at', '>', now())
                ->orderBy('published_at', 'asc')
                ->limit(5)
                ->with('author:id,name')
                ->get()
                ->map(fn ($post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'published_at' => $post->published_at->toIso8601String(),
                    'author_name' => $post->author?->name ?? 'Unknown',
                ])
                ->values()
                ->toArray(),

            'recent_posts' => Post::orderBy('updated_at', 'desc')
                ->limit(5)
                ->with('author:id,name')
                ->get()
                ->map(fn ($post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'status' => $post->status,
                    'published_at' => $post->published_at?->toIso8601String(),
                    'updated_at' => $post->updated_at->toIso8601String(),
                    'author_name' => $post->author?->name ?? 'Unknown',
                ])
                ->values()
                ->toArray(),

            'crm' => $hasCrm ? [
                'contacts_count' => $user->can('manage contacts') ? Contact::count() : null,
                'companies_count' => $user->can('manage companies') ? Company::count() : null,
                'open_deals_count' => $user->can('manage deals')
                    ? Deal::whereNotIn('stage', ['won', 'lost'])->count()
                    : null,
                'open_deals_value' => $user->can('manage deals')
                    ? (float) Deal::whereNotIn('stage', ['won', 'lost'])->sum('value')
                    : null,
                'active_call_lists' => $user->can('manage call lists')
                    ? CallList::where('status', 'active')->count()
                    : null,
                'recent_activities' => Activity::with('creator:id,name', 'subject')
                    ->latest('occurred_at')
                    ->limit(8)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'type' => $a->type,
                        'description' => $a->description,
                        'occurred_at' => $a->occurred_at->toIso8601String(),
                        'creator_name' => $a->creator?->name ?? 'Unknown',
                        'subject_type' => class_basename($a->subject_type),
                        'subject_name' => match (true) {
                            $a->subject instanceof Contact => $a->subject->full_name,
                            $a->subject instanceof Company => $a->subject->name,
                            $a->subject instanceof Deal => $a->subject->name,
                            default => '—',
                        },
                    ]),
                'upcoming_callbacks' => $user->can('manage call lists')
                    ? CallList::where('status', 'active')
                        ->with(['contacts' => fn ($q) => $q
                            ->wherePivot('call_status', 'callback')
                            ->orderByPivot('updated_at', 'desc')
                            ->limit(5),
                        ])
                        ->get()
                        ->flatMap(fn ($list) => $list->contacts->map(fn ($c) => [
                            'contact_name' => $c->full_name,
                            'phone' => $c->phone,
                            'list_name' => $list->name,
                            'list_id' => $list->id,
                            'notes' => $c->pivot->notes,
                        ]))
                        ->take(5)
                        ->values()
                    : [],
            ] : null,
        ]);
    }
}
