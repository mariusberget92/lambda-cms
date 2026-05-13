<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('posts')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'description' => $c->description,
                'color'       => $c->color,
                'posts_count' => $c->posts_count,
            ]);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $validated['slug'] = Category::generateSlug($validated['name']);

        $category = Category::create($validated);

        ActivityLogger::log('created', "Created category '{$category->name}'", 'Category', $category->id);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Categories/Form', [
            'category' => [
                'id'          => $category->id,
                'name'        => $category->name,
                'slug'        => $category->slug,
                'description' => $category->description,
                'color'       => $category->color,
            ],
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'color'       => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $validated['slug'] = Category::generateSlug($validated['name'], $category->id);

        $category->update($validated);

        ActivityLogger::log('updated', "Updated category '{$category->name}'", 'Category', $category->id);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        ActivityLogger::log('deleted', "Deleted category '{$category->name}'", 'Category', $category->id);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category deleted.');
    }
}
