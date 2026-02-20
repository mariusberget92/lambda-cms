<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::with('author:id,name', 'category:id,name', 'tags:id,name')
            ->search($request->input('search'))
            ->when(
                $request->input('status'),
                fn ($q, $status) => $q->where('status', $status)
            )
            ->when(
                $request->input('category'),
                fn ($q, $categoryId) => $q->where('category_id', $categoryId)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($post) => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'excerpt'      => $post->excerpt,
                'status'       => $post->status,
                'published_at' => $post->published_at?->toDateString(),
                'created_at'   => $post->created_at->toDateString(),
                'author'       => $post->author->name,
                'category'     => $post->category?->name,
                'tags'         => $post->tags->pluck('name'),
            ]);

        return Inertia::render('Posts/Index', [
            'posts'      => $posts,
            'filters'    => $request->only('search', 'status', 'category'),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Posts/Create', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags'       => Tag::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'excerpt'     => ['nullable', 'string', 'max:500'],
            'body'        => ['nullable', 'string'],
            'status'      => ['required', 'in:draft,published'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['exists:tags,id'],
        ]);

        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $validated['slug']    = Post::generateSlug($validated['title']);
        $validated['user_id'] = $request->user()->id;

        if ($validated['status'] === 'published') {
            $validated['published_at'] = Carbon::now();
        }

        $post = Post::create($validated);
        $post->tags()->sync($tagIds);

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post created successfully.');
    }

    public function edit(Post $post)
    {
        if ($post->user_id !== request()->user()->id && !request()->user()->hasRole('administrator')) {
            abort(403);
        }

        $post->load('tags:id,name');

        return Inertia::render('Posts/Edit', [
            'post' => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'excerpt'      => $post->excerpt,
                'body'         => $post->body,
                'status'       => $post->status,
                'published_at' => $post->published_at?->toDateString(),
                'category_id'  => $post->category_id,
                'tag_ids'      => $post->tags->pluck('id'),
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags'       => Tag::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'excerpt'     => ['nullable', 'string', 'max:500'],
            'body'        => ['nullable', 'string'],
            'status'      => ['required', 'in:draft,published'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'tag_ids'     => ['nullable', 'array'],
            'tag_ids.*'   => ['exists:tags,id'],
        ]);

        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $validated['slug'] = Post::generateSlug($validated['title'], $post->id);

        if ($validated['status'] === 'published' && $post->status !== 'published') {
            $validated['published_at'] = Carbon::now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $post->update($validated);
        $post->tags()->sync($tagIds);

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post updated successfully.');
    }

    public function destroy(Post $post)
    {
        if ($post->user_id !== request()->user()->id && !request()->user()->hasRole('administrator')) {
            abort(403);
        }

        $post->delete();

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post deleted.');
    }
}
