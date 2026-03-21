# Editor Autosave, Revisions & Comments Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ship four features: fix Tiptap toolbar icons, add server-side autosave for posts + pages, add revision history (last 25) for posts + pages, and polish the comments moderation UI with inline admin reply.

**Architecture:** Each feature is self-contained. Autosave and revisions use polymorphic Laravel tables (`autosaves`, `revisions`) shared between posts and pages. Comments reply is a self-referential FK on the existing `comments` table. All Vue changes add onto existing `Posts/Edit.vue`, `Pages/Edit.vue`, and `Comments/Index.vue` without replacing them wholesale.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3 (`script setup`), `axios` (already in project via npm), `lucide-vue-next`, Tailwind CSS 4, SQLite.

---

## Task 1: Tiptap Toolbar Icon Fix

**Files:**
- Modify: `resources/js/Components/TiptapEditor.vue`

**Context:** All 16 toolbar buttons currently use raw inline `<svg>` elements (lines 6–80 of TiptapEditor.vue). Project rule: lucide-vue-next only, no raw SVGs. H2/H3 currently use `<span class="text-xs font-bold">` text. All must become lucide components.

---

### Step 1: Add lucide imports to TiptapEditor.vue

In the `<script setup>` block, the current imports are (line 99–108):
```js
import { ref, computed, onBeforeUnmount, watch } from "vue";
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import Placeholder from "@tiptap/extension-placeholder";
import CharacterCount from "@tiptap/extension-character-count";
import Underline from "@tiptap/extension-underline";
import TextAlign from "@tiptap/extension-text-align";
import Image from "@tiptap/extension-image";
import MediaPicker from "@/Components/MediaPicker.vue";
```

Add one new import line after the existing imports, before `const props`:
```js
import {
  Bold, Italic, Underline as UnderlineIcon, Strikethrough,
  Heading2, Heading3, List, ListOrdered, Quote, Code,
  AlignLeft, AlignCenter, AlignRight, Undo2, Redo2, ImageIcon,
} from "lucide-vue-next";
```

Note: `Underline` is already imported from `@tiptap/extension-underline`, so alias the lucide one as `UnderlineIcon`.

---

### Step 2: Replace all raw SVGs in the toolbar

Replace the entire `<div class="toolbar">` block (lines 4–85) with:

```html
<!-- Toolbar -->
<div class="toolbar">
  <div class="toolbar-group">
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()" title="Bold">
      <Bold class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()" title="Italic">
      <Italic class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('underline') }" @click="editor.chain().focus().toggleUnderline().run()" title="Underline">
      <UnderlineIcon class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('strike') }" @click="editor.chain().focus().toggleStrike().run()" title="Strikethrough">
      <Strikethrough class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="toolbar-divider"/>

  <div class="toolbar-group">
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" title="Heading 2">
      <Heading2 class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" title="Heading 3">
      <Heading3 class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="toolbar-divider"/>

  <div class="toolbar-group">
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()" title="Bullet list">
      <List class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()" title="Ordered list">
      <ListOrdered class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('blockquote') }" @click="editor.chain().focus().toggleBlockquote().run()" title="Blockquote">
      <Quote class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive('code') }" @click="editor.chain().focus().toggleCode().run()" title="Code">
      <Code class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="toolbar-divider"/>

  <div class="toolbar-group">
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'left' }) }" @click="editor.chain().focus().setTextAlign('left').run()" title="Align left">
      <AlignLeft class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'center' }) }" @click="editor.chain().focus().setTextAlign('center').run()" title="Align center">
      <AlignCenter class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :class="{ 'is-active': editor?.isActive({ textAlign: 'right' }) }" @click="editor.chain().focus().setTextAlign('right').run()" title="Align right">
      <AlignRight class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="toolbar-divider"/>

  <div class="toolbar-group">
    <button type="button" class="toolbar-btn" :disabled="!editor?.can().undo()" @click="editor.chain().focus().undo().run()" title="Undo">
      <Undo2 class="w-3.5 h-3.5" />
    </button>
    <button type="button" class="toolbar-btn" :disabled="!editor?.can().redo()" @click="editor.chain().focus().redo().run()" title="Redo">
      <Redo2 class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="toolbar-divider"/>
  <div class="toolbar-group">
    <button type="button" class="toolbar-btn" @click="pickerOpen = true" title="Insert image">
      <ImageIcon class="w-3.5 h-3.5" />
    </button>
  </div>

  <div class="ml-auto flex items-center text-xs text-muted-foreground/70 select-none pr-1">
    {{ wordCount }} words
  </div>
</div>
```

---

### Step 3: Verify in browser

Run `npm run dev` (already running in Herd). Open any post edit page. Confirm all toolbar buttons show lucide icons. Click each to verify the editor commands still fire.

---

### Step 4: Commit

```bash
git add resources/js/Components/TiptapEditor.vue
git commit -m "fix: replace raw SVG icons in Tiptap toolbar with lucide-vue-next"
```

---

## Task 2: Autosave (Posts + Pages)

**Files:**
- Create: `database/migrations/2026_03_21_000001_create_autosaves_table.php`
- Create: `app/Models/Autosave.php`
- Create: `app/Http/Controllers/AutosaveController.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/PostController.php` (edit method)
- Modify: `app/Http/Controllers/PageController.php` (edit method)
- Modify: `resources/js/Pages/Posts/Edit.vue`
- Modify: `resources/js/Pages/Pages/Edit.vue`

