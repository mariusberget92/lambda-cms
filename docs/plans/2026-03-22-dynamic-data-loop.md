# Dynamic Data Loop Block Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a Loop block to the page builder that queries a data source (posts, categories, tags, or pages), renders child blocks once per item, supports field binding and block-level visibility conditions, with server-side SEO resolution and client-side live URL-param filtering.

**Architecture:** A PHP `QueryBuilder` service executes all data queries and is shared between `PublicPageController` (server-side Inertia render) and a new `POST /api/v1/query` endpoint (client-side live filtering). Vue's `provide/inject` scopes each loop item to its child tree without prop threading. Top-level `bindings` and `condition` objects on blocks are handled transparently by the existing `updateBlock({ id, data, ...attrs })` mutation pattern.

**Tech Stack:** Laravel 12, Vue 3 Composition API, Inertia 2, axios (already in project), VueDraggable, lucide-vue-next, SelectBox component.

---

### Task 1: PHP QueryBuilder service

**Files:**
- Create: `app/Services/QueryBuilder.php`

**Step 1: Create the service class**

```php
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
        $source  = $data['source'] ?? 'posts';
        $filters = $this->applyUrlParams($data['filters'] ?? [], $urlParams);
        $sort    = $data['sort']   ?? ['field' => 'created_at', 'direction' => 'desc'];
        $limit   = min((int) ($data['limit']  ?? 12), 100);
        $offset  = (int) ($data['offset'] ?? 0);

        return match ($source) {
            'posts'      => $this->resolvePosts($filters, $sort, $limit, $offset),
            'categories' => $this->resolveCategories($filters, $sort, $limit, $offset),
            'tags'       => $this->resolveTags($filters, $sort, $limit, $offset),
            'pages'      => $this->resolvePages($filters, $sort, $limit, $offset),
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

    private function resolvePosts(array $filters, array $sort, int $limit, int $offset): array
    {
        $query = Post::query()
            ->with(['author:id,name', 'featuredImage:id,path,disk'])
            ->where('status', 'published')
            ->where('published_at', '<=', now());

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter, 'posts');
        }

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

    private function resolveCategories(array $filters, array $sort, int $limit, int $offset): array
    {
        $query = Category::query()->withCount('posts');

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter, 'categories');
        }

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

    private function resolveTags(array $filters, array $sort, int $limit, int $offset): array
    {
        $query = Tag::query()->withCount('posts');

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter, 'tags');
        }

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

    private function resolvePages(array $filters, array $sort, int $limit, int $offset): array
    {
        $query = Page::published();

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter, 'pages');
        }

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
        }
    }
}
```

**Step 2: Commit**

```bash
git add app/Services/QueryBuilder.php
git commit -m "feat: add QueryBuilder service for loop block data resolution"
```

---

### Task 2: QueryController + API route

**Files:**
- Create: `app/Http/Controllers/Api/V1/QueryController.php`
- Modify: `routes/api.php`

**Step 1: Create QueryController**

```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QueryController extends Controller
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'source'          => ['required', Rule::in(['posts', 'categories', 'tags', 'pages'])],
            'filters'         => ['nullable', 'array'],
            'filters.*.field' => ['nullable', 'string', 'max:50'],
            'filters.*.op'    => ['nullable', 'string', Rule::in(['=', '!=', 'not_empty', 'empty'])],
            'filters.*.value' => ['nullable'],
            'sort'            => ['nullable', 'array'],
            'sort.field'      => ['nullable', 'string', 'max:50'],
            'sort.direction'  => ['nullable', Rule::in(['asc', 'desc'])],
            'limit'           => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset'          => ['nullable', 'integer', 'min:0'],
            'url_params'      => ['nullable', 'array'],
        ]);

        $result = $this->queryBuilder->resolve(
            $validated,
            $validated['url_params'] ?? []
        );

        return response()->json($result);
    }
}
```

**Step 2: Register route in `routes/api.php`**

Add after the existing tag routes:

```php
use App\Http\Controllers\Api\V1\QueryController;

// Inside Route::prefix('v1')->name('api.v1.')->group(function () { ... })
Route::post('query', QueryController::class)->name('query');
```

Full updated file:

```php
<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\QueryController;
use App\Http\Controllers\Api\V1\TagController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('posts/{slug}', [PostController::class, 'show'])->name('posts.show');

    Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

    Route::get('tags', [TagController::class, 'index'])->name('tags.index');
    Route::get('tags/{slug}', [TagController::class, 'show'])->name('tags.show');

    Route::post('query', QueryController::class)->name('query');
});
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Api/V1/QueryController.php routes/api.php
git commit -m "feat: add POST /api/v1/query endpoint for live loop data fetching"
```

---

### Task 3: Update PublicPageController to resolve loop blocks

**Files:**
- Modify: `app/Http/Controllers/PublicPageController.php`

**Step 1: Inject QueryBuilder and update resolveBlocks**

