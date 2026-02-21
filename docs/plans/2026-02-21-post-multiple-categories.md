# Post Multiple Categories Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow a post to belong to multiple categories via a many-to-many pivot table, replacing the current single `category_id` foreign key.

**Architecture:** Drop `category_id` from the `posts` table and create a `category_post` pivot table. Update the `Post` model relationship from `belongsTo` to `belongsToMany`. Mirror the existing tag pattern exactly: `category_ids` array in forms, `sync()` on store/update. Update the API to return an array of categories. Update all tests.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, SQLite, PHPUnit

---

### Task 1: Migration — pivot table + drop category_id

**Files:**
- Create: `database/migrations/2026_02_21_000002_create_category_post_table.php`

**Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        // Migrate existing category_id data into pivot before dropping column
        DB::statement('
            INSERT INTO category_post (category_id, post_id)
            SELECT category_id, id FROM posts WHERE category_id IS NOT NULL
        ');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::dropIfExists('category_post');
    }
};
```

**Step 2: Run the migration**

```bash
php artisan migrate
```

Expected: `DONE` — no errors.

**Step 3: Commit**

```bash
git add database/migrations/2026_02_21_000002_create_category_post_table.php
git commit -m "feat: add category_post pivot table and drop category_id from posts"
```

---

### Task 2: Update Post model relationship

**Files:**
- Modify: `app/Models/Post.php`

**Step 1: Replace `category()` belongsTo with belongsToMany**

In `app/Models/Post.php`, replace:

```php
public function category(): BelongsTo
{
    return $this->belongsTo(Category::class);
}
```

With:

```php
public function categories(): BelongsToMany
{
    return $this->belongsToMany(Category::class);
}
```

Also remove the `BelongsTo` import if it becomes unused; add `BelongsToMany` import if not already present:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

**Step 2: Run existing tests — expect failures**

```bash
php artisan test
```

Expected: multiple failures related to `category`, `category_id`. This is correct — they'll be fixed in subsequent tasks.

**Step 3: Commit**

```bash
git add app/Models/Post.php
git commit -m "feat: change Post category to belongsToMany"
```

---

### Task 3: Update PostController

**Files:**
- Modify: `app/Http/Controllers/PostController.php`

**Step 1: Update `index()` — replace `category:id,name` with `categories:id,name`, fix filter**

In `index()`:

Replace:
```php
$posts = Post::with('author:id,name', 'category:id,name', 'tags:id,name')
```
With:
```php
$posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name')
```

Replace the category filter:
```php
->when(
    $request->input('category'),
    fn ($q, $categoryId) => $q->where('category_id', $categoryId)
)
```
With:
```php
->when(
    $request->input('category'),
    fn ($q, $categoryId) => $q->whereHas('categories', fn ($c) => $c->where('categories.id', $categoryId))
)
```

Replace in the `through()` mapping:
```php
'category'     => $post->category?->name,
```
With:
```php
'categories'   => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
```

**Step 2: Update `store()` — replace `category_id` with `category_ids`**

Replace validation rule:
```php
'category_id' => ['nullable', 'exists:categories,id'],
```
With:
```php
'category_ids'   => ['nullable', 'array'],
'category_ids.*' => ['exists:categories,id'],
```

After `$tagIds` extraction, add category extraction:
```php
$tagIds      = $validated['tag_ids'] ?? [];
$categoryIds = $validated['category_ids'] ?? [];
unset($validated['tag_ids'], $validated['category_ids']);
```

After `$post = Post::create($validated);`, add:
```php
$post->tags()->sync($tagIds);
$post->categories()->sync($categoryIds);
```

Remove the old:
```php
$post->tags()->sync($tagIds);
```

**Step 3: Update `edit()` — load categories relationship**

Replace:
```php
$post->load('tags:id,name', 'featuredImage:id,path,disk,alt');
```
With:
```php
$post->load('tags:id,name', 'categories:id,name', 'featuredImage:id,path,disk,alt');
```

Replace in the returned post array:
```php
'category_id'       => $post->category_id,
```
With:
```php
'category_ids'      => $post->categories->pluck('id'),
```

**Step 4: Update `update()` — replace `category_id` with `category_ids`**

Replace validation:
```php
'category_id' => ['nullable', 'exists:categories,id'],
```
With:
```php
'category_ids'   => ['nullable', 'array'],
'category_ids.*' => ['exists:categories,id'],
```

Add category extraction alongside tags:
```php
$tagIds      = $validated['tag_ids'] ?? [];
$categoryIds = $validated['category_ids'] ?? [];
unset($validated['tag_ids'], $validated['category_ids']);
```

After `$post->update($validated);`, sync both:
```php
$post->tags()->sync($tagIds);
$post->categories()->sync($categoryIds);
```

**Step 5: Commit**

```bash
git add app/Http/Controllers/PostController.php
git commit -m "feat: update PostController to use category_ids (many-to-many)"
```

---

### Task 4: Update Create.vue — category single select → multi-checkbox

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`

