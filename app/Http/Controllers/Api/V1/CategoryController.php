<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'description' => $c->description,
                'posts_count' => $c->posts_count,
            ]);

        return response()->json(['data' => $categories]);
    }

    public function show(string $slug): JsonResponse
    {
        $category = Category::withCount(['posts' => fn ($q) => $q->published()])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'id'          => $category->id,
            'name'        => $category->name,
            'slug'        => $category->slug,
            'description' => $category->description,
            'posts_count' => $category->posts_count,
        ]);
    }
}