Replace the entire file with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Services\QueryBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function __construct(private QueryBuilder $queryBuilder) {}

    public function show(Request $request, string $slug)
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
                'blocks' => $this->resolveBlocks($page->blocks ?? [], $request->query()),
            ],
            'seo' => $seo,
        ]);
    }

    private function resolveBlocks(array $blocks, array $urlParams = []): array
    {
        return array_map(function ($block) use ($urlParams) {
            // Recurse into container/section children
            if (in_array($block['type'] ?? '', ['container', 'section'], true) && !empty($block['children'])) {
                $block['children'] = $this->resolveBlocks($block['children'], $urlParams);
            }

            // Resolve loop block — query data, embed in block.data.resolved
            if (($block['type'] ?? '') === 'loop') {
                $result = $this->queryBuilder->resolve($block['data'] ?? [], $urlParams);
                $block['data']['resolved'] = $result;
                // Children are templates — do NOT recurse them (no real blocks to resolve)
                return $block;
            }

            // Legacy component block
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
```

**Step 2: Verify the route still works**

The route signature changed from `show(string $slug)` to `show(Request $request, string $slug)`. Laravel resolves the Request via DI automatically — no route change needed.

**Step 3: Commit**

```bash
git add app/Http/Controllers/PublicPageController.php
git commit -m "feat: resolve loop blocks server-side via QueryBuilder in PublicPageController"
```

---

### Task 4: Frontend shared constants + Tailwind safelist

**Files:**
- Create: `resources/js/lib/loopSources.js`
- Create: `resources/js/Components/BlockEditor/safelist.js`

The `safelist.js` is already referenced in `resources/css/app.css` via `@source "../js/Components/BlockEditor/safelist.js"`. If it exists from a prior task, extend it; if not, create it fresh.

**Step 1: Create `resources/js/lib/loopSources.js`**

```js
// Shared constants for Loop block data sources, fields, operators.
// Used by LoopSettings.vue, BlockEditor.vue (ancestry), LoopBlock.vue.

export const SOURCES = [
  { value: 'posts',      label: 'Posts' },
  { value: 'categories', label: 'Categories' },
  { value: 'tags',       label: 'Tags' },
  { value: 'pages',      label: 'Pages' },
]

export const SOURCE_FIELDS = {
  posts:      ['title', 'slug', 'excerpt', 'body', 'featured', 'published_at', 'author_name', 'featured_image_url', 'url'],
  categories: ['name', 'slug', 'description', 'posts_count', 'url'],
  tags:       ['name', 'slug', 'posts_count', 'url'],
  pages:      ['title', 'slug', 'meta_description', 'url'],
}

export const SORT_FIELDS = {
  posts:      ['published_at', 'title', 'created_at'],
  categories: ['name', 'created_at', 'posts_count'],
  tags:       ['name', 'created_at', 'posts_count'],
  pages:      ['title', 'created_at', 'updated_at'],
}

export const FILTER_OPS = [
  { value: '=',         label: 'Equals' },
  { value: '!=',        label: 'Not equals' },
  { value: 'not_empty', label: 'Is not empty' },
  { value: 'empty',     label: 'Is empty' },
]
```

**Step 2: Create `resources/js/Components/BlockEditor/safelist.js`**

This file exists only so Tailwind's scanner can find dynamically-generated class strings. The `@source` directive in `app.css` points here.

```js
// Tailwind CSS safelist — dynamically generated classes that can't be found in source files.
// This file is referenced by @source in resources/css/app.css.
// DO NOT import this file in JS code — it exists only for Tailwind's scanner.

// Loop block grid/gap classes (generated from block.data.columns and block.data.gap)
const _ = [
  'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4',
  'gap-2', 'gap-4', 'gap-6', 'gap-8',
  // Container/Section responsive classes (from resolveResponsive)
  'flex-row', 'flex-col',
  'sm:flex-row', 'sm:flex-col',
  'lg:flex-row', 'lg:flex-col',
  'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4',
  'sm:grid-cols-1', 'sm:grid-cols-2', 'sm:grid-cols-3', 'sm:grid-cols-4',
  'lg:grid-cols-1', 'lg:grid-cols-2', 'lg:grid-cols-3', 'lg:grid-cols-4',
  'h-1', 'h-2', 'h-4', 'h-6', 'h-8', 'h-12', 'h-16', 'h-20', 'h-24', 'h-32', 'h-40', 'h-48',
  'sm:h-1', 'sm:h-2', 'sm:h-4', 'sm:h-6', 'sm:h-8', 'sm:h-12', 'sm:h-16', 'sm:h-20', 'sm:h-24',
  'lg:h-1', 'lg:h-2', 'lg:h-4', 'lg:h-6', 'lg:h-8', 'lg:h-12', 'lg:h-16', 'lg:h-20', 'lg:h-24',
  'pt-2', 'pt-4', 'pt-8', 'pt-12', 'pt-16', 'pt-20', 'pt-24', 'pt-32',
  'pb-2', 'pb-4', 'pb-8', 'pb-12', 'pb-16', 'pb-20', 'pb-24', 'pb-32',
  'px-2', 'px-4', 'px-8', 'px-12', 'px-16',
  'sm:pt-2', 'sm:pt-4', 'sm:pt-8', 'sm:pt-12', 'sm:pt-16',
  'sm:pb-2', 'sm:pb-4', 'sm:pb-8', 'sm:pb-12', 'sm:pb-16',
  'sm:px-2', 'sm:px-4', 'sm:px-8', 'sm:px-12',
  'max-w-sm', 'max-w-md', 'max-w-lg', 'max-w-xl', 'max-w-2xl', 'max-w-4xl', 'max-w-6xl', 'max-w-full',
]
```

**Step 3: Commit**

```bash
git add resources/js/lib/loopSources.js resources/js/Components/BlockEditor/safelist.js
git commit -m "feat: add loopSources constants and Tailwind safelist for dynamic classes"
```

---

### Task 5: useLoopBinding composable + update block renderers

**Files:**
- Create: `resources/js/composables/useLoopBinding.js`
- Modify: `resources/js/Components/Blocks/HeadingBlock.vue`
- Modify: `resources/js/Components/Blocks/ParagraphBlock.vue`
- Modify: `resources/js/Components/Blocks/ImageBlock.vue`
- Modify: `resources/js/Components/Blocks/CtaBlock.vue`

**Step 1: Create `resources/js/composables/useLoopBinding.js`**

```js
import { inject, computed } from 'vue'

/**
 * Resolve a block field, preferring the loop item binding when inside a Loop block.
 *
 * @param {() => Object} getBlock   - getter for the block prop (e.g. () => props.block)
 * @param {string}       fieldName  - the block.data field name (e.g. 'text', 'url', 'content')
 * @returns {ComputedRef<any>}
 *
 * How it works:
 *   1. injects 'loopItem' from the nearest LoopItemProvider ancestor (null if not inside a loop)
 *   2. if block.bindings[fieldName] is set AND loopItem exists, returns loopItem[binding]
 *   3. otherwise falls back to block.data[fieldName]
 */
export function useFieldBinding(getBlock, fieldName) {
  const loopItem = inject('loopItem', null)

  return computed(() => {
    const block   = getBlock()
    const binding = block?.bindings?.[fieldName]
    if (binding && loopItem?.value) {
      return loopItem.value[binding] ?? block?.data?.[fieldName]
    }
    return block?.data?.[fieldName]
  })
}
```

**Step 2: Update `resources/js/Components/Blocks/HeadingBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/HeadingBlock.vue -->
<template>
  <component :is="'h' + block.data.level" class="font-bold leading-tight">
    {{ resolvedText }}
  </component>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedText = useFieldBinding(() => props.block, 'text')
</script>
```

**Step 3: Update `resources/js/Components/Blocks/ParagraphBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/ParagraphBlock.vue -->
<template>
  <div class="prose prose-sm max-w-none dark:prose-invert" v-html="resolvedContent" />
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedContent = useFieldBinding(() => props.block, 'content')
</script>
```

**Step 4: Update `resources/js/Components/Blocks/ImageBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/ImageBlock.vue -->
<template>
  <figure class="my-4">
    <img
      v-if="resolvedUrl"
      :src="resolvedUrl"
      :alt="resolvedAlt || ''"
      class="w-full rounded-lg object-cover"
      @error="onError"
    />
    <div
      v-else
      class="w-full h-32 rounded-lg border-2 border-dashed border-border flex items-center justify-center text-muted-foreground text-sm"
    >
      Image not available
    </div>
    <figcaption v-if="block.data.caption" class="mt-2 text-center text-sm text-muted-foreground">
      {{ block.data.caption }}
    </figcaption>
  </figure>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedUrl = useFieldBinding(() => props.block, 'url')
const resolvedAlt = useFieldBinding(() => props.block, 'alt')
function onError(e) { e.target.style.display = 'none' }
</script>
```

**Step 5: Update `resources/js/Components/Blocks/CtaBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/CtaBlock.vue -->
<template>
  <div class="my-4 rounded-lg border bg-card p-6 text-center">
    <h3 v-if="resolvedHeadline" class="text-xl font-bold mb-2">{{ resolvedHeadline }}</h3>
    <p v-if="block.data.text" class="text-muted-foreground mb-4">{{ block.data.text }}</p>
    <a
      v-if="resolvedButtonUrl"
      :href="resolvedButtonUrl"
      class="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] transition-colors"
    >
      {{ block.data.button_label || 'Learn more' }}
    </a>
  </div>
</template>
<script setup>
import { useFieldBinding } from '@/composables/useLoopBinding.js'
const props = defineProps({ block: { type: Object, required: true } })
const resolvedHeadline  = useFieldBinding(() => props.block, 'headline')
const resolvedButtonUrl = useFieldBinding(() => props.block, 'button_url')
</script>
```

**Step 6: Commit**

```bash
git add resources/js/composables/useLoopBinding.js \
        resources/js/Components/Blocks/HeadingBlock.vue \
        resources/js/Components/Blocks/ParagraphBlock.vue \
        resources/js/Components/Blocks/ImageBlock.vue \
        resources/js/Components/Blocks/CtaBlock.vue
git commit -m "feat: add useFieldBinding composable, update block renderers for loop data binding"
```

---

### Task 6: LoopItemProvider + LoopBlock

**Files:**
- Create: `resources/js/Components/Blocks/LoopItemProvider.vue`
- Create: `resources/js/Components/Blocks/LoopBlock.vue`

**Step 1: Create `resources/js/Components/Blocks/LoopItemProvider.vue`**

A tiny renderless wrapper. `provide('loopItem', ...)` makes the current item available to all descendants without threading props.

```vue
<!-- resources/js/Components/Blocks/LoopItemProvider.vue -->
<script setup>
import { provide, toRef } from 'vue'
const props = defineProps({ item: { type: Object, required: true } })
// Provide as a ref so watchers on loopItem.value in children react to item changes
provide('loopItem', toRef(props, 'item'))
</script>
<template><slot /></template>
```

**Step 2: Create `resources/js/Components/Blocks/LoopBlock.vue`**

```vue
<!-- resources/js/Components/Blocks/LoopBlock.vue -->
<template>
  <div :class="wrapperClass">
    <!-- Items: each wrapped in a LoopItemProvider that scopes the item via provide/inject -->
    <template v-if="items.length">
      <LoopItemProvider
        v-for="item in items"
        :key="item.id ?? item.slug ?? item.name"
        :item="item"
      >
        <BlockRenderer :blocks="block.children ?? []" />
      </LoopItemProvider>
    </template>

    <!-- Loading skeleton -->
    <template v-else-if="isLoading">
      <div
        v-for="i in (block.data.limit ?? 6)"
        :key="i"
        class="h-40 rounded-lg bg-muted animate-pulse"
      />
    </template>

    <!-- Empty state -->
    <p v-else class="col-span-full text-muted-foreground text-sm text-center py-8">
      No items found.
    </p>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import LoopItemProvider from './LoopItemProvider.vue'
import BlockRenderer from '@/Components/BlockRenderer.vue'

const props = defineProps({ block: { type: Object, required: true } })

const page      = usePage()
const items     = ref(props.block.data?.resolved?.items ?? [])
const isLoading = ref(false)

// CSS grid wrapper — columns and gap driven by block.data
const GAP_CLASS = { sm: 'gap-2', md: 'gap-4', lg: 'gap-6', xl: 'gap-8' }
const wrapperClass = computed(() => {
  const cols = props.block.data?.columns ?? 1
  const gap  = GAP_CLASS[props.block.data?.gap ?? 'md'] ?? 'gap-4'
  return `grid grid-cols-${cols} ${gap}`
})

// Does this loop have any filters that depend on URL params?
const hasUrlParamFilters = computed(() =>
  (props.block.data?.filters ?? []).some(f => f.urlParam)
)

// Extract relevant URL param values from the current window URL
function getUrlParams() {
  if (!hasUrlParamFilters.value) return {}
  const keys   = (props.block.data?.filters ?? []).filter(f => f.urlParam).map(f => f.urlParam)
  const search = new URL(window.location.href).searchParams
  return Object.fromEntries(keys.filter(k => search.has(k)).map(k => [k, search.get(k)]))
}

async function fetchItems() {
  isLoading.value = true
  try {
    const { data } = await axios.post('/api/v1/query', {
      source:     props.block.data?.source ?? 'posts',
      filters:    props.block.data?.filters ?? [],
      sort:       props.block.data?.sort    ?? { field: 'published_at', direction: 'desc' },
      limit:      props.block.data?.limit   ?? 12,
      offset:     props.block.data?.offset  ?? 0,
      url_params: getUrlParams(),
    })
    items.value = data.items ?? []
  } catch (err) {
    if (import.meta.env.DEV) console.error('[LoopBlock] fetch error', err)
  } finally {
    isLoading.value = false
  }
}

// Watch for Inertia URL changes (client-side navigation / URL param changes)
// Only set up the watcher when we actually have urlParam filters — avoids unnecessary overhead
if (hasUrlParamFilters.value) {
  watch(
    () => page.url,
    (newUrl, oldUrl) => { if (newUrl !== oldUrl) fetchItems() }
  )
}
</script>
```

**Step 3: Commit**

```bash
git add resources/js/Components/Blocks/LoopItemProvider.vue \
        resources/js/Components/Blocks/LoopBlock.vue
git commit -m "feat: add LoopItemProvider and LoopBlock frontend renderer"
```

---

### Task 7: Update BlockRenderer.vue

Add `loop` to `BLOCK_MAP`, add per-block condition checking via `inject('loopItem')`, and recurse into `loop` children for font loading.

**Files:**
- Modify: `resources/js/Components/BlockRenderer.vue`

**Step 1: Replace the file**

```vue
<!-- resources/js/Components/BlockRenderer.vue -->
<template>
  <div class="space-y-4">
    <template v-for="block in blocks" :key="block.id">
      <!-- Skip block if its visibility condition evaluates to false -->
      <template v-if="isVisible(block)">
        <component
          v-if="block.customCss"
          :is="'style'"
        >#{{ block.customId ? CSS.escape(block.customId) : 'block-' + block.id }} { {{ sanitizeCss(block.customCss) }} }</component>
        <div
          :id="block.customId || `block-${block.id}`"
          :class="block.customClasses || undefined"
          :style="block.fontFamily ? { fontFamily: `'${block.fontFamily}', sans-serif` } : undefined"
        >
          <component
            :is="BLOCK_MAP[block.type]"
            :block="block"
          />
        </div>
      </template>
    </template>
  </div>
</template>

<script setup>
import { inject, onMounted, watch } from 'vue'
import ParagraphBlock from '@/Components/Blocks/ParagraphBlock.vue'
import HeadingBlock   from '@/Components/Blocks/HeadingBlock.vue'
import ImageBlock     from '@/Components/Blocks/ImageBlock.vue'
import QuoteBlock     from '@/Components/Blocks/QuoteBlock.vue'
import CodeBlock      from '@/Components/Blocks/CodeBlock.vue'
import GalleryBlock   from '@/Components/Blocks/GalleryBlock.vue'
import VideoBlock     from '@/Components/Blocks/VideoBlock.vue'
import DividerBlock   from '@/Components/Blocks/DividerBlock.vue'
import CtaBlock       from '@/Components/Blocks/CtaBlock.vue'
import HtmlBlock      from '@/Components/Blocks/HtmlBlock.vue'
import PostListBlock  from '@/Components/Blocks/PostListBlock.vue'
import ContainerBlock from '@/Components/Blocks/ContainerBlock.vue'
import SectionBlock   from '@/Components/Blocks/SectionBlock.vue'
import SpacerBlock    from '@/Components/Blocks/SpacerBlock.vue'
import LoopBlock      from '@/Components/Blocks/LoopBlock.vue'

const props = defineProps({ blocks: { type: Array, default: () => [] } })

function sanitizeCss(css) {
  return css.replace(/<\/?style/gi, '')
}

const BLOCK_MAP = {
  paragraph: ParagraphBlock,
  heading:   HeadingBlock,
  image:     ImageBlock,
  quote:     QuoteBlock,
  code:      CodeBlock,
  gallery:   GalleryBlock,
  video:     VideoBlock,
  divider:   DividerBlock,
  cta:       CtaBlock,
  html:      HtmlBlock,
  component: PostListBlock,
  container: ContainerBlock,
  section:   SectionBlock,
  spacer:    SpacerBlock,
  loop:      LoopBlock,
}

// Injected by LoopItemProvider when this renderer is inside a loop iteration
const loopItem = inject('loopItem', null)

// Evaluate a block's visibility condition against the current loop item.
// If the block has no condition, or we're not inside a loop, always show it.
function isVisible(block) {
  const c = block.condition
  if (!c || !loopItem?.value) return true
  const v = loopItem.value[c.field]
  switch (c.op) {
    case '===':       return v === c.value
    case '!==':       return v !== c.value
    case 'not_empty': return !!v
    case 'empty':     return !v
    default:          return true
  }
}

// Google Fonts loader
const loadedFonts = new Set()

function loadFont(family) {
  if (!family || loadedFonts.has(family)) return
  loadedFonts.add(family)
  const link = document.createElement('link')
  link.rel  = 'stylesheet'
  link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@400;600;700&display=swap`
  document.head.appendChild(link)
}

function loadFontsFromBlocks(blocks) {
  for (const block of blocks) {
    if (block.fontFamily) loadFont(block.fontFamily)
    if (['container', 'section', 'loop'].includes(block.type) && block.children?.length) {
      loadFontsFromBlocks(block.children)
    }
  }
}

onMounted(() => loadFontsFromBlocks(props.blocks))
watch(() => props.blocks, (val) => loadFontsFromBlocks(val), { deep: true })
</script>
```

**Step 2: Commit**

```bash
git add resources/js/Components/BlockRenderer.vue
git commit -m "feat: add loop to BlockRenderer — condition checking, font recursion"
```

---

### Task 8: LoopSettings.vue

The settings panel for the loop block. Query, Sort/Limit, and Appearance sections.

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/LoopSettings.vue`

**Step 1: Create the file**

```vue
<!-- resources/js/Components/BlockEditor/blocks/LoopSettings.vue -->
<template>
  <div class="space-y-4">

    <!-- ── Data Source ─────────────────────────────────────────────── -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Data Source</label>
      <SelectBox
        :model-value="block.data.source"
        :data="SOURCES"
        @update:model-value="onSourceChange"
      />
    </div>

    <!-- ── Filters ────────────────────────────────────────────────── -->
    <div>
      <div class="flex items-center justify-between mb-2">
        <label class="text-xs font-medium text-muted-foreground">Filters</label>
        <button
          type="button"
          class="text-xs text-primary hover:underline"
          @click="addFilter"
        >+ Add filter</button>
      </div>

      <div
        v-for="(filter, i) in filters"
        :key="i"
        class="mb-2 p-2 rounded-md border bg-muted/30 space-y-1.5"
      >
        <!-- Field -->
        <SelectBox
          :model-value="filter.field"
          :data="filterableFields"
          placeholder="Field..."
          @update:model-value="v => updateFilter(i, { field: v })"
        />

        <!-- Operator -->
        <SelectBox
          :model-value="filter.op"
          :data="FILTER_OPS"
          @update:model-value="v => updateFilter(i, { op: v })"
        />

        <!-- Value or URL param (hidden when op is not_empty / empty) -->
        <template v-if="filter.op !== 'not_empty' && filter.op !== 'empty'">
          <label class="flex items-center gap-2 text-xs cursor-pointer">
            <input
              type="checkbox"
              :checked="!!filter.urlParam"
              class="accent-primary"
              @change="toggleUrlParam(i, $event)"
            />
            From URL param
          </label>

          <input
            v-if="filter.urlParam"
            :value="filter.urlParam"
            type="text"
            placeholder="param name (e.g. category)"
            class="w-full rounded border bg-background px-2 py-1 text-xs"
            @input="updateFilter(i, { urlParam: $event.target.value })"
          />
          <input
            v-else
            :value="filter.value ?? ''"
            type="text"
            placeholder="Value..."
            class="w-full rounded border bg-background px-2 py-1 text-xs"
            @input="updateFilter(i, { value: $event.target.value })"
          />
        </template>

        <button
          type="button"
          class="text-xs text-destructive hover:underline"
          @click="removeFilter(i)"
        >Remove</button>
      </div>
    </div>

    <!-- ── Sort ───────────────────────────────────────────────────── -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Sort By</label>
      <div class="flex gap-2">
        <SelectBox
          :model-value="block.data.sort?.field"
          :data="sortFieldOptions"
          class="flex-1"
          @update:model-value="v => emitData({ sort: { ...block.data.sort, field: v } })"
        />
        <SelectBox
          :model-value="block.data.sort?.direction ?? 'desc'"
          :data="[{ value: 'desc', label: 'Desc' }, { value: 'asc', label: 'Asc' }]"
          class="w-[4.5rem]"
          @update:model-value="v => emitData({ sort: { ...block.data.sort, direction: v } })"
        />
      </div>
    </div>

    <!-- ── Limit + Offset ─────────────────────────────────────────── -->
    <div class="flex gap-2">
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Limit</label>
        <input
          type="number"
          min="1"
          max="100"
          :value="block.data.limit ?? 12"
          class="w-full rounded border bg-background px-2 py-1.5 text-sm"
          @input="emitData({ limit: parseInt($event.target.value) || 12 })"
        />
      </div>
      <div class="flex-1">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Offset</label>
        <input
          type="number"
          min="0"
          :value="block.data.offset ?? 0"
          class="w-full rounded border bg-background px-2 py-1.5 text-sm"
          @input="emitData({ offset: parseInt($event.target.value) || 0 })"
        />
      </div>
    </div>

    <!-- ── Appearance ─────────────────────────────────────────────── -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Columns per row</label>
      <SelectBox
        :model-value="block.data.columns ?? 1"
        :data="[1, 2, 3, 4].map(n => ({ value: n, label: `${n} col${n > 1 ? 's' : ''}` }))"
        @update:model-value="v => emitData({ columns: Number(v) })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Gap</label>
      <SelectBox
        :model-value="block.data.gap ?? 'md'"
        :data="[
          { value: 'sm', label: 'Small' },
          { value: 'md', label: 'Medium' },
          { value: 'lg', label: 'Large' },
          { value: 'xl', label: 'X-Large' },
        ]"
        @update:model-value="v => emitData({ gap: v })"
      />
    </div>

  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'
import { SOURCES, SOURCE_FIELDS, SORT_FIELDS, FILTER_OPS } from '@/lib/loopSources.js'

const props = defineProps({ block: { type: Object, required: true } })
const emit  = defineEmits(['update'])

const source = computed(() => props.block.data?.source ?? 'posts')
const filters = computed(() => props.block.data?.filters ?? [])

const filterableFields = computed(() =>
  (SOURCE_FIELDS[source.value] ?? []).map(f => ({ value: f, label: f }))
)

const sortFieldOptions = computed(() =>
  (SORT_FIELDS[source.value] ?? []).map(f => ({ value: f, label: f }))
)

function emitData(patch) {
  emit('update', { id: props.block.id, data: patch })
}

function onSourceChange(v) {
  // Clear filters when source changes — field names differ per source
  emit('update', { id: props.block.id, data: { source: v, filters: [] } })
}

function addFilter() {
  emitData({ filters: [...filters.value, { field: '', op: '=', value: '' }] })
}

function removeFilter(i) {
  const updated = filters.value.filter((_, idx) => idx !== i)
  emitData({ filters: updated })
}

function updateFilter(i, patch) {
  const updated = filters.value.map((f, idx) => idx === i ? { ...f, ...patch } : f)
  emitData({ filters: updated })
}

function toggleUrlParam(i, e) {
  if (e.target.checked) {
    updateFilter(i, { urlParam: '', value: undefined })
  } else {
    const updated = filters.value.map((f, idx) => {
      if (idx !== i) return f
      const { urlParam, ...rest } = f
      return { ...rest, value: '' }
    })
    emitData({ filters: updated })
  }
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/LoopSettings.vue
git commit -m "feat: add LoopSettings panel (source, filters, sort, limit, appearance)"
```

---

### Task 9: DynamicField + ConditionSettings + update block settings panels

**Files:**
- Create: `resources/js/Components/BlockEditor/blocks/DynamicField.vue`
- Create: `resources/js/Components/BlockEditor/blocks/ConditionSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/HeadingSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/ImageSettings.vue`
- Modify: `resources/js/Components/BlockEditor/blocks/CtaSettings.vue`

**Step 1: Create `DynamicField.vue`**

A reusable wrapper that adds a "Bind" toggle to any settings field. When bound, shows a field picker dropdown instead of the static input (passed via slot).

```vue
<!-- resources/js/Components/BlockEditor/blocks/DynamicField.vue -->
<template>
  <div>
    <div class="flex items-center justify-between mb-1">
      <label class="text-xs font-medium text-muted-foreground">{{ label }}</label>
      <button
        v-if="loopFields.length"
        type="button"
        class="text-[10px] px-1.5 py-0.5 rounded border transition-colors"
        :class="isBound
          ? 'border-primary text-primary bg-primary/10'
          : 'border-border text-muted-foreground hover:border-primary'"
        @click="toggleBinding"
      >{{ isBound ? 'Dynamic ✓' : 'Bind' }}</button>
    </div>

    <!-- Bound: field picker replaces the static input -->
    <SelectBox
      v-if="isBound"
      :model-value="boundField"
      :data="fieldOptions"
      placeholder="Pick a field..."
      @update:model-value="v => emit('bind', fieldName, v)"
    />

    <!-- Static: whatever the parent renders in the slot -->
    <slot v-else />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  label:      { type: String, required: true },
  fieldName:  { type: String, required: true },
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})

const emit = defineEmits(['bind', 'unbind'])

const isBound     = computed(() => !!props.block.bindings?.[props.fieldName])
const boundField  = computed(() => props.block.bindings?.[props.fieldName] ?? null)
const fieldOptions = computed(() => props.loopFields.map(f => ({ value: f, label: f })))

function toggleBinding() {
  if (isBound.value) {
    emit('unbind', props.fieldName)
  } else {
    // Start with empty string — user picks from dropdown
    emit('bind', props.fieldName, '')
  }
}
</script>
```

**Step 2: Create `ConditionSettings.vue`**

Shown below the AdvancedSettings panel when the selected block is inside a Loop.

```vue
<!-- resources/js/Components/BlockEditor/blocks/ConditionSettings.vue -->
<template>
  <div class="space-y-2 pt-3 border-t mt-3">
    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Visibility Condition</p>

    <label class="flex items-center gap-2 text-xs cursor-pointer">
      <input
        type="checkbox"
        :checked="hasCondition"
        class="accent-primary"
        @change="toggleCondition"
      />
      Show only if…
    </label>

    <template v-if="hasCondition">
      <SelectBox
        :model-value="condition.field"
        :data="fieldOptions"
        placeholder="Field..."
        @update:model-value="v => update({ field: v })"
      />
      <SelectBox
        :model-value="condition.op"
        :data="OPS"
        @update:model-value="v => update({ op: v })"
      />
      <input
        v-if="condition.op !== 'not_empty' && condition.op !== 'empty'"
        :value="condition.value ?? ''"
        type="text"
        placeholder="Value..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="update({ value: $event.target.value })"
      />
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import SelectBox from '@/Components/SelectBox.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})

const emit = defineEmits(['update'])

const OPS = [
  { value: '===',       label: 'Equals' },
  { value: '!==',       label: 'Not equals' },
  { value: 'not_empty', label: 'Is not empty' },
  { value: 'empty',     label: 'Is empty' },
]

const fieldOptions = computed(() => props.loopFields.map(f => ({ value: f, label: f })))
const hasCondition = computed(() => !!props.block.condition)
const condition    = computed(() => props.block.condition ?? { field: '', op: '===', value: '' })

function toggleCondition(e) {
  // 'condition' is a top-level block attr (not inside data) — goes into updateBlock(...attrs)
  emit('update', {
    id:        props.block.id,
    condition: e.target.checked ? { field: '', op: '===', value: '' } : null,
  })
}

function update(patch) {
  emit('update', { id: props.block.id, condition: { ...condition.value, ...patch } })
}
</script>
```

**Step 3: Update `HeadingSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/HeadingSettings.vue -->
<template>
  <div class="space-y-3">
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Level</label>
      <SelectBox
        :model-value="block.data.level"
        :data="[1,2,3,4,5,6].map(n => ({ value: n, label: `H${n}` }))"
        @update:model-value="v => emit('update', { id: block.id, data: { level: Number(v) } })"
      />
    </div>

    <DynamicField
      label="Text"
      field-name="text"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.text"
        type="text"
        placeholder="Heading text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </DynamicField>
  </div>
</template>

<script setup>
import SelectBox   from '@/Components/SelectBox.vue'
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 4: Update `ParagraphSettings.vue`**

```vue
<!-- resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue -->
<template>
  <div>
    <DynamicField
      label="Content"
      field-name="content"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <TiptapEditor
        :model-value="block.data.content"
        @update:model-value="emit('update', { id: block.id, data: { content: $event } })"
      />
    </DynamicField>
  </div>
</template>

<script setup>
import TiptapEditor from '@/Components/TiptapEditor.vue'
import DynamicField  from './DynamicField.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 5: Update `ImageSettings.vue`**

Add `loopFields` prop and DynamicField wrappers around the URL and alt fields. The media picker button is hidden when `url` field is bound (the loop item provides the URL).

```vue
<!-- resources/js/Components/BlockEditor/blocks/ImageSettings.vue -->
<template>
  <div class="space-y-3">
    <!-- URL via DynamicField — media picker shown only in static mode -->
    <DynamicField
      label="Image URL"
      field-name="url"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <div>
        <div v-if="block.data.url" class="rounded-md overflow-hidden border mb-2">
          <img :src="block.data.url" :alt="block.data.alt" class="w-full object-cover max-h-32" />
        </div>
        <button
          type="button"
          class="w-full rounded-md border border-dashed px-3 py-2 text-xs text-muted-foreground hover:border-primary hover:text-primary transition-colors"
          @click="showPicker = true"
        >
          {{ block.data.url ? 'Change image' : 'Select image' }}
        </button>
        <MediaPicker v-model="showPicker" @select="onMediaSelect" />
      </div>
    </DynamicField>

    <DynamicField
      label="Alt text"
      field-name="alt"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.alt"
        type="text"
        placeholder="Describe the image..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { alt: $event.target.value } })"
      />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Caption (optional)</label>
      <input
        :value="block.data.caption"
        type="text"
        placeholder="Caption..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { caption: $event.target.value } })"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import MediaPicker  from '@/Components/MediaPicker.vue'
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

const showPicker = ref(false)

function onMediaSelect(media) {
  showPicker.value = false
  emit('update', { id: props.block.id, data: { media_id: media.id, url: media.url, alt: media.alt ?? '' } })
}
function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 6: Update `CtaSettings.vue`**

Add DynamicField to `headline` and `button_url` fields; `text` and `button_label` remain static (no binding).

```vue
<!-- resources/js/Components/BlockEditor/blocks/CtaSettings.vue -->
<template>
  <div class="space-y-3">
    <DynamicField
      label="Headline"
      field-name="headline"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.headline"
        type="text"
        placeholder="Bold headline..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { headline: $event.target.value } })"
      />
    </DynamicField>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Body text</label>
      <input
        :value="block.data.text"
        type="text"
        placeholder="Supporting text..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { text: $event.target.value } })"
      />
    </div>

    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Button label</label>
      <input
        :value="block.data.button_label"
        type="text"
        placeholder="Click here"
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_label: $event.target.value } })"
      />
    </div>

    <DynamicField
      label="Button URL"
      field-name="button_url"
      :block="block"
      :loop-fields="loopFields"
      @bind="onBind"
      @unbind="onUnbind"
    >
      <input
        :value="block.data.button_url"
        type="url"
        placeholder="https://..."
        class="w-full rounded-md border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @input="emit('update', { id: block.id, data: { button_url: $event.target.value } })"
      />
    </DynamicField>
  </div>
</template>

<script setup>
import DynamicField from './DynamicField.vue'

const props = defineProps({
  block:      { type: Object, required: true },
  loopFields: { type: Array,  default: () => [] },
})
const emit = defineEmits(['update'])

function onBind(fieldName, loopField) {
  emit('update', { id: props.block.id, bindings: { ...props.block.bindings, [fieldName]: loopField } })
}
function onUnbind(fieldName) {
  const b = { ...(props.block.bindings ?? {}) }
  delete b[fieldName]
  emit('update', { id: props.block.id, bindings: Object.keys(b).length ? b : undefined })
}
</script>
```

**Step 7: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/DynamicField.vue \
        resources/js/Components/BlockEditor/blocks/ConditionSettings.vue \
        resources/js/Components/BlockEditor/blocks/HeadingSettings.vue \
        resources/js/Components/BlockEditor/blocks/ParagraphSettings.vue \
        resources/js/Components/BlockEditor/blocks/ImageSettings.vue \
        resources/js/Components/BlockEditor/blocks/CtaSettings.vue
git commit -m "feat: add DynamicField, ConditionSettings, update block settings with loop binding UI"
```

---

### Task 10: BlockEditor + BlockLayers — loop ancestry + register loop

**Files:**
- Modify: `resources/js/Components/BlockEditor/BlockEditor.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`

**Step 1: Update `BlockEditor.vue`**

Add `findLoopAncestor` helper, compute `loopAncestor` and `loopFields`, update `hasChildren` to include `loop`, and pass `loopFields` to `BlockLayers`.

Replace the script section (template is unchanged):

```vue
<!-- resources/js/Components/BlockEditor/BlockEditor.vue -->
<template>
  <div class="flex border rounded-xl overflow-hidden bg-background" style="min-height: 500px">
    <BlockTypePanel :is-admin="isAdmin" />
    <BlockCanvas
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      @select="selectBlock"
      @reorder="onReorder"
      @update-children="onUpdateChildren"
    />
    <BlockLayers
      :blocks="localBlocks"
      :selected-id="selectedBlockId"
      :selected-block="selectedBlock"
      :is-admin="isAdmin"
      :meta="meta"
      :loop-fields="loopFields"
      @select="selectBlock"
      @remove="removeBlock"
      @reorder="onReorder"
      @update="updateBlock"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BlockTypePanel from './BlockTypePanel.vue'
import BlockCanvas    from './BlockCanvas.vue'
import BlockLayers    from './BlockLayers.vue'
import { SOURCE_FIELDS } from '@/lib/loopSources.js'

const props = defineProps({
  modelValue: { type: Array,   default: () => [] },
  isAdmin:    { type: Boolean, default: false },
  meta:       { type: Object,  default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

const localBlocks     = ref([...(props.modelValue ?? [])])
const selectedBlockId = ref(null)

// ── Recursive helpers ────────────────────────────────────────────────────────

function hasChildren(block) {
  return block.type === 'container' || block.type === 'section' || block.type === 'loop'
}

function findBlock(blocks, id) {
  if (!id) return null
  for (const b of blocks) {
    if (b.id === id) return b
    if (hasChildren(b) && b.children?.length) {
      const found = findBlock(b.children, id)
      if (found) return found
    }
  }
  return null
}

/**
 * Returns the nearest loop block ancestor of the target block id.
 * Returns null if the block is not inside a loop.
 * Returns undefined if the block wasn't found in this subtree (internal use).
 */
function findLoopAncestor(blocks, targetId, currentLoop = null) {
  for (const b of blocks) {
    if (b.id === targetId) return currentLoop
    const nextLoop = b.type === 'loop' ? b : currentLoop
    if (hasChildren(b) && b.children?.length) {
      const found = findLoopAncestor(b.children, targetId, nextLoop)
      if (found !== undefined) return found
    }
  }
  return undefined
}

function updateBlockInList(blocks, id, data, attrs) {
  return blocks.map(b => {
    if (b.id === id) {
      return {
        ...b,
        ...attrs,
        ...(data !== undefined ? { data: { ...b.data, ...data } } : {}),
      }
    }
    if (hasChildren(b) && b.children?.length) {
      return { ...b, children: updateBlockInList(b.children, id, data, attrs) }
    }
    return b
  })
}

function removeFromList(blocks, id) {
  return blocks
    .filter(b => b.id !== id)
    .map(b => {
      if (hasChildren(b) && b.children?.length) {
        return { ...b, children: removeFromList(b.children, id) }
      }
      return b
    })
}

// ── Computed ─────────────────────────────────────────────────────────────────

const selectedBlock = computed(() =>
  findBlock(localBlocks.value, selectedBlockId.value)
)

// The nearest loop block that is an ancestor of the selected block (or null)
const loopAncestor = computed(() => {
  if (!selectedBlockId.value) return null
  const result = findLoopAncestor(localBlocks.value, selectedBlockId.value)
  return result ?? null
})

// Field names exposed by the loop ancestor's source — used to populate binding dropdowns
const loopFields = computed(() => {
  if (!loopAncestor.value) return []
  return SOURCE_FIELDS[loopAncestor.value.data?.source ?? 'posts'] ?? []
})

// ── Sync ─────────────────────────────────────────────────────────────────────

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal === localBlocks.value) return
    localBlocks.value = [...(newVal ?? [])]
    if (!findBlock(localBlocks.value, selectedBlockId.value)) {
      selectedBlockId.value = null
    }
  }
)

// ── Mutations ─────────────────────────────────────────────────────────────────

function selectBlock(id) { selectedBlockId.value = id }

function onReorder(newList) {
  localBlocks.value = newList
  emit('update:modelValue', localBlocks.value)
}

function removeBlock(id) {
  const block = findBlock(localBlocks.value, id)
  if (block) {
    const hasContent = Object.values(block.data ?? {}).some(v =>
      v !== null && v !== '' && !(Array.isArray(v) && v.length === 0)
    )
    const hasChildBlocks = (block.children?.length ?? 0) > 0
    if ((hasContent || hasChildBlocks) && !confirm('Remove this block? Its content will be lost.')) return
  }
  localBlocks.value = removeFromList(localBlocks.value, id)
  if (selectedBlockId.value === id) selectedBlockId.value = null
  emit('update:modelValue', localBlocks.value)
}

function updateBlock({ id, data, ...attrs }) {
  localBlocks.value = updateBlockInList(localBlocks.value, id, data, attrs)
  emit('update:modelValue', localBlocks.value)
}

function onUpdateChildren({ id, children }) {
  localBlocks.value = updateBlockInList(localBlocks.value, id, undefined, { children })
  emit('update:modelValue', localBlocks.value)
}
</script>
```

**Step 2: Update `BlockLayers.vue`**

Add `loopFields` prop, pass it to settings components, add `ConditionSettings` below `AdvancedSettings`, register `loop` in LABELS and COMPONENT_MAP.

```vue
<!-- resources/js/Components/BlockEditor/BlockLayers.vue -->
<template>
  <div class="w-64 shrink-0 border-l flex flex-col bg-sidebar overflow-hidden">

    <!-- Layers list -->
    <div class="flex flex-col shrink-0" style="max-height: 40%">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Layers</p>
      </div>

      <div v-if="draggableBlocks.length === 0" class="px-3 py-4 text-xs text-muted-foreground text-center">
        No blocks yet
      </div>

      <VueDraggable
        v-else
        v-model="draggableBlocks"
        tag="div"
        class="overflow-y-auto p-1.5 space-y-0.5"
        handle=".layer-handle"
        :animation="150"
      >
        <div v-for="block in draggableBlocks" :key="block.id">
          <LayerItem
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @remove="$emit('remove', $event)"
          />
        </div>
      </VueDraggable>
    </div>

    <!-- Settings panel -->
    <div class="flex-1 flex flex-col border-t overflow-hidden">
      <div class="px-3 py-2 border-b shrink-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
          {{ selectedBlock ? blockLabel(selectedBlock.type) + ' Settings' : 'Settings' }}
        </p>
      </div>

      <div class="flex-1 overflow-y-auto p-3">
        <div v-if="!selectedBlock" class="h-full flex items-center justify-center">
          <p class="text-xs text-muted-foreground text-center">Select a block<br>to edit its settings</p>
        </div>

        <div v-else-if="selectedBlock.type === 'html' && !isAdmin" class="rounded-md border border-dashed p-4 text-center">
          <p class="text-xs text-muted-foreground">HTML blocks are admin-only and cannot be edited here.</p>
        </div>

        <template v-else>
          <component
            :is="settingsComponent"
            :block="selectedBlock"
            :is-admin="isAdmin"
            :meta="meta"
            :loop-fields="loopFields"
            @update="$emit('update', $event)"
          />
          <AdvancedSettings
            :block="selectedBlock"
            @update="$emit('update', $event)"
          />
          <!-- Condition settings — only shown when block is inside a Loop -->
          <ConditionSettings
            v-if="loopFields.length"
            :block="selectedBlock"
            :loop-fields="loopFields"
            @update="$emit('update', $event)"
          />
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import LayerItem         from './LayerItem.vue'
import AdvancedSettings  from './blocks/AdvancedSettings.vue'
import ConditionSettings from './blocks/ConditionSettings.vue'
import ContainerSettings from './blocks/ContainerSettings.vue'
import SectionSettings   from './blocks/SectionSettings.vue'
import SpacerSettings    from './blocks/SpacerSettings.vue'
import LoopSettings      from './blocks/LoopSettings.vue'
import HeadingSettings   from './blocks/HeadingSettings.vue'
import ParagraphSettings from './blocks/ParagraphSettings.vue'
import ImageSettings     from './blocks/ImageSettings.vue'
import QuoteSettings     from './blocks/QuoteSettings.vue'
import CodeSettings      from './blocks/CodeSettings.vue'
import GallerySettings   from './blocks/GallerySettings.vue'
import VideoSettings     from './blocks/VideoSettings.vue'
import DividerSettings   from './blocks/DividerSettings.vue'
import CtaSettings       from './blocks/CtaSettings.vue'
import HtmlSettings      from './blocks/HtmlSettings.vue'
import ComponentSettings from './blocks/ComponentSettings.vue'

const props = defineProps({
  blocks:        { type: Array,   default: () => [] },
  selectedId:    { type: String,  default: null },
  selectedBlock: { type: Object,  default: null },
  isAdmin:       { type: Boolean, default: false },
  meta:          { type: Object,  default: () => ({}) },
  loopFields:    { type: Array,   default: () => [] },
})

const emit = defineEmits(['select', 'remove', 'reorder', 'update'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
}

const COMPONENT_MAP = {
  paragraph: ParagraphSettings, heading: HeadingSettings, image: ImageSettings,
  quote: QuoteSettings, code: CodeSettings, gallery: GallerySettings,
  video: VideoSettings, divider: DividerSettings, cta: CtaSettings,
  html: HtmlSettings, component: ComponentSettings, container: ContainerSettings,
  section: SectionSettings, spacer: SpacerSettings, loop: LoopSettings,
}

const settingsComponent = computed(() =>
  props.selectedBlock ? COMPONENT_MAP[props.selectedBlock.type] ?? null : null
)

function blockLabel(type) {
  return LABELS[type] ?? type
}
</script>
```

**Step 3: Update `BlockTypePanel.vue`**

Add `loop` to `ALL_TYPES` and `DEFAULT_DATA`. Also add `loop` to the `cloneBlock` children initializer (loop blocks get `children: []`).

In the `<script setup>` section, add the `Repeat2` icon import and entries:

```js
// Add to existing imports from 'lucide-vue-next':
import { /* existing ... */ Repeat2 } from 'lucide-vue-next'

// Add to ALL_TYPES array:
{ type: 'loop', label: 'Loop', icon: Repeat2 },

// Add to DEFAULT_DATA:
loop: {
  source:  'posts',
  filters: [],
  sort:    { field: 'published_at', direction: 'desc' },
  limit:   6,
  offset:  0,
  columns: 1,
  gap:     'md',
},

// Update cloneBlock to include loop in the children initializer:
...(typeDef.type === 'container' || typeDef.type === 'section' || typeDef.type === 'loop'
  ? { children: [] }
  : {}),
```

Full updated `BlockTypePanel.vue`:

```vue
<!-- resources/js/Components/BlockEditor/BlockTypePanel.vue -->
<template>
  <div class="w-48 shrink-0 border-r flex flex-col bg-sidebar">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Add Block</p>
    </div>

    <VueDraggable
      v-model="typeList"
      tag="div"
      class="flex-1 overflow-y-auto p-2.5 grid grid-cols-2 gap-2 content-start"
      :group="{ name: 'canvas', pull: 'clone', put: false }"
      :sort="false"
      :clone="cloneBlock"
      :animation="150"
    >
      <div
        v-for="btype in typeList"
        :key="btype.type"
        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-border bg-background px-2 py-4 cursor-grab active:cursor-grabbing hover:border-primary hover:text-primary transition-colors select-none"
      >
        <component :is="btype.icon" class="w-5 h-5 shrink-0" />
        <span class="text-xs leading-none">{{ btype.label }}</span>
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import {
  AlignLeft,
  Heading2,
  ImageIcon,
  Quote,
  Code2,
  LayoutGrid,
  Video,
  Minus,
  MousePointerClick,
  FileCode,
  Puzzle,
  LayoutTemplate,
  Rows2,
  ArrowUpDown,
  Repeat2,
} from 'lucide-vue-next'

const props = defineProps({
  isAdmin: { type: Boolean, default: false },
})

const ALL_TYPES = [
  { type: 'paragraph', label: 'Paragraph', icon: AlignLeft },
  { type: 'heading',   label: 'Heading',   icon: Heading2 },
  { type: 'image',     label: 'Image',     icon: ImageIcon },
  { type: 'quote',     label: 'Quote',     icon: Quote },
  { type: 'code',      label: 'Code',      icon: Code2 },
  { type: 'gallery',   label: 'Gallery',   icon: LayoutGrid },
  { type: 'video',     label: 'Video',     icon: Video },
  { type: 'divider',   label: 'Divider',   icon: Minus },
  { type: 'cta',       label: 'CTA',       icon: MousePointerClick },
  { type: 'html',      label: 'HTML',      icon: FileCode, adminOnly: true },
  { type: 'component', label: 'Component', icon: Puzzle },
  { type: 'container', label: 'Container', icon: LayoutTemplate },
  { type: 'section',   label: 'Section',   icon: Rows2 },
  { type: 'spacer',    label: 'Spacer',    icon: ArrowUpDown },
  { type: 'loop',      label: 'Loop',      icon: Repeat2 },
]

const typeList = computed({
  get: () => ALL_TYPES.filter(t => !t.adminOnly || props.isAdmin),
  set: () => {},
})

const DEFAULT_DATA = {
  paragraph: { content: '' },
  heading:   { level: 2, text: '' },
  image:     { media_id: null, url: '', caption: '', alt: '' },
  quote:     { text: '', attribution: '' },
  code:      { code: '', language: 'javascript' },
  gallery:   { items: [] },
  video:     { url: '', caption: '' },
  divider:   { style: 'line' },
  cta:       { headline: '', text: '', button_label: '', button_url: '' },
  html:      { content: '' },
  component: { component: 'post-list', limit: 6, offset: 0, order: 'latest', featured_only: false, category_ids: [], tag_ids: [] },
  container: { direction: 'row', wrap: true, gap: 4, justify: 'start', align: 'start', maxWidth: 'full', padding: 4 },
  section: {
    bgType: 'none',
    bgColor: '#ffffff',
    bgImage: { url: '', position: 'center', size: 'cover' },
    bgGradient: { from: '#3b4252', to: '#4c566a', direction: 'to-r' },
    fullWidth: false,
    innerMaxWidth: 'xl',
    paddingY: { default: 16, sm: 8 },
    paddingX: { default: 8,  sm: 4 },
    minHeight: 'auto',
  },
  spacer: {
    height: { default: 8, sm: 4 },
  },
  loop: {
    source:  'posts',
    filters: [],
    sort:    { field: 'published_at', direction: 'desc' },
    limit:   6,
    offset:  0,
    columns: 1,
    gap:     'md',
  },
}

function cloneBlock(typeDef) {
  const id = typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID()
    : Math.random().toString(36).slice(2) + Date.now().toString(36)
  return {
    id,
    type: typeDef.type,
    data: { ...(DEFAULT_DATA[typeDef.type] ?? {}) },
    customId: '',
    customClasses: '',
    customCss: '',
    fontFamily: '',
    ...(typeDef.type === 'container' || typeDef.type === 'section' || typeDef.type === 'loop'
      ? { children: [] }
      : {}),
  }
}
</script>
```

**Step 4: Commit**

```bash
git add resources/js/Components/BlockEditor/BlockEditor.vue \
        resources/js/Components/BlockEditor/BlockLayers.vue \
        resources/js/Components/BlockEditor/BlockTypePanel.vue
git commit -m "feat: wire loop ancestry detection, loopFields prop, register loop in editor"
```

---

### Task 11: EditorLoopBlock + update BlockCanvas

**Files:**
- Create: `resources/js/Components/BlockEditor/EditorLoopBlock.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`

**Step 1: Create `EditorLoopBlock.vue`**

Modelled exactly on `EditorSectionBlock.vue` but with teal styling and a "Loop — Source" label.

```vue
<!-- resources/js/Components/BlockEditor/EditorLoopBlock.vue -->
<template>
  <div class="border-2 border-dashed border-teal-400/60 rounded-lg p-2 relative min-h-[60px]">
    <span class="absolute top-1 left-1 text-[10px] text-teal-500 font-semibold uppercase tracking-wider select-none">
      Loop — {{ SOURCE_LABELS[block.data?.source] ?? block.data?.source ?? 'Posts' }}
    </span>

    <VueDraggable
      v-model="localChildren"
      tag="div"
      class="pt-4 min-h-[40px] space-y-1.5"
      :group="{ name: 'canvas' }"
      :animation="150"
      handle=".child-drag-handle"
      ghost-class="opacity-40"
      @add="onAdd"
    >
      <div
        v-for="child in localChildren"
        :key="child.id"
        class="group relative flex items-center gap-2 rounded-md border bg-background px-2 py-1.5 cursor-pointer text-xs transition-colors"
        :class="child.id === selectedId
          ? 'border-primary ring-1 ring-primary'
          : 'border-border hover:border-muted-foreground'"
        @click.stop="$emit('select', child.id)"
      >
        <span class="child-drag-handle cursor-grab active:cursor-grabbing text-muted-foreground shrink-0" @click.stop>
          <GripVertical class="w-3 h-3" />
        </span>
        <div class="flex-1 min-w-0 overflow-hidden pointer-events-none">
          <component
            v-if="BLOCK_MAP[child.type]"
            :is="BLOCK_MAP[child.type]"
            :block="child"
            class="text-xs scale-90 origin-left"
          />
          <span v-else class="text-muted-foreground text-xs">{{ LABELS[child.type] ?? child.type }}</span>
        </div>
      </div>

      <div
        v-if="localChildren.length === 0"
        class="text-center py-2 text-xs text-muted-foreground/60 pointer-events-none"
      >
        Drop blocks here — they repeat for each item
      </div>
    </VueDraggable>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { GripVertical } from 'lucide-vue-next'
import ParagraphBlock from '@/Components/Blocks/ParagraphBlock.vue'
import HeadingBlock   from '@/Components/Blocks/HeadingBlock.vue'
import ImageBlock     from '@/Components/Blocks/ImageBlock.vue'
import QuoteBlock     from '@/Components/Blocks/QuoteBlock.vue'
import CodeBlock      from '@/Components/Blocks/CodeBlock.vue'
import GalleryBlock   from '@/Components/Blocks/GalleryBlock.vue'
import VideoBlock     from '@/Components/Blocks/VideoBlock.vue'
import DividerBlock   from '@/Components/Blocks/DividerBlock.vue'
import CtaBlock       from '@/Components/Blocks/CtaBlock.vue'
import HtmlBlock      from '@/Components/Blocks/HtmlBlock.vue'
import PostListBlock  from '@/Components/Blocks/PostListBlock.vue'

const BLOCK_MAP = {
  paragraph: ParagraphBlock,
  heading:   HeadingBlock,
  image:     ImageBlock,
  quote:     QuoteBlock,
  code:      CodeBlock,
  gallery:   GalleryBlock,
  video:     VideoBlock,
  divider:   DividerBlock,
  cta:       CtaBlock,
  html:      HtmlBlock,
  component: PostListBlock,
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
}

const SOURCE_LABELS = {
  posts: 'Posts', categories: 'Categories', tags: 'Tags', pages: 'Pages',
}

const props = defineProps({
  block:      { type: Object, required: true },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'update-children'])

const localChildren = computed({
  get: () => props.block.children ?? [],
  set: (val) => emit('update-children', { id: props.block.id, children: val }),
})

function onAdd(evt) {
  const newChild = localChildren.value[evt.newIndex]
  if (newChild) emit('select', newChild.id)
}
</script>
```

**Step 2: Update `BlockCanvas.vue`**

Add `EditorLoopBlock` import and the `v-else-if` branch for `block.type === 'loop'`. Also add `loop` to `LABELS`.

The only changes are: import `EditorLoopBlock`, add the v-else-if branch, and add `loop: 'Loop'` to `LABELS`.

Replace the relevant sections:

```vue
<!-- resources/js/Components/BlockEditor/BlockCanvas.vue -->
<!-- Only showing the changed parts — rest of file is identical -->

<!-- In <script setup>, add import: -->
import EditorLoopBlock from './EditorLoopBlock.vue'

<!-- In LABELS object, add: -->
loop: 'Loop',

<!-- In the template, after the EditorSectionBlock v-else-if, add: -->
<EditorLoopBlock
  v-else-if="block.type === 'loop'"
  :block="block"
  :selected-id="selectedId"
  @select="$emit('select', $event)"
  @update-children="$emit('update-children', $event)"
/>
```

Full updated `BlockCanvas.vue` for clarity:

```vue
<!-- resources/js/Components/BlockEditor/BlockCanvas.vue -->
<template>
  <div class="flex-1 flex flex-col overflow-hidden border-r bg-background">
    <div class="px-3 py-2 border-b shrink-0">
      <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Canvas</p>
    </div>

    <div class="relative flex-1 overflow-y-auto">
      <div
        v-if="draggableBlocks.length === 0"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
      >
        <div class="text-center">
          <p class="text-sm text-muted-foreground">Drag blocks from the left panel</p>
          <p class="text-xs text-muted-foreground/60 mt-1">or click a block type to add it</p>
        </div>
      </div>

      <VueDraggable
        v-model="draggableBlocks"
        tag="div"
        class="p-4 space-y-2 min-h-full"
        :group="{ name: 'canvas' }"
        :animation="150"
        handle=".block-drag-handle"
        ghost-class="opacity-40"
        @add="onAdd"
      >
        <div
          v-for="block in draggableBlocks"
          :key="block.id"
          class="group relative rounded-lg border bg-card transition-colors cursor-pointer"
          :class="block.id === selectedId
            ? 'border-primary ring-1 ring-primary'
            : 'border-border hover:border-muted-foreground'"
          @click="$emit('select', block.id)"
        >
          <div
            class="block-drag-handle absolute left-2 top-3 cursor-grab active:cursor-grabbing text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity"
            @click.stop
          >
            <GripVertical class="w-4 h-4" />
          </div>

          <EditorContainerBlock
            v-if="block.type === 'container'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />

          <EditorSectionBlock
            v-else-if="block.type === 'section'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />

          <EditorLoopBlock
            v-else-if="block.type === 'loop'"
            :block="block"
            :selected-id="selectedId"
            @select="$emit('select', $event)"
            @update-children="$emit('update-children', $event)"
          />

          <div
            v-else-if="block.type === 'spacer'"
            class="px-8 py-3"
          >
            <div
              class="w-full flex items-center justify-center bg-muted/30 border border-dashed border-muted-foreground/30 rounded text-xs text-muted-foreground select-none"
              :style="{ height: `${(block.data?.height?.default ?? 8) * 4}px` }"
            >
              Spacer (h-{{ block.data?.height?.default ?? 8 }})
            </div>
          </div>

          <div v-else class="px-8 py-3 min-h-[2.5rem]">
            <div
              v-if="isEmptyBlock(block)"
              class="text-xs text-muted-foreground italic"
            >{{ LABELS[block.type] ?? block.type }} — empty</div>
            <component
              v-else
              :is="BLOCK_MAP[block.type]"
              :block="block"
              class="pointer-events-none"
            />
          </div>
        </div>
      </VueDraggable>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { VueDraggable }       from 'vue-draggable-plus'
import { GripVertical }       from 'lucide-vue-next'
import EditorContainerBlock   from './EditorContainerBlock.vue'
import EditorSectionBlock     from './EditorSectionBlock.vue'
import EditorLoopBlock        from './EditorLoopBlock.vue'
import ParagraphBlock from '@/Components/Blocks/ParagraphBlock.vue'
import HeadingBlock   from '@/Components/Blocks/HeadingBlock.vue'
import ImageBlock     from '@/Components/Blocks/ImageBlock.vue'
import QuoteBlock     from '@/Components/Blocks/QuoteBlock.vue'
import CodeBlock      from '@/Components/Blocks/CodeBlock.vue'
import GalleryBlock   from '@/Components/Blocks/GalleryBlock.vue'
import VideoBlock     from '@/Components/Blocks/VideoBlock.vue'
import DividerBlock   from '@/Components/Blocks/DividerBlock.vue'
import CtaBlock       from '@/Components/Blocks/CtaBlock.vue'
import HtmlBlock      from '@/Components/Blocks/HtmlBlock.vue'
import PostListBlock  from '@/Components/Blocks/PostListBlock.vue'

const BLOCK_MAP = {
  paragraph: ParagraphBlock,
  heading:   HeadingBlock,
  image:     ImageBlock,
  quote:     QuoteBlock,
  code:      CodeBlock,
  gallery:   GalleryBlock,
  video:     VideoBlock,
  divider:   DividerBlock,
  cta:       CtaBlock,
  html:      HtmlBlock,
  component: PostListBlock,
}

const LABELS = {
  paragraph: 'Paragraph', heading: 'Heading', image: 'Image',
  quote: 'Quote', code: 'Code', gallery: 'Gallery', video: 'Video',
  divider: 'Divider', cta: 'CTA', html: 'HTML', component: 'Component',
  container: 'Container', section: 'Section', spacer: 'Spacer', loop: 'Loop',
}

const props = defineProps({
  blocks:     { type: Array,  default: () => [] },
  selectedId: { type: String, default: null },
})

const emit = defineEmits(['select', 'reorder', 'update-children'])

const draggableBlocks = computed({
  get: () => props.blocks,
  set: (val) => emit('reorder', val),
})

function onAdd(evt) {
  const newBlock = draggableBlocks.value[evt.newIndex]
  if (newBlock) emit('select', newBlock.id)
}

function isEmptyBlock(block) {
  const d = block.data ?? {}
  switch (block.type) {
    case 'paragraph': return !d.content
    case 'heading':   return !d.text
    case 'image':     return !d.url
    case 'quote':     return !d.text
    case 'code':      return !d.content
    case 'gallery':   return !(d.items?.length)
    case 'video':     return !d.url
    case 'cta':       return !d.headline && !d.text
    case 'html':      return !d.content
    default:          return false
  }
}
</script>
```

**Step 3: Commit**

```bash
git add resources/js/Components/BlockEditor/EditorLoopBlock.vue \
        resources/js/Components/BlockEditor/BlockCanvas.vue
git commit -m "feat: add EditorLoopBlock canvas component, register in BlockCanvas"
```

---

## Summary

After all 11 tasks, the Loop block is fully functional:

| Layer | What changed |
|-------|-------------|
| **PHP** | `QueryBuilder` service + `QueryController` (`POST /api/v1/query`) + `PublicPageController` resolves loops server-side |
| **Vue renderer** | `LoopItemProvider` (provide/inject) + `LoopBlock` (iterates items, live refetch on URL change) |
| **Block components** | `HeadingBlock`, `ParagraphBlock`, `ImageBlock`, `CtaBlock` all use `useFieldBinding` composable |
| **BlockRenderer** | Condition check via injected `loopItem`, `loop` in BLOCK_MAP, font recursion |
| **Editor settings** | `LoopSettings` (query config) + `DynamicField` (bind toggle) + `ConditionSettings` (visibility rule) |
| **Settings panels** | `HeadingSettings`, `ParagraphSettings`, `ImageSettings`, `CtaSettings` updated with `DynamicField` |
| **Editor wiring** | `BlockEditor` computes `loopAncestor` + `loopFields`, passes to `BlockLayers` → settings |
| **Canvas** | `EditorLoopBlock` (teal dashed border, drop zone, "Loop — Posts" label) |
| **Palette** | `BlockTypePanel` registers `loop` type with `Repeat2` icon + `DEFAULT_DATA` |
