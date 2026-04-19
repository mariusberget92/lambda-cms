# Features Implementation Plan (FIX.txt 6, 7, 8, 9, 11, 12)

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement six features: Navigation block, front-page redesign, category colorization, parallax on section/container blocks, dynamic media type settings, and accent color theming.

**Architecture:** Backend-first for features with DB/config changes (12, 8, 11), then block editor (9, 6), then pure visual (7). Each task commits independently. Tests cover all PHP changes; Vue components are manually verified.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3 `<script setup>`, Tailwind CSS 4, VueDraggable Plus, PHPUnit 11, SQLite (test DB).

---

## Task 1: Feature 12 — Accent color backend

**Files:**
- Modify: `database/seeders/SettingsSeeder.php`
- Modify: `app/Http/Controllers/SettingsController.php`
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`
- Modify: `resources/views/app.blade.php`
- Test: `tests/Feature/SettingsTest.php`

**Step 1: Write failing test**

Open `tests/Feature/SettingsTest.php` and add at the end of the class (before closing `}`):

```php
public function test_admin_can_save_accent_color(): void
{
    $admin = $this->makeAdmin();
    $this->actingAs($admin)
        ->post('/settings/appearance', ['site.accent_color' => '#a3be8c'])
        ->assertRedirect();

    $this->assertDatabaseHas('settings', [
        'key'   => 'site.accent_color',
        'value' => '#a3be8c',
    ]);
}

public function test_accent_color_is_shared_as_inertia_prop(): void
{
    Setting::set('site.accent_color', '#a3be8c');

    $admin = $this->makeAdmin();
    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertInertia(fn ($page) =>
        $page->where('accentColor', '#a3be8c')
    );
}
```

**Step 2: Run to confirm failure**

```bash
php artisan test tests/Feature/SettingsTest.php --filter="accent" --no-coverage
```
Expected: 2 failures — route not found / key not found.

**Step 3: Add setting row to seeder**

In `database/seeders/SettingsSeeder.php`, inside the `$defaults` array, after the site URL entry add:

```php
['group' => 'site', 'key' => 'site.accent_color', 'value' => '', 'type' => 'string'],
```

**Step 4: Add route + controller method**

In `routes/web.php`, inside the admin settings route group (wherever `Route::post('/settings/{group}', ...)` is), confirm the existing generic route handles `appearance`. If the controller uses a `{group}` parameter, it should already work — check that `appearance` is in the allowed list.

Open `app/Http/Controllers/SettingsController.php`. In the `update()` method, find the `match ($group => ...)` or similar branching logic. Add a case for `'appearance'`:

```php
'appearance' => $request->validate([
    'site\.accent_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
]),
```

Also add `site.accent_color` to any list of keys that `Setting::set()` is called for. Look at how the existing groups iterate over validated data and call `Setting::set($key, $value)` — the appearance group follows the same pattern.

**Step 5: Re-seed the setting**

```bash
php artisan db:seed --class=SettingsSeeder
```

**Step 6: Share accent color via Inertia**

In `app/Http/Middleware/HandleInertiaRequests.php`, add to the `share()` return array:

```php
'accentColor' => fn () => Setting::get('site.accent_color') ?: null,
```

Add `use App\Models\Setting;` at the top if not already present.

**Step 7: Inject CSS variable in blade**

In `resources/views/app.blade.php`, add after `@routes`:

```blade
@php
    $accentColor = \App\Models\Setting::get('site.accent_color');
    $hoverMap = [
        '#5e81ac' => '#4a6d92',
        '#a3be8c' => '#8aaa70',
        '#ebcb8b' => '#d4b06a',
        '#d08770' => '#bb6f58',
        '#bf616a' => '#a84d56',
        '#b48ead' => '#9d7596',
    ];
    $accentHover = $hoverMap[$accentColor] ?? null;
@endphp
@if($accentColor)
<style>
:root {
    --primary: {{ $accentColor }};
    --primary-hover: {{ $accentHover ?? $accentColor }};
    --primary-foreground: #ffffff;
}
</style>
@endif
```

**Step 8: Run tests**

```bash
php artisan test tests/Feature/SettingsTest.php --filter="accent" --no-coverage
```
Expected: 2 passing.

**Step 9: Commit**

```bash
git add database/seeders/SettingsSeeder.php app/Http/Controllers/SettingsController.php app/Http/Middleware/HandleInertiaRequests.php resources/views/app.blade.php tests/Feature/SettingsTest.php
git commit -m "feat: add accent color setting backend + CSS variable injection"
```

---

## Task 2: Feature 12 — Settings Appearance UI

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`

**Step 1: Read the file**

Read `resources/js/Pages/Settings/Index.vue` in full. Identify:
- The `tabs` array (has entries like `{ id: 'site', label: 'Site' }`)
- The `activeTab` ref
- The pattern for form submission (each tab has its own `useForm` + `submit*()` function)
- The tab bar template

**Step 2: Add Appearance tab**

In the `tabs` array, add:
```js
{ id: 'appearance', label: 'Appearance' },
```

**Step 3: Add form**

In the `<script setup>`, add:

```js
const ACCENT_SWATCHES = [
  { label: 'Frost Blue (default)', value: '#5e81ac', hover: '#4a6d92' },
  { label: 'Nord Green',           value: '#a3be8c', hover: '#8aaa70' },
  { label: 'Nord Yellow',          value: '#ebcb8b', hover: '#d4b06a' },
  { label: 'Nord Orange',          value: '#d08770', hover: '#bb6f58' },
  { label: 'Nord Red',             value: '#bf616a', hover: '#a84d56' },
  { label: 'Nord Purple',          value: '#b48ead', hover: '#9d7596' },
]

const appearanceForm = useForm({
  'site.accent_color': props.settings['site.accent_color'] ?? '#5e81ac',
})

function submitAppearance() {
  appearanceForm.post(route('settings.update', 'appearance'), {
    preserveScroll: true,
  })
}
```

**Step 4: Add tab panel template**

After the last `</div>` closing a `v-show` tab panel, add:

```html
<!-- Appearance -->
<div v-show="activeTab === 'appearance'">
  <form @submit.prevent="submitAppearance">
    <div class="rounded-lg border bg-card p-6 space-y-4">
      <div>
        <h3 class="text-sm font-semibold">Appearance</h3>
        <p class="text-xs text-muted-foreground mt-0.5">Choose an accent color for the admin interface and public site.</p>
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium">Accent color</label>
        <div class="flex flex-wrap gap-3 mt-2">
          <button
            v-for="swatch in ACCENT_SWATCHES"
            :key="swatch.value"
            type="button"
            :title="swatch.label"
            @click="appearanceForm['site.accent_color'] = swatch.value"
            class="relative w-9 h-9 rounded-full border-2 transition-all focus:outline-none"
            :style="{ backgroundColor: swatch.value }"
            :class="appearanceForm['site.accent_color'] === swatch.value
              ? 'border-foreground scale-110 shadow-md'
              : 'border-transparent hover:scale-105'"
          >
            <svg
              v-if="appearanceForm['site.accent_color'] === swatch.value"
              class="w-4 h-4 absolute inset-0 m-auto text-white drop-shadow"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </button>
        </div>
        <p class="text-xs text-muted-foreground mt-1">
          Selected: <code class="font-mono">{{ appearanceForm['site.accent_color'] }}</code>
        </p>
      </div>

      <div class="flex justify-end pt-1">
        <button
          type="submit"
          :disabled="appearanceForm.processing"
          class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-[var(--primary-hover)] disabled:opacity-50"
        >
          {{ appearanceForm.processing ? 'Saving...' : 'Save changes' }}
        </button>
      </div>
    </div>
  </form>
</div>
```

**Step 5: Verify manually**

Run `npm run dev`, visit `/settings`, click "Appearance" tab. Verify swatches render, clicking one selects it (ring + checkmark), saving posts to `/settings/appearance`.

**Step 6: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue
git commit -m "feat: add appearance tab with accent color swatch picker"
```

---

## Task 3: Feature 8 — Category color migration + backend

**Files:**
- Create: `database/migrations/TIMESTAMP_add_color_to_categories_table.php`
- Modify: `app/Models/Category.php`
- Modify: `app/Http/Controllers/CategoryController.php`
- Test: `tests/Feature/CategoryTest.php`

**Step 1: Write failing tests**

Add to `tests/Feature/CategoryTest.php`:

```php
public function test_user_can_create_category_with_color(): void
{
    $this->actingAs($this->makeUser())
        ->post('/categories', ['name' => 'Colorful', 'color' => '#a3be8c'])
        ->assertRedirect('/categories');

    $this->assertDatabaseHas('categories', ['name' => 'Colorful', 'color' => '#a3be8c']);
}

public function test_invalid_color_is_rejected(): void
{
    $this->actingAs($this->makeUser())
        ->post('/categories', ['name' => 'Bad Color', 'color' => 'notacolor'])
        ->assertSessionHasErrors('color');
}

public function test_color_is_returned_in_edit_response(): void
{
    $cat = Category::factory()->create(['color' => '#bf616a']);
    $response = $this->actingAs($this->makeUser())
        ->get("/categories/{$cat->id}/edit");
    $response->assertInertia(fn ($page) =>
        $page->where('category.color', '#bf616a')
    );
}
```

**Step 2: Run to confirm failures**

```bash
php artisan test tests/Feature/CategoryTest.php --filter="color" --no-coverage
```
Expected: 3 failures.

**Step 3: Create migration**

```bash
php artisan make:migration add_color_to_categories_table
```

Edit the generated file:

```php
public function up(): void
{
    Schema::table('categories', function (Blueprint $table) {
        $table->string('color', 7)->nullable()->after('description');
    });
}

public function down(): void
{
    Schema::table('categories', function (Blueprint $table) {
        $table->dropColumn('color');
    });
}
```

Run it:

```bash
php artisan migrate
```

**Step 4: Update Category model**

In `app/Models/Category.php`, add `'color'` to the `$fillable` array.

**Step 5: Update CategoryController**

In `store()`, add to the `validate()` array:
```php
'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/'],
```

Same in `update()`.

In `edit()`, add `'color' => $category->color` to the array passed as `category` prop.

In the `index()` map, add `'color' => $c->color`.

**Step 6: Run tests**

```bash
php artisan test tests/Feature/CategoryTest.php --no-coverage
```
Expected: all pass.

**Step 7: Commit**

```bash
git add database/migrations/ app/Models/Category.php app/Http/Controllers/CategoryController.php tests/Feature/CategoryTest.php
git commit -m "feat: add color column to categories with validation"
```

---

## Task 4: Feature 8 — ColorPickerPopover.vue component

**Files:**
- Create: `resources/js/Components/ColorPickerPopover.vue`

**Step 1: Create the component**

```vue
<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: null },
  label:      { type: String, default: 'Color' },
})
const emit = defineEmits(['update:modelValue'])

