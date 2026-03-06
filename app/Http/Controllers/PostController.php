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
        $posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name')
            ->withCount('comments')
            ->search($request->input('search'))
            ->when(
                $request->input('status'),
                fn ($q, $status) => $q->where('status', $status)
            )
            ->when(
                $request->input('category'),
                fn ($q, $categoryId) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $categoryId))
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
                'categories'     => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'tags'           => $post->tags->pluck('name'),
                'comments_count' => $post->comments_count,
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
            'category_ids'   => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'tag_ids'          => ['nullable', 'array'],
            'tag_ids.*'        => ['exists:tags,id'],
            'featured_image_id' => ['nullable', 'exists:media,id'],
            'comments_enabled' => ['nullable', 'boolean'],
        ]);

        $tagIds      = $validated['tag_ids'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['tag_ids'], $validated['category_ids']);

        $validated['comments_enabled'] = $validated['comments_enabled'] ?? true;

        $validated['slug']    = Post::generateSlug($validated['title']);
        $validated['user_id'] = $request->user()->id;

        if ($validated['status'] === 'published') {
            $validated['published_at'] = Carbon::now();
        }

        $post = Post::create($validated);
        $post->tags()->sync($tagIds);
        $post->categories()->sync($categoryIds);

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post created successfully.');
    }

    public function edit(Post $post)
    {
        if ($post->user_id !== request()->user()->id && !request()->user()->hasRole('administrator')) {
            abort(403);
        }

        $post->load('tags:id,name', 'categories:id,name', 'featuredImage:id,path,disk,alt');

        return Inertia::render('Posts/Edit', [
            'post' => [
                'id'                => $post->id,
                'title'             => $post->title,
                'slug'              => $post->slug,
                'excerpt'           => $post->excerpt,
                'body'              => $post->body,
                'status'            => $post->status,
                'published_at'      => $post->published_at?->toDateString(),
                'category_ids'      => $post->categories->pluck('id'),
                'tag_ids'           => $post->tags->pluck('id'),
                'featured_image_id' => $post->featured_image_id,
                'featured_image'    => $post->featured_image_id ? [
                    'id'  => $post->featuredImage->id,
                    'url' => $post->featuredImage->url,
                    'alt' => $post->featuredImage->alt,
                ] : null,
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
            'category_ids'   => ['nullable', 'array'],
            'category_ids.*' => ['exists:categories,id'],
            'tag_ids'           => ['nullable', 'array'],
            'tag_ids.*'         => ['exists:tags,id'],
            'featured_image_id' => ['nullable', 'exists:media,id'],
            'comments_enabled'  => ['nullable', 'boolean'],
        ]);

        $tagIds      = $validated['tag_ids'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['tag_ids'], $validated['category_ids']);

        $validated['comments_enabled'] = $validated['comments_enabled'] ?? true;

        $validated['slug'] = Post::generateSlug($validated['title'], $post->id);

        if ($validated['status'] === 'published' && $post->status !== 'published') {
            $validated['published_at'] = Carbon::now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $post->update($validated);
        $post->tags()->sync($tagIds);
        $post->categories()->sync($categoryIds);

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