**Step 1: Update the form initial state**

In `<script setup>`, replace:
```js
const form = useForm({
  ...
  category_id:      null,
  ...
});
```
With:
```js
const form = useForm({
  ...
  category_ids:     [],
  ...
});
```

**Step 2: Replace the Category sidebar section**

Replace:
```html
<!-- Category -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Category</h3>
  <select
    v-model="form.category_id"
    class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
  >
    <option :value="null">— None —</option>
    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
  </select>
  <p v-if="categories.length === 0" class="mt-2 text-xs text-muted-foreground">
    No categories yet.
    <a :href="route('categories.create')" class="underline hover:text-foreground">Create one</a>
  </p>
</div>
```
With:
```html
<!-- Categories -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Categories</h3>
  <div v-if="categories.length === 0" class="text-xs text-muted-foreground">
    No categories yet.
    <a :href="route('categories.create')" class="underline hover:text-foreground">Create one</a>
  </div>
  <div v-else class="flex flex-wrap gap-2">
    <label
      v-for="cat in categories"
      :key="cat.id"
      class="flex items-center gap-1.5 cursor-pointer"
    >
      <input
        type="checkbox"
        :value="cat.id"
        v-model="form.category_ids"
        class="accent-primary rounded"
      />
      <span
        class="text-xs px-2 py-0.5 rounded-full border transition-colors"
        :class="form.category_ids.includes(cat.id)
          ? 'bg-primary text-primary-foreground border-primary'
          : 'text-muted-foreground border-border hover:border-foreground'"
      >
        {{ cat.name }}
      </span>
    </label>
  </div>
</div>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue
git commit -m "feat: update Create.vue category to multi-select checkboxes"
```

---

### Task 5: Update Edit.vue — category single select → multi-checkbox

**Files:**
- Modify: `resources/js/Pages/Posts/Edit.vue`

**Step 1: Update the form initial state**

In `<script setup>`, replace:
```js
const form = useForm({
  ...
  category_id:       props.post.category_id ?? null,
  ...
});
```
With:
```js
const form = useForm({
  ...
  category_ids:      props.post.category_ids ?? [],
  ...
});
```

**Step 2: Replace the Category sidebar section** (identical replacement as Create.vue Task 4 Step 2 — same HTML, bound to `form.category_ids`)

**Step 3: Commit**

```bash
git add resources/js/Pages/Posts/Edit.vue
git commit -m "feat: update Edit.vue category to multi-select checkboxes"
```

---

### Task 6: Update Posts/Index.vue — show multiple categories

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`

The index page currently shows `post.category` as a single string. Since the explore agent confirmed both Create and Edit follow the same structure, find any reference to `post.category` in the index and update it to display an array.

**Step 1: Find and update category display**

Any instance like:
```html
{{ post.category }}
```
or
```html
<span>{{ post.category }}</span>
```

Replace with:
```html
<span v-if="post.categories?.length">
  {{ post.categories.map(c => c.name).join(', ') }}
</span>
<span v-else class="text-muted-foreground/50">—</span>
```

Also update the category filter dropdown if it filters by `category_id` — it should still work since it passes an ID, but confirm the `v-model` or query param key hasn't changed.

**Step 2: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue
git commit -m "feat: update posts index to display multiple categories"
```

---

### Task 7: Update API PostController — return categories array

**Files:**
- Modify: `app/Http/Controllers/Api/V1/PostController.php`

**Step 1: Update `index()` eager load**

Replace:
```php
->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt,width,height'])
```
With:
```php
->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt,width,height'])
```

**Step 2: Update `show()` eager load**

Replace:
```php
->with(['author:id,name', 'category:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt,description,width,height'])
```
With:
```php
->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt,description,width,height'])
```

**Step 3: Update `toArray()` — replace `category` with `categories`**

Replace:
```php
'category'       => $post->category ? ['id' => $post->category->id, 'name' => $post->category->name, 'slug' => $post->category->slug] : null,
```
With:
```php
'categories'     => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/Api/V1/PostController.php
git commit -m "feat: update API PostController to return categories array"
```

---

### Task 8: Update blog frontend — BlogController + Blog views

**Files:**
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `resources/js/Pages/Blog/Index.vue`
- Modify: `resources/js/Pages/Blog/Show.vue`

**Step 1: Update BlogController — replace `category` with `categories`**

Find any `with('category')` or `category:id,name,slug` calls in `BlogController.php` and update to `categories:id,name,slug`.

