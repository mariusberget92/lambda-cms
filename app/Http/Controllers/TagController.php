<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('posts')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'posts_count' => $t->posts_count,
            ]);

        return Inertia::render('Tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tags/Form');
    }

    public function store(StoreTagRequest $request)
    {
        $validated = $request->validated();

        $validated['slug'] = Tag::generateSlug($validated['name']);

        Tag::create($validated);

        return redirect()
            ->route('tags.index')
            ->with('status', 'Tag created.');
    }

    public function edit(Tag $tag)
    {
        return Inertia::render('Tags/Form', [
            'tag' => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $validated = $request->validated();

        $validated['slug'] = Tag::generateSlug($validated['name'], $tag->id);

        $tag->update($validated);

        return redirect()
            ->route('tags.index')
            ->with('status', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()
            ->route('tags.index')
            ->with('status', 'Tag deleted.');
    }
}
