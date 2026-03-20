# Component Block — Post List Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a `component` block type to the block editor that renders a server-resolved post list with configurable filters (categories, tags, limit, offset, order, featured-only).

**Architecture:** Block data is stored as JSON in `pages.blocks`. On public page load, `PublicPageController` scans for `type: "component"` blocks and injects resolved post data before handing to Inertia — no client-side API calls. The editor settings panel receives available categories/tags as Inertia props from `PageController`.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, vue-draggable-plus, Tailwind CSS 4, PHPUnit

---

### Task 1: Migration — add `featured` to posts

**Files:**
- Create: `database/migrations/2026_03_20_000001_add_featured_to_posts_table.php`

**Step 1: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->boolean('featured')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('featured');
        });
    }
};
```

**Step 2: Add `featured` to Post model's fillable**

In `app/Models/Post.php`, add `'featured'` to the `$fillable` array.

**Step 3: Add `featured` to Post factory**

In `database/factories/PostFactory.php`, add `'featured' => false` to the `definition()` return array.

**Step 4: Run migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_03_20_000001_add_featured_to_posts_table` then `Migrated`.

**Step 5: Commit**

```bash
git add database/migrations/2026_03_20_000001_add_featured_to_posts_table.php app/Models/Post.php database/factories/PostFactory.php
git commit -m "feat: add featured column to posts table"
```

---

### Task 2: Backend — resolve component blocks in PublicPageController

**Files:**
- Modify: `app/Http/Controllers/PublicPageController.php`

**Step 1: Write the failing test**