const NORD_SWATCHES = [
  '#5e81ac', '#81a1c1', '#88c0d0', '#8fbcbb',
  '#a3be8c', '#ebcb8b', '#d08770', '#bf616a',
  '#b48ead', '#4c566a',
]

const open        = ref(false)
const showCustom  = ref(false)

const selected = computed(() => props.modelValue)

function pick(hex) {
  emit('update:modelValue', hex)
  showCustom.value = false
}

function clear() {
  emit('update:modelValue', null)
  showCustom.value = false
}

function onCustomInput(e) {
  emit('update:modelValue', e.target.value)
}

function toggle() {
  open.value = !open.value
  if (!open.value) showCustom.value = false
}
</script>

<template>
  <div class="relative inline-block">
    <!-- Trigger -->
    <button
      type="button"
      @click="toggle"
      class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-accent transition-colors focus:outline-none focus:ring-2 focus:ring-ring"
    >
      <span
        class="w-5 h-5 rounded-full border border-border transition-colors shrink-0"
        :style="selected ? { backgroundColor: selected } : {}"
        :class="!selected ? 'bg-muted' : ''"
      />
      <span class="text-muted-foreground">{{ selected ?? 'None' }}</span>
      <svg class="w-3.5 h-3.5 text-muted-foreground ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Popover panel -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="open"
        class="absolute left-0 top-full mt-1.5 z-50 w-56 rounded-xl border bg-card shadow-xl p-3 space-y-3"
      >
        <!-- Nord swatches grid -->
        <div>
          <p class="text-xs text-muted-foreground mb-2 font-medium">Nord palette</p>
          <div class="grid grid-cols-5 gap-2">
            <button
              v-for="hex in NORD_SWATCHES"
              :key="hex"
              type="button"
              @click="pick(hex)"
              :title="hex"
              class="relative w-8 h-8 rounded-full border-2 transition-all hover:scale-110 focus:outline-none"
              :style="{ backgroundColor: hex }"
              :class="selected === hex ? 'border-foreground shadow-md' : 'border-transparent'"
            >
              <svg
                v-if="selected === hex"
                class="w-3.5 h-3.5 absolute inset-0 m-auto text-white drop-shadow"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Custom color -->
        <div class="border-t pt-2">
          <button
            type="button"
            @click="showCustom = !showCustom"
            class="text-xs text-primary hover:underline"
          >
            {{ showCustom ? 'Hide' : 'Custom color…' }}
          </button>
          <div v-if="showCustom" class="flex items-center gap-2 mt-2">
            <input
              type="color"
              :value="selected ?? '#5e81ac'"
              @input="onCustomInput"
              class="h-8 w-10 cursor-pointer rounded border border-border"
            />
            <span class="text-xs text-muted-foreground font-mono">{{ selected ?? '—' }}</span>
          </div>
        </div>

        <!-- Footer actions -->
        <div class="border-t pt-2 flex items-center justify-between">
          <button
            v-if="selected"
            type="button"
            @click="clear"
            class="text-xs text-muted-foreground hover:text-destructive transition-colors"
          >
            Clear
          </button>
          <button
            type="button"
            @click="open = false"
            class="ml-auto text-xs text-muted-foreground hover:text-foreground transition-colors"
          >
            Done
          </button>
        </div>
      </div>
    </Transition>

    <!-- Click-outside overlay -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
  </div>
</template>
```

**Step 2: Commit**

```bash
git add resources/js/Components/ColorPickerPopover.vue
git commit -m "feat: add ColorPickerPopover component with Nord swatches + custom picker"
```

---

## Task 5: Feature 8 — Category form integration + badge rendering

**Files:**
- Modify: `resources/js/Pages/Categories/Form.vue` (or Create.vue / Edit.vue — check which file is used for both create and edit)
- Modify: `resources/js/Components/PostCard.vue`
- Modify: `resources/js/Pages/Blog/Show.vue`
- Modify: `resources/js/Pages/Blog/Archive.vue` (if it exists)
- Modify: `resources/js/Pages/Categories/Index.vue` (admin list — show color dot)

**Step 1: Find the category form file**

```bash
ls resources/js/Pages/Categories/
```

There should be a `Form.vue` used for both create and edit (the controller renders `Categories/Form` in both cases — see Task 3 Step 5 above).

**Step 2: Add color picker to category form**

In `resources/js/Pages/Categories/Form.vue`:

1. Import: `import ColorPickerPopover from '@/Components/ColorPickerPopover.vue'`
2. Add `color` to the `useForm()` definition: `color: props.category?.color ?? null`
3. Add to the template after the description field:

```html
<div class="space-y-1">
  <label class="text-sm font-medium">Category color <span class="text-xs text-muted-foreground font-normal">(optional)</span></label>
  <ColorPickerPopover v-model="form.color" />
  <p v-if="form.errors.color" class="text-xs text-destructive">{{ form.errors.color }}</p>
</div>
```

**Step 3: Helper function for badge styles**

This pattern is used in multiple files. In each file that renders category badges, add this helper:

```js
function categoryStyle(cat) {
  if (!cat.color) return {}
  return {
    backgroundColor: cat.color + '20', // 12% opacity hex suffix
    color: cat.color,
    borderColor: cat.color + '40',
  }
}

