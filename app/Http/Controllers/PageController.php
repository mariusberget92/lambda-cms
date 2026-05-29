<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Models\Autosave;
use App\Models\Category;
use App\Models\Page;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('creator:id,name')
            ->latest()
            ->paginate(20)
            ->through(fn ($page) => [
                'id'         => $page->id,
                'title'      => $page->title,
                'slug'       => $page->slug,
                'status'     => $page->status,
                'created_at' => $page->created_at->toDateString(),
                'creator'    => $page->creator->name,
            ]);

        return Inertia::render('Pages/Index', ['pages' => $pages]);
    }

    public function create()
    {
        return Inertia::render('Pages/Create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags'       => Tag::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePageRequest $request)
    {
        $validated = $request->validated();

        Page::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('pages.index')->with('status', 'Page created.');
    }

    public function edit(Request $request, Page $page)
    {
        return Inertia::render('Pages/Edit', [
            'page' => [
                'id'               => $page->id,
                'title'            => $page->title,
                'slug'             => $page->slug,
                'status'           => $page->status,
                'updated_at'       => $page->updated_at?->toISOString(),
                'blocks'           => $page->blocks,
                'meta_title'       => $page->meta_title,
                'meta_description' => $page->meta_description,
                'meta_keywords'    => $page->meta_keywords,
                'custom_js'        => $page->custom_js,
                'preview_token'    => $page->preview_token,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags'       => Tag::orderBy('name')->get(['id', 'name']),
            'autosave'   => Autosave::where([
                'autosaveable_type' => Page::class,
                'autosaveable_id'   => $page->id,
                'user_id'           => $request->user()->id,
            ])->first()?->only(['payload', 'updated_at']),
        ]);
    }

    public function update(UpdatePageRequest $request, Page $page)
    {
        $validated = $request->validated();

        $page->update($validated);

        $page->saveRevision($request->user()->id);

        Autosave::where([
            'autosaveable_type' => Page::class,
            'autosaveable_id'   => $page->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return redirect()->route('pages.edit', $page->id)->with('status', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('pages.index')->with('status', 'Page deleted.');
    }
}
