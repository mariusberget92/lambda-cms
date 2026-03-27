<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Revision;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function indexPost(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $revisions = $post->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'created_at']);

        return response()->json($revisions);
    }

    public function indexPage(Request $request, Page $page): JsonResponse
    {
        if ($page->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $revisions = $page->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'created_at']);

        return response()->json($revisions);
    }

    public function indexTemplate(Request $request, Template $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $revisions = $template->revisions()
            ->with('user:id,name')
            ->orderByDesc('id')
            ->limit(25)
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'user'       => $r->user->name,
                'created_at' => $r->created_at->diffForHumans(),
            ]);

        return response()->json($revisions);
    }

    public function restore(Request $request, Revision $revision): JsonResponse
    {
        $revisable = $revision->revisable;

        if ($revisable === null) {
            abort(404);
        }

        if ($revisable->user_id !== $request->user()->id
            && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        return response()->json($revision->payload);
    }
}