function categoryClass(cat) {
  return cat.color
    ? 'border'
    : 'bg-primary/10 text-primary'
}
```

**Step 4: Update PostCard.vue**

Replace the existing category badge span:

```html
<!-- Before -->
<span class="inline-block text-xs font-medium bg-primary/10 text-primary px-2 py-0.5 rounded-full">

<!-- After -->
<span
  class="inline-block text-xs font-medium px-2 py-0.5 rounded-full"
  :class="categoryClass(cat)"
  :style="categoryStyle(cat)"
>
```

Add the `categoryStyle` and `categoryClass` helper functions in `<script setup>`.

**Step 5: Update Blog/Show.vue**

Same replacement for the category badge spans in the single post view.

**Step 6: Update Categories/Index.vue (admin)**

Add a color dot before each category name in the admin list:

```html
<span
  v-if="cat.color"
  class="inline-block w-2.5 h-2.5 rounded-full mr-1.5 shrink-0"
  :style="{ backgroundColor: cat.color }"
/>
```

**Step 7: Commit**

```bash
git add resources/js/Pages/Categories/ resources/js/Components/PostCard.vue resources/js/Pages/Blog/Show.vue
git commit -m "feat: integrate category color picker in forms and badge rendering"
```

---

## Task 6: Feature 11 — Media settings backend

**Files:**
- Modify: `database/seeders/SettingsSeeder.php`
- Modify: `config/media.php`
- Modify: `app/Http/Controllers/SettingsController.php`
- Modify: `app/Http/Controllers/MediaController.php`
- Test: `tests/Feature/SettingsTest.php`

**Step 1: Write failing test**

Add to `tests/Feature/SettingsTest.php`:

```php
public function test_admin_can_save_media_allowed_categories(): void
{
    $admin = $this->makeAdmin();
    $this->actingAs($admin)
        ->post('/settings/media', [
            'media.max_upload_mb'         => 10,
            'media.resize_max_width'      => 1920,
            'media.allowed_categories'    => ['image', 'document'],
            'media.custom_mimes'          => [],
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('settings', [
        'key'   => 'media.allowed_categories',
        'value' => json_encode(['image', 'document']),
    ]);
}
```

**Step 2: Run to confirm failure**

```bash
php artisan test tests/Feature/SettingsTest.php --filter="allowed_categories" --no-coverage
```
Expected: failure — key doesn't exist.

**Step 3: Add settings to seeder**

In `database/seeders/SettingsSeeder.php`, inside the Media section add:

```php
['group' => 'media', 'key' => 'media.allowed_categories', 'value' => json_encode(['image','document','video','audio']), 'type' => 'string'],
['group' => 'media', 'key' => 'media.custom_mimes',        'value' => json_encode([]),                                   'type' => 'string'],
```

Re-seed:
```bash
php artisan db:seed --class=SettingsSeeder
```

**Step 4: Update config/media.php**

Replace the `allowed_mimes` key with a dynamic version:

```php
// Grouped MIME definitions — source of truth
$allMimeGroups = [
    'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
    'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'video'    => ['video/mp4', 'video/webm'],
    'audio'    => ['audio/mpeg', 'audio/wav'],
];

$enabledCategories = json_decode($settingGet('media.allowed_categories', json_encode(['image','document','video','audio'])), true) ?? ['image','document','video','audio'];
$customMimes       = json_decode($settingGet('media.custom_mimes', '[]'), true) ?? [];

$enabledMimes = collect($enabledCategories)
    ->flatMap(fn ($cat) => $allMimeGroups[$cat] ?? [])
    ->merge($customMimes)
    ->unique()
    ->values()
    ->all();

return [
    'max_upload_mb'     => $settingGet('media.max_upload_mb', env('MEDIA_MAX_UPLOAD_MB', 10)),
    'allowed_mimes'     => $enabledMimes,
    'mime_groups'       => $allMimeGroups,            // used by controller for display
    'allowed_categories'=> $enabledCategories,
    'custom_mimes'      => $customMimes,
    'resize_max_width'  => $settingGet('media.resize_max_width', 1920),
];
```

**Step 5: Update SettingsController**

In `update()`, add `'allowed_categories'` and `'custom_mimes'` to the `media` validation case:

```php
'media' => $request->validate([
    'media\.max_upload_mb'        => ['required', 'integer', 'min:1', 'max:100'],
    'media\.resize_max_width'     => ['required', 'integer', 'min:320', 'max:8000'],
    'media\.allowed_categories'   => ['nullable', 'array'],
    'media\.allowed_categories.*' => ['string', Rule::in(['image','document','video','audio'])],
    'media\.custom_mimes'         => ['nullable', 'array'],
    'media\.custom_mimes.*'       => ['string', 'max:100'],
]),
```

Before calling `Setting::set($key, $value)` in the loop for the media group, JSON-encode the arrays:

```php
foreach ($validated as $key => $value) {
    Setting::set($key, is_array($value) ? json_encode($value) : ($value ?? ''));
}
```

**Step 6: Add allowedExtensions to MediaController@index**

Add a MIME → extension map constant and compute the prop:

```php
private const MIME_EXT = [
    'image/jpeg'        => 'jpg',
    'image/png'         => 'png',
    'image/gif'         => 'gif',
    'image/webp'        => 'webp',
    'image/svg+xml'     => 'svg',
    'application/pdf'   => 'pdf',
    'application/msword'=> 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'video/mp4'         => 'mp4',
    'video/webm'        => 'webm',
    'audio/mpeg'        => 'mp3',
    'audio/wav'         => 'wav',
];

// In index():
$allowedMimes      = config('media.allowed_mimes', []);
$allowedExtensions = collect($allowedMimes)
    ->map(fn ($m) => self::MIME_EXT[$m] ?? null)
    ->filter()
    ->unique()
    ->values()
    ->implode(', ');

// Add to Inertia::render() props:
'allowedExtensions'    => $allowedExtensions,
'allowedCategories'    => config('media.allowed_categories', ['image','document','video','audio']),
'customMimes'          => config('media.custom_mimes', []),
```

**Step 7: Run tests**

```bash
php artisan test tests/Feature/SettingsTest.php --no-coverage
```
Expected: all pass.

**Step 8: Commit**

```bash
git add database/seeders/SettingsSeeder.php config/media.php app/Http/Controllers/SettingsController.php app/Http/Controllers/MediaController.php tests/Feature/SettingsTest.php
git commit -m "feat: dynamic media allowed types from settings, expose allowedExtensions prop"
```

---

## Task 7: Feature 11 — Settings Media UI + Media/Index hint

**Files:**
- Modify: `resources/js/Pages/Settings/Index.vue`
- Modify: `resources/js/Pages/Media/Index.vue`

**Step 1: Add new fields to mediaForm in Settings/Index.vue**

In `<script setup>`, update the `mediaForm` `useForm()` to include the new fields (read current values from `props.settings`):

```js
const mediaForm = useForm({
  'media.max_upload_mb':      props.settings['media.max_upload_mb']      ?? 10,
  'media.resize_max_width':   props.settings['media.resize_max_width']   ?? 1920,
  'media.allowed_categories': JSON.parse(props.settings['media.allowed_categories'] ?? '["image","document","video","audio"]'),
  'media.custom_mimes':       JSON.parse(props.settings['media.custom_mimes'] ?? '[]'),
})
```

**Step 2: Add allowed types UI to the media tab template**

Below the existing resize_max_width field group and before the save button, add:

```html
<!-- Allowed file types -->
<div class="space-y-3 border-t pt-4">
  <div>
    <h4 class="text-sm font-medium">Allowed file types</h4>
    <p class="text-xs text-muted-foreground mt-0.5">Control which file types users can upload.</p>
  </div>

  <div class="grid grid-cols-2 gap-2">
    <label v-for="cat in [
      { key: 'image',    label: 'Images',    hint: 'JPG, PNG, GIF, WebP, SVG' },
      { key: 'document', label: 'Documents', hint: 'PDF, DOC, DOCX' },
      { key: 'video',    label: 'Video',     hint: 'MP4, WebM' },
      { key: 'audio',    label: 'Audio',     hint: 'MP3, WAV' },
    ]" :key="cat.key" class="flex items-start gap-2 rounded-md border p-3 cursor-pointer hover:bg-accent/50 transition-colors"
      :class="mediaForm['media.allowed_categories'].includes(cat.key) ? 'border-primary bg-primary/5' : ''"
    >
      <input
        type="checkbox"
        :value="cat.key"
        v-model="mediaForm['media.allowed_categories']"
        class="mt-0.5 accent-primary"
      />
      <div>
        <span class="text-sm font-medium">{{ cat.label }}</span>
        <p class="text-xs text-muted-foreground">{{ cat.hint }}</p>
      </div>
    </label>
  </div>

  <!-- Custom MIME tag input -->
  <div class="space-y-1">
    <label class="text-sm font-medium">Custom MIME types</label>
    <p class="text-xs text-muted-foreground">Add any additional MIME types (e.g. <code>model/gltf+json</code>).</p>
    <div class="flex flex-wrap gap-1.5 rounded-md border bg-background px-3 py-2 min-h-[42px]">
      <span
        v-for="(mime, i) in mediaForm['media.custom_mimes']"
        :key="mime"
        class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium"
      >
        {{ mime }}
        <button type="button" @click="mediaForm['media.custom_mimes'].splice(i, 1)" class="ml-0.5 text-muted-foreground hover:text-destructive">×</button>
      </span>
      <input
        type="text"
        placeholder="Add MIME type…"
        class="flex-1 min-w-28 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
        @keydown.enter.prevent="(e) => {
          const v = e.target.value.trim()
          if (v && !mediaForm['media.custom_mimes'].includes(v)) {
            mediaForm['media.custom_mimes'].push(v)
            e.target.value = ''
          }
        }"
        @keydown.backspace="(e) => {
          if (!e.target.value && mediaForm['media.custom_mimes'].length) {
            mediaForm['media.custom_mimes'].pop()
          }
        }"
      />
    </div>
  </div>
