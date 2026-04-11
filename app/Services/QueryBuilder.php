<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;

class QueryBuilder
{
    // Fields allowed in filter conditions per source (security whitelist)
    private const FILTERABLE = [
        'posts'      => ['featured', 'title', 'slug'],
        'categories' => ['name', 'slug'],
        'tags'       => ['name', 'slug'],
        'pages'      => ['title', 'slug'],
    ];

    private const SORT_ALLOWED = [
        'posts'      => ['published_at', 'title', 'created_at'],
        'categories' => ['name', 'created_at', 'posts_count'],
        'tags'       => ['name', 'created_at', 'posts_count'],
        'pages'      => ['title', 'created_at', 'updated_at'],
    ];

    /**
     * Resolve a loop block's data config into an items + total array.
     *
     * @param  array  $data       block.data (source, filters, sort, limit, offset)
     * @param  array  $urlParams  current URL query params (used for urlParam filters)
     * @return array  ['items' => [...], 'total' => int]
     */
    public function resolve(array $data, array $urlParams = []): array
    {
        $source      = $data['source']       ?? 'posts';
        $filters     = $this->applyUrlParams($data['filters'] ?? [], $urlParams);
        $sort        = $data['sort']         ?? ['field' => 'created_at', 'direction' => 'desc'];
        $limit       = min((int) ($data['limit']  ?? 12), 100);
        $offset      = (int) ($data['offset'] ?? 0);
        $filterLogic = ($data['filter_logic'] ?? 'and') === 'or' ? 'or' : 'and';

        return match ($source) {
            'posts'      => $this->resolvePosts($filters, $sort, $limit, $offset, $filterLogic),
            'categories' => $this->resolveCategories($filters, $sort, $limit, $offset, $filterLogic),
            'tags'       => $this->resolveTags($filters, $sort, $limit, $offset, $filterLogic),
            'pages'      => $this->resolvePages($filters, $sort, $limit, $offset, $filterLogic),
            default      => ['items' => [], 'total' => 0],
        };
    }

    // Replace urlParam placeholders with actual URL param values
    private function applyUrlParams(array $filters, array $urlParams): array
    {
        return array_map(function ($filter) use ($urlParams) {
            $paramKey = $filter['urlParam'] ?? null;
            if ($paramKey && isset($urlParams[$paramKey])) {
                $filter['value'] = $urlParams[$paramKey];
                unset($filter['urlParam']);
            }
            return $filter;
        }, $filters);
    }

    private function resolvePosts(array $filters, array $sort, int $limit, int $offset, string $filterLogic = 'and'): array
    {
        $query = Post::query()
            ->with(['author:id,name', 'featuredImage:id,path,disk'])
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        $this->applyFilters($query, $filters, 'posts', $filterLogic);

        $field = in_array($sort['field'] ?? '', self::SORT_ALLOWED['posts'], true)
            ? $sort['field'] : 'published_at';
        $dir = ($sort['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($field, $dir);

        $total = $query->count();

        $items = $query->skip($offset)->take($limit)->get()->map(fn ($post) => [
            'id'                 => $post->id,
            'title'              => $post->title,
            'slug'               => $post->slug,
            'excerpt'            => $post->excerpt,
            'body'               => $post->body,
            'featured'           => (bool) $post->featured,
            'published_at'       => $post->published_at?->toIso8601String(),
            'author_name'        => $post->author->name ?? '',
            'featured_image_url' => $post->featuredImage?->url,
            'url'                => url("/blog/{$post->slug}"),
        ])->all();

        return ['items' => $items, 'total' => $total];
    }

    private function resolveCategories(array $filters, array $sort, int $limit, int $offset, string $filterLogic = 'and'): array
    {
        $query = Category::query()->withCount('posts');

        $this->applyFilters($query, $filters, 'categories', $filterLogic);

        $field = in_array($sort['field'] ?? '', self::SORT_ALLOWED['categories'], true)
            ? $sort['field'] : 'name';
        $dir = ($sort['direction'] ?? 'asc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($field, $dir);

        $total = $query->count();

        $items = $query->skip($offset)->take($limit)->get()->map(fn ($cat) => [
            'id'          => $cat->id,
            'name'        => $cat->name,
            'slug'        => $cat->slug,
            'description' => $cat->description,
            'posts_count' => $cat->posts_count,
            'url'         => url("/categories/{$cat->slug}"),
        ])->all();

        return ['items' => $items, 'total' => $total];
    }

    private function resolveTags(array $filters, array $sort, int $limit, int $offset, string $filterLogic = 'and'): array
    {
        $query = Tag::query()->withCount('posts');

        $this->applyFilters($query, $filters, 'tags', $filterLogic);

        $field = in_array($sort['field'] ?? '', self::SORT_ALLOWED['tags'], true)
            ? $sort['field'] : 'name';
        $dir = ($sort['direction'] ?? 'asc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($field, $dir);

        $total = $query->count();

        $items = $query->skip($offset)->take($limit)->get()->map(fn ($tag) => [
            'id'          => $tag->id,
            'name'        => $tag->name,
            'slug'        => $tag->slug,
            'posts_count' => $tag->posts_count,
            'url'         => url("/tags/{$tag->slug}"),
        ])->all();

        return ['items' => $items, 'total' => $total];
    }

    private function resolvePages(array $filters, array $sort, int $limit, int $offset, string $filterLogic = 'and'): array
    {
        $query = Page::published();

        $this->applyFilters($query, $filters, 'pages', $filterLogic);

        $field = in_array($sort['field'] ?? '', self::SORT_ALLOWED['pages'], true)
            ? $sort['field'] : 'title';
        $dir = ($sort['direction'] ?? 'asc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($field, $dir);

        $total = $query->count();

        $items = $query->skip($offset)->take($limit)->get()->map(fn ($page) => [
            'id'               => $page->id,
            'title'            => $page->title,
            'slug'             => $page->slug,
            'meta_description' => $page->meta_description,
            'url'              => url("/{$page->slug}"),
        ])->all();

        return ['items' => $items, 'total' => $total];
    }

    private function applyFilters($query, array $filters, string $source, string $logic): void
    {
        if (empty($filters)) return;

        if ($logic === 'or') {
            $query->where(function ($q) use ($filters, $source) {
                foreach ($filters as $filter) {
                    $q->orWhere(function ($inner) use ($filter, $source) {
                        $this->applyFilter($inner, $filter, $source);
                    });
                }
            });
        } else {
            foreach ($filters as $filter) {
                $this->applyFilter($query, $filter, $source);
            }
        }
    }

    private function applyFilter($query, array $filter, string $source): void
    {
        $field = $filter['field'] ?? null;
        $op    = $filter['op']    ?? '=';
        $value = $filter['value'] ?? null;

        if (!$field) return;

        // Security: only allow whitelisted filter fields
        if (!in_array($field, self::FILTERABLE[$source] ?? [], true)) return;

        switch ($op) {
            case '=':
                if ($value !== null) $query->where($field, $value);
                break;
            case '!=':
                if ($value !== null) $query->where($field, '!=', $value);
                break;
            case 'not_empty':
                $query->whereNotNull($field)->where($field, '!=', '');
                break;
            case 'empty':
                $query->where(fn ($q) => $q->whereNull($field)->orWhere($field, ''));
                break;
            case 'contains':
                if ($value !== null && $value !== '') {
                    $query->where($field, 'LIKE', "%{$value}%");
                }
                break;
        }
    }
}