In `tests/Feature/BlogTest.php` (or create `tests/Feature/PageTest.php` if it doesn't exist), add:

```php
<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
    }

    public function test_component_post_list_block_is_resolved_on_page_load(): void
    {
        $post = Post::factory()->create([
            'title'        => 'Test Post',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'My Page',
            'slug'    => 'my-page',
            'status'  => 'published',
            'blocks'  => [
                [
                    'id'   => 'block-1',
                    'type' => 'component',
                    'data' => [
                        'component'    => 'post-list',
                        'limit'        => 6,
                        'offset'       => 0,
                        'order'        => 'latest',
                        'featured_only' => false,
                        'category_ids' => [],
                        'tag_ids'      => [],
                    ],
                ],
            ],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(
                fn ($p) => $p
                    ->component('Blog/Page')
                    ->where('page.blocks.0.data.resolved.posts.0.title', 'Test Post')
            );
    }

    public function test_component_post_list_respects_category_filter(): void
    {
        $category = Category::factory()->create();
        $included = Post::factory()->published()->create(['title' => 'In Category']);
        $excluded = Post::factory()->published()->create(['title' => 'Not In Category']);
        $included->categories()->attach($category);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Filtered Page',
            'slug'    => 'filtered-page',
            'status'  => 'published',
            'blocks'  => [[
                'id'   => 'block-1',
                'type' => 'component',
                'data' => [
                    'component'     => 'post-list',
                    'limit'         => 6,
                    'offset'        => 0,
                    'order'         => 'latest',
                    'featured_only' => false,
                    'category_ids'  => [$category->id],
                    'tag_ids'       => [],
                ],
            ]],
        ]);

        $this->get("/{$page->slug}")
            ->assertOk()
            ->assertInertia(fn ($p) => $p
                ->where('page.blocks.0.data.resolved.posts.0.title', 'In Category')
                ->where('page.blocks.0.data.resolved', fn ($resolved) =>
                    count($resolved['posts']) === 1
                )
            );
    }

    public function test_draft_posts_are_excluded_from_component_post_list(): void
    {
        Post::factory()->create(['title' => 'Draft Post', 'status' => 'draft']);

        $page = Page::create([
            'user_id' => User::factory()->create()->id,
            'title'   => 'Test Page',
            'slug'    => 'test-page-draft',
            'status'  => 'published',
            'blocks'  => [[
                'id'   => 'block-1',
                'type' => 'component',
                'data' => ['component' => 'post-list', 'limit' => 6, 'offset' => 0,
                           'order' => 'latest', 'featured_only' => false,
                           'category_ids' => [], 'tag_ids' => []],
            ]],
        ]);

        $this->get("/{$page->slug}")
            ->assertInertia(fn ($p) => $p
                ->where('page.blocks.0.data.resolved.posts', [])
            );
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/PageTest.php
```

Expected: 3 failures — `resolved` key missing from block data.

**Step 3: Implement resolveBlocks in PublicPageController**

Replace `PublicPageController.php` with:

```php
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
            ->with(['author:id,name', 'featuredImage:id,path'])
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
                'featured_image_url' => $post->featuredImage?->url ?? null,
            ])
            ->all();

        $block['data']['resolved'] = ['posts' => $posts];

        return $block;
    }
}
```

Note: `$post->featuredImage->url` requires a `url` accessor on the Media model — check if it exists. If not, use `Storage::url($post->featuredImage->path)` instead.

**Step 4: Run tests**

```bash
php artisan test tests/Feature/PageTest.php
```

Expected: 3 tests pass.

**Step 5: Commit**

```bash
git add app/Http/Controllers/PublicPageController.php tests/Feature/PageTest.php
git commit -m "feat: resolve component post-list blocks in PublicPageController"
```

---

### Task 3: Backend — pass categories and tags to PageController

**Files:**
- Modify: `app/Http/Controllers/PageController.php`

**Step 1: Add imports and update create() and edit()**

At the top of `PageController.php`, add imports:
```php
use App\Models\Category;
use App\Models\Tag;
```

Update `create()`:
```php
public function create()
{
    return Inertia::render('Pages/Create', [
        'categories' => Category::orderBy('name')->get(['id', 'name']),
        'tags'       => Tag::orderBy('name')->get(['id', 'name']),
    ]);
}
```

Update `edit()` — add `categories` and `tags` alongside the existing `page` prop:
```php
public function edit(Page $page)
{
    return Inertia::render('Pages/Edit', [
        'page' => [
            'id'               => $page->id,
            'title'            => $page->title,
            'slug'             => $page->slug,
            'status'           => $page->status,
            'blocks'           => $page->blocks,
            'meta_title'       => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keywords'    => $page->meta_keywords,
        ],
        'categories' => Category::orderBy('name')->get(['id', 'name']),
        'tags'       => Tag::orderBy('name')->get(['id', 'name']),
    ]);
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/PageController.php
git commit -m "feat: pass categories and tags to page editor"
```

---

### Task 4: Frontend — add component type to BlockTypePanel + BlockEditor defaults

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/components/BlockEditor/BlockEditor.vue`

**Step 1: Add to BlockTypePanel `ALL_TYPES` array**

In `BlockTypePanel.vue`, add to the `ALL_TYPES` array (before the closing `]`):

```js
{ type: 'component', label: 'Component', icon: '⚙️' },
```

Add to `DEFAULT_DATA`:

```js
component: { component: 'post-list', limit: 6, offset: 0, order: 'latest', featured_only: false, category_ids: [], tag_ids: [] },
```

**Step 2: BlockEditor.vue is no longer needed for defaultData** — BlockTypePanel owns it now, so no change needed to BlockEditor.vue.

**Step 3: Verify in browser**

Navigate to Pages → New page. The palette should show a "⚙️ Component" tile. Drag it onto the canvas — a component block card should appear.

**Step 4: Commit**

```bash
git add resources/js/components/BlockEditor/BlockTypePanel.vue
git commit -m "feat: add component block type to palette"
```

---

### Task 5: Frontend — ComponentSettings.vue

**Files:**
- Create: `resources/js/components/BlockEditor/blocks/ComponentSettings.vue`

**Step 1: Create the settings component**

```vue
<!-- resources/js/Components/BlockEditor/blocks/ComponentSettings.vue -->
<template>
  <div class="space-y-4">
    <!-- Sub-type selector (extensible) -->
    <div>
      <label class="text-xs font-medium text-muted-foreground block mb-1">Component type</label>
      <select
        :value="block.data.component"
        class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
        @change="update('component', $event.target.value)"
      >
        <option value="post-list">Post List</option>
      </select>
    </div>

    <!-- Post List options -->
    <template v-if="block.data.component === 'post-list'">

      <!-- Order -->
      <div>
        <label class="text-xs font-medium text-muted-foreground block mb-1">Order</label>
        <select
          :value="block.data.order"
          class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="update('order', $event.target.value)"
        >
          <option value="latest">Latest first</option>
          <option value="oldest">Oldest first</option>
          <option value="alpha">Alphabetical</option>
        </select>
      </div>

      <!-- Limit + Offset -->
      <div class="grid grid-cols-2 gap-2">
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Limit</label>
          <input
            type="number"
            min="1"
            max="100"
            :value="block.data.limit"
            class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="update('limit', parseInt($event.target.value) || 6)"
          />
        </div>
        <div>
          <label class="text-xs font-medium text-muted-foreground block mb-1">Offset</label>
          <input
            type="number"
            min="0"
            :value="block.data.offset"
            class="w-full rounded border bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            @input="update('offset', parseInt($event.target.value) || 0)"
          />
        </div>
      </div>

      <!-- Featured only -->
      <label class="flex items-center gap-2 cursor-pointer">
        <input
          type="checkbox"
          :checked="block.data.featured_only"
          class="accent-primary"
          @change="update('featured_only', $event.target.checked)"
        />
        <span class="text-xs font-medium text-muted-foreground">Featured posts only</span>
      </label>

      <!-- Filter by categories -->
      <div v-if="meta.categories?.length">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Filter by categories</label>
        <div class="space-y-1 max-h-32 overflow-y-auto">
          <label
            v-for="cat in meta.categories"
            :key="cat.id"
            class="flex items-center gap-2 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="cat.id"
              :checked="block.data.category_ids?.includes(cat.id)"
              class="accent-primary"
              @change="toggleId('category_ids', cat.id, $event.target.checked)"
            />
            <span class="text-xs">{{ cat.name }}</span>
          </label>
        </div>
      </div>

      <!-- Filter by tags -->
      <div v-if="meta.tags?.length">
        <label class="text-xs font-medium text-muted-foreground block mb-1">Filter by tags</label>
        <div class="space-y-1 max-h-32 overflow-y-auto">
          <label
            v-for="tag in meta.tags"
            :key="tag.id"
            class="flex items-center gap-2 cursor-pointer"
          >
            <input
              type="checkbox"
              :value="tag.id"
              :checked="block.data.tag_ids?.includes(tag.id)"
              class="accent-primary"
              @change="toggleId('tag_ids', tag.id, $event.target.checked)"
            />
            <span class="text-xs">{{ tag.name }}</span>
          </label>
        </div>
      </div>

    </template>
  </div>
