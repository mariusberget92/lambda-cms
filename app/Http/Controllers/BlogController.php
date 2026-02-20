<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    /**
     * Public blog index — paginated published posts + sidebar data.
     */
    public function index(): Response
    {
        $posts = Post::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'excerpt'      => $post->excerpt,
                'published_at' => $post->published_at?->toDateString(),
                'author'       => [
                    'name'       => $post->author->name,
                    'avatar_url' => $post->author->avatar_url,
                ],
                'category'     => $post->category ? [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ] : null,
                'tags'         => $post->tags->map(fn ($t) => [
                    'name' => $t->name,
                    'slug' => $t->slug,
                ]),
            ]);

        return Inertia::render('Blog/Index', [
            'posts'   => $posts,
            'sidebar' => $this->sidebarData(),
        ]);
    }

    /**
     * Public single post view.
     */
    public function show(string $slug): Response
    {
        $post = Post::published()
            ->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug'])
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Blog/Show', [
            'post' => [
                'id'           => $post->id,
                'title'        => $post->title,
                'slug'         => $post->slug,
                'excerpt'      => $post->excerpt,
                'body'         => $post->body,
                'published_at' => $post->published_at?->toDateString(),
                'author'       => [
                    'name'       => $post->author->name,
                    'avatar_url' => $post->author->avatar_url,
                ],
                'category'     => $post->category ? [
                    'name' => $post->category->name,
                    'slug' => $post->category->slug,
                ] : null,
                'tags'         => $post->tags->map(fn ($t) => [
                    'name' => $t->name,
                    'slug' => $t->slug,
                ]),
            ],
            'sidebar' => $this->sidebarData(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Data shared between index and show: categories, tags, recent posts.
     */
    private function sidebarData(): array
    {
        return [
            'categories'   => Category::withCount(['posts' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($c) => [
                    'name'        => $c->name,
                    'slug'        => $c->slug,
                    'posts_count' => $c->posts_count,
                ]),
            'tags'         => Tag::withCount(['posts' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($t) => [
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'posts_count' => $t->posts_count,
                ]),
            'recentPosts'  => Post::published()
                ->orderByDesc('published_at')
                ->limit(5)
                ->get(['title', 'slug', 'published_at'])
                ->map(fn ($p) => [
                    'title'        => $p->title,
                    'slug'         => $p->slug,
                    'published_at' => $p->published_at?->toDateString(),
                ]),
        ];
    }
}
