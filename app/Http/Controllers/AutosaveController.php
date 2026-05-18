<?php

namespace App\Http\Controllers;

use App\Models\Autosave;
use App\Models\Page;
use App\Models\Post;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutosaveController extends Controller
{
    public function storePost(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id && ! $request->user()->can('edit any post')) {
            abort(403);
        }

        $request->validate(['payload' => ['required', 'array']]);

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Post::class,
                'autosaveable_id'   => $post->id,
                'user_id'           => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function storePage(Request $request, Page $page): JsonResponse
    {
        abort_if(! $request->user()->can('edit pages'), 403);

        $request->validate(['payload' => ['required', 'array']]);

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Page::class,
                'autosaveable_id'   => $page->id,
                'user_id'           => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function destroyPost(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id && ! $request->user()->can('edit any post')) {
            abort(403);
        }

        Autosave::where([
            'autosaveable_type' => Post::class,
            'autosaveable_id'   => $post->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyPage(Request $request, Page $page): JsonResponse
    {
        abort_if(! $request->user()->can('edit pages'), 403);

        Autosave::where([
            'autosaveable_type' => Page::class,
            'autosaveable_id'   => $page->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return response()->json(['ok' => true]);
    }

    public function storeTemplate(Request $request, Template $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->can('edit templates')) {
            abort(403);
        }

        $request->validate(['payload' => ['required', 'array']]);

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Template::class,
                'autosaveable_id'   => $template->id,
                'user_id'           => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function destroyTemplate(Request $request, Template $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->can('edit templates')) {
            abort(403);
        }

        Autosave::where([
            'autosaveable_type' => Template::class,
            'autosaveable_id'   => $template->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return response()->json(['ok' => true]);
    }
}
