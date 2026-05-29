<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkPostRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Autosave;
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
        $posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name', 'featuredImage:id,path,disk')
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
                'published_at' => $post->status === 'scheduled'
                    ? $post->published_at?->format('Y-m-d H:i')
                    : $post->published_at?->toDateString(),
                'created_at'   => $post->created_at->toDateString(),
                'author'       => $post->author->name,
                'categories'     => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'tags'           => $post->tags->pluck('name'),
                'comments_count' => $post->comments_count,
                'featured_image_url' => $post->featuredImage?->url,
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

    public function store(StorePostRequest $request)
    {
        $validated = $request->validated();

        $tagIds      = $validated['tag_ids'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];
        $newTagNames = $validated['new_tag_names'] ?? [];
        unset($validated['tag_ids'], $validated['category_ids'], $validated['new_tag_names']);

        foreach ($newTagNames as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $tagIds[] = Tag::firstOrCreate(
                ['name' => $name],
                ['slug' => Tag::generateSlug($name)]
            )->id;
        }

        $validated['body_format']      = $validated['body_format']      ?? 'html';
        $validated['comments_enabled'] = $validated['comments_enabled'] ?? true;
        $validated['featured']         = $validated['featured'] ?? false;
        $validated['meta_title']       = $validated['meta_title']       ?? null;
        $validated['meta_description'] = $validated['meta_description'] ?? null;
        $validated['meta_keywords']    = $validated['meta_keywords']    ?? null;

        $validated['slug']    = Post::generateSlug($validated['title']);
        $validated['user_id'] = $request->user()->id;

        if ($validated['status'] === 'published') {
            $validated['published_at'] = Carbon::now();
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }
        // status === 'scheduled': published_at comes from the validated request as-is

        $post = Post::create($validated);
        $post->tags()->sync($tagIds);
        $post->categories()->sync($categoryIds);

        return redirect()
            ->route('posts.index')
            ->with('status', 'Post created successfully.');
    }

    public function edit(Request $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
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
                'published_at'      => $post->published_at?->format('Y-m-d\TH:i'),
                'updated_at'        => $post->updated_at?->toISOString(),
                'category_ids'      => $post->categories->pluck('id'),
                'tag_ids'           => $post->tags->pluck('id'),
                'featured_image_id' => $post->featured_image_id,
                'featured_image'    => $post->featured_image_id ? [
                    'id'  => $post->featuredImage->id,
                    'url' => $post->featuredImage->url,
                    'alt' => $post->featuredImage->alt,
                ] : null,
                'comments_enabled'  => $post->comments_enabled,
                'featured'          => (bool) $post->featured,
                'meta_title'        => $post->meta_title,
                'meta_description'  => $post->meta_description,
                'meta_keywords'     => $post->meta_keywords,
                'custom_js'         => $post->custom_js,
                'body_format'       => $post->body_format ?? 'html',
                'use_block_editor'  => (bool) $post->use_block_editor,
                'blocks'            => $post->blocks,
                'preview_token'     => $post->preview_token,
            ],
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'tags'       => Tag::orderBy('name')->get(['id', 'name']),
            'autosave'   => Autosave::where([
                'autosaveable_type' => Post::class,
                'autosaveable_id'   => $post->id,
                'user_id'           => $request->user()->id,
            ])->first()?->only(['payload', 'updated_at']),
        ]);
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        if ($post->user_id !== $request->user()->id && !$request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validated();

        $tagIds      = $validated['tag_ids'] ?? [];
        $categoryIds = $validated['category_ids'] ?? [];
        $newTagNames = $validated['new_tag_names'] ?? [];
        unset($validated['tag_ids'], $validated['category_ids'], $validated['new_tag_names']);

        foreach ($newTagNames as $name) {
            $name = trim($name);
            if ($name === '') continue;
            $tagIds[] = Tag::firstOrCreate(
                ['name' => $name],
                ['slug' => Tag::generateSlug($name)]
            )->id;
        }

        $validated['comments_enabled'] = $validated['comments_enabled'] ?? $post->comments_enabled;
        $validated['featured']         = $validated['featured'] ?? $post->featured;
        $validated['meta_title']       = $validated['meta_title']       ?? $post->meta_title;
        $validated['meta_description'] = $validated['meta_description'] ?? $post->meta_description;
        $validated['meta_keywords']    = $validated['meta_keywords']    ?? $post->meta_keywords;

        $validated['slug'] = Post::generateSlug($validated['title'], $post->id);

        if ($validated['status'] === 'scheduled') {
            // published_at comes from the validated request as-is (future timestamp)
        } elseif ($validated['status'] === 'published' && $post->status !== 'published') {
            $validated['published_at'] = Carbon::now();
        } elseif ($validated['status'] === 'published' && $post->status === 'published') {
            unset($validated['published_at']); // preserve existing; do not overwrite
        } elseif ($validated['status'] === 'draft') {
            $validated['published_at'] = null;
        }

        $post->update($validated);
        $post->tags()->sync($tagIds);
        $post->categories()->sync($categoryIds);

        $post->saveRevision($request->user()->id);

        Autosave::where([
            'autosaveable_type' => Post::class,
            'autosaveable_id'   => $post->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return redirect()
            ->route('posts.edit', $post->id)
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

    public function bulk(BulkPostRequest $request)
    {
        $validated = $request->validated();
        $posts = Post::whereIn('id', $validated['ids'])->get();

        // Authorize each post individually — abort immediately if any fail.
        // Admins can operate on any post; regular users only their own.
        foreach ($posts as $post) {
            if (! ($post->user_id === $request->user()->id || $request->user()->hasRole('administrator'))) {
                abort(403, 'You are not authorised to perform this action on one or more selected posts.');
            }
        }

        // Use ->each() to fire Eloquent model events on every record
        // (unlike bulk ->update() which bypasses events).
        match ($validated['action']) {
            'publish' => $posts->each(function (Post $post) {
                $post->update([
                    'status'       => 'published',
                    'published_at' => Carbon::now(),
                ]);
            }),
            'draft' => $posts->each(fn (Post $post) => $post->update([
                'status'       => 'draft',
                'published_at' => null,
            ])),
            'delete' => $posts->each->delete(),
        };

        $count  = $posts->count();
        $labels = ['publish' => 'published', 'draft' => 'drafted', 'delete' => 'deleted'];
        $label  = $labels[$validated['action']];

        return redirect()->back()->with('status', "{$count} post" . ($count === 1 ? '' : 's') . " {$label}.");
    }

}
