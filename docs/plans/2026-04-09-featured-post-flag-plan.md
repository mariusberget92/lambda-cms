# Featured Post Flag Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Let editors mark a post as "featured" via a checkbox in the post editor, and save that flag through the controller.

**Architecture:** Three-file change — PostController gets `featured` in validation and saves it; Create.vue and Edit.vue each get a featured checkbox in their publishing sidebar. The block editor loop filter already works end-to-end (no changes needed there).

**Tech Stack:** Laravel 12 (PHP), Vue 3 + Inertia.js, Tailwind CSS 4

---

### Task 1: PostController — wire up `featured` in store() and update()

**Files:**
- Modify: `app/Http/Controllers/PostController.php`

**Context:**
`featured` is already in `Post::$fillable` and the `posts` table has the column. The controller just never validates or saves it. Two methods need changes: `store()` and `update()`. There is also an `edit()` method that passes post data back to the Vue page — it needs to include `featured` so the Edit form can pre-fill.

**Step 1: Add `featured` to store() validation and defaults**

In `store()`, find the validation block that contains:
```php
'featured_image_id' => ['nullable', 'exists:media,id'],
'comments_enabled' => ['nullable', 'boolean'],
```

Add `'featured'` immediately after `comments_enabled`:
```php
'featured_image_id' => ['nullable', 'exists:media,id'],
'comments_enabled'  => ['nullable', 'boolean'],
'featured'          => ['nullable', 'boolean'],
```

Then find the defaults block:
```php
$validated['comments_enabled'] = $validated['comments_enabled'] ?? true;
```

Add below it:
```php
$validated['featured'] = $validated['featured'] ?? false;
```

**Step 2: Add `featured` to update() validation and defaults**

Same pattern in `update()`:

In validation:
```php
'featured_image_id' => ['nullable', 'exists:media,id'],
'comments_enabled'  => ['nullable', 'boolean'],
'featured'          => ['nullable', 'boolean'],
```

In defaults:
```php
$validated['comments_enabled'] = $validated['comments_enabled'] ?? $post->comments_enabled;
$validated['featured']         = $validated['featured'] ?? $post->featured;
```

**Step 3: Add `featured` to the edit() post data returned to Vue**

In `edit()` (or whichever method returns the post data for the Edit page), find the array that includes:
```php
'comments_enabled'  => $post->comments_enabled,
```

Add after it:
```php
'featured'          => (bool) $post->featured,
```

**Step 4: Verify by reading the file**

Read `app/Http/Controllers/PostController.php` and confirm all three changes are present.

**Step 5: Commit**
```bash
git add app/Http/Controllers/PostController.php
git commit -m "feat: save featured flag in PostController store and update"
```

---

### Task 2: Create.vue — add Featured checkbox to sidebar

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`

**Context:**
The sidebar has a Comments card that looks like this:
```html
<!-- Comments -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Comments</h3>
  <label class="flex items-center gap-3 cursor-pointer">
    <input
      type="checkbox"
      v-model="form.comments_enabled"
      class="w-4 h-4 rounded border-border accent-nord-green"
    />
    <div>
      <span class="text-sm font-medium">Allow comments</span>
      <p class="text-xs text-muted-foreground">Let readers comment on this post</p>
    </div>
  </label>
</div>
```

The form initial state (in `<script setup>`) includes:
```js
comments_enabled:  true,
```

**Step 1: Add `featured: false` to the form initial state**

In the `useForm({...})` call, find:
```js
comments_enabled:  true,
```

Add `featured` immediately before it:
```js
featured:          false,
comments_enabled:  true,
```

**Step 2: Add the Featured card to the sidebar**

Add a new card immediately after the closing `</div>` of the Comments card:
```html
<!-- Featured -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Featured</h3>
  <label class="flex items-center gap-3 cursor-pointer">
    <input
      type="checkbox"
      v-model="form.featured"
      class="w-4 h-4 rounded border-border accent-nord-green"
    />
    <div>
      <span class="text-sm font-medium">Featured post</span>
      <p class="text-xs text-muted-foreground">Highlight this post in featured loops</p>
    </div>
  </label>
</div>
```

**Step 3: Verify by reading the file**

Read `resources/js/Pages/Posts/Create.vue` and confirm both changes are present.

**Step 4: Commit**
```bash
git add resources/js/Pages/Posts/Create.vue
git commit -m "feat: add featured checkbox to post Create form"
```

---

### Task 3: Edit.vue — add Featured checkbox to sidebar

**Files:**
- Modify: `resources/js/Pages/Posts/Edit.vue`

**Context:**
Same sidebar structure as Create.vue. The form initial state reads from `props.post`:
```js
featured_image_id: props.post.featured_image_id ?? null,
comments_enabled:  props.post.comments_enabled ?? true,
```

**Step 1: Add `featured` to the form initial state**

Find:
```js
comments_enabled:  props.post.comments_enabled ?? true,
```

Add `featured` immediately before it:
```js
featured:          props.post.featured ?? false,
comments_enabled:  props.post.comments_enabled ?? true,
```

**Step 2: Add the Featured card to the sidebar**

Find the Comments card (same HTML as Create.vue). Add the Featured card immediately after its closing `</div>`:
```html
<!-- Featured -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Featured</h3>
  <label class="flex items-center gap-3 cursor-pointer">
    <input
      type="checkbox"
      v-model="form.featured"
      class="w-4 h-4 rounded border-border accent-nord-green"
    />
    <div>
      <span class="text-sm font-medium">Featured post</span>
      <p class="text-xs text-muted-foreground">Highlight this post in featured loops</p>
    </div>
  </label>
</div>
```

**Step 3: Verify by reading the file**

Read `resources/js/Pages/Posts/Edit.vue` and confirm both changes are present.

**Step 4: Commit**
```bash
git add resources/js/Pages/Posts/Edit.vue
git commit -m "feat: add featured checkbox to post Edit form"
```

---

### Task 4: Build and verify

**Step 1: Run Vite build**
```bash
npm run build
```
Expected: `✓ built in X.XXs` with no errors.

**Step 2: Manual verification checklist**

- Open `/posts/create` — confirm "Featured" card appears below "Comments" in the sidebar
- Create a post with "Featured post" checked — verify it saves (check DB or open the post in Edit)
- Open an existing post in `/posts/{id}/edit` — confirm the checkbox reflects the stored value
- In the block editor, add a Loop block with source = Posts, add a filter — confirm "Is Featured" appears in the field dropdown and filtering by `featured = true` works
