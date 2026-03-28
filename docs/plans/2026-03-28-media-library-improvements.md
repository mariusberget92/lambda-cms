# Media Library Improvements Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Fix bulk selection UX, add "Used in" references, lightbox, file type badges, upload guidance, char count, copy toast, and mobile detail panel.

**Architecture:** Backend adds a single new `usage()` method to `MediaController` and passes `maxUploadMb` to the index view. All frontend work lives in `Media/Index.vue` (existing) and a new `Media/MediaLightbox.vue` component. No new migrations.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4, axios (already used), `@vueuse/core` (already used)

---

## Task 1: Backend — `usage()` endpoint + `maxUploadMb` prop

**Files:**
- Modify: `app/Http/Controllers/MediaController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/MediaTest.php`

### Step 1: Add `usage()` to MediaController

In `MediaController`, import `App\Models\Post` at the top, then add this method after `destroy()`:

```php
use App\Models\Post;

public function usage(Media $media): JsonResponse
{
    $posts = Post::where('featured_image_id', $media->id)
        ->select('id', 'title', 'slug')
        ->get();

    return response()->json(['posts' => $posts]);
}
```

Also update the `index()` method — add `maxUploadMb` to the Inertia render:

```php
return Inertia::render('Media/Index', [
    'media'        => $media,
    'filters'      => $request->only('type', 'search'),
    'maxUploadMb'  => (int) config('media.max_upload_mb', 10),
]);
```

### Step 2: Register the route

In `routes/web.php`, inside the media resource group, add:

```php
Route::get('/media/{media}/usage', [MediaController::class, 'usage'])->name('media.usage');
```

Place it **before** the existing resource routes to avoid `{media}` matching "usage" as a slug.

### Step 3: Write the test

In `tests/Feature/MediaTest.php`, add two tests:

```php
public function test_usage_returns_posts_using_media(): void
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    $media = Media::factory()->create(['user_id' => $admin->id]);
    $post  = Post::factory()->create(['featured_image_id' => $media->id, 'user_id' => $admin->id]);

    $this->actingAs($admin)
        ->getJson(route('media.usage', $media))
        ->assertOk()
        ->assertJsonFragment(['id' => $post->id, 'title' => $post->title]);
}

public function test_usage_returns_empty_when_not_used(): void
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $media = Media::factory()->create(['user_id' => $admin->id]);

    $this->actingAs($admin)
        ->getJson(route('media.usage', $media))
        ->assertOk()
        ->assertJson(['posts' => []]);
}
```

### Step 4: Run the tests

```bash
cd /c/Users/mariu/Herd/lambda-cms
php artisan test --filter=test_usage
```

Expected: 2 tests pass.

### Step 5: Commit

```bash
git add app/Http/Controllers/MediaController.php routes/web.php tests/Feature/MediaTest.php
git commit -m "feat: add media usage endpoint and pass maxUploadMb to index"
```

---

## Task 2: Grid Checkbox Selection + Select All

**Files:**
- Modify: `resources/js/Pages/Media/Index.vue`

### Step 1: Add `toggleSelect` and `selectAll`/`deselectAll` helpers

In the `<script setup>` block, add after the `selected` ref:

```js
function toggleSelect(id, event) {
  event.stopPropagation()
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}

const allSelected = computed(() => localItems.value.length > 0 && localItems.value.every(i => selected.value.includes(i.id)))

function toggleSelectAll() {
  if (allSelected.value) selected.value = []
  else selected.value = localItems.value.map(i => i.id)
}
```

Add `computed` to the `vue` import.

### Step 2: Add "Select all / Deselect all" button to toolbar

In the toolbar `<div class="flex items-center gap-2">` (left side, alongside the type filter and search), add a button after the search input:

```html
<button
  v-if="localItems.length"
  type="button"
  class="rounded-md border px-3 py-2 text-sm text-muted-foreground hover:bg-accent transition-colors"
  @click="toggleSelectAll"
>
  {{ allSelected ? 'Deselect all' : 'Select all' }}
</button>
```

