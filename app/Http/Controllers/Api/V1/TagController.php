<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function index(): JsonResponse
    {
        $tags = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->map(fn (Tag $t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'slug'        => $t->slug,
                'posts_count' => $t->posts_count,
            ]);

        return response()->json(['data' => $tags]);
    }

    public function show(string $slug): JsonResponse
    {
        $tag = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'id'          => $tag->id,
            'name'        => $tag->name,
            'slug'        => $tag->slug,
            'posts_count' => $tag->posts_count,
        ]);
    }
}
