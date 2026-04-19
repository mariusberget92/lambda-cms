# Media Library Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a centralised media library for Lambda CMS where authenticated users can upload, manage and bulk-delete files (images, documents, video, audio), with image resizing via Intervention/Image v3, featured images on posts, and inline image insertion in the Tiptap editor.

**Architecture:** A `media` table stores file metadata; files live on the `public` disk at `media/{year}/{month}/{uuid}.{ext}`. A `MediaController` handles upload/index/update/destroy (including bulk delete). A `MediaPicker.vue` modal is shared between the post editor (featured image sidebar card + Tiptap image toolbar button). Max upload size is read from `config('media.max_upload_mb')` which defaults to 10 MB and will be overridden by a settings system later.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind 4, Intervention/Image v3 (GD driver), @tiptap/extension-image, reka-ui Dialog, axios (for upload progress), @vueuse/core useDropZone.

---

## Task 1: Install Intervention/Image v3

**Files:**
- Modify: `composer.json` (via composer command)
- Create: `config/media.php`

**Step 1: Install the package**

```bash
cd C:\Users\mariu\Herd\lambda-cms
composer require intervention/image
```

Expected: Package installs cleanly. Note: GD extension is the default driver and is available on the dev machine (used by Laravel's image fakes in tests, though `->image()` helper was disabled — but the raw GD extension IS present, just not enabled for `imagejpeg()` in the test runner; Intervention/Image handles this differently and works fine).

**Step 2: Create the media config file**

Create `config/media.php`:

```php
<?php

return [
    /*
     * Maximum upload size in megabytes.
     * This will be overridden by the settings system when it is built.
     */
    'max_upload_mb' => env('MEDIA_MAX_UPLOAD_MB', 10),

    /*
     * Allowed MIME types grouped by category.
     */
    'allowed_mimes' => [
        'image'    => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'],
        'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'video'    => ['video/mp4', 'video/webm'],
        'audio'    => ['audio/mpeg', 'audio/wav'],
    ],

    /*
     * Image resize width in pixels applied on upload.
     * Images wider than this will be scaled down proportionally.
     * Set to null to disable resizing.
     */
    'resize_max_width' => 1920,
];
```

**Step 3: Commit**

```bash
git add composer.json composer.lock config/media.php
git commit -m "feat: install Intervention/Image v3 and add media config"
```

---

## Task 2: Migration — media table + featured_image_id on posts

**Files:**
- Create: `database/migrations/2026_02_20_000001_create_media_table.php`
- Create: `database/migrations/2026_02_20_000002_add_featured_image_id_to_posts_table.php`

**Step 1: Create the media migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('filename');          // uuid.ext stored on disk
            $table->string('original_filename'); // original name shown in UI
            $table->string('disk')->default('public');
            $table->string('path');              // media/2026/02/uuid.jpg
            $table->string('mime_type');
            $table->string('type');              // image | document | video | audio
            $table->unsignedBigInteger('size');  // bytes
            $table->unsignedInteger('width')->nullable();  // px (images only)
            $table->unsignedInteger('height')->nullable(); // px (images only)
            $table->string('alt')->nullable();   // alt text for images
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
```

**Step 2: Create the featured_image_id migration**

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
            $table->foreignId('featured_image_id')
                  ->nullable()
                  ->after('category_id')
                  ->constrained('media')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Media::class, 'featured_image_id');
            $table->dropColumn('featured_image_id');
        });
    }
};
```

**Step 3: Run migrations**

```bash
php artisan migrate
```

Expected: `media` table created, `featured_image_id` column added to `posts`.

**Step 4: Commit**

```bash
git add database/migrations/
git commit -m "feat: add media table and featured_image_id on posts"
```

---

## Task 3: Media Model + MediaFactory

**Files:**
- Create: `app/Models/Media.php`
- Create: `database/factories/MediaFactory.php`

**Step 1: Write the failing test**

Add to `tests/Unit/MediaTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_attribute_returns_full_public_url(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'disk'    => 'public',
            'path'    => 'media/2026/02/abc123.jpg',
        ]);

        $this->assertStringContainsString('media/2026/02/abc123.jpg', $media->url);
    }

    public function test_type_is_image_for_image_mime(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create([
            'user_id'   => $user->id,
            'mime_type' => 'image/jpeg',
            'type'      => 'image',
        ]);

        $this->assertEquals('image', $media->type);
    }

    public function test_formatted_size_attribute(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();

        $user = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create([
            'user_id' => $user->id,
            'size'    => 1536, // 1.5 KB
        ]);

        $this->assertStringContainsString('KB', $media->formatted_size);
    }
}
```

**Step 2: Run test to verify it fails**

```bash
php artisan test tests/Unit/MediaTest.php
```

Expected: FAIL — `Media` class not found.

**Step 3: Create the Media model**

Create `app/Models/Media.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'filename',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'type',
        'size',
        'width',
        'height',
        'alt',
    ];

    protected $casts = [
        'size'   => 'integer',
        'width'  => 'integer',
        'height' => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Full public URL to the file.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Human-readable file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }

        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 2) . ' KB';
        }

        return $bytes . ' B';
    }

    /**
     * Whether this media item is an image.
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    // ─── Static helpers ───────────────────────────────────────────────────────

    /**
     * Resolve the type category from a MIME type string.
     */
    public static function typeFromMime(string $mime): string
    {
        $allowed = config('media.allowed_mimes', []);

        foreach ($allowed as $type => $mimes) {
            if (in_array($mime, $mimes, true)) {
                return $type;
            }
        }

        return 'other';
    }
}
```

**Step 4: Create the MediaFactory**

Create `database/factories/MediaFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $uuid = Str::uuid();
        $ext  = 'jpg';

        return [
            'user_id'           => User::factory(),
            'filename'          => "{$uuid}.{$ext}",
            'original_filename' => $this->faker->word() . ".{$ext}",
            'disk'              => 'public',
            'path'              => "media/2026/02/{$uuid}.{$ext}",
            'mime_type'         => 'image/jpeg',
            'type'              => 'image',
            'size'              => $this->faker->numberBetween(10_000, 5_000_000),
            'width'             => $this->faker->numberBetween(400, 4000),
            'height'            => $this->faker->numberBetween(300, 3000),
            'alt'               => null,
        ];
    }
}
```

**Step 5: Run tests to verify they pass**

```bash
php artisan test tests/Unit/MediaTest.php
```

Expected: 3 tests pass.

**Step 6: Commit**

```bash
git add app/Models/Media.php database/factories/MediaFactory.php tests/Unit/MediaTest.php
git commit -m "feat: add Media model with url/formatted_size accessors and factory"
```

---

## Task 4: Post model — add featuredImage relationship

**Files:**
- Modify: `app/Models/Post.php`

**Step 1: Add the relationship to Post.php**

In `app/Models/Post.php`, add the import at the top and the relationship method:

```php
// Add to imports
use App\Models\Media;

// Add relationship method after the tags() relationship
public function featuredImage(): BelongsTo
{
    return $this->belongsTo(Media::class, 'featured_image_id');
}
```

Also add `featured_image_id` to `$fillable`:

```php
protected $fillable = [
    'user_id',
    'category_id',
    'featured_image_id',  // <-- add this
    'title',
    'slug',
    'excerpt',
    'body',
    'status',
    'published_at',
];
```

**Step 2: Run the full test suite to check for regressions**

```bash
php artisan test
```

Expected: All existing tests still pass.

**Step 3: Commit**

```bash
git add app/Models/Post.php
git commit -m "feat: add featuredImage relationship to Post model"
```

---

## Task 5: MediaController — index, store (upload), update (alt text), destroy, bulk-destroy

**Files:**
- Create: `app/Http/Controllers/MediaController.php`

**Step 1: Write the failing test**

Create `tests/Feature/MediaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_media_index(): void
    {
        $this->markAsInstalled();
        $response = $this->get(route('media.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_media_index(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        $user = User::factory()->create()->assignRole('user');

        $response = $this->actingAs($user)->get(route('media.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Media/Index'));
    }

    // ── Upload ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_upload_an_image(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $file = UploadedFile::fake()->create('photo.jpg', 100, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('media.store'), [
            'file' => $file,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['id', 'url', 'filename', 'type', 'size', 'formatted_size']);

        $this->assertDatabaseHas('media', [
            'user_id'   => $user->id,
            'mime_type' => 'image/jpeg',
            'type'      => 'image',
        ]);
    }

    public function test_upload_rejects_files_over_max_size(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        // Set very small max for test
        config(['media.max_upload_mb' => 0.001]); // ~1KB

        $user = User::factory()->create()->assignRole('user');
        $file = UploadedFile::fake()->create('big.jpg', 500, 'image/jpeg'); // 500KB

        $response = $this->actingAs($user)->post(route('media.store'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_rejects_disallowed_mime_types(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user = User::factory()->create()->assignRole('user');
        $file = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

        $response = $this->actingAs($user)->post(route('media.store'), [
            'file' => $file,
        ]);

        $response->assertStatus(422);
    }

    // ── Update (alt text) ─────────────────────────────────────────────────────

    public function test_owner_can_update_alt_text(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user  = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->patch(route('media.update', $media), [
            'alt' => 'A beautiful sunset',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('media', ['id' => $media->id, 'alt' => 'A beautiful sunset']);
    }

    public function test_non_owner_cannot_update_alt_text(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $owner = User::factory()->create()->assignRole('user');
        $other = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->patch(route('media.update', $media), [
            'alt' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_owner_can_delete_their_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user  = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $user->id, 'disk' => 'public', 'path' => 'media/2026/02/test.jpg']);
        Storage::disk('public')->put($media->path, 'fake-content');

        $response = $this->actingAs($user)->delete(route('media.destroy', $media));

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_admin_can_delete_any_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $owner = User::factory()->create()->assignRole('user');
        $admin = User::factory()->create()->assignRole('administrator');
        $media = Media::factory()->create(['user_id' => $owner->id, 'disk' => 'public', 'path' => 'media/2026/02/test.jpg']);
        Storage::disk('public')->put($media->path, 'fake-content');

        $response = $this->actingAs($admin)->delete(route('media.destroy', $media));

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_non_owner_regular_user_cannot_delete_others_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $owner = User::factory()->create()->assignRole('user');
        $other = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($other)->delete(route('media.destroy', $media));

        $response->assertForbidden();
    }

    // ── Bulk Destroy ──────────────────────────────────────────────────────────

    public function test_user_can_bulk_delete_own_media(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user   = User::factory()->create()->assignRole('user');
        $media1 = Media::factory()->create(['user_id' => $user->id, 'disk' => 'public', 'path' => 'media/2026/02/a.jpg']);
        $media2 = Media::factory()->create(['user_id' => $user->id, 'disk' => 'public', 'path' => 'media/2026/02/b.jpg']);
        Storage::disk('public')->put($media1->path, 'x');
        Storage::disk('public')->put($media2->path, 'x');

        $response = $this->actingAs($user)->delete(route('media.bulk-destroy'), [
            'ids' => [$media1->id, $media2->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseMissing('media', ['id' => $media1->id]);
        $this->assertDatabaseMissing('media', ['id' => $media2->id]);
    }

    public function test_bulk_delete_silently_skips_others_media_for_regular_user(): void
    {
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        Storage::fake('public');

        $user  = User::factory()->create()->assignRole('user');
        $owner = User::factory()->create()->assignRole('user');
        $media = Media::factory()->create(['user_id' => $owner->id]);

        // User tries to bulk-delete another user's media — it should be skipped (not deleted)
        $response = $this->actingAs($user)->delete(route('media.bulk-destroy'), [
            'ids' => [$media->id],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('media', ['id' => $media->id]); // still exists
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/MediaTest.php
```

Expected: FAIL — route `media.index` not found.

**Step 3: Create the MediaController**

Create `app/Http/Controllers/MediaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Media::with('uploader:id,name')
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('search'), fn ($q, $search) => $q->where('original_filename', 'like', "%{$search}%"))
            ->latest();

        // Scope to own files unless admin
        if (! $request->user()->hasRole('administrator')) {
            $query->where('user_id', $request->user()->id);
        }

        $media = $query->paginate(40)->withQueryString()->through(fn (Media $m) => $this->toArray($m));

        return Inertia::render('Media/Index', [
            'media'   => $media,
            'filters' => $request->only('type', 'search'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $maxKb = (int) (config('media.max_upload_mb', 10) * 1024);

        $allMimes = collect(config('media.allowed_mimes', []))->flatten()->implode(',');

        $request->validate([
            'file' => [
                'required',
                'file',
                "max:{$maxKb}",
                "mimetypes:{$allMimes}",
            ],
        ]);

        $file     = $request->file('file');
        $mimeType = $file->getMimeType();
        $type     = Media::typeFromMime($mimeType);
        $ext      = $file->guessExtension() ?? 'bin';
        $uuid     = Str::uuid()->toString();
        $filename = "{$uuid}.{$ext}";
        $folder   = 'media/' . now()->format('Y/m');
        $path     = "{$folder}/{$filename}";

        // Store the raw file first
        $file->storeAs($folder, $filename, 'public');

        $width  = null;
        $height = null;

        // Resize images (skip SVG — they are vector)
        if ($type === 'image' && $mimeType !== 'image/svg+xml') {
            $fullPath   = Storage::disk('public')->path($path);
            $maxWidth   = config('media.resize_max_width', 1920);

            $manager = new ImageManager(new Driver());
            $img     = $manager->read($fullPath);

            // Only scale down, never up
            if ($img->width() > $maxWidth) {
                $img->scaleDown(width: $maxWidth);
                $img->save($fullPath);
            }

            $width  = $img->width();
            $height = $img->height();
        }

        $media = Media::create([
            'user_id'           => $request->user()->id,
            'filename'          => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'disk'              => 'public',
            'path'              => $path,
            'mime_type'         => $mimeType,
            'type'              => $type,
            'size'              => Storage::disk('public')->size($path),
            'width'             => $width,
            'height'            => $height,
            'alt'               => null,
        ]);

        return response()->json($this->toArray($media));
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validate([
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $media->update($validated);

        return response()->json($this->toArray($media));
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json(['deleted' => true]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $query = Media::whereIn('id', $request->input('ids'));

        // Non-admins can only delete their own
        if (! $request->user()->hasRole('administrator')) {
            $query->where('user_id', $request->user()->id);
        }

        $items = $query->get();

        foreach ($items as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        return response()->json(['deleted' => $items->count()]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function toArray(Media $media): array
    {
        return [
            'id'                => $media->id,
            'url'               => $media->url,
            'filename'          => $media->filename,
            'original_filename' => $media->original_filename,
            'mime_type'         => $media->mime_type,
            'type'              => $media->type,
            'size'              => $media->size,
            'formatted_size'    => $media->formatted_size,
            'width'             => $media->width,
            'height'            => $media->height,
            'alt'               => $media->alt,
            'created_at'        => $media->created_at->toDateTimeString(),
            'uploader'          => $media->uploader ? $media->uploader->name : null,
        ];
    }
}
```

**Step 4: Add routes**

In `routes/web.php`, inside the `['auth', 'verified']` middleware group (after the profile routes), add:

```php
// Media library
Route::get('/media',        [MediaController::class, 'index'])->name('media.index');
Route::post('/media',       [MediaController::class, 'store'])->name('media.store');
Route::patch('/media/{media}', [MediaController::class, 'update'])->name('media.update');
Route::delete('/media/bulk',   [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
```

Also add the import at the top of `routes/web.php`:

```php
use App\Http\Controllers\MediaController;
```

> **Important:** The bulk-destroy route (`/media/bulk`) must be registered BEFORE the single-destroy route (`/media/{media}`), otherwise Laravel will try to resolve `bulk` as a `{media}` model ID.

**Step 5: Run tests**

```bash
php artisan test tests/Feature/MediaTest.php
```

Expected: All MediaTest tests pass.

**Step 6: Run full test suite**

```bash
php artisan test
```

Expected: All tests pass.

**Step 7: Commit**

```bash
git add app/Http/Controllers/MediaController.php routes/web.php tests/Feature/MediaTest.php
git commit -m "feat: add MediaController with upload, update, delete and bulk-delete"
```

---

## Task 6: Update PostController — wire featured_image_id

**Files:**
- Modify: `app/Http/Controllers/PostController.php`

**Step 1: Update create(), store(), edit(), update() in PostController**

In `create()`, pass media count for UI awareness (no media list needed — the picker loads it):

No change needed here.

In `store()`, add `featured_image_id` to validation and the data passed to `Post::create()`:

```php
// Add to the validate() array in store():
'featured_image_id' => ['nullable', 'exists:media,id'],

// The existing `$validated` array will already contain featured_image_id after validation
// No other changes needed — it's in $fillable on the Post model
```

In `edit()`, add `featured_image_id` to the array returned to the front-end:

```php
// In the 'post' array returned by edit():
'featured_image_id' => $post->featured_image_id,
'featured_image'    => $post->featured_image_id ? [
    'id'  => $post->featuredImage->id,
    'url' => $post->featuredImage->url,
    'alt' => $post->featuredImage->alt,
] : null,
```

In `update()`, add `featured_image_id` to validation:

```php
'featured_image_id' => ['nullable', 'exists:media,id'],
```

In `index()` and `edit()` / `create()` → load featuredImage when eager-loading the post in `edit()`:

```php
$post->load('tags:id,name', 'featuredImage:id,path,alt');
```

**Step 2: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass (existing PostTest assertions still work because `featured_image_id` is nullable).

**Step 3: Commit**

```bash
git add app/Http/Controllers/PostController.php
git commit -m "feat: wire featured_image_id through PostController store/edit/update"
```

---

## Task 7: MediaPicker.vue — shared modal component

**Files:**
- Create: `resources/js/Components/MediaPicker.vue`

This is the shared modal used by both the post editor sidebar and the Tiptap image button.

**Step 1: Create MediaPicker.vue**

```vue
<template>
  <Dialog v-model:open="isOpen">
    <DialogContent class="max-w-4xl max-h-[85vh] flex flex-col gap-0 p-0">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b">
        <h2 class="text-base font-semibold">Media Library</h2>
        <div class="flex items-center gap-2">
          <!-- Type filter -->
          <select
            v-model="filters.type"
            class="rounded-md border bg-background px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          >
            <option value="">All types</option>
            <option value="image">Images</option>
            <option value="document">Documents</option>
            <option value="video">Video</option>
            <option value="audio">Audio</option>
          </select>
          <!-- Search -->
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search..."
            class="rounded-md border bg-background px-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-ring w-40"
          />
        </div>
      </div>

      <!-- Drop zone + grid -->
      <div
        ref="dropZoneRef"
        class="flex-1 overflow-y-auto p-4"
        :class="isOverDropZone ? 'bg-primary/5 ring-2 ring-primary ring-inset' : ''"
      >
        <!-- Upload progress -->
        <div v-if="uploading" class="mb-4 rounded-lg border bg-card p-3 flex items-center gap-3">
          <div class="flex-1 bg-muted rounded-full h-1.5">
            <div class="bg-primary h-1.5 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
          </div>
          <span class="text-xs text-muted-foreground shrink-0">{{ uploadProgress }}%</span>
        </div>

        <!-- Upload button / drop hint -->
        <label class="mb-4 flex cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed p-4 text-sm text-muted-foreground hover:border-primary hover:text-primary transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Click to upload or drag & drop files here
          <input type="file" class="hidden" multiple @change="onFileInput" />
        </label>

        <!-- Empty state -->
        <div v-if="!items.length && !loading" class="py-16 text-center text-sm text-muted-foreground">
          No media files yet.
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 gap-2">
          <button
            v-for="item in items"
            :key="item.id"
            type="button"
            class="relative group aspect-square rounded-md overflow-hidden border bg-muted focus:outline-none focus:ring-2 focus:ring-ring transition-colors"
            :class="selectedId === item.id ? 'ring-2 ring-primary border-primary' : 'hover:border-foreground/30'"
            @click="selectedId = item.id"
          >
            <!-- Image thumbnail -->
            <img
              v-if="item.type === 'image'"
              :src="item.url"
              :alt="item.alt ?? item.original_filename"
              class="w-full h-full object-cover"
              loading="lazy"
            />
            <!-- Non-image icon -->
            <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 p-2">
              <svg class="w-6 h-6 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span class="text-xs text-muted-foreground text-center leading-tight line-clamp-2">{{ item.original_filename }}</span>
            </div>

            <!-- Selected check -->
            <div v-if="selectedId === item.id" class="absolute inset-0 bg-primary/20 flex items-center justify-center">
              <div class="w-6 h-6 rounded-full bg-primary flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
          </button>
        </div>

        <!-- Load more -->
        <div v-if="nextPageUrl" class="mt-4 text-center">
          <button
            type="button"
            class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
            :disabled="loading"
            @click="loadMore"
          >
            {{ loading ? 'Loading...' : 'Load more' }}
          </button>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between px-6 py-4 border-t gap-4">
        <!-- Selected info -->
        <div v-if="selectedItem" class="text-sm text-muted-foreground truncate">
          <span class="font-medium text-foreground">{{ selectedItem.original_filename }}</span>
          · {{ selectedItem.formatted_size }}
          <span v-if="selectedItem.width">· {{ selectedItem.width }}×{{ selectedItem.height }}</span>
        </div>
        <div v-else class="text-sm text-muted-foreground">No file selected</div>

        <div class="flex gap-2 shrink-0">
          <button
            type="button"
            class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
            @click="isOpen = false"
          >
            Cancel
          </button>
          <button
            type="button"
            class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors disabled:opacity-50"
            :disabled="!selectedId"
            @click="confirm"
          >
            {{ confirmLabel }}
          </button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useDropZone } from '@vueuse/core'
import axios from 'axios'
import { Dialog, DialogContent } from '@/Components/ui/dialog'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  confirmLabel: { type: String, default: 'Select' },
})

const emit = defineEmits(['update:modelValue', 'select'])

const isOpen = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

// ── State ──────────────────────────────────────────────────────────────────

const items       = ref([])
const nextPageUrl = ref(null)
const loading     = ref(false)
const selectedId  = ref(null)
const uploading   = ref(false)
const uploadProgress = ref(0)
const filters = ref({ type: '', search: '' })
const dropZoneRef = ref(null)

const selectedItem = computed(() => items.value.find(i => i.id === selectedId.value) ?? null)

// ── Drop zone ──────────────────────────────────────────────────────────────

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files) => uploadFiles(files),
})

// ── Data loading ───────────────────────────────────────────────────────────

async function load(reset = true) {
  loading.value = true
  if (reset) {
    items.value = []
    nextPageUrl.value = null
    selectedId.value = null
  }

  const params = {}
  if (filters.value.type)   params.type   = filters.value.type
  if (filters.value.search) params.search = filters.value.search

  try {
    const url = nextPageUrl.value ?? '/media'
    const { data } = await axios.get(url, { params: reset ? params : {} })
    // The Inertia endpoint returns paginated data — but we're calling it as a JSON API
    // We handle the Inertia JSON response shape:
    const page = data?.props?.media ?? data
    items.value = reset ? page.data : [...items.value, ...page.data]
    nextPageUrl.value = page.next_page_url ?? null
  } finally {
    loading.value = false
  }
}

async function loadMore() {
  if (!nextPageUrl.value || loading.value) return
  await load(false)
}

// Watch filters with debounce
let filterTimer = null
watch(filters, () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(() => load(true), 300)
}, { deep: true })

// Load when modal opens
watch(isOpen, (open) => {
  if (open) load(true)
})

// ── Upload ─────────────────────────────────────────────────────────────────

function onFileInput(event) {
  uploadFiles(Array.from(event.target.files))
  event.target.value = ''
}

async function uploadFiles(files) {
  if (!files?.length) return

  for (const file of files) {
    uploading.value = true
    uploadProgress.value = 0

    const formData = new FormData()
    formData.append('file', file)

    try {
      const { data } = await axios.post('/media', formData, {
        headers: { 'Content-Type': 'multipart/form-data', 'X-Inertia': false },
        onUploadProgress: (e) => {
          uploadProgress.value = e.total ? Math.round((e.loaded * 100) / e.total) : 0
        },
      })
      items.value.unshift(data)
      selectedId.value = data.id
    } catch (err) {
      console.error('Upload failed', err)
      alert(err.response?.data?.message ?? 'Upload failed. Check file type and size.')
    } finally {
      uploading.value = false
    }
  }
}

// ── Confirm selection ──────────────────────────────────────────────────────

function confirm() {
  if (!selectedItem.value) return
  emit('select', selectedItem.value)
  isOpen.value = false
}
</script>
```

> **Note on the axios GET call:** The `media.index` route returns an Inertia response. When axios calls it directly (without Inertia's adapter), the server returns a full Inertia page JSON object. The shape is `{ component, props: { media: { data, next_page_url, ... } } }`. The `load()` function handles this with `data?.props?.media ?? data`.
>
> An alternative is to add a dedicated JSON endpoint `/api/media` — but for simplicity we re-use the Inertia route and parse the props. This works because Inertia returns JSON when the `X-Inertia` header is set; we explicitly set `X-Inertia: false` to get the plain JSON wrapper instead.
>
> Actually, the cleanest approach: set the `X-Requested-With: XMLHttpRequest` and `Accept: application/json` headers. But Inertia's JSON is already well-structured. See the comment in the upload function — we pass `X-Inertia: false` to avoid Inertia's redirect handling.

**Step 2: Commit**

```bash
git add resources/js/Components/MediaPicker.vue
git commit -m "feat: add MediaPicker.vue shared media modal component"
```

---

## Task 8: Media/Index.vue — full media library admin page

**Files:**
- Create: `resources/js/Pages/Media/Index.vue`

**Step 1: Create Media/Index.vue**

```vue
<template>
  <AppLayout title="Media Library">
    <Head title="Media Library" />

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <!-- Type filter -->
        <select
          v-model="filters.type"
          class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          @change="applyFilters"
        >
          <option value="">All types</option>
          <option value="image">Images</option>
          <option value="document">Documents</option>
          <option value="video">Video</option>
          <option value="audio">Audio</option>
        </select>

        <!-- Search -->
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search files..."
          class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring w-48"
          @input="debouncedSearch"
        />
      </div>

      <div class="flex items-center gap-2">
        <!-- Bulk delete -->
        <button
          v-if="selected.length"
          type="button"
          class="rounded-md border border-destructive/50 px-3 py-2 text-sm text-destructive hover:bg-destructive hover:text-destructive-foreground transition-colors"
          @click="confirmBulkDelete"
        >
          Delete {{ selected.length }} selected
        </button>

        <!-- Upload -->
        <label class="cursor-pointer rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
          <svg class="w-4 h-4 inline-block -mt-0.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Upload
          <input type="file" class="hidden" multiple @change="onFileInput" />
        </label>
      </div>
    </div>

    <!-- Drop zone -->
    <div
      ref="dropZoneRef"
      class="min-h-[60vh] rounded-lg transition-colors"
      :class="isOverDropZone ? 'bg-primary/5 ring-2 ring-primary ring-inset' : ''"
    >
      <!-- Upload progress -->
      <div v-if="uploading" class="mb-4 rounded-lg border bg-card p-4 flex items-center gap-4">
        <span class="text-sm text-muted-foreground shrink-0">Uploading {{ uploadingName }}...</span>
        <div class="flex-1 bg-muted rounded-full h-2">
          <div class="bg-primary h-2 rounded-full transition-all" :style="{ width: uploadProgress + '%' }" />
        </div>
        <span class="text-xs text-muted-foreground shrink-0">{{ uploadProgress }}%</span>
      </div>

      <!-- Drop hint (when no items) -->
      <div v-if="!localItems.length && !uploading" class="flex flex-col items-center justify-center py-24 text-muted-foreground">
        <svg class="w-10 h-10 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        <p class="text-sm">Drop files here or click Upload to add media.</p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8 gap-3">
        <div
          v-for="item in localItems"
          :key="item.id"
          class="relative group aspect-square rounded-md overflow-hidden border bg-muted cursor-pointer"
          :class="selected.includes(item.id) ? 'ring-2 ring-primary border-primary' : 'hover:border-foreground/30'"
          @click="toggleSelect(item.id)"
        >
          <!-- Image -->
          <img
            v-if="item.type === 'image'"
            :src="item.url"
            :alt="item.alt ?? item.original_filename"
            class="w-full h-full object-cover"
            loading="lazy"
          />
          <!-- Non-image -->
          <div v-else class="w-full h-full flex flex-col items-center justify-center gap-1 p-2">
            <svg class="w-7 h-7 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-xs text-muted-foreground text-center leading-tight line-clamp-2">{{ item.original_filename }}</span>
          </div>

          <!-- Checkbox overlay (shows on hover or when selected) -->
          <div
            class="absolute top-1.5 left-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
            :class="{ 'opacity-100': selected.includes(item.id) }"
          >
            <div
              class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
              :class="selected.includes(item.id) ? 'bg-primary border-primary' : 'bg-background/80 border-muted-foreground'"
            >
              <svg v-if="selected.includes(item.id)" class="w-3 h-3 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </div>
          </div>

          <!-- Info overlay on hover -->
          <div class="absolute bottom-0 left-0 right-0 bg-background/80 backdrop-blur-sm p-1.5 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
            <p class="truncate text-foreground font-medium leading-tight">{{ item.original_filename }}</p>
            <p class="text-muted-foreground">{{ item.formatted_size }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="media.links?.length > 3" class="flex items-center justify-center gap-1 mt-8">
      <template v-for="link in media.links" :key="link.label">
        <Link
          v-if="link.url"
          :href="link.url"
          class="px-3 py-1.5 text-sm rounded-md border transition-colors"
          :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'bg-card text-muted-foreground hover:text-foreground hover:border-foreground'"
        >
          {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
        </Link>
        <span v-else class="px-3 py-1.5 text-sm rounded-md border text-muted-foreground/40 cursor-not-allowed">
          {{ link.label.replace('&laquo;', '«').replace('&raquo;', '»') }}
        </span>
      </template>
    </div>

    <!-- Bulk delete confirmation modal -->
    <Dialog v-model:open="showBulkConfirm">
      <DialogContent class="max-w-sm">
        <div class="p-6">
          <h3 class="text-base font-semibold mb-2">Delete {{ selected.length }} files?</h3>
          <p class="text-sm text-muted-foreground mb-6">This action cannot be undone.</p>
          <div class="flex justify-end gap-2">
            <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent" @click="showBulkConfirm = false">Cancel</button>
            <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm text-destructive-foreground hover:bg-destructive/90" @click="doBulkDelete">Delete</button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useDropZone } from '@vueuse/core'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Dialog, DialogContent } from '@/Components/ui/dialog'

const props = defineProps({
  media:   Object,
  filters: { type: Object, default: () => ({}) },
})

// Local state
const localItems      = ref([...props.media.data])
const selected        = ref([])
const uploading       = ref(false)
const uploadProgress  = ref(0)
const uploadingName   = ref('')
const showBulkConfirm = ref(false)
const dropZoneRef     = ref(null)

const filters = ref({
  type:   props.filters.type   ?? '',
  search: props.filters.search ?? '',
})

// Drop zone
const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files) => uploadFiles(files),
})

// ── Filters ────────────────────────────────────────────────────────────────

function applyFilters() {
  router.get(route('media.index'), filters.value, { preserveState: true, replace: true })
}

let searchTimer = null
function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 300)
}

// ── Selection ──────────────────────────────────────────────────────────────

function toggleSelect(id) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) {
    selected.value.push(id)
  } else {
    selected.value.splice(idx, 1)
  }
}

// ── Upload ─────────────────────────────────────────────────────────────────

function onFileInput(event) {
  uploadFiles(Array.from(event.target.files))
  event.target.value = ''
}

async function uploadFiles(files) {
  if (!files?.length) return

  for (const file of files) {
    uploading.value     = true
    uploadProgress.value = 0
    uploadingName.value  = file.name

    const formData = new FormData()
    formData.append('file', file)

    try {
      const { data } = await axios.post(route('media.store'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (e) => {
          uploadProgress.value = e.total ? Math.round((e.loaded * 100) / e.total) : 0
        },
      })
      localItems.value.unshift(data)
    } catch (err) {
      alert(err.response?.data?.message ?? 'Upload failed. Check file type and size.')
    } finally {
      uploading.value = false
    }
  }
}

// ── Bulk delete ────────────────────────────────────────────────────────────

function confirmBulkDelete() {
  showBulkConfirm.value = true
}

async function doBulkDelete() {
  try {
    await axios.delete(route('media.bulk-destroy'), {
      data: { ids: selected.value },
    })
    localItems.value = localItems.value.filter(i => !selected.value.includes(i.id))
    selected.value = []
    showBulkConfirm.value = false
  } catch (err) {
    alert('Bulk delete failed.')
  }
}
</script>
```

**Step 2: Add Media link to AppLayout sidebar**

In `resources/js/Layouts/AppLayout.vue`, add after the Tags link (in the Content section):

```vue
<SidebarLink :href="route('media.index')" :active="currentRoute?.startsWith('media.')">
  <template #icon>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
  </template>
  Media
</SidebarLink>
```

**Step 3: Run tests and visit the page manually to verify**

```bash
php artisan test
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add resources/js/Pages/Media/Index.vue resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Media/Index.vue library page with upload, grid and bulk delete"
```

---

## Task 9: Post editor — Featured Image sidebar card + Tiptap image button

**Files:**
- Modify: `resources/js/Pages/Posts/Create.vue`
- Modify: `resources/js/Pages/Posts/Edit.vue`
- Modify: `resources/js/Components/TiptapEditor.vue`

### Part A — Featured Image card in Create.vue and Edit.vue

**Step 1: Update Create.vue sidebar**

Add `featured_image_id: null` to the `useForm()` call in Create.vue.

Add the Featured Image card to the sidebar (after the Status card):

```vue
<!-- Featured Image -->
<div class="rounded-lg border bg-card p-4">
  <h3 class="text-sm font-medium mb-3">Featured Image</h3>

  <!-- Preview -->
  <div v-if="featuredImage" class="mb-3 relative group">
    <img
      :src="featuredImage.url"
      :alt="featuredImage.alt ?? 'Featured image'"
      class="w-full aspect-video object-cover rounded-md"
    />
    <button
      type="button"
      class="absolute top-1 right-1 w-6 h-6 rounded-full bg-background/80 flex items-center justify-center text-muted-foreground hover:text-destructive opacity-0 group-hover:opacity-100 transition-opacity"
      @click="removeFeaturedImage"
    >
      <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>

  <button
    type="button"
    class="w-full rounded-md border border-dashed px-3 py-2 text-sm text-muted-foreground hover:border-primary hover:text-primary transition-colors"
    @click="showMediaPicker = true"
  >
    {{ featuredImage ? 'Change image' : 'Select image' }}
  </button>
</div>

<!-- MediaPicker modal -->
<MediaPicker
  v-model="showMediaPicker"
  confirm-label="Use as featured image"
  @select="onFeaturedImageSelect"
/>
```

Add to script setup:

```js
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const showMediaPicker = ref(false)
const featuredImage   = ref(null)

function onFeaturedImageSelect(media) {
  featuredImage.value       = media
  form.featured_image_id    = media.id
}

function removeFeaturedImage() {
  featuredImage.value    = null
  form.featured_image_id = null
}
```

**Step 2: Update Edit.vue sidebar**

Same as Create.vue, but also initialise `featuredImage` from the `post` prop:

```js
const featuredImage = ref(props.post.featured_image ?? null)
```

And add `featured_image_id: props.post.featured_image_id ?? null` to `useForm()`.

### Part B — Tiptap image insertion button

**Step 1: Install @tiptap/extension-image**

```bash
npm install @tiptap/extension-image
```

**Step 2: Update TiptapEditor.vue**

Add the Image extension import:

```js
import Image from '@tiptap/extension-image'
```

Add `Image.configure({ inline: false })` to the extensions array in `useEditor()`.

Add an `insertImage(url, alt)` method:

```js
function insertImage(url, alt) {
  editor.value?.chain().focus().setImage({ src: url, alt: alt ?? '' }).run()
}

// Expose for parent components
defineExpose({ insertImage })
```

Add an Image toolbar button in the toolbar (after the Undo/Redo group):

```vue
<div class="toolbar-divider"/>
<div class="toolbar-group">
  <ToolbarButton @click="pickerOpen = true" title="Insert image">
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
    </svg>
  </ToolbarButton>
</div>

<!-- MediaPicker inline -->
<MediaPicker
  v-model="pickerOpen"
  confirm-label="Insert image"
  @select="(m) => insertImage(m.url, m.alt)"
/>
```

Add to script setup in TiptapEditor.vue:

```js
import { ref } from 'vue'
import MediaPicker from '@/Components/MediaPicker.vue'

const pickerOpen = ref(false)
```

Add image styling to the scoped `<style>`:

```css
:deep(.prose-editor img) {
  max-width: 100%;
  height: auto;
  border-radius: var(--radius-md);
  margin: 0.75rem 0;
}
```

**Step 3: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

**Step 4: Commit**

```bash
git add resources/js/Pages/Posts/Create.vue resources/js/Pages/Posts/Edit.vue resources/js/Components/TiptapEditor.vue package.json package-lock.json
git commit -m "feat: add featured image picker and Tiptap inline image insertion to post editor"
```

---

## Task 10: Blog frontend — featured image on index cards and show page

**Files:**
- Modify: `resources/js/Pages/Blog/Index.vue`
- Modify: `resources/js/Pages/Blog/Show.vue`
- Modify: `app/Http/Controllers/BlogController.php`
- Modify: `app/Http/Controllers/PostController.php`

### Part A — Pass featured image from BlogController

**Step 1: Update BlogController `index()` and `show()`**

Load `featuredImage` relationship and include it in the mapped arrays:

```php
// In index() — update the with() call:
->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk'])

// Add to the mapped array in index():
'featured_image_url' => $post->featuredImage?->url,

// In show() — update the with() call:
->with(['author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt'])

// Add to the mapped array in show():
'featured_image_url' => $post->featuredImage?->url,
'featured_image_alt' => $post->featuredImage?->alt,
```

### Part B — Blog/Index.vue featured image on cards

Add above the title in each article card:

```vue
<!-- Featured image -->
<div v-if="post.featured_image_url" class="mb-4 -mx-6 -mt-6 overflow-hidden rounded-t-xl">
  <img
    :src="post.featured_image_url"
    :alt="post.title"
    class="w-full h-48 object-cover"
    loading="lazy"
  />
</div>
```

### Part C — Blog/Show.vue featured image as hero

Add after the author/date row and before the post body:

```vue
<div v-if="post.featured_image_url" class="mb-8">
  <img
    :src="post.featured_image_url"
    :alt="post.featured_image_alt ?? post.title"
    class="w-full rounded-xl object-cover max-h-96"
  />
</div>
```

**Step 2: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass.

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Index.vue resources/js/Pages/Blog/Show.vue app/Http/Controllers/BlogController.php
git commit -m "feat: show featured images on blog index cards and post show page"
```

---

## Task 11: Update HandleInertiaRequests — share media count (optional nice-to-have)

> **Skip if not needed.** Only implement if the dashboard or sidebar needs to display a media count.

No changes needed for MVP.

---

## Task 12: Build assets + smoke test

**Step 1: Build assets**

```bash
npm run build
```

Expected: Build completes without errors.

**Step 2: Run the full test suite**

```bash
php artisan test
```

Expected: All tests pass, showing the count from previous sessions + new MediaTest assertions.

**Step 3: Link storage**

```bash
php artisan storage:link
```

(Only needed if not already done — creates `public/storage` symlink.)

**Step 4: Final commit if any build artifacts changed**

```bash
git add .
git commit -m "chore: build assets for media library feature"
```

---

## Task 13: PR

**Step 1: Push branch and open PR**

```bash
git push -u origin HEAD
gh pr create \
  --title "feat: media library with upload, image resizing, featured images and Tiptap inline images" \
  --body "## Summary

- **Media table**: stores file metadata (path, mime, type, size, width, height, alt)
- **MediaController**: upload (with Intervention/Image v3 resize), update alt text, delete (owner or admin), bulk delete
- **MediaPicker.vue**: shared modal used by post editor (featured image) and Tiptap (inline image)
- **Media/Index.vue**: full library page with drag-and-drop upload, progress bar, multi-select bulk delete
- **Post editor**: featured image sidebar card, Tiptap image toolbar button
- **Blog frontend**: featured image on index cards and post show page
- **Config**: \`config/media.php\` with configurable max upload MB (ready for settings system)

## Test plan
- [ ] Upload an image — verify it appears in grid and is resized to ≤ 1920px wide
- [ ] Upload a PDF — verify it appears with document icon
- [ ] Upload a file > max size — verify 422 error
- [ ] Upload a .php file — verify 422 error
- [ ] Select multiple items and bulk-delete
- [ ] Set alt text via update endpoint
- [ ] Non-owner cannot delete or update others' media
- [ ] Create a post with a featured image — verify it shows on blog index and show page
- [ ] Insert image inline in Tiptap editor
- [ ] Run \`php artisan test\` — all tests pass

🤖 Generated with Claude Code"
```

---

## Quick Reference

| Route | Method | Controller Action |
|-------|--------|-------------------|
| `/media` | GET | `MediaController@index` |
| `/media` | POST | `MediaController@store` |
| `/media/{media}` | PATCH | `MediaController@update` |
| `/media/bulk` | DELETE | `MediaController@bulkDestroy` |
| `/media/{media}` | DELETE | `MediaController@destroy` |

| Config key | Default | Description |
|-----------|---------|-------------|
| `media.max_upload_mb` | 10 | Max file size in MB (future: settings system) |
| `media.resize_max_width` | 1920 | Max image width after upload |
| `media.allowed_mimes` | image/document/video/audio | Allowed MIME groups |

**Storage path pattern:** `media/{Y}/{m}/{uuid}.{ext}` on the `public` disk.
