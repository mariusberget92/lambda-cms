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

    public function indexPage(Page $page): JsonResponse
    {
        $revisions = $page->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'created_at']);

        return response()->json($revisions);
    }

    public function indexTemplate(Template $template): JsonResponse
    {
        $revisions = $template->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'created_at']);

        return response()->json($revisions);
    }

    public function restore(Revision $revision): JsonResponse
    {
        $revisable = $revision->revisable;

        if ($revisable instanceof Post) {
            if ($revisable->user_id !== request()->user()->id
                && ! request()->user()->hasRole('administrator')) {
                abort(403);
            }
        } elseif ($revisable instanceof Page) {
            if (! request()->user()->hasRole('administrator')) {
                abort(403);
            }
        }

        return response()->json($revision->payload);
    }
}