</template>

<script setup>
const props = defineProps({
  block: { type: Object, required: true },
  meta:  { type: Object, default: () => ({ categories: [], tags: [] }) },
})

const emit = defineEmits(['update'])

function update(key, value) {
  emit('update', { id: props.block.id, data: { ...props.block.data, [key]: value } })
}

function toggleId(field, id, checked) {
  const current = [...(props.block.data[field] ?? [])]
  const next = checked ? [...current, id] : current.filter(v => v !== id)
  update(field, next)
}
</script>
```

**Step 2: Commit**

```bash
git add resources/js/components/BlockEditor/blocks/ComponentSettings.vue
git commit -m "feat: add ComponentSettings panel for post-list block"
```

---

### Task 6: Frontend — wire meta prop through BlockEditor → BlockLayers → ComponentSettings

**Files:**
- Modify: `resources/js/components/BlockEditor/BlockEditor.vue`
- Modify: `resources/js/components/BlockEditor/BlockLayers.vue`

**Step 1: Add `meta` prop to BlockEditor.vue**

In `BlockEditor.vue`, add `meta` to `defineProps`:

```js
const props = defineProps({
  modelValue: { type: Array,   default: () => [] },
  isAdmin:    { type: Boolean, default: false },
  meta:       { type: Object,  default: () => ({}) },
})
```

Pass it down to `BlockLayers` in the template:

```html
<BlockLayers
  :blocks="localBlocks"
  :selected-id="selectedBlockId"
  :selected-block="selectedBlock"
  :is-admin="isAdmin"
  :meta="meta"
  @select="selectBlock"
  @remove="removeBlock"
  @reorder="onReorder"
  @update="updateBlock"
/>
```

**Step 2: Add `meta` prop to BlockLayers.vue and forward to ComponentSettings**

In `BlockLayers.vue`, add to `defineProps`:

```js
meta: { type: Object, default: () => ({}) },
```

Import `ComponentSettings`:

```js
import ComponentSettings from './blocks/ComponentSettings.vue'
```

Add to `COMPONENT_MAP`:

```js
component: ComponentSettings,
```

Pass `meta` to the dynamic settings component. In the template, change the `<component>` tag to:

```html
<component
  v-else
  :is="settingsComponent"
  :block="selectedBlock"
  :is-admin="isAdmin"
  :meta="meta"
  @update="$emit('update', $event)"
/>
```

**Step 3: Commit**

```bash
git add resources/js/components/BlockEditor/BlockEditor.vue resources/js/components/BlockEditor/BlockLayers.vue
git commit -m "feat: thread meta prop through BlockEditor to ComponentSettings"
```

---

### Task 7: Frontend — update Pages/Create.vue and Pages/Edit.vue

**Files:**
- Modify: `resources/js/Pages/Pages/Create.vue`
- Modify: `resources/js/Pages/Pages/Edit.vue`

**Step 1: Update Create.vue**

Add `categories` and `tags` to `defineProps`:

```js
const props = defineProps({
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
})
```

Update the `<BlockEditor>` usage to pass `:meta`:

```html
<BlockEditor
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  :meta="{ categories: props.categories, tags: props.tags }"
  @update:model-value="form.blocks = $event"