**Context:** One autosave row per (autosaveable_type, autosaveable_id, user_id) — upserted on every debounced save. The recovery banner shows on page load when `autosave.updated_at > post.updated_at`. Restore merges payload into form then deletes the row. Dismiss just deletes the row.

---

### Step 1: Create the migration

Create `database/migrations/2026_03_21_000001_create_autosaves_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('autosaves', function (Blueprint $table) {
            $table->id();
            $table->morphs('autosaveable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['autosaveable_type', 'autosaveable_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('autosaves');
    }
};
```

Run it:
```bash
php artisan migrate
```

Expected: `Migrating: 2026_03_21_000001_create_autosaves_table` then `Migrated`.

---

### Step 2: Create the Autosave model

Create `app/Models/Autosave.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Autosave extends Model
{
    protected $fillable = [
        'autosaveable_type',
        'autosaveable_id',
        'user_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function autosaveable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### Step 3: Create AutosaveController

Create `app/Http/Controllers/AutosaveController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Autosave;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AutosaveController extends Controller
{
    public function storePost(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $request->validate(['payload' => ['required', 'array']]);

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Post::class,
                'autosaveable_id'   => $post->id,
                'user_id'           => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function storePage(Request $request, Page $page): JsonResponse
    {
        $request->validate(['payload' => ['required', 'array']]);

        Autosave::updateOrCreate(
            [
                'autosaveable_type' => Page::class,
                'autosaveable_id'   => $page->id,
                'user_id'           => $request->user()->id,
            ],
            ['payload' => $request->input('payload')]
        );

        return response()->json(['saved_at' => now()->format('H:i')]);
    }

    public function destroyPost(Request $request, Post $post): JsonResponse
    {
        Autosave::where([
            'autosaveable_type' => Post::class,
            'autosaveable_id'   => $post->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyPage(Request $request, Page $page): JsonResponse
    {
        Autosave::where([
            'autosaveable_type' => Page::class,
            'autosaveable_id'   => $page->id,
            'user_id'           => $request->user()->id,
        ])->delete();

        return response()->json(['ok' => true]);
    }
}
```

---

### Step 4: Add routes

In `routes/web.php`, inside the `auth + verified` middleware group (after line 94 `Route::resource('posts', ...)`), add:

```php
// Autosave
Route::post('/posts/{post}/autosave',   [AutosaveController::class, 'storePost'])->name('posts.autosave');
Route::delete('/posts/{post}/autosave', [AutosaveController::class, 'destroyPost'])->name('posts.autosave.destroy');
```

Inside the `auth + verified + administrator` middleware group (after `Route::resource('pages', ...)`), add:

```php
Route::post('/pages/{page}/autosave',   [AutosaveController::class, 'storePage'])->name('pages.autosave');
Route::delete('/pages/{page}/autosave', [AutosaveController::class, 'destroyPage'])->name('pages.autosave.destroy');
```

Also add the use statement at the top of `routes/web.php`:
```php
use App\Http\Controllers\AutosaveController;
```

---

### Step 5: Pass autosave prop from PostController@edit

In `app/Http/Controllers/PostController.php`, find the `edit` method's `Inertia::render('Posts/Edit', [...])` call. Add `autosave` to the props array after `'tags'`:

```php
'autosave' => \App\Models\Autosave::where([
    'autosaveable_type' => Post::class,
    'autosaveable_id'   => $post->id,
    'user_id'           => $request->user()->id,
])->first()?->only(['payload', 'updated_at']),
```

Also add `'updated_at' => $post->updated_at?->toISOString(),` to the `'post'` array (after `'meta_keywords'`):
```php
'updated_at' => $post->updated_at?->toISOString(),
```

---

### Step 6: Pass autosave prop from PageController@edit

In `app/Http/Controllers/PageController.php`, the `edit` method does `Inertia::render('Pages/Edit', [...])`. Update the signature to accept `Request $page` — wait, it currently has `public function edit(Page $page)`. Add `Request $request` parameter:

```php
public function edit(Request $request, Page $page)
```

Then add `autosave` and `updated_at` to the props:

```php
'autosave' => \App\Models\Autosave::where([
    'autosaveable_type' => Page::class,
    'autosaveable_id'   => $page->id,
    'user_id'           => $request->user()->id,
])->first()?->only(['payload', 'updated_at']),
```

And in the `'page'` array, add:
```php
'updated_at' => $page->updated_at?->toISOString(),
```

Also add at the top of PageController.php:
```php
use Illuminate\Http\Request;
```
(It's already imported — check, if not already there, add it.)

---

### Step 7: Add autosave + recovery banner to Posts/Edit.vue

In `resources/js/Pages/Posts/Edit.vue`:

**Script changes:**

1. Add `watch` to the Vue import (it already imports `ref, computed`, add `watch`):
```js
import { ref, computed, watch } from 'vue'
```

2. After existing imports, add:
```js
import axios from 'axios'
```

3. Update `defineProps` to add `autosave`:
```js
const props = defineProps({
  post:       Object,
  categories: { type: Array, default: () => [] },
  tags:       { type: Array, default: () => [] },
  autosave:   { type: Object, default: null },
});
```

4. After the form definition, add the autosave logic:
```js
// Autosave
const autosaveStatus = ref(null) // null | 'saving' | 'saved' | 'error'
const autosaveSavedAt = ref(null)

const showRestoreBanner = ref(
  props.autosave !== null &&
  props.post.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.post.updated_at)
)

let autosaveTimer = null

watch(form, () => {
  clearTimeout(autosaveTimer)
  autosaveTimer = setTimeout(doAutosave, 10000)
}, { deep: true })

async function doAutosave() {
  autosaveStatus.value = 'saving'
  try {
    const res = await axios.post(route('posts.autosave', props.post.id), {
      payload: form.data(),
    })
    autosaveSavedAt.value = res.data.saved_at
    autosaveStatus.value = 'saved'
  } catch {
    autosaveStatus.value = 'error'
  }
}

async function restoreAutosave() {
  const payload = props.autosave.payload
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
  showRestoreBanner.value = false
  await axios.delete(route('posts.autosave.destroy', props.post.id))
}

async function dismissAutosave() {
  showRestoreBanner.value = false
  await axios.delete(route('posts.autosave.destroy', props.post.id))
}
```

**Template changes:**

1. Add the recovery banner at the very top of the `<form>` tag (before the flex header row):
```html
<!-- Autosave recovery banner -->
<div
  v-if="showRestoreBanner"
  class="mb-4 flex items-center gap-3 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-4 py-3 text-sm"
>
  <span class="flex-1 text-amber-800 dark:text-amber-300">
    You have unsaved changes from a previous session.
  </span>
  <button type="button" @click="restoreAutosave" class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)]">Restore</button>
  <button type="button" @click="dismissAutosave" class="rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent">Dismiss</button>
</div>
```

2. Add the autosave status indicator next to the submit buttons (inside the `<div class="flex gap-2">` after the buttons):
```html
<span v-if="autosaveStatus === 'saving'" class="text-xs text-muted-foreground self-center">Saving draft…</span>
<span v-else-if="autosaveStatus === 'saved'" class="text-xs text-muted-foreground self-center">Draft saved at {{ autosaveSavedAt }}</span>
<span v-else-if="autosaveStatus === 'error'" class="text-xs text-destructive self-center">Autosave failed</span>
```

---

### Step 8: Add autosave + recovery banner to Pages/Edit.vue

Repeat the same changes for `resources/js/Pages/Pages/Edit.vue`:

**Script changes** — add after the `useForm(...)` block:
```js
import axios from 'axios'
```

Update `defineProps` to add `autosave`:
```js
const props = defineProps({
  page:       { type: Object, required: true },
  categories: { type: Array,  default: () => [] },
  tags:       { type: Array,  default: () => [] },
  autosave:   { type: Object, default: null },
})
```

Add `watch` to the vue import line (already has `useForm, usePage, Head`; add watch from vue):
```js
import { watch } from 'vue'
```

Add after form definition:
```js
// Autosave
import { ref } from 'vue'
```

Wait — `ref` may not yet be imported in Pages/Edit.vue. The current script setup only has `import { useForm, usePage, Head } from '@inertiajs/vue3'` and `import { filterEmptyBlocks } from '@/lib/utils.js'`. There are no vue imports. Add:

```js
import { ref, watch } from 'vue'
import axios from 'axios'
```

Add autosave state after the form:
```js
const autosaveStatus = ref(null)
const autosaveSavedAt = ref(null)

const showRestoreBanner = ref(
  props.autosave !== null &&
  props.page.updated_at !== null &&
  new Date(props.autosave.updated_at) > new Date(props.page.updated_at)
)

let autosaveTimer = null

watch(form, () => {
  clearTimeout(autosaveTimer)
  autosaveTimer = setTimeout(doAutosave, 10000)
}, { deep: true })

async function doAutosave() {
  autosaveStatus.value = 'saving'
  try {
    const res = await axios.post(route('pages.autosave', props.page.id), {
      payload: form.data(),
    })
    autosaveSavedAt.value = res.data.saved_at
    autosaveStatus.value = 'saved'
  } catch {
    autosaveStatus.value = 'error'
  }
}

async function restoreAutosave() {
  const payload = props.autosave.payload
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
  showRestoreBanner.value = false
  await axios.delete(route('pages.autosave.destroy', props.page.id))
}

async function dismissAutosave() {
  showRestoreBanner.value = false
  await axios.delete(route('pages.autosave.destroy', props.page.id))
}
```

**Template changes** — add recovery banner at top of `<form>`, and autosave status next to the Update button:

```html
<!-- Autosave recovery banner -->
<div
  v-if="showRestoreBanner"
  class="mb-4 flex items-center gap-3 rounded-md border border-amber-300 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700 px-4 py-3 text-sm"
>
  <span class="flex-1 text-amber-800 dark:text-amber-300">
    You have unsaved changes from a previous session.
  </span>
  <button type="button" @click="restoreAutosave" class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)]">Restore</button>
  <button type="button" @click="dismissAutosave" class="rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent">Dismiss</button>
