<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::withCount('posts')
            ->search($request->input('search'))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'color' => $c->color,
                'hue' => $c->hue,
                'posts_count' => $c->posts_count,
            ]);

        $allCategories = Category::withCount('posts')
            ->orderBy('name')
            ->get(['id', 'name', 'posts_count']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'allCategories' => $allCategories,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Categories/Form');
    }

    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Category::generateSlug($validated['name']);

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Categories/Form', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'color' => $category->color,
                'hue' => $category->hue,
            ],
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $validated = $request->validated();

        $validated['slug'] = Category::generateSlug($validated['name'], $category->id);

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('status', 'Category deleted.');
    }

    public function bulk(BulkCategoryRequest $request)
    {
        $validated = $request->validated();
        $categories = Category::whereIn('id', $validated['ids'])->get();

        match ($validated['action']) {
            'delete' => $categories->each->delete(),
        };

        $count = $categories->count();

        return redirect()->back()->with('status', "{$count} ".($count === 1 ? 'category' : 'categories').' deleted.');
    }
}
