<?php

namespace App\Http\Controllers;

use App\Http\Requests\CalendarDataRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $month = Carbon::now()->format('Y-m');
        $data  = $this->buildMonthData($request, $month);

        return Inertia::render('Calendar/Index', [
            'month'              => $month,
            'grouped'            => $data['grouped'],
            'unscheduled_drafts' => $data['unscheduled_drafts'],
        ]);
    }

    public function data(CalendarDataRequest $request): JsonResponse
    {
        $month = $request->validated('month') ?? Carbon::now()->format('Y-m');
        $data  = $this->buildMonthData($request, $month);

        return response()->json([
            'grouped'            => $data['grouped'],
            'unscheduled_drafts' => $data['unscheduled_drafts'],
        ]);
    }

    private function buildMonthData(Request $request, string $month): array
    {
        $user    = $request->user();
        $isAdmin = $user->hasRole('administrator');
        $start   = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end     = $start->copy()->endOfMonth();

        // Posts with a published_at falling in this month
        $postsQuery = Post::with('author:id,name')
            ->whereNotNull('published_at')
            ->whereBetween('published_at', [$start, $end]);

        if (! $isAdmin) {
            $postsQuery->where(function ($q) use ($user) {
                $q->where('status', 'published')
                  ->orWhere('user_id', $user->id);
            });
        }

        $grouped = $postsQuery->get()
            ->map(fn ($post) => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'status'       => $post->status,
                'published_at' => $post->published_at->toIso8601String(),
                'author_name'  => $post->author?->name ?? 'Unknown',
            ])
            ->groupBy(fn ($post) => Carbon::parse($post['published_at'])->toDateString())
            ->map->values()
            ->toArray();

        // Unscheduled drafts (draft + no published_at)
        $draftsQuery = Post::with('author:id,name')
            ->where('status', 'draft')
            ->whereNull('published_at');

        if (! $isAdmin) {
            $draftsQuery->where('user_id', $user->id);
        }

        $unscheduledDrafts = $draftsQuery->get()
            ->map(fn ($post) => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'status'       => $post->status,
                'published_at' => null,
                'author_name'  => $post->author?->name ?? 'Unknown',
            ])
            ->values()
            ->toArray();

        return [
            'grouped'            => $grouped,
            'unscheduled_drafts' => $unscheduledDrafts,
        ];
    }
}
