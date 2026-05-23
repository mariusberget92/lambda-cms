<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Services\QueryBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PreviewController extends Controller
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function post(string $token): Response
    {
        $post = Post::where('preview_token', $token)
            ->with([
                'author:id,name,avatar',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'featuredImage:id,path,disk,alt',
            ])
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
            'description' => $post->meta_description ?: Setting::get('seo.default_description', ''),
            'image'       => $post->featuredImage?->url ?? Setting::get('seo.default_og_image_url', ''),
            'keywords'    => $post->meta_keywords ?: Setting::get('seo.default_keywords', ''),
        ];

        return Inertia::render('Blog/Show', [
            'post' => [
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
                'author'             => [
                    'name'       => $post->author?->name ?? 'Deleted User',
                    'avatar_url' => $post->author?->avatar_url,
                ],
                'categories' => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
                'tags'       => $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
                'custom_js'  => $post->custom_js,
            ],
            'sidebar'         => [],
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
            'commentsEnabled' => false,
            'seo'             => $seo,
            'authUser'        => null,
            'isPreview'       => true,
        ]);
    }

    public function page(Request $request, string $token): Response
    {
        $page = Page::where('preview_token', $token)->firstOrFail();

        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => ($page->meta_title ?: $page->title) . $separator . $siteName,
            'description' => $page->meta_description ?: Setting::get('seo.default_description', ''),
            'image'       => Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url("/preview/pages/{$page->preview_token}"),
            'type'        => 'website',
            'keywords'    => $page->meta_keywords ?: Setting::get('seo.default_keywords', ''),
        ];

        return Inertia::render('Blog/Page', [
            'page' => [
                'title'     => $page->title,
                'slug'      => $page->slug,
                'blocks'    => $this->resolveBlocks($page->blocks ?? [], $request->query()),
                'custom_js' => $page->custom_js,
            ],
            'seo'       => $seo,
            'isPreview' => true,
        ]);
    }

    private function resolveBlocks(array $blocks, array $urlParams = []): array
    {
        return array_map(function ($block) use ($urlParams) {
            if (in_array($block['type'] ?? '', ['container', 'section'], true) && !empty($block['children'])) {
                $block['children'] = $this->resolveBlocks($block['children'], $urlParams);
            }

            if (($block['type'] ?? '') === 'loop') {
                $result = $this->queryBuilder->resolve($block['data'] ?? [], $urlParams);
                $block['data']['resolved'] = $result;
                return $block;
            }

            if (($block['type'] ?? '') === 'table' && ($block['data']['mode'] ?? '') === 'dynamic') {
                $result = $this->queryBuilder->resolve($block['data'] ?? [], $urlParams);
                $block['data']['resolved'] = $result;
                return $block;
            }

            return $block;
        }, $blocks);
    }
}