</div>
```

**Step 3: Update Media/Index.vue hint text**

In `resources/js/Pages/Media/Index.vue`:

1. Add `allowedExtensions` to `defineProps`: `allowedExtensions: { type: String, default: '' }`
2. Replace the hardcoded hint text:

```html
<!-- Before -->
<p v-show="!uploading" class="text-xs text-muted-foreground mb-3">
  Accepted: JPG, PNG, GIF, WebP, SVG, PDF, MP4, MP3 · Max {{ maxUploadMb }} MB
</p>

<!-- After -->
<p v-show="!uploading" class="text-xs text-muted-foreground mb-3">
  Accepted: {{ allowedExtensions || 'JPG, PNG, GIF, WebP, SVG, PDF, MP4, MP3' }} · Max {{ maxUploadMb }} MB
</p>
```

**Step 4: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue resources/js/Pages/Media/Index.vue
git commit -m "feat: media allowed types UI in settings + dynamic uploader hint"
```

---

## Task 8: Feature 9 — Parallax on Section block

**Files:**
- Modify: `resources/js/Components/BlockEditor/blocks/SectionSettings.vue`
- Modify: `resources/js/components/Blocks/SectionBlock.vue`

**Step 1: Read both files fully**

```bash
cat resources/js/Components/BlockEditor/blocks/SectionSettings.vue
cat resources/js/components/Blocks/SectionBlock.vue
```