### Step 3: Add checkbox overlay to each grid tile

The current grid tile div starts at:
```html
<div
  v-for="item in localItems"
  :key="item.id"
  class="relative group aspect-square rounded-md overflow-hidden border bg-muted cursor-pointer"
  :class="[
    selected.includes(item.id) ? 'ring-2 ring-primary border-primary' : 'hover:border-foreground/30',
    activeItem?.id === item.id ? 'ring-2 ring-primary border-primary' : '',
  ]"
  @click="openDetail(item)"
>
```

Add a checkbox inside it, as the **first child** (before the `<img>` and the `<div v-else>`):

```html
<!-- Selection checkbox -->
<div
  class="absolute top-1.5 left-1.5 z-10 transition-opacity"
  :class="selected.includes(item.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
  @click.stop="toggleSelect(item.id, $event)"
>
  <div
    class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
    :class="selected.includes(item.id)
      ? 'bg-primary border-primary'
      : 'bg-background/80 border-foreground/40 backdrop-blur-sm'"
  >
    <svg v-if="selected.includes(item.id)" class="w-3 h-3 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
  </div>
</div>
```

### Step 4: Build and verify

```bash
npm run build
```

Verify: hovering a tile shows the checkbox; clicking it selects without opening detail panel; clicking the image itself opens detail panel; "Select all" / "Deselect all" button appears and works.

### Step 5: Commit

```bash
git add resources/js/Pages/Media/Index.vue
git commit -m "feat: add checkbox selection to media grid with select-all toggle"
```

---

## Task 3: "Used In" References in Detail Panel

**Files:**
- Modify: `resources/js/Pages/Media/Index.vue`

### Step 1: Add `usedIn` ref and fetch logic

In `<script setup>`, add:

```js
const usedIn      = ref(null)  // null = not loaded, [] = empty, [...] = posts
const usedInLoading = ref(false)

async function fetchUsage(mediaId) {
  usedIn.value = null
  usedInLoading.value = true
  try {
    const { data } = await axios.get(route('media.usage', mediaId))
    usedIn.value = data.posts
  } catch {
    usedIn.value = []
  } finally {
    usedInLoading.value = false
  }
}
```

Update `openDetail()` to call it:

```js
function openDetail(item) {
  activeItem.value = item
  detailForm.value = { alt: item.alt ?? '', description: item.description ?? '' }
  copied.value     = false
  fetchUsage(item.id)
}
```

Also update `closeDetail()`:

```js
function closeDetail() {
  activeItem.value = null
  usedIn.value     = null
}
```

### Step 2: Add "Used in" section to the detail panel

In the detail panel, after the `<!-- Filename + meta -->` block and before `<!-- Alt text -->`, insert:

```html
<!-- Used in -->
<div class="flex flex-col gap-1">
  <p class="text-xs font-medium text-foreground">Used in</p>
  <div v-if="usedInLoading" class="space-y-1.5">
    <div class="h-3 rounded bg-muted animate-pulse w-3/4" />
    <div class="h-3 rounded bg-muted animate-pulse w-1/2" />
  </div>
  <p v-else-if="!usedIn?.length" class="text-xs text-muted-foreground">Not used anywhere</p>
  <ul v-else class="space-y-1">
    <li v-for="post in usedIn" :key="post.id">
      <a
        :href="route('posts.edit', post.id)"
        target="_blank"
        rel="noopener"
        class="text-xs text-primary hover:underline underline-offset-2 line-clamp-1"
      >
        {{ post.title }}
      </a>
    </li>
  </ul>
</div>
```

### Step 3: Build and verify

```bash
npm run build
```

Open the media library, click an image — "Used in" section should appear and load. If the image is used as a featured image on a post, it should list the post title as a link.

### Step 4: Commit

```bash
git add resources/js/Pages/Media/Index.vue
git commit -m "feat: show 'used in' post references in media detail panel"
```

---

## Task 4: Delete Warning with "Used In" Posts

**Files:**
- Modify: `resources/js/Pages/Media/Index.vue`

