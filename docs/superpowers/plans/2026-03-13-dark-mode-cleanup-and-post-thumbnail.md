# Dark Mode Cleanup & Post Featured Image Thumbnail Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix three hardcoded dark-blue colour values left from the Nord palette, correct the `--muted-foreground` contrast token, and add a featured-image thumbnail to the Posts index table.

**Architecture:** All dark-mode fixes are single-line changes to existing files — no new abstractions required. The thumbnail feature requires one backend change (eager-load + transform field in `PostController::index()`) and one frontend change (title cell markup in `Posts/Index.vue`). No migrations, no new routes, no new components.

**Tech Stack:** Laravel 12 (PHP 8.2), Inertia.js 2, Vue 3 Composition API, Tailwind CSS 4, PHPUnit.

> **Spec:** `docs/superpowers/specs/2026-03-13-dark-mode-cleanup-and-post-thumbnail.md`
> **Working directory:** `C:\Users\mariu\Herd\lambda-cms`

---

## File Structure

| Action | File | What changes |
|--------|------|-------------|
| Modify | `resources/scss/app.scss` | `--muted-foreground` in `.dark` (line 72) |
| Modify | `resources/js/Layouts/AuthLayout.vue` | `bg-[#2e3440]` → `bg-[oklch(14%_0.01_260)]` (line 24) |
| Modify | `resources/js/composables/useParticleCanvas.js` | `BG_COLOR` constant (line 6) |
| Modify | `app/Http/Controllers/PostController.php` | `index()`: add eager-load + `featured_image_url` to transform |
| Modify | `tests/Feature/PostTest.php` | New test: `featured_image_url` present in index response |
| Modify | `resources/js/Pages/Posts/Index.vue` | Title `<td>`: wrap in flex row, prepend conditional thumbnail |

---

## Chunk 1: Dark mode colour fixes

### Task 1: Fix `--muted-foreground` contrast in dark mode

**Files:**
- Modify: `resources/scss/app.scss`

> No unit test — this is a CSS token value; correctness is verified visually. The change is a single token inside `.dark`.

- [ ] **Step 1: Apply the fix**

Open `resources/scss/app.scss`. Find line 72 (inside the `.dark` block):

```scss
--muted-foreground: #4c566a;
```

Replace with:

```scss
--muted-foreground: oklch(60% 0.01 260);
```

`oklch(60% 0.01 260)` ≈ `#8898aa` — a desaturated slate at L 60%, giving adequate contrast against all dark surfaces (L 14–37%).

- [ ] **Step 2: Commit**

```bash
git add resources/scss/app.scss
git commit -m "fix: lighten dark mode --muted-foreground for adequate contrast on slate surfaces"
```

---

### Task 2: Fix hardcoded Nord blue in `AuthLayout.vue`

**Files:**
- Modify: `resources/js/Layouts/AuthLayout.vue`

> No unit test — this is a static class value on a decorative element.

- [ ] **Step 1: Apply the fix**

Open `resources/js/Layouts/AuthLayout.vue`. Find line 24:

```vue
<div class="hidden md:flex w-1/2 bg-[#2e3440]" aria-hidden="true">
```

Replace `bg-[#2e3440]` with `bg-[oklch(14%_0.01_260)]`:

```vue
<div class="hidden md:flex w-1/2 bg-[oklch(14%_0.01_260)]" aria-hidden="true">
```

Note: Tailwind CSS 4 supports `oklch()` in arbitrary values. Spaces must be written as underscores inside the brackets.

- [ ] **Step 2: Commit**

```bash
git add resources/js/Layouts/AuthLayout.vue
git commit -m "fix: desaturate AuthLayout particle panel from Nord blue to neutral slate"
```

---

### Task 3: Fix hardcoded Nord blue in `useParticleCanvas.js`

**Files:**
- Modify: `resources/js/composables/useParticleCanvas.js`

> No unit test — this is a canvas fill constant. The canvas always renders dark (not theme-adaptive by design). `PARTICLE_RGB` and `PARTICLE_COLOR` are unchanged.

- [ ] **Step 1: Apply the fix**

Open `resources/js/composables/useParticleCanvas.js`. Find lines 6–8:

```js
const BG_COLOR             = '#2e3440'
const PARTICLE_RGB         = '216, 222, 233'
const PARTICLE_COLOR       = `rgba(${PARTICLE_RGB}, 0.85)`
```

Change only `BG_COLOR` (line 6):

```js
const BG_COLOR             = 'oklch(14% 0.01 260)'
const PARTICLE_RGB         = '216, 222, 233'
const PARTICLE_COLOR       = `rgba(${PARTICLE_RGB}, 0.85)`
```

Canvas 2D `fillStyle` accepts `oklch()` in all modern browsers. The particle colour (`#d8dee9`) stays unchanged — it already provides good contrast on dark backgrounds.