</div>
```

The Update button is a lone button in the header row. Wrap that area to also show the status:
```html
<div class="flex items-center gap-3">
  <span v-if="autosaveStatus === 'saving'" class="text-xs text-muted-foreground">Saving draft…</span>
  <span v-else-if="autosaveStatus === 'saved'" class="text-xs text-muted-foreground">Draft saved at {{ autosaveSavedAt }}</span>
  <span v-else-if="autosaveStatus === 'error'" class="text-xs text-destructive">Autosave failed</span>
  <button
    type="submit"
    :disabled="form.processing"
    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50 transition-colors"
  >
    {{ form.processing ? 'Saving...' : 'Update page' }}
  </button>
</div>
```

---

### Step 9: Verify autosave works

1. Open a post/page edit page
2. Make a change — wait 10 seconds — confirm the "Draft saved at HH:MM" status appears
3. Reload the page — if `autosave.updated_at > post.updated_at`, banner appears
4. Click Restore — form fields update, banner disappears
5. Click Dismiss — banner disappears only

---

### Step 10: Commit

```bash
git add database/migrations/2026_03_21_000001_create_autosaves_table.php
git add app/Models/Autosave.php
git add app/Http/Controllers/AutosaveController.php
git add routes/web.php
git add app/Http/Controllers/PostController.php
git add app/Http/Controllers/PageController.php
git add resources/js/Pages/Posts/Edit.vue
git add resources/js/Pages/Pages/Edit.vue
git commit -m "feat: server-side autosave for posts and pages"
```

---

## Task 3: Revisions (Posts + Pages)

**Files:**
- Create: `database/migrations/2026_03_21_000002_create_revisions_table.php`
- Create: `app/Models/Revision.php`
- Create: `app/Models/Concerns/HasRevisions.php`
- Create: `app/Http/Controllers/RevisionController.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/PostController.php` (update method + use trait)
- Modify: `app/Http/Controllers/PageController.php` (update method + use trait)
- Modify: `app/Models/Post.php` (use trait)
- Modify: `app/Models/Page.php` (use trait)
- Modify: `resources/js/Pages/Posts/Edit.vue` (revisions panel)
- Modify: `resources/js/Pages/Pages/Edit.vue` (revisions panel)

**Context:** Revisions are immutable snapshots — no `updated_at`. Only `created_at`. Max 25 kept per record (oldest pruned). `restore` returns JSON payload; frontend loads it into the form — user must manually save to commit the restore.

---

### Step 1: Create the migration

Create `database/migrations/2026_03_21_000002_create_revisions_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisable');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
```

Run it:
```bash
php artisan migrate
```

---

### Step 2: Create the Revision model

Create `app/Models/Revision.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Revision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'revisable_type',
        'revisable_id',
        'user_id',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload'    => 'array',
        'created_at' => 'datetime',
    ];

    public function revisable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### Step 3: Create the HasRevisions trait

