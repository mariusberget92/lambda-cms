<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Comment;
use App\Services\TemplateResolver;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function __construct(private readonly TemplateResolver $templates) {}

    public function index(): Response
    {
        $template = $this->templates->resolve('blog-index');

        $posts = Post::published()
            ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => $this->postData($post));

        return Inertia::render('Blog/TemplatePage', [
            'blocks'      => $template?->blocks ?? [],
            'postContext' => ['posts' => $posts],
            'seo'         => $this->buildIndexSeo(),
        ]);
    }

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
        $template  = $this->templates->resolve('single-post');

        return Inertia::render('Blog/TemplatePage', [
            'blocks'   => $template?->blocks ?? [],
            'customJs' => $post->custom_js,
            'postContext' => [
                'id'                 => $post->id,
                'title'              => $post->title,
                'slug'               => $post->slug,
                'excerpt'            => $post->excerpt,
                'body'               => $post->body,
                'use_block_editor'   => (bool) $post->use_block_editor,
                'blocks'             => $post->blocks,
                'published_at'       => $post->published_at?->toDateString(),
                'featured_image_url' => $post->featuredImage?->url,
                'featured_image_alt' => $post->featuredImage?->alt,
                'author'             => ['name' => $post->author?->name ?? 'Deleted User', 'avatar_url' => $post->author?->avatar_url],
                'categories'         => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'color' => $c->color ?? null])->values(),
                'tags'               => $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
            ],
            'commentsData' => [
                'total'   => $total,
                'hasMore' => $post->comments()->approved()->count() > $perPage,
                'perPage' => $perPage,
                'enabled' => $post->commentsOpen(),
            ],
            'seo' => $this->buildShowSeo($post),
        ]);
    }

    public function category(Category $category): Response
    {
        $template = $this->templates->resolve('archive');

        $posts = Post::published()
            ->whereHas('categories', fn ($q) => $q->where('categories.id', $category->id))
            ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => $this->postData($post));

        $siteName  = Setting::get('site.name', config('app.name'));
        $separator = Setting::get('seo.title_separator', ' | ');

        return Inertia::render('Blog/TemplatePage', [
            'blocks'         => $template?->blocks ?? [],
            'archiveContext' => [
                'type'       => 'category',
                'name'       => $category->name,
                'slug'       => $category->slug,
                'postsCount' => $posts->total(),
                'posts'      => $posts,
            ],
            'seo' => [
                'title'       => "Posts in {$category->name}{$separator}{$siteName}",
                'description' => "All posts in the {$category->name} category.",
                'image'       => Setting::get('seo.default_og_image_url', ''),
                'canonical'   => url("/blog/category/{$category->slug}"),
                'type'        => 'website',
                'keywords'    => Setting::get('seo.default_keywords', ''),
            ],
        ]);
    }

    public function tag(Tag $tag): Response
    {
        $template = $this->templates->resolve('archive');

        $posts = Post::published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with(['author:id,name,avatar', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => $this->postData($post));

        $siteName  = Setting::get('site.name', config('app.name'));
        $separator = Setting::get('seo.title_separator', ' | ');

        return Inertia::render('Blog/TemplatePage', [
            'blocks'         => $template?->blocks ?? [],
            'archiveContext' => [
                'type'       => 'tag',
                'name'       => $tag->name,
                'slug'       => $tag->slug,
                'postsCount' => $posts->total(),
                'posts'      => $posts,
            ],
            'seo' => [
                'title'       => "Posts tagged '{$tag->name}'{$separator}{$siteName}",
                'description' => "All posts tagged '{$tag->name}'.",
                'image'       => Setting::get('seo.default_og_image_url', ''),
                'canonical'   => url("/blog/tag/{$tag->slug}"),
                'type'        => 'website',
                'keywords'    => Setting::get('seo.default_keywords', ''),
            ],
        ]);
    }

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

    private function buildIndexSeo(): array
    {
        $siteName = Setting::get('site.name', config('app.name'));

        return [
            'title'       => $siteName,
            'description' => Setting::get('seo.default_description', ''),
            'image'       => Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url('/'),
            'type'        => 'website',
            'keywords'    => Setting::get('seo.default_keywords', ''),
        ];
    }

    private function buildShowSeo(Post $post): array
    {
        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        return [
            'title'       => ($post->meta_title ?: $post->title) . $separator . $siteName,
            'description' => $post->meta_description ?: $post->excerpt ?: Setting::get('seo.default_description', ''),
            'image'       => $post->featuredImage?->url ?: Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url("/blog/{$post->slug}"),
            'type'        => 'article',
            'keywords'    => $post->meta_keywords ?: Setting::get('seo.default_keywords', ''),
        ];
    }

    private function postData(Post $post): array
    {
        return [
            'id'                  => $post->id,
            'title'               => $post->title,
            'slug'                => $post->slug,
            'excerpt'             => $post->excerpt,
            'published_at'        => $post->published_at?->toDateString(),
            'featured_image_url'  => $post->featuredImage?->url,
            'author'              => [
                'name'       => $post->author?->name ?? 'Deleted User',
                'avatar_url' => $post->author?->avatar_url,
            ],
            'categories' => $post->categories
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'color' => $c->color ?? null])
                ->values(),
            'tags' => $post->tags
                ->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
        ];
    }
}