/>
```

**Step 2: Update Edit.vue**

Same changes: add `categories` and `tags` to `defineProps`, pass `:meta` to `<BlockEditor>`. Note `Edit.vue` already has a `page` prop — add alongside it:

```js
const props = defineProps({
  page:       { type: Object, required: true },
  categories: { type: Array,  default: () => [] },
  tags:       { type: Array,  default: () => [] },
})
```

```html
<BlockEditor
  :model-value="form.blocks"
  :is-admin="authUser?.role === 'administrator'"
  :meta="{ categories: props.categories, tags: props.tags }"
  @update:model-value="form.blocks = $event"
/>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Pages/Create.vue resources/js/Pages/Pages/Edit.vue
git commit -m "feat: pass categories and tags meta to BlockEditor in page editor"
```

---

### Task 8: Frontend — PostListBlock.vue public renderer

**Files:**
- Create: `resources/js/components/Blocks/PostListBlock.vue`

**Step 1: Create the component**

```vue
<!-- resources/js/Components/Blocks/PostListBlock.vue -->
<template>
  <div>
    <div v-if="!posts.length" class="text-sm text-muted-foreground py-4 text-center">
      No posts found.
    </div>

    <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <a
        v-for="post in posts"
        :key="post.id"
        :href="`/blog/${post.slug}`"
        class="group flex flex-col rounded-lg border bg-card overflow-hidden hover:border-primary transition-colors"
      >
        <!-- Featured image -->
        <div v-if="post.featured_image_url" class="aspect-video overflow-hidden bg-muted">
          <img
            :src="post.featured_image_url"
            :alt="post.title"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
        </div>

        <div class="flex flex-col flex-1 p-4">
          <h3 class="font-semibold text-sm line-clamp-2 group-hover:text-primary transition-colors mb-2">
            {{ post.title }}
          </h3>
          <p v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-3 flex-1">
            {{ post.excerpt }}
          </p>
          <div class="flex items-center gap-2 mt-3 text-xs text-muted-foreground">
            <span>{{ post.author_name }}</span>
            <span>·</span>
            <span>{{ formatDate(post.published_at) }}</span>
          </div>
        </div>
      </a>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  block: { type: Object, required: true },
})

const posts = computed(() => props.block.data?.resolved?.posts ?? [])

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('en-GB', {
    day: 'numeric', month: 'short', year: 'numeric',
  })
}
</script>
```

**Step 2: Register in BlockRenderer.vue**

Add import:
```js
import PostListBlock from '@/Components/Blocks/PostListBlock.vue'
```

Add to `BLOCK_MAP`:
```js
component: PostListBlock,
```

**Step 3: Commit**

```bash
git add resources/js/components/Blocks/PostListBlock.vue resources/js/components/BlockRenderer.vue
git commit -m "feat: add PostListBlock renderer and register in BlockRenderer"
```

---

### Task 9: Check Media model for `url` accessor

**Step 1: Verify**

```bash
grep -n "url\|getUrl\|Storage" /c/Users/mariu/Herd/lambda-cms/app/Models/Media.php | head -10
```

If the Media model has a `url` getter/accessor, `$post->featuredImage->url` works in the controller. If not, update the `resolvePostList` map in `PublicPageController`:

```php
'featured_image_url' => $post->featuredImage
    ? \Illuminate\Support\Facades\Storage::url($post->featuredImage->path)
    : null,
```

**Step 2: Run all tests**

```bash
php artisan test
```

Expected: all tests pass including the 3 new PageTest cases.

**Step 3: Final commit if any fix needed**

```bash
git add app/Http/Controllers/PublicPageController.php
git commit -m "fix: use Storage::url for featured_image_url in post-list resolver"
```

---

### Task 10: Manual smoke test

1. `php artisan migrate:fresh --seed` (if using fresh DB) or just `php artisan migrate`
2. Open browser → log in → Pages → New page
3. Drag "⚙️ Component" tile onto canvas → block card appears
4. Click the card → right panel shows Component settings
5. Set limit to 3, check a category, pick "Oldest first"
6. Save the page → visit it publicly → post list renders with correct posts
7. Verify draft/unpublished posts are excluded
8. Verify `featured_only` checkbox works (mark a post as featured in DB, check the box, confirm filter)