Find any mapping that outputs `'category' => $post->category?->...` and update to:
```php
'categories' => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
```

**Step 2: Update Blog/Index.vue and Blog/Show.vue**

Find any `post.category` references and update to loop over `post.categories` (same pattern as Posts/Index.vue above).

**Step 3: Commit**

```bash
git add app/Http/Controllers/BlogController.php resources/js/Pages/Blog/Index.vue resources/js/Pages/Blog/Show.vue
git commit -m "feat: update blog frontend to handle multiple categories"
```

---

### Task 9: Update PostFactory

**Files:**
- Modify: `database/factories/PostFactory.php`

**Step 1: Remove `category_id` from factory definition**

The factory currently generates `category_id` (or it may already be absent — check). Since there's no longer a `category_id` column, remove it if present. Categories are now assigned via the pivot so won't be set in the factory definition directly.

**Step 2: Commit**

```bash
git add database/factories/PostFactory.php
git commit -m "chore: remove category_id from PostFactory (now pivot)"
```

---

### Task 10: Update Feature tests

**Files:**
- Modify: `tests/Feature/PostTest.php`
- Modify: `tests/Feature/BlogTest.php`
- Modify: `tests/Feature/Api/V1/PostApiTest.php`

**Step 1: Update PostTest.php**

Find any test that uses `category_id` in post creation and update to `category_ids`:

```php
// Before
$this->actingAs($user)->post(route('posts.store'), [
    'category_id' => $category->id,
    ...
]);

// After
$this->actingAs($user)->post(route('posts.store'), [
    'category_ids' => [$category->id],
    ...
]);
```

Add a new test for multiple categories:

```php
public function test_categories_are_synced_on_store(): void
{
    $this->markAsInstalled();
    $this->seedRolesAndPermissions();

    $user = User::factory()->create()->assignRole('user');
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();

    $this->actingAs($user)->post(route('posts.store'), [
        'title'        => 'Multi cat post',
        'status'       => 'draft',
        'category_ids' => [$cat1->id, $cat2->id],
    ]);

    $post = Post::where('title', 'Multi cat post')->first();
    $this->assertCount(2, $post->categories);
    $this->assertTrue($post->categories->contains($cat1));
    $this->assertTrue($post->categories->contains($cat2));
}

public function test_categories_are_synced_on_update(): void
{
    $this->markAsInstalled();
    $this->seedRolesAndPermissions();

    $user = User::factory()->create()->assignRole('user');
    $post = Post::factory()->create(['user_id' => $user->id]);
    $cat1 = Category::factory()->create();
    $cat2 = Category::factory()->create();
    $post->categories()->sync([$cat1->id]);

    $this->actingAs($user)->put(route('posts.update', $post), [
        'title'        => $post->title,
        'status'       => $post->status,
        'category_ids' => [$cat2->id],
    ]);

    $post->refresh();
    $this->assertCount(1, $post->categories);
    $this->assertTrue($post->categories->contains($cat2));
    $this->assertFalse($post->categories->contains($cat1));
}
```

Remember to add `use App\Models\Category;` at the top of PostTest.php if not already there.

**Step 2: Update BlogTest.php**

Find any assertions checking `post.category` (single) and update to `post.categories` (array). For example:

```php
// Before
$response->assertInertia(fn ($page) => $page->has('post.category'));

// After
$response->assertInertia(fn ($page) => $page->has('post.categories'));
```

**Step 3: Update PostApiTest.php**

Replace any assertion for `category` with `categories`:

```php
// Before
$response->assertJsonStructure([
    'data' => [['id', 'title', 'slug', ..., 'category', ...]],
]);

// After
$response->assertJsonStructure([
    'data' => [['id', 'title', 'slug', ..., 'categories', ...]],
]);
```

Add a test for API category filter:

```php
public function test_api_posts_index_filters_by_category(): void
{
    // This test already exists — ensure it still passes with the new whereHas filter.
    // No changes needed if it already uses ?category=slug.
}
```

**Step 4: Run all tests**

```bash
php artisan test
```

Expected: all tests pass. Fix any remaining failures before continuing.

**Step 5: Commit**

```bash
git add tests/
git commit -m "test: update PostTest, BlogTest, PostApiTest for multiple categories"
```

---

### Task 11: Final verification

**Step 1: Run the full test suite**

```bash
php artisan test
```

Expected: all tests pass, no failures.

**Step 2: Confirm pivot table works in tinker**

```bash
php artisan tinker
```

```php
$post = Post::first();
$post->categories()->sync([1, 2]);
$post->categories->pluck('name');
```

Expected: collection with category names.

**Step 3: Final commit (if any cleanup)**

```bash
git add -A
git commit -m "feat: posts can now belong to multiple categories"
```