**Step 2: Add parallax checkbox to SectionSettings**

In `SectionSettings.vue`, find the `<!-- Image picker -->` section (inside `v-if="d.bgType === 'image'"`). After the existing image URL input and position/size SelectBoxes, add:

```html
<!-- Parallax toggle -->
<label class="flex items-center gap-2 cursor-pointer mt-1">
  <input
    type="checkbox"
    :checked="d.bgImage?.parallax ?? false"
    @change="updateNested('bgImage', 'parallax', $event.target.checked)"
    class="rounded accent-primary"
  />
  <span class="text-xs font-medium">Parallax scrolling</span>
  <span class="text-xs text-muted-foreground">(fixed background)</span>
</label>
```

**Step 3: Apply backgroundAttachment in SectionBlock**

In `resources/js/components/Blocks/SectionBlock.vue`, find the `outerStyle` computed property. Inside the `else if (d.bgType === 'image' && d.bgImage?.url)` branch, add after setting `backgroundRepeat`:

```js
if (d.bgImage?.parallax) {
  styles.backgroundAttachment = 'fixed'
}
```

**Step 4: Verify manually**

In the block editor, add a Section block, set Background → Image, enter an image URL, check "Parallax scrolling". On the public page, the background should stay fixed while content scrolls.

> Note: `background-attachment: fixed` doesn't work on iOS Safari due to a long-standing WebKit bug. This is a known limitation — acceptable for now.

**Step 5: Commit**

```bash
git add resources/js/Components/BlockEditor/blocks/SectionSettings.vue resources/js/components/Blocks/SectionBlock.vue
git commit -m "feat: add parallax scrolling option to Section block background settings"
```

---

## Task 9: Feature 6 — Navigation block

**Files:**
- Create: `resources/js/components/Blocks/NavigationBlock.vue`
- Create: `resources/js/Components/BlockEditor/blocks/NavigationSettings.vue`
- Create: `resources/js/Components/BlockEditor/EditorNavigationBlock.vue`
- Modify: `resources/js/Components/BlockEditor/BlockTypePanel.vue`
- Modify: `resources/js/Components/BlockEditor/BlockCanvas.vue`
- Modify: `resources/js/Components/BlockEditor/BlockLayers.vue`
- Modify: `resources/js/Components/BlockEditor/EditorLoopBlock.vue`
- Modify: `resources/js/Components/BlockRenderer.vue`

**Step 1: Create NavigationBlock.vue (renderer)**

```vue
<script setup>
import { computed } from 'vue'
import { useFieldBinding } from '@/composables/useFieldBinding'

const props = defineProps({ block: { type: Object, required: true } })

const d = computed(() => props.block.data ?? {})
const links   = computed(() => d.value.links ?? [])
const style   = computed(() => d.value.style ?? 'horizontal')
const align   = computed(() => d.value.align ?? 'left')

const ALIGN_CLASS = { left: 'justify-start', center: 'justify-center', right: 'justify-end' }
const NAV_WRAP_CLASS = computed(() => ({
  horizontal: `flex flex-wrap gap-4 items-center ${ALIGN_CLASS[align.value]}`,
  vertical:   'flex flex-col gap-1',
  pills:      `flex flex-wrap gap-2 ${ALIGN_CLASS[align.value]}`,
  minimal:    `flex flex-wrap gap-6 ${ALIGN_CLASS[align.value]}`,
})[style.value])

const LINK_CLASS = computed(() => ({
  horizontal: 'text-sm text-foreground hover:text-primary transition-colors',
  vertical:   'text-sm text-foreground hover:text-primary transition-colors py-1',
  pills:      'text-xs font-medium px-3 py-1.5 rounded-full bg-muted hover:bg-primary hover:text-primary-foreground transition-colors',
  minimal:    'text-sm text-muted-foreground hover:text-foreground transition-colors uppercase tracking-wide text-xs font-semibold',
})[style.value])
</script>

<template>
  <nav :class="NAV_WRAP_CLASS">
    <a
      v-for="(link, i) in links"
      :key="i"
      :href="link.url || '#'"
      :target="link.newTab ? '_blank' : undefined"
      :rel="link.newTab ? 'noopener noreferrer' : undefined"
      :class="LINK_CLASS"
    >
      {{ link.label }}
    </a>
  </nav>
</template>
```

**Step 2: Create NavigationSettings.vue**