### Step 1: Extend the single-delete Dialog

Find the existing `<!-- Single delete confirmation -->` Dialog. Replace its body content with:

```html
<Dialog v-model:open="showSingleConfirm">
  <DialogContent class="max-w-sm">
    <div class="p-6">
      <h3 class="text-base font-semibold mb-2">Delete this file?</h3>
      <p class="text-sm text-muted-foreground mb-1 break-all">{{ activeItem?.original_filename }}</p>

      <!-- Used-in warning -->
      <div v-if="usedIn?.length" class="rounded-md border border-destructive/30 bg-destructive/5 p-3 mb-4 mt-3">
        <p class="text-xs font-medium text-destructive mb-1.5">
          This file is used as the featured image in {{ usedIn.length }} post{{ usedIn.length !== 1 ? 's' : '' }}:
        </p>
        <ul class="space-y-0.5">
          <li v-for="post in usedIn" :key="post.id" class="text-xs text-muted-foreground truncate">
            · {{ post.title }}
          </li>
        </ul>
        <p class="text-xs text-muted-foreground mt-1.5">Deleting will remove the featured image from those posts.</p>
      </div>

      <p v-else class="text-sm text-muted-foreground mb-6">This action cannot be undone.</p>

      <div class="flex justify-end gap-2">
        <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent" @click="showSingleConfirm = false">Cancel</button>
        <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm text-destructive-foreground hover:bg-destructive/90" @click="doSingleDelete">Delete anyway</button>
      </div>
    </div>
  </DialogContent>
</Dialog>
```

No script changes needed — `usedIn` is already populated when the detail panel is open and the delete button is clicked.

### Step 2: Build and verify

```bash
npm run build
```

Test: open a media item used as a featured image, click "Delete file" — the modal should show the warning with post titles.

### Step 3: Commit

```bash
git add resources/js/Pages/Media/Index.vue
git commit -m "feat: warn about post usage in media delete confirmation"
```

---

## Task 5: MediaLightbox Component

**Files:**
- Create: `resources/js/Pages/Media/MediaLightbox.vue`
- Modify: `resources/js/Pages/Media/Index.vue`

### Step 1: Create `MediaLightbox.vue`

```vue
<template>
  <Teleport to="body">
    <Transition name="lightbox">
      <div
        v-if="modelValue !== null"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
        @click.self="close"
      >
        <!-- Close button -->
        <button
          type="button"
          class="absolute top-4 right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
          @click="close"
          aria-label="Close"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>

        <!-- Prev arrow -->
        <button
          v-if="images.length > 1"
          type="button"
          class="absolute left-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
          @click="prev"
          aria-label="Previous image"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>

        <!-- Image -->
        <img
          :src="currentImage.url"
          :alt="currentImage.alt ?? currentImage.original_filename"
          class="max-h-[90vh] max-w-[90vw] object-contain select-none"
          draggable="false"
        />

        <!-- Next arrow -->
        <button
          v-if="images.length > 1"
          type="button"
          class="absolute right-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 transition-colors"
          @click="next"
          aria-label="Next image"
        >
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
          </svg>
        </button>

        <!-- Counter + filename -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-center pointer-events-none">
          <p class="text-white/70 text-xs">{{ currentImage.original_filename }}</p>
          <p v-if="images.length > 1" class="text-white/50 text-xs mt-0.5">{{ modelValue + 1 }} / {{ images.length }}</p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: Number, default: null },  // current index, null = closed
  images:     { type: Array,  default: () => [] },
})

const emit = defineEmits(['update:modelValue'])

const currentImage = computed(() => props.images[props.modelValue] ?? {})

function close() { emit('update:modelValue', null) }
function prev()  { emit('update:modelValue', (props.modelValue - 1 + props.images.length) % props.images.length) }
function next()  { emit('update:modelValue', (props.modelValue + 1) % props.images.length) }

function onKeydown(e) {
  if (props.modelValue === null) return
  if (e.key === 'Escape')     close()
  if (e.key === 'ArrowLeft')  prev()
  if (e.key === 'ArrowRight') next()
}

onMounted(()   => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.lightbox-enter-active, .lightbox-leave-active { transition: opacity 0.2s; }
.lightbox-enter-from, .lightbox-leave-to { opacity: 0; }
</style>
```

