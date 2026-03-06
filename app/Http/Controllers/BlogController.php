<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
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
            ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => [
                'id'                  => $post->id,
                'title'               => $post->title,
                'slug'                => $post->slug,
                'excerpt'             => $post->excerpt,
                'published_at'        => $post->published_at?->toDateString(),
                'featured_image_url'  => $post->featuredImage?->url,
                'author'              => [
                    'name'       => $post->author->name,
                    'avatar_url' => $post->author->avatar_url,
                ],
                'categories'          => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
                'tags'                => $post->tags->map(fn ($t) => [
                    'name' => $t->name,
                    'slug' => $t->slug,
                ]),
            ]);

        $siteName = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => $siteName,
            'description' => Setting::get('seo.default_description', ''),
            'image'       => Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url('/blog'),
            'type'        => 'website',
        ];

        return Inertia::render('Blog/Index', [
            'posts'   => $posts,
            'sidebar' => $this->sidebarData(),
            'seo'     => $seo,
        ]);
    }

    /**
     * Public single post view.
     */
    public function show(string $slug): Response
    {
        $post = Post::published()
            ->with([
                'author:id,name,avatar',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'featuredImage:id,path,disk,alt',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $perPage   = (int) Setting::get('comments.per_page', 10);
        $total     = $post->comments()->approved()->count();
        $firstPage = $post->comments()
            ->approved()
            ->oldest()
            ->with('user:id,name,avatar')
            ->limit($perPage)
            ->get();

        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => ($post->meta_title ?: $post->title) . $separator . $siteName,
            'description' => $post->meta_description ?: $post->excerpt ?: Setting::get('seo.default_description', ''),
            'image'       => $post->featuredImage?->url ?: Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url("/blog/{$post->slug}"),
            'type'        => 'article',
        ];

        return Inertia::render('Blog/Show', [
            'post' => [
                'id'                  => $post->id,
                'title'               => $post->title,
                'slug'                => $post->slug,
                'excerpt'             => $post->excerpt,
                'body'                => $post->body,
                'published_at'        => $post->published_at?->toDateString(),
                'featured_image_url'  => $post->featuredImage?->url,
                'featured_image_alt'  => $post->featuredImage?->alt,
                'author'              => [
                    'name'       => $post->author->name,
                    'avatar_url' => $post->author->avatar_url,
                ],
                'categories'          => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
                'tags'                => $post->tags->map(fn ($t) => [
                    'name' => $t->name,
                    'slug' => $t->slug,
                ]),
            ],
            'sidebar'         => $this->sidebarData(),
            'comments'        => $firstPage->map(fn (Comment $c) => [
                'id'          => $c->id,
                'author_name' => $c->author_name,
                'avatar_url'  => $c->user?->avatar_url ?? null,
                'body'        => $c->body,
                'created_at'  => $c->created_at->diffForHumans(),
            ]),
            'commentsTotal'   => $total,
            'commentsHasMore' => $firstPage->count() < $total,
            'commentsPerPage' => $perPage,
            'commentsEnabled' => $post->commentsOpen(),
            'seo'             => $seo,
            'authUser'        => auth()->check() ? [
                'name'  => auth()->user()->name,
                'email' => auth()->user()->email,
            ] : null,
        ]);
    }

    /**
     * Public JSON endpoint — paginated approved comments for a post.
     */
    public function comments(Post $post): \Illuminate\Http\JsonResponse
    {
        abort_unless($post->isPublished(), 404);
        abort_unless($post->commentsOpen(), 403);

        $perPage = (int) Setting::get('comments.per_page', 10);
        $page    = max(1, (int) request('page', 1));

        $paginator = $post->comments()
            ->approved()
            ->oldest()
            ->with('user:id,name,avatar')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data'     => $paginator->map(fn (Comment $c) => [
                'id'          => $c->id,
                'author_name' => $c->author_name,
                'avatar_url'  => $c->user?->avatar_url ?? null,
                'body'        => $c->body,
                'created_at'  => $c->created_at->diffForHumans(),
            ]),
            'has_more' => $paginator->hasMorePages(),
            'total'    => $paginator->total(),
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