```vue
<script setup>
import { computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import SelectBox from '@/Components/SelectBox.vue'
import { GripVertical, Plus, Trash2 } from 'lucide-vue-next'

const props = defineProps({ block: { type: Object, required: true }, tab: { type: String, default: null } })
const emit  = defineEmits(['update'])

const d     = computed(() => props.block.data ?? {})
const links = computed(() => d.value.links ?? [])

function updateLinks(newLinks) {
  emit('update', { id: props.block.id, data: { links: newLinks } })
}

function addLink() {
  updateLinks([...links.value, { label: 'Link', url: '', newTab: false }])
}

function removeLink(i) {
  const arr = [...links.value]
  arr.splice(i, 1)
  updateLinks(arr)
}

function updateLink(i, field, value) {
  const arr = links.value.map((l, idx) => idx === i ? { ...l, [field]: value } : l)
  updateLinks(arr)
}

function update(key, value) {
  emit('update', { id: props.block.id, data: { [key]: value } })
}
</script>

<template>
  <div class="space-y-4">

    <!-- Content tab: link manager -->
    <div v-show="!tab || tab === 'content'" class="space-y-3">
      <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">Links</label>
        <button type="button" @click="addLink" class="flex items-center gap-1 text-xs text-primary hover:underline">
          <Plus class="w-3 h-3" /> Add link
        </button>
      </div>

      <VueDraggable
        :model-value="links"
        @update:model-value="updateLinks"
        handle=".drag-handle"
        item-key="(el, i) => i"
        class="space-y-2"
      >
        <template #item="{ element: link, index: i }">
          <div class="rounded-md border bg-background p-2 space-y-1.5">
            <div class="flex items-center gap-1">
              <GripVertical class="drag-handle w-3.5 h-3.5 text-muted-foreground cursor-grab shrink-0" />
              <input
                :value="link.label"
                @input="updateLink(i, 'label', $event.target.value)"
                placeholder="Label"
                class="flex-1 rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
              />
              <button type="button" @click="removeLink(i)" class="text-muted-foreground hover:text-destructive transition-colors shrink-0">
                <Trash2 class="w-3.5 h-3.5" />
              </button>
            </div>
            <input
              :value="link.url"
              @input="updateLink(i, 'url', $event.target.value)"
              placeholder="https://… or /relative"
              class="w-full rounded border bg-background px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <label class="flex items-center gap-1.5 cursor-pointer">
              <input
                type="checkbox"
                :checked="link.newTab"
                @change="updateLink(i, 'newTab', $event.target.checked)"
                class="accent-primary"
              />
              <span class="text-xs text-muted-foreground">Open in new tab</span>
            </label>
          </div>
        </template>
      </VueDraggable>

      <p v-if="!links.length" class="text-xs text-muted-foreground italic">No links yet. Click "Add link".</p>
    </div>

    <!-- Style tab -->
    <div v-show="!tab || tab === 'style'" class="space-y-3">
      <SelectBox
        label="Style"
        :model-value="d.style ?? 'horizontal'"
        :data="[
          { value: 'horizontal', label: 'Horizontal' },
          { value: 'vertical',   label: 'Vertical' },
          { value: 'pills',      label: 'Pills' },
          { value: 'minimal',    label: 'Minimal' },
        ]"
        @update:model-value="update('style', $event)"
      />
      <SelectBox
        label="Alignment"
        :model-value="d.align ?? 'left'"
        :data="[
          { value: 'left',   label: 'Left' },
          { value: 'center', label: 'Center' },
          { value: 'right',  label: 'Right' },
        ]"
        @update:model-value="update('align', $event)"
      />
    </div>

  </div>
</template>
```

**Step 3: Create EditorNavigationBlock.vue**

```vue
<script setup>
import { computed } from 'vue'

const props = defineProps({ block: { type: Object, required: true } })
const links = computed(() => props.block.data?.links ?? [])
</script>

<template>
  <div class="border border-dashed border-primary/30 rounded-md px-3 py-2 bg-primary/5">
    <p class="text-[10px] font-semibold text-primary/60 uppercase tracking-wider mb-2">Navigation</p>
    <div v-if="links.length" class="flex flex-wrap gap-3">
      <span
        v-for="(link, i) in links"
        :key="i"
        class="text-xs text-muted-foreground border-b border-dashed border-muted-foreground/40"
      >
        {{ link.label || '(untitled)' }}
      </span>
    </div>
    <p v-else class="text-xs text-muted-foreground italic">No links — edit in Settings panel.</p>
  </div>
</template>
```

**Step 4: Register in BlockTypePanel.vue**

In `ALL_TYPES`, add to the Layout group:
```js
{ type: 'navigation', label: 'Navigation', icon: Menu, group: 'Layout' },
```
Import `Menu` from `lucide-vue-next` at the top.

In `DEFAULT_DATA`, add:
```js
navigation: { links: [], style: 'horizontal', align: 'left' },
```

**Step 5: Register in BlockCanvas.vue**

Add import: `import EditorNavigationBlock from './EditorNavigationBlock.vue'`

In `LABELS`, add: `navigation: 'Navigation'`

In the `BLOCK_MAP`, add: `navigation: EditorNavigationBlock`

In `isEmptyBlock()`, add a case: `if (b.type === 'navigation') return !b.data?.links?.length`

**Step 6: Register in BlockLayers.vue**

In `LABELS`, add: `navigation: 'Navigation'`

In `COMPONENT_MAP`, add: `navigation: NavigationSettings`

In `STYLE_BLOCKS` set, add `'navigation'`.

Import `NavigationSettings` from `./blocks/NavigationSettings.vue`.

**Step 7: Register in EditorLoopBlock.vue**

In `LABELS`, add: `navigation: 'Navigation'`

Add async import:
```js
const EditorNavigationBlock = defineAsyncComponent(() => import('./EditorNavigationBlock.vue'))
```

Add `v-else-if` branch in the template before the fallback paragraph:
```html
<EditorNavigationBlock v-else-if="child.type === 'navigation'" :block="child" />
```

**Step 8: Register in BlockRenderer.vue**

Add import:
```js
import NavigationBlock from '@/components/Blocks/NavigationBlock.vue'
```