### Step 2: Wire lightbox into `Index.vue`

**Import** the component:
```js
import MediaLightbox from './MediaLightbox.vue'
```

**Add refs** in `<script setup>`:
```js
const lightboxIndex = ref(null)

const lightboxImages = computed(() =>
  localItems.value.filter(i => i.type === 'image')
)

function openLightbox(item) {
  const idx = lightboxImages.value.findIndex(i => i.id === item.id)
  if (idx !== -1) lightboxIndex.value = idx
}
```

**Make the preview image clickable** in the detail panel. Find:
```html
<img
  v-if="activeItem.type === 'image'"
  :src="activeItem.url"
  :alt="activeItem.alt ?? activeItem.original_filename"
  class="w-full h-full object-contain"
/>
```
Replace with:
```html
<img
  v-if="activeItem.type === 'image'"
  :src="activeItem.url"
  :alt="activeItem.alt ?? activeItem.original_filename"
  class="w-full h-full object-contain cursor-zoom-in"
  @click="openLightbox(activeItem)"
/>
```

**Add the component** before `</AppLayout>`:
```html
<MediaLightbox v-model="lightboxIndex" :images="lightboxImages" />
```

### Step 3: Build and verify

```bash
npm run build
```

Click any image in the detail panel preview — lightbox opens. Escape closes it. Arrow keys navigate. Counter shows `2 / 14`.

### Step 4: Commit

```bash
git add resources/js/Pages/Media/MediaLightbox.vue resources/js/Pages/Media/Index.vue
git commit -m "feat: add image lightbox with keyboard navigation"
```

---

## Task 6: File Type Badges

**Files:**
- Modify: `resources/js/Pages/Media/Index.vue`

### Step 1: Add `fileTypeBadge` helper

In `<script setup>`:

```js
function fileTypeBadge(mimeType) {
  if (!mimeType) return null
  const map = {
    'application/pdf':                                          'PDF',
    'application/msword':                                       'DOC',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'DOCX',
    'application/vnd.ms-excel':                                 'XLS',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'XLSX',
    'application/zip':                                          'ZIP',
    'video/mp4':    'MP4',
    'video/webm':   'WEBM',
    'video/ogg':    'OGV',
    'audio/mpeg':   'MP3',
    'audio/ogg':    'OGG',
    'audio/wav':    'WAV',
    'audio/webm':   'WEBA',
  }
  return map[mimeType] ?? null
}
```

### Step 2: Add badge to non-image grid tiles

Inside the `<div v-else ...>` (the non-image fallback in the grid tile), after the filename `<span>`, add:

```html
<span
  v-if="fileTypeBadge(item.mime_type)"
  class="text-[9px] font-bold tracking-wide px-1.5 py-0.5 rounded bg-muted-foreground/15 text-muted-foreground uppercase"
>
  {{ fileTypeBadge(item.mime_type) }}
</span>
```

### Step 3: Build and verify

```bash
npm run build
```

Upload a PDF — the grid tile should show a "PDF" label below the document icon.

### Step 4: Commit

```bash
git add resources/js/Pages/Media/Index.vue
git commit -m "feat: add file type badges to non-image media grid tiles"
```

---

## Task 7: Minor Polish

**Files:**
- Modify: `resources/js/Pages/Media/Index.vue`

### Step 1: Add `maxUploadMb` prop

In `defineProps`, add:
```js
maxUploadMb: { type: Number, default: 10 },
```

### Step 2: Add upload guidance text

Find the empty state `<p class="text-sm">Drop files here or click Upload to add media.</p>` and also find where the drop zone renders when there ARE items (the grid container div). Add a guidance line just below the `<!-- Upload progress -->` block and above the `<!-- Empty state -->`:

```html
<p class="text-xs text-muted-foreground mb-3">
  Accepted: JPG, PNG, GIF, WebP, SVG, PDF, MP4, MP3 · Max {{ maxUploadMb }} MB
</p>
```

### Step 3: Add description character count

Find the `<textarea v-model="detailForm.description" ...>` in the detail panel. After the closing `/>`, add:

```html
<p class="text-xs text-right"
   :class="detailForm.description.length >= 1900 ? 'text-destructive' : 'text-muted-foreground'">
  {{ detailForm.description.length }} / 2000
</p>
```

### Step 4: Add toast on copy URL

Find `copyUrl()` in the script. After the `copied.value = true` line, add:
```js
notify('URL copied', 'success')
```

### Step 5: Mobile bottom sheet for detail panel

The detail panel is currently:
```html
<div
  v-if="activeItem"
  class="w-72 shrink-0 rounded-lg border bg-card flex flex-col self-start sticky top-6"
>
```

Replace with a responsive version that's a sidebar on desktop and a bottom sheet on mobile:

```html
<!-- Detail panel — sidebar on md+, bottom sheet on mobile -->
<Teleport to="body">
  <Transition name="sheet">
    <div
      v-if="activeItem"
      class="fixed inset-x-0 bottom-0 z-40 md:hidden rounded-t-2xl border-t bg-card shadow-2xl max-h-[85vh] overflow-y-auto"
    >
      <!-- Drag handle -->
      <div class="flex justify-center pt-3 pb-1">
        <div class="w-10 h-1 rounded-full bg-muted-foreground/30" />
      </div>
      <!-- Close button -->
      <button
        type="button"
        class="absolute top-3 right-4 text-muted-foreground hover:text-foreground"
        @click="closeDetail"
        aria-label="Close"
      >
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
      <!-- Reuse same inner content slot -->
      <MediaDetailContent :active-item="activeItem" :detail-form="detailForm" :used-in="usedIn" :used-in-loading="usedInLoading" :copied="copied" :saving="saving" @copy="copyUrl" @save="saveDetail" @delete="confirmSingleDelete" @close="closeDetail" />
    </div>
  </Transition>
</Teleport>

<!-- Sidebar for md+ -->
<div
  v-if="activeItem"
  class="hidden md:flex w-72 shrink-0 rounded-lg border bg-card flex-col self-start sticky top-6"
>
  <MediaDetailContent :active-item="activeItem" :detail-form="detailForm" :used-in="usedIn" :used-in-loading="usedInLoading" :copied="copied" :saving="saving" @copy="copyUrl" @save="saveDetail" @delete="confirmSingleDelete" @close="closeDetail" />
</div>
```

**Note:** Extracting the panel content into a `MediaDetailContent` component avoids duplication. Create `resources/js/Pages/Media/MediaDetailContent.vue` containing the inner panel markup (preview + fields + actions). Move the existing panel HTML there, accepting props: `activeItem`, `detailForm`, `usedIn`, `usedInLoading`, `copied`, `saving`; emitting: `copy`, `save`, `delete`, `close`.

Add scoped styles for the sheet transition:
```css
<style scoped>
.sheet-enter-active, .sheet-leave-active { transition: transform 0.25s ease; }
.sheet-enter-from, .sheet-leave-to { transform: translateY(100%); }
</style>
```

### Step 6: Build and verify

```bash
npm run build
```

Check:
- Upload guidance text appears above the grid
- Description field shows `0 / 2000` counter that turns red near limit
- Copying a URL shows a toast notification
- On a narrow window (`< 768px`) the detail panel slides up from the bottom when an item is clicked

### Step 7: Commit

```bash
git add resources/js/Pages/Media/Index.vue resources/js/Pages/Media/MediaDetailContent.vue
git commit -m "feat: upload guidance, description char count, copy toast, mobile bottom sheet"
```

---

## Task 8: Final build + push

```bash
cd /c/Users/mariu/Herd/lambda-cms
npm run build
git push origin master
```

Expected: clean build (chunk size warning is pre-existing, not a new error).
