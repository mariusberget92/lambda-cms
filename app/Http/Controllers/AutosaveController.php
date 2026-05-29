<?php

namespace App\Http\Controllers;

use App\Http\Requests\AutosaveRequest;
use App\Models\Autosave;
use App\Models\Page;
use App\Models\Post;
use App\Models\Template;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutosaveController extends Controller
{
    public function storePost(AutosaveRequest $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Post::class,
                'autosaveable_id' => $post->id,
                'user_id' => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function storePage(AutosaveRequest $request, Page $page): JsonResponse
    {
        if ($page->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Page::class,
                'autosaveable_id' => $page->id,
                'user_id' => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function destroyPost(Request $request, Post $post): JsonResponse
    {
        $isAdmin = $request->user()->hasRole('administrator');

        if ($post->user_id !== $request->user()->id && ! $isAdmin) {
            abort(403);
        }

        $query = Autosave::where('autosaveable_type', Post::class)
            ->where('autosaveable_id', $post->id);

        if (! $isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        $query->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyPage(Request $request, Page $page): JsonResponse
    {
        $isAdmin = $request->user()->hasRole('administrator');

        if ($page->user_id !== $request->user()->id && ! $isAdmin) {
            abort(403);
        }

        $query = Autosave::where('autosaveable_type', Page::class)
            ->where('autosaveable_id', $page->id);

        if (! $isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        $query->delete();

        return response()->json(['ok' => true]);
    }

    public function storeTemplate(AutosaveRequest $request, Template $template): JsonResponse
    {
        if ($template->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Template::class,
                'autosaveable_id' => $template->id,
                'user_id' => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function destroyTemplate(Request $request, Template $template): JsonResponse
    {
        $isAdmin = $request->user()->hasRole('administrator');

        if ($template->user_id !== $request->user()->id && ! $isAdmin) {
            abort(403);
        }

        $query = Autosave::where('autosaveable_type', Template::class)
            ->where('autosaveable_id', $template->id);

        if (! $isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        $query->delete();

        return response()->json(['ok' => true]);
    }
}
