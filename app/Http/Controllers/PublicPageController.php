<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => ($page->meta_title ?: $page->title) . $separator . $siteName,
            'description' => $page->meta_description ?: Setting::get('seo.default_description', ''),
            'image'       => Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url("/{$page->slug}"),
            'type'        => 'website',
            'keywords'    => $page->meta_keywords ?: Setting::get('seo.default_keywords', ''),
        ];

        return Inertia::render('Blog/Page', [
            'page' => [
                'title'  => $page->title,
                'slug'   => $page->slug,
                'blocks' => $this->resolveBlocks($page->blocks ?? []),
            ],
            'seo' => $seo,
        ]);
    }

    private function resolveBlocks(array $blocks): array
    {
        return array_map(function ($block) {
            if (($block['type'] ?? '') !== 'component') {
                return $block;
            }

            return match ($block['data']['component'] ?? null) {
                'post-list' => $this->resolvePostList($block),
                default     => $block,
            };
        }, $blocks);
    }

    private function resolvePostList(array $block): array
    {
        $data = $block['data'];

        $query = Post::query()
            ->with(['author:id,name', 'featuredImage:id,path,disk'])
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        if (!empty($data['featured_only'])) {
            $query->where('featured', true);
        }

        if (!empty($data['category_ids'])) {
            $query->whereHas('categories', fn ($q) =>
                $q->whereIn('categories.id', $data['category_ids'])
            );
        }

        if (!empty($data['tag_ids'])) {
            $query->whereHas('tags', fn ($q) =>
                $q->whereIn('tags.id', $data['tag_ids'])
            );
        }

        match ($data['order'] ?? 'latest') {
            'oldest' => $query->orderBy('published_at'),
            'alpha'  => $query->orderBy('title'),
            default  => $query->orderByDesc('published_at'),
        };

        $posts = $query
            ->skip((int) ($data['offset'] ?? 0))
            ->take((int) ($data['limit'] ?? 6))
            ->get()
            ->map(fn ($post) => [
                'id'                 => $post->id,
                'title'              => $post->title,
                'slug'               => $post->slug,
                'excerpt'            => $post->excerpt,
                'published_at'       => $post->published_at?->toIso8601String(),
                'author_name'        => $post->author->name ?? '',
                'featured_image_url' => $post->featuredImage ? $post->featuredImage->url : null,
            ])
            ->all();

        $block['data']['resolved'] = ['posts' => $posts];

        return $block;
    }
}
