<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::withCount('posts')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'slug'        => $t->slug,
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

    public function store(Request $request)
    {
        abort_if(! $request->user()->can('create tags'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

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
                'id'   => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ],
        ]);
    }

    public function update(Request $request, Tag $tag)
    {
        abort_if(! $request->user()->can('edit tags'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        $validated['slug'] = Tag::generateSlug($validated['name'], $tag->id);

        $tag->update($validated);

        return redirect()
            ->route('tags.index')
            ->with('status', 'Tag updated.');
    }

    public function destroy(Tag $tag)
    {
        abort_if(! request()->user()->can('delete tags'), 403);

        $tag->delete();

        return redirect()
            ->route('tags.index')
            ->with('status', 'Tag deleted.');
    }
}
