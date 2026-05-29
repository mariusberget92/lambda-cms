<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'total' => Post::count(),
                'published' => Post::published()->count(),
                'scheduled' => Post::scheduled()->count(),
                'drafts' => Post::draft()->count(),
                'pendingCommentsCount' => Comment::pending()->count(),
            ],

            // Up to 5 upcoming scheduled posts (future dated only), ascending.
            // NOTE: ->where('published_at', '>', now()) is intentional —
            // the scheduled() scope only filters on status, not the date.
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

            // Last 5 posts by updated_at DESC, any status.
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
        ]);
    }
}
