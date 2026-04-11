<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\TemplateResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    public function __construct(private readonly TemplateResolver $templates) {}

    public function index(Request $request)
    {
        $q = trim($request->get('q', ''));

        $results = Post::published()
            ->when($q, fn ($query) => $query->where(fn ($q2) =>
                $q2->where('title',   'LIKE', "%{$q}%")
                   ->orWhere('excerpt', 'LIKE', "%{$q}%")
                   ->orWhere('body',    'LIKE', "%{$q}%")
            ))
            ->with(['author:id,name,avatar', 'featuredImage:id,path,disk'])
            ->orderByDesc('published_at')
            ->paginate(15)
            ->through(fn (Post $post) => [
                'id'                 => $post->id,
                'title'              => $post->title,
                'slug'               => $post->slug,
                'excerpt'            => $post->excerpt,
                'published_at'       => $post->published_at?->toDateString(),
                'featured_image_url' => $post->featuredImage?->url,
                'author'             => ['name' => $post->author->name, 'avatar_url' => $post->author->avatar_url],
            ]);

        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => ($q ? "Search: {$q}" : 'Search') . $separator . $siteName,
            'description' => $q ? "Search results for \"{$q}\"" : '',
            'canonical'   => url("/search?q={$q}"),
            'type'        => 'website',
        ];

        if ($template = $this->templates->resolve('search-results')) {
            return Inertia::render('Blog/TemplatePage', [
                'blocks'        => $template->blocks ?? [],
                'searchContext' => ['query' => $q, 'results' => $results],
                'seo'           => $seo,
            ]);
        }

        return Inertia::render('Blog/Search', [
            'query'   => $q,
            'results' => $results,
            'sidebar' => $this->sidebarData(),
            'seo'     => $seo,
        ]);
    }

    private function sidebarData(): array
    {
        return [
            'categories'  => Category::withCount(['posts' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($c) => [
                    'name'        => $c->name,
                    'slug'        => $c->slug,
                    'posts_count' => $c->posts_count,
                ]),
            'tags'        => Tag::withCount(['posts' => fn ($q) => $q->published()])
                ->orderByDesc('posts_count')
                ->get(['id', 'name', 'slug'])
                ->map(fn ($t) => [
                    'name'        => $t->name,
                    'slug'        => $t->slug,
                    'posts_count' => $t->posts_count,
                ]),
            'recentPosts' => Post::published()
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