Add to `BLOCK_MAP`:
```js
navigation: NavigationBlock,
```

**Step 9: Commit**

```bash
git add resources/js/components/Blocks/NavigationBlock.vue \
        resources/js/Components/BlockEditor/blocks/NavigationSettings.vue \
        resources/js/Components/BlockEditor/EditorNavigationBlock.vue \
        resources/js/Components/BlockEditor/BlockTypePanel.vue \
        resources/js/Components/BlockEditor/BlockCanvas.vue \
        resources/js/Components/BlockEditor/BlockLayers.vue \
        resources/js/Components/BlockEditor/EditorLoopBlock.vue \
        resources/js/Components/BlockRenderer.vue
git commit -m "feat: add Navigation block to block editor with inline link manager"
```

---

## Task 10: Feature 7 — Front page visual redesign

**Files:**
- Modify: `resources/js/Layouts/BlogLayout.vue`
- Modify: `resources/js/Components/PostCard.vue`
- Modify: `resources/js/Components/BlogSidebar.vue`

**Step 1: Update BlogLayout.vue**

**Hero strip** — replace:
```html
<!-- Before -->
<div class="bg-primary/5 border-b">
  <div class="max-w-5xl mx-auto px-4 py-10">
    <h1 class="text-3xl font-bold tracking-tight">{{ appName }}</h1>
    <p class="mt-1 text-muted-foreground text-base">A simple, clean blog powered by Lambda CMS.</p>
  </div>
</div>
```

With:
```html
<div class="bg-gradient-to-br from-primary/10 via-background to-background border-b">
  <div class="max-w-5xl mx-auto px-4 py-12">
    <div class="flex items-center gap-3 mb-3">
      <span class="block w-1 h-8 rounded-full bg-primary shrink-0"></span>
      <h1 class="text-3xl font-bold tracking-tight">{{ appName }}</h1>
    </div>
    <p class="ml-4 text-muted-foreground text-base max-w-lg">A clean, modern blog powered by Lambda CMS.</p>
  </div>
</div>
```

**Header nav links** — update nav link classes from:
```html
class="text-sm text-muted-foreground hover:text-foreground transition-colors"
```
To:
```html
class="text-sm text-muted-foreground hover:text-foreground transition-colors relative after:absolute after:bottom-0 after:left-0 after:w-full after:h-0.5 after:bg-primary after:scale-x-0 hover:after:scale-x-100 after:transition-transform after:origin-left"
```

**Step 2: Update PostCard.vue**

1. **Card container** — add `hover:border-primary/40 hover:shadow-md transition-all duration-200` to the article classes.

2. **Featured image wrapper** — change from:
```html
<div v-if="post.featured_image_url" class="w-full h-48 overflow-hidden">
  <img ... class="w-full h-full object-cover" loading="lazy" />
```
To:
```html
<div v-if="post.featured_image_url" class="w-full h-48 overflow-hidden group">
  <img ... class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" />
```

3. **Category badges** — add `categoryStyle()` and `categoryClass()` helpers (see Task 5 Step 3) and update the badge `<span>`.

4. **Reading time badge** — add `readingTime()` helper:
```js
function readingTime(post) {
  const text = post.excerpt ?? post.body ?? ''
  const words = text.trim().split(/\s+/).length
  return Math.max(1, Math.ceil(words / 200))
}
```
Add after the date span in the meta row:
```html
<span class="text-xs text-muted-foreground">·</span>
<span class="text-xs text-muted-foreground">{{ readingTime(post) }} min read</span>
```

**Step 3: Update BlogSidebar.vue**

For each section heading (`<h3>`), replace the current class with one that includes a colored left accent:

```html
<!-- Before -->
<h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Categories</h3>

<!-- After -->
<h3 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">
  <span class="block w-1 h-3.5 rounded-full bg-primary shrink-0"></span>
  Categories
</h3>
```

Apply the same pattern to Tags and Recent Posts headings.

Also fix the raw date in the Recent Posts section — `post.published_at` is a raw string. Import and use `formatDate` from `@/lib/utils.js`.

**Step 4: Commit**

```bash
git add resources/js/Layouts/BlogLayout.vue resources/js/Components/PostCard.vue resources/js/Components/BlogSidebar.vue
git commit -m "feat: front page visual redesign — gradient hero, card hover effects, colored accents"
```

---

## Task 11: Final verification

**Step 1: Run full test suite**

```bash
php artisan test --no-coverage
```
Expected: 465+ tests passing, 0 failures.

**Step 2: Re-seed the database**

```bash
php artisan db:seed --class=SettingsSeeder
```

**Step 3: Build frontend**

```bash
npm run build
```
Expected: no errors.

**Step 4: Smoke test checklist**

- [ ] Settings > Appearance: pick a color, save, reload — `--primary` changes
- [ ] Categories > Create: color picker popover opens, Nord swatches work, custom picker works, saved to DB
- [ ] PostCard: category badges use category color when set
- [ ] Block editor: add Navigation block, add links, see them in canvas; public page renders nav
- [ ] Block editor: Section > Background > Image + Parallax checkbox → public page scrolls with fixed bg
- [ ] Settings > Media: category checkboxes toggle, custom MIME tag-input works, Media library hint updates
- [ ] Front page: gradient hero, image zoom on hover, reading time badge, sidebar accent bars

**Step 5: Final commit**

```bash
git add -A
git commit -m "chore: final verification pass for FIX.txt features 6, 7, 8, 9, 11, 12"
```