First create the directory: `app/Models/Concerns/` (if it doesn't exist).

Create `app/Models/Concerns/HasRevisions.php`:

```php
<?php

namespace App\Models\Concerns;

use App\Models\Revision;

trait HasRevisions
{
    public function revisions()
    {
        return $this->morphMany(Revision::class, 'revisable');
    }

    /**
     * Save a snapshot of current DB attributes as a new revision,
     * then prune to keep only the 25 most recent.
     */
    public function saveRevision(int $userId): void
    {
        $this->revisions()->create([
            'user_id'    => $userId,
            'payload'    => $this->getAttributes(),
            'created_at' => now(),
        ]);

        $this->pruneRevisions();
    }

    /**
     * Delete revisions beyond the 25 most recent (by id DESC).
     */
    public function pruneRevisions(): void
    {
        $keepIds = $this->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        $this->revisions()->whereNotIn('id', $keepIds)->delete();
    }
}
```

---

### Step 4: Add the trait to Post and Page models

In `app/Models/Post.php`, add near the top of the class:
```php
use App\Models\Concerns\HasRevisions;
```
And in the class body, add the trait:
```php
use HasRevisions;
```

In `app/Models/Page.php`, do the same.

---

### Step 5: Call saveRevision in PostController@update

In `app/Http/Controllers/PostController.php`, in the `update` method, after the existing lines:
```php
$post->update($validated);
$post->tags()->sync($tagIds);
$post->categories()->sync($categoryIds);
```

Add:
```php
$post->saveRevision($request->user()->id);
```

---

### Step 6: Call saveRevision in PageController@update

In `app/Http/Controllers/PageController.php`, in the `update` method, after:
```php
$page->update($validated);
```

Add:
```php
$page->saveRevision($request->user()->id);
```

---

### Step 7: Create RevisionController

Create `app/Http/Controllers/RevisionController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Post;
use App\Models\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RevisionController extends Controller
{
    public function indexPost(Request $request, Post $post): JsonResponse
    {
        if ($post->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $revisions = $post->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'created_at']);

        return response()->json($revisions);
    }

    public function indexPage(Page $page): JsonResponse
    {
        $revisions = $page->revisions()
            ->orderByDesc('id')
            ->limit(25)
            ->with('user:id,name')
            ->get(['id', 'user_id', 'created_at']);

        return response()->json($revisions);
    }

    public function restore(Revision $revision): JsonResponse
    {
        return response()->json($revision->payload);
    }
}
```

---

### Step 8: Add revision routes

In `routes/web.php`, add the use statement at the top:
```php
use App\Http\Controllers\RevisionController;
```

Inside the `auth + verified` middleware group, add (after autosave routes):
```php
// Revisions
Route::get('/posts/{post}/revisions',   [RevisionController::class, 'indexPost'])->name('posts.revisions');
Route::get('/revisions/{revision}/restore', [RevisionController::class, 'restore'])->name('revisions.restore');
```

Inside the `auth + verified + administrator` middleware group, add (after pages autosave):
```php
Route::get('/pages/{page}/revisions',   [RevisionController::class, 'indexPage'])->name('pages.revisions');
```

---

### Step 9: Add revisions sidebar panel to Posts/Edit.vue

In the `<script setup>` block, add after the autosave section:

```js
// Revisions
const revisionsOpen    = ref(false)
const revisionsLoading = ref(false)
const revisions        = ref([])

async function loadRevisions() {
  if (revisions.value.length > 0) return // already loaded
  revisionsLoading.value = true
  try {
    const res = await axios.get(route('posts.revisions', props.post.id))
    revisions.value = res.data
  } finally {
    revisionsLoading.value = false
  }
}

function toggleRevisions() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) loadRevisions()
}

async function restoreRevision(revision) {
  if (!window.confirm('Restore this version? Your current changes will be replaced.')) return
  const res = await axios.get(route('revisions.restore', revision.id))
  const payload = res.data
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
}
```

In the template, inside the sidebar `<div class="space-y-4">`, add after the Details card:

```html
<!-- Revisions panel -->
<div class="rounded-lg border bg-card">
  <button
    type="button"
    class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium"
    @click="toggleRevisions"
  >
    <span>Revision History</span>
    <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': revisionsOpen }" />
  </button>

  <div v-if="revisionsOpen" class="border-t px-4 py-3 space-y-1">
    <div v-if="revisionsLoading" class="text-xs text-muted-foreground text-center py-3">Loading…</div>
    <div v-else-if="revisions.length === 0" class="text-xs text-muted-foreground text-center py-3">No revisions yet.</div>
    <div
      v-for="rev in revisions"
      :key="rev.id"
      class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted/50"
    >
      <div class="min-w-0">
        <p class="text-xs font-medium truncate">{{ rev.user?.name ?? 'Unknown' }}</p>
        <p class="text-[11px] text-muted-foreground">{{ new Date(rev.created_at).toLocaleString() }}</p>
      </div>
      <button
        type="button"
        class="shrink-0 rounded-md border px-2 py-1 text-xs hover:bg-accent transition-colors"
        @click="restoreRevision(rev)"
      >
        Restore
      </button>
    </div>
  </div>
</div>
```

Add `ChevronDown` to the lucide imports at the top of the script. The current import is just vue+inertia — add:
```js
import { ChevronDown } from 'lucide-vue-next'
```

---

### Step 10: Add revisions sidebar panel to Pages/Edit.vue

Same changes as Step 9 but for pages:

In script, add:
```js
// Revisions
const revisionsOpen    = ref(false)
const revisionsLoading = ref(false)
const revisions        = ref([])

async function loadRevisions() {
  if (revisions.value.length > 0) return
  revisionsLoading.value = true
  try {
    const res = await axios.get(route('pages.revisions', props.page.id))
    revisions.value = res.data
  } finally {
    revisionsLoading.value = false
  }
}

function toggleRevisions() {
  revisionsOpen.value = !revisionsOpen.value
  if (revisionsOpen.value) loadRevisions()
}

async function restoreRevision(revision) {
  if (!window.confirm('Restore this version? Your current changes will be replaced.')) return
  const res = await axios.get(route('revisions.restore', revision.id))
  const payload = res.data
  Object.keys(payload).forEach(key => {
    if (key in form) form[key] = payload[key]
  })
}
```

Add `ChevronDown` import:
```js
import { ChevronDown } from 'lucide-vue-next'
```

In the sidebar (currently has URL Slug, Status, SEO `<details>`), add the revisions panel after the SEO details:

```html
<!-- Revisions panel -->
<div class="rounded-lg border bg-card">
  <button
    type="button"
    class="flex w-full items-center justify-between px-4 py-3 text-sm font-medium"
    @click="toggleRevisions"
  >
    <span>Revision History</span>
    <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': revisionsOpen }" />
  </button>

  <div v-if="revisionsOpen" class="border-t px-4 py-3 space-y-1">
    <div v-if="revisionsLoading" class="text-xs text-muted-foreground text-center py-3">Loading…</div>
    <div v-else-if="revisions.length === 0" class="text-xs text-muted-foreground text-center py-3">No revisions yet.</div>
    <div
      v-for="rev in revisions"
      :key="rev.id"
      class="flex items-center justify-between gap-2 rounded-md px-2 py-1.5 hover:bg-muted/50"
    >
      <div class="min-w-0">
        <p class="text-xs font-medium truncate">{{ rev.user?.name ?? 'Unknown' }}</p>
        <p class="text-[11px] text-muted-foreground">{{ new Date(rev.created_at).toLocaleString() }}</p>
      </div>
      <button
        type="button"
        class="shrink-0 rounded-md border px-2 py-1 text-xs hover:bg-accent transition-colors"
        @click="restoreRevision(rev)"
      >
        Restore
      </button>
    </div>
  </div>
</div>
```

---

### Step 11: Verify revisions

1. Open a post, make changes, click Update — check the revisions sidebar shows an entry
2. Make another change, click Update — two entries appear
3. Click Restore on any entry — confirm dialog appears — confirm — form fields update
4. The form is now dirty with old data; user must click Update to commit it

---

### Step 12: Commit

```bash
git add database/migrations/2026_03_21_000002_create_revisions_table.php
git add app/Models/Revision.php
git add app/Models/Concerns/HasRevisions.php
git add app/Http/Controllers/RevisionController.php
git add routes/web.php
git add app/Http/Controllers/PostController.php
git add app/Http/Controllers/PageController.php
git add app/Models/Post.php
git add app/Models/Page.php
git add resources/js/Pages/Posts/Edit.vue
git add resources/js/Pages/Pages/Edit.vue
git commit -m "feat: revision history for posts and pages (last 25)"
```

---

## Task 4: Comments Moderation UI + Inline Admin Reply

**Files:**
- Create: `database/migrations/2026_03_21_000003_add_parent_id_to_comments_table.php`
- Modify: `app/Models/Comment.php`
- Modify: `app/Http/Controllers/CommentController.php`
- Create: `app/Mail/CommentReplyMail.php`
- Create: `resources/views/emails/comment-reply.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/js/Pages/Comments/Index.vue`

**Context:** The current Comments/Index.vue uses a table layout with 80-char excerpt. We need to switch to a card layout with full body + show-more toggle, initials avatars, and inline reply form. Backend gets a `reply()` method that creates a child comment and optionally emails the parent commenter.

---

### Step 1: Create the parent_id migration

Create `database/migrations/2026_03_21_000003_add_parent_id_to_comments_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('comments')
                ->cascadeOnDelete()
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
```

Run it:
```bash
php artisan migrate
```

---

### Step 2: Update Comment model

In `app/Models/Comment.php`:

1. Add `parent_id` to `$fillable`:
```php
protected $fillable = [
    'post_id',
    'parent_id',
    'user_id',
    'author_name',
    'author_email',
    'body',
    'status',
];
```

2. Add two new relationship methods:
```php
use Illuminate\Database\Eloquent\Relations\HasMany;

public function replies(): HasMany
{
    return $this->hasMany(Comment::class, 'parent_id')->with('user:id,name');
}

public function parent(): BelongsTo
{
    return $this->belongsTo(Comment::class, 'parent_id');
}
```

---

### Step 3: Update CommentController@index to include replies

The current `index` loads `Comment::with(['post:id,title,slug', 'user:id,name'])` and uses `->through(...)` with 80-char excerpts. We need to:
- Filter to top-level comments only (`whereNull('parent_id')`)
- Eager load replies
- Pass full body, not just excerpt
- Pass reply count

Replace the `index` method's query with:

```php
$comments = Comment::with(['post:id,title,slug', 'user:id,name', 'replies.user:id,name'])
    ->whereNull('parent_id')
    ->when($filter !== 'all', fn ($q) => $q->where('status', $filter))
    ->latest()
    ->paginate(25)
    ->withQueryString()
    ->through(fn (Comment $c) => [
        'id'           => $c->id,
        'author_name'  => $c->author_name,
        'author_email' => $c->author_email,
        'body'         => $c->body,
        'status'       => $c->status,
        'created_at'   => $c->created_at->diffForHumans(),
        'post'         => [
            'title' => $c->post->title,
            'slug'  => $c->post->slug,
        ],
        'replies' => $c->replies->map(fn ($r) => [
            'id'          => $r->id,
            'author_name' => $r->author_name,
            'body'        => $r->body,
            'created_at'  => $r->created_at->diffForHumans(),
        ])->values(),
    ]);
```

---

### Step 4: Add reply() method to CommentController

Add after the `bulk()` method:

```php
/**
 * Admin — reply to a comment, optionally notify the original commenter.
 */
public function reply(Request $request, Comment $comment): RedirectResponse
{
    $validated = $request->validate([
        'body' => ['required', 'string', 'max:2000'],
    ]);

    $reply = Comment::create([
        'post_id'      => $comment->post_id,
        'parent_id'    => $comment->id,
        'user_id'      => $request->user()->id,
        'author_name'  => $request->user()->name,
        'author_email' => $request->user()->email,
        'body'         => $validated['body'],
        'status'       => 'approved',
    ]);

    if ($comment->author_email) {
        \Mail::to($comment->author_email)
            ->queue(new \App\Mail\CommentReplyMail($comment, $reply));
    }

    return back()->with('status', 'Reply sent.');
}
```

---

### Step 5: Create CommentReplyMail

Create `app/Mail/CommentReplyMail.php`:

```php
<?php

namespace App\Mail;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommentReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Comment $parent,
        public readonly Comment $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Someone replied to your comment on "' . $this->parent->post->title . '"',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.comment-reply',
        );
    }
}
```

---

### Step 6: Create the reply email Blade view

Create `resources/views/emails/comment-reply.blade.php`:

```blade
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; color: #333;">
  <h2 style="margin-bottom: 8px;">Someone replied to your comment</h2>
  <p style="color: #666;">Your comment on <strong>{{ $parent->post->title }}</strong>:</p>
  <blockquote style="border-left: 3px solid #ddd; margin: 8px 0; padding: 8px 16px; color: #555;">
    {{ $parent->body }}
  </blockquote>
  <p style="color: #666;">Reply from <strong>{{ $reply->author_name }}</strong>:</p>
  <blockquote style="border-left: 3px solid #5e81ac; margin: 8px 0; padding: 8px 16px; color: #333;">
    {{ $reply->body }}
  </blockquote>
</body>
</html>
```

---

### Step 7: Add reply route

In `routes/web.php`, add the use statement:
```php
// Already imported: use App\Http\Controllers\CommentController;
```

Inside the `auth + verified + administrator` group, after the existing comment routes, add:
```php
Route::post('/comments/{comment}/reply', [CommentController::class, 'reply'])->name('comments.reply');
```

---

### Step 8: Rewrite Comments/Index.vue

The current table-based UI needs to become a card layout. Replace the entire template and script with the following.

The Nord accent colors for avatars (cycling by first letter): use a simple CSS class cycle. We'll pick from: `#5e81ac` (blue), `#88c0d0` (teal), `#a3be8c` (green), `#ebcb8b` (yellow), `#d08770` (orange), `#b48ead` (purple).

Full replacement of `resources/js/Pages/Comments/Index.vue`:

```vue
<template>
  <AppLayout title="Comments">
    <Head title="Comments" />

    <!-- Page header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-lg font-semibold">Comments</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Moderate reader comments</p>
      </div>
    </div>

    <!-- Flash message -->
    <Transition name="fade">
      <div
        v-if="$page.props.flash?.status"
        class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
      >
        <CircleCheck class="w-4 h-4 shrink-0" />
        {{ $page.props.flash.status }}
      </div>
    </Transition>

    <!-- Filter tabs -->
    <div class="flex gap-1 mb-4 border-b">
      <a
        v-for="tab in tabs"
        :key="tab.value"
        :href="route('comments.index') + (tab.value !== 'pending' ? '?filter=' + tab.value : '')"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
        :class="filter === tab.value
          ? 'border-primary text-foreground'
          : 'border-transparent text-muted-foreground hover:text-foreground'"
      >
        {{ tab.label }}
        <span v-if="tab.value === 'pending' && pendingCount" class="ml-1.5 rounded-full bg-destructive px-1.5 py-0.5 text-[10px] font-semibold text-destructive-foreground">{{ pendingCount }}</span>
      </a>
    </div>

    <!-- Bulk actions bar -->
    <Transition name="fade">
      <div v-if="selected.length" class="flex items-center gap-3 mb-4 rounded-md border bg-muted/50 px-4 py-2.5 text-sm">
        <span class="text-muted-foreground">{{ selected.length }} selected</span>
        <div class="flex gap-2 ml-auto">
          <button type="button" @click="bulkAction('approve')" class="rounded-md bg-status-success-bg px-3 py-1.5 text-xs font-medium text-status-success-fg hover:opacity-80 transition-opacity">Approve</button>
          <button type="button" @click="bulkAction('reject')" class="rounded-md bg-status-warning-bg px-3 py-1.5 text-xs font-medium text-status-warning-fg hover:opacity-80 transition-opacity">Reject</button>
          <button type="button" @click="bulkAction('delete')" class="rounded-md bg-destructive/10 px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors">Delete</button>
        </div>
      </div>
    </Transition>

    <!-- Empty state -->
    <div v-if="comments.data.length === 0" class="py-16 text-center">
      <MessageSquare class="w-10 h-10 mx-auto mb-3 text-muted-foreground/30" />
      <p class="text-muted-foreground text-sm">No comments in this category.</p>
    </div>

    <!-- Comment cards -->
    <div v-else class="space-y-3">
      <div
        v-for="comment in comments.data"
        :key="comment.id"
        class="rounded-lg border bg-card"
      >
        <!-- Card header -->
        <div class="flex items-start gap-3 p-4">
          <!-- Checkbox -->
          <input
            type="checkbox"
            :value="comment.id"
            v-model="selected"
            class="mt-1 rounded border-border accent-nord-green"
          />

          <!-- Avatar -->
          <div
            class="shrink-0 w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold text-white"
            :style="{ backgroundColor: avatarColor(comment.author_name) }"
          >
            {{ initials(comment.author_name) }}
          </div>

          <!-- Author + meta -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-semibold">{{ comment.author_name }}</span>
              <span v-if="comment.author_email" class="text-xs text-muted-foreground">{{ comment.author_email }}</span>
              <span class="text-xs text-muted-foreground">· {{ comment.created_at }}</span>
            </div>
            <a
              :href="`/blog/${comment.post.slug}`"
              target="_blank"
              rel="noopener"
              class="text-xs text-primary hover:underline mt-0.5 inline-block"
            >
              {{ comment.post.title }}
            </a>
          </div>

          <!-- Status badge -->
          <span
            class="shrink-0 text-[11px] font-semibold px-2 py-0.5 rounded-full"
            :class="{
              'bg-status-warning-bg text-status-warning-fg': comment.status === 'pending',
              'bg-status-success-bg text-status-success-fg': comment.status === 'approved',
              'bg-muted text-muted-foreground': comment.status === 'rejected',
            }"
          >{{ comment.status }}</span>
        </div>

        <!-- Body -->
        <div class="px-4 pb-3 pl-16">
          <p class="text-sm text-foreground whitespace-pre-wrap leading-relaxed">
            {{ expanded[comment.id] ? comment.body : truncate(comment.body) }}
          </p>
          <button
            v-if="comment.body.length > 300"
            type="button"
            class="mt-1 text-xs text-primary hover:underline"
            @click="toggle(comment.id)"
          >
            {{ expanded[comment.id] ? 'Show less' : 'Show more' }}
          </button>
        </div>

        <!-- Action footer -->
        <div class="flex items-center gap-2 px-4 py-2.5 border-t pl-16 flex-wrap">
          <button
            v-if="comment.status !== 'approved'"
            type="button"
            @click="router.patch(route('comments.approve', comment.id))"
            class="rounded-md bg-status-success-bg px-3 py-1 text-xs font-medium text-status-success-fg hover:opacity-80 transition-opacity"
          >Approve</button>
          <button
            v-if="comment.status !== 'rejected'"
            type="button"
            @click="router.patch(route('comments.reject', comment.id))"
            class="rounded-md bg-status-warning-bg px-3 py-1 text-xs font-medium text-status-warning-fg hover:opacity-80 transition-opacity"
          >Reject</button>
          <button
            type="button"
            @click="router.delete(route('comments.destroy', comment.id))"
            class="rounded-md bg-destructive/10 px-3 py-1 text-xs font-medium text-destructive hover:bg-destructive/20 transition-colors"
          >Delete</button>

          <!-- Reply button (only on top-level, not on replies) -->
          <button
            type="button"
            class="ml-auto rounded-md border px-3 py-1 text-xs font-medium hover:bg-accent transition-colors flex items-center gap-1"
            @click="toggleReply(comment.id)"
          >
            <MessageSquare class="w-3 h-3" />
            {{ comment.replies?.length ? `Reply (${comment.replies.length})` : 'Reply' }}
          </button>
        </div>

        <!-- Existing replies -->
        <div v-if="comment.replies?.length" class="border-t divide-y divide-border ml-16">
          <div v-for="reply in comment.replies" :key="reply.id" class="px-4 py-3 bg-muted/20">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-semibold">{{ reply.author_name }}</span>
              <span class="text-xs text-muted-foreground">· {{ reply.created_at }}</span>
              <span class="text-[10px] bg-primary/10 text-primary px-1.5 py-0.5 rounded-full font-medium">Admin reply</span>
            </div>
            <p class="text-sm text-foreground whitespace-pre-wrap">{{ reply.body }}</p>
          </div>
        </div>

        <!-- Inline reply form -->
        <div v-if="replyingTo === comment.id" class="border-t px-4 py-3 ml-16 bg-muted/10">
          <textarea
            v-model="replyBody"
            rows="3"
            placeholder="Write a reply…"
            class="w-full rounded-md border bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-ring"
          />
          <div class="flex gap-2 mt-2">
            <button
              type="button"
              class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-[var(--primary-hover)] disabled:opacity-50"
              :disabled="!replyBody.trim()"
              @click="sendReply(comment.id)"
            >Send reply</button>
            <button
              type="button"
              class="rounded-md border px-3 py-1.5 text-xs font-medium hover:bg-accent"
              @click="cancelReply"
            >Cancel</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="comments.last_page > 1" class="mt-6 flex justify-center gap-2">
      <a
        v-for="page in comments.links"
        :key="page.label"
        :href="page.url"
        v-html="page.label"
        class="rounded-md border px-3 py-1.5 text-sm transition-colors"
        :class="page.active
          ? 'bg-primary text-primary-foreground border-primary'
          : page.url ? 'hover:bg-accent' : 'opacity-40 cursor-default pointer-events-none'"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { CircleCheck, MessageSquare } from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  comments:     Object,
  filter:       { type: String, default: 'pending' },
  pendingCount: { type: Number, default: 0 },
})

const tabs = [
  { value: 'pending',  label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
  { value: 'all',      label: 'All' },
]

// Selection
const selected = ref([])
const allSelected = ref(false)

function toggleAll() {
  selected.value = selected.value.length === props.comments.data.length
    ? []
    : props.comments.data.map(c => c.id)
}

function bulkAction(action) {
  router.post(route('comments.bulk'), { action, ids: selected.value }, {
    onSuccess: () => { selected.value = [] },
  })
}

// Show more / less
const expanded = reactive({})
function truncate(body) {
  return body.length > 300 ? body.slice(0, 300) + '…' : body
}
function toggle(id) {
  expanded[id] = !expanded[id]
}

// Reply
const replyingTo = ref(null)
const replyBody  = ref('')

function toggleReply(id) {
  replyingTo.value = replyingTo.value === id ? null : id
  replyBody.value  = ''
}

function cancelReply() {
  replyingTo.value = null
  replyBody.value  = ''
}

function sendReply(commentId) {
  router.post(route('comments.reply', commentId), { body: replyBody.value }, {
    onSuccess: () => cancelReply(),
  })
}

// Avatar helpers
const AVATAR_COLORS = [
  '#5e81ac', '#88c0d0', '#a3be8c', '#ebcb8b', '#d08770', '#b48ead',
]

function avatarColor(name) {
  const code = (name ?? 'A').charCodeAt(0)
  return AVATAR_COLORS[code % AVATAR_COLORS.length]
}

function initials(name) {
  return (name ?? '?')
    .split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>
```

---

### Step 9: Verify comments UI

1. Go to `/comments` as admin
2. Confirm cards show avatar, full body, show-more toggle for long comments
3. Click Reply → inline textarea appears → type and Send → reload, reply appears indented
4. Approve/Reject/Delete buttons still work
5. Bulk selection + bulk actions still work

---

### Step 10: Commit

```bash
git add database/migrations/2026_03_21_000003_add_parent_id_to_comments_table.php
git add app/Models/Comment.php
git add app/Http/Controllers/CommentController.php
git add app/Mail/CommentReplyMail.php
git add resources/views/emails/comment-reply.blade.php
git add routes/web.php
git add resources/js/Pages/Comments/Index.vue
git commit -m "feat: comments UI polish and inline admin reply"
```

---

## Final: Finish Development Branch

After all 4 tasks are committed, use the `superpowers:finishing-a-development-branch` skill to merge, push, and reset the DB.