- [ ] **Step 2: Commit**

```bash
git add resources/js/composables/useParticleCanvas.js
git commit -m "fix: desaturate particle canvas background from Nord blue to neutral slate"
```

---

## Chunk 2: Post featured image thumbnail

### Task 4: Expose `featured_image_url` in `PostController::index()`

**Files:**
- Modify: `app/Http/Controllers/PostController.php`
- Test: `tests/Feature/PostTest.php`

- [ ] **Step 1: Write the failing test**

Open `tests/Feature/PostTest.php`. Add the following test in the `// ── Index ──` section (after the existing `test_authenticated_user_can_access_posts_index` test). Add the `Media` use statement at the top of the file alongside the existing imports:

```php
use App\Models\Media;
```

Then add the test method:

```php
public function test_posts_index_includes_featured_image_url(): void
{
    $user  = $this->makeUser();
    $media = Media::factory()->create(['user_id' => $user->id]);
    Post::factory()->create([
        'user_id'           => $user->id,
        'featured_image_id' => $media->id,
    ]);

    $response = $this->actingAs($user)->get('/posts');

    $response->assertOk();
    $response->assertInertia(fn ($page) =>
        $page->has('posts.data.0.featured_image_url')
    );
}

public function test_posts_index_featured_image_url_is_null_when_no_image(): void
{
    $user = $this->makeUser();
    Post::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/posts');

    $response->assertOk();
    $response->assertInertia(fn ($page) =>
        $page->where('posts.data.0.featured_image_url', null)
    );
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --filter="test_posts_index_includes_featured_image_url|test_posts_index_featured_image_url_is_null"
```

Expected: both FAIL — `featured_image_url` key is not present in the response data.

- [ ] **Step 3: Implement the fix in `PostController::index()`**

Open `app/Http/Controllers/PostController.php`. Find line 17:

```php
$posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name')
```

Append `'featuredImage:id,path,disk'` to the `with()` call:

```php
$posts = Post::with('author:id,name', 'categories:id,name', 'tags:id,name', 'featuredImage:id,path,disk')
```

Then inside the `->through()` closure, find the last field `'comments_count' => $post->comments_count,` and add one line after it:

```php
'comments_count'     => $post->comments_count,
'featured_image_url' => $post->featuredImage?->url,
```

The `->url` accessor on `Media` computes `Storage::disk($this->disk)->url($this->path)`. The eager-load selects `id`, `path`, and `disk` — all three are required (`id` for Eloquent's relationship key; `path` and `disk` for the accessor). Do not reduce the column list.

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --filter="test_posts_index_includes_featured_image_url|test_posts_index_featured_image_url_is_null"
```

Expected: both PASS.

- [ ] **Step 5: Run full test suite to check for regressions**

```bash
php artisan test
```

Expected: all 307 existing tests pass plus the 2 new ones = 309 passing.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/PostController.php tests/Feature/PostTest.php
git commit -m "feat: expose featured_image_url in posts index response"
```

---

### Task 5: Add thumbnail to `Posts/Index.vue` title cell

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`

> No unit test — this is a Vue template change; correctness is verified by building and visually inspecting.

- [ ] **Step 1: Update the title `<td>`**

Open `resources/js/Pages/Posts/Index.vue`. Find the title cell in the `#rows` slot (lines 106–109):

```vue
          <td>
            <div class="font-medium line-clamp-1">{{ post.title }}</div>
            <div v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-1 mt-0.5 hidden sm:block">{{ post.excerpt }}</div>
          </td>
```

Replace with:

```vue
          <td>
            <div class="flex items-center gap-3">
              <img
                v-if="post.featured_image_url"
                :src="post.featured_image_url"
                alt=""
                class="hidden sm:block w-10 h-7 rounded object-cover shrink-0 bg-muted"
              />
              <div>
                <div class="font-medium line-clamp-1">{{ post.title }}</div>
                <div v-if="post.excerpt" class="text-xs text-muted-foreground line-clamp-1 mt-0.5 hidden sm:block">{{ post.excerpt }}</div>
              </div>
            </div>
          </td>
```

Design notes:
- `hidden sm:block` — hides thumbnail on mobile (same breakpoint as excerpt) to keep rows compact
- `w-10 h-7` (40×28 px) — compact thumbnail; `object-cover` handles any aspect ratio
- `bg-muted` — visible placeholder while image loads
- `alt=""` — decorative image; title text provides the accessible label
- No new column — the thumbnail lives inside the existing Title cell

- [ ] **Step 2: Build assets**

```bash
npm run build
```

Expected: exits 0. Chunk-size warning for the main bundle is normal and harmless.

- [ ] **Step 3: Run full test suite**

```bash
php artisan test
```

Expected: 309 passing (307 original + 2 from Task 4).

- [ ] **Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue
git commit -m "feat: show featured image thumbnail in Posts index table"
```
