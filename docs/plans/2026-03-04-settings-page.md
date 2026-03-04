# Settings Page Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add an administrator-only Settings page to the CMS dashboard, backed by a key-value `settings` database table with a 1-hour cache, covering Site, Locale, Media, and Mail panels — including a live test-email button.

**Architecture:** A `settings` table (key/value/type/group) seeded with defaults, read via `Setting::get()` with cache-bust-on-save. A `SettingsController` handles `index`, per-group `update`, and `testEmail`. A `BootstrapSettings` middleware applies timezone at boot. The frontend is a single Vue page with four independent `useForm` panels following the existing Profile page pattern.

**Tech Stack:** Laravel 11, Inertia.js, Vue 3 `<script setup>`, Tailwind CSS 4, Spatie Laravel-Permission, PHPUnit.

---

## Task 1: Create the `settings` migration and seed defaults

**Files:**
- Create: `database/migrations/2026_03_04_000001_create_settings_table.php`
- Create: `database/seeders/SettingsSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

**Step 1: Write the migration**

Create `database/migrations/2026_03_04_000001_create_settings_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string', 'integer', 'boolean'])->default('string');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

**Step 2: Create the seeder**

Create `database/seeders/SettingsSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Site
            ['group' => 'site', 'key' => 'site.name',  'value' => config('app.name', 'Lambda CMS'), 'type' => 'string'],
            ['group' => 'site', 'key' => 'site.url',   'value' => config('app.url',  'http://localhost'), 'type' => 'string'],

            // Locale
            ['group' => 'locale', 'key' => 'locale.timezone',    'value' => 'UTC',    'type' => 'string'],
            ['group' => 'locale', 'key' => 'locale.date_format', 'value' => 'Y-m-d',  'type' => 'string'],

            // Media
            ['group' => 'media', 'key' => 'media.max_upload_mb',   'value' => '10',   'type' => 'integer'],
            ['group' => 'media', 'key' => 'media.resize_max_width', 'value' => '1920', 'type' => 'integer'],

            // Mail
            ['group' => 'mail', 'key' => 'mail.driver',       'value' => 'smtp', 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.host',         'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.port',         'value' => '587',  'type' => 'integer'],
            ['group' => 'mail', 'key' => 'mail.username',     'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.password',     'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.from_address', 'value' => '',     'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.from_name',    'value' => config('app.name', 'Lambda CMS'), 'type' => 'string'],
            ['group' => 'mail', 'key' => 'mail.encryption',   'value' => 'tls',  'type' => 'string'],
        ];

        foreach ($defaults as $setting) {
            DB::table('settings')->insertOrIgnore($setting + ['created_at' => now(), 'updated_at' => now()]);
        }
    }
}
```

**Step 3: Register the seeder**

Open `database/seeders/DatabaseSeeder.php`. Add `SettingsSeeder` to the `$this->call()` array (add it after any existing seeders):

```php
$this->call([
    SettingsSeeder::class,
    // ... other seeders
]);
```

**Step 4: Run the migration and seeder**

```bash
cd C:\Users\mariu\Herd\lambda-cms
php artisan migrate
php artisan db:seed --class=SettingsSeeder
```

Expected: Migration runs cleanly. Settings table has 14 rows.

**Step 5: Commit**

```bash
git add database/migrations/2026_03_04_000001_create_settings_table.php database/seeders/SettingsSeeder.php database/seeders/DatabaseSeeder.php
git commit -m "feat: add settings table migration and seeder with defaults"
```

---

## Task 2: Create the `Setting` model and `SettingService`

**Files:**
- Create: `app/Models/Setting.php`
- Create: `app/Services/SettingService.php`

**Step 1: Write the failing unit test first**

Create `tests/Unit/SettingServiceTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        Cache::flush();
    }

    public function test_get_returns_string_value(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'My CMS', 'type' => 'string']);

        $this->assertSame('My CMS', Setting::get('site.name'));
    }

    public function test_get_casts_integer_type(): void
    {
        Setting::create(['group' => 'media', 'key' => 'media.max_upload_mb', 'value' => '42', 'type' => 'integer']);

        $result = Setting::get('media.max_upload_mb');
        $this->assertSame(42, $result);
        $this->assertIsInt($result);
    }

    public function test_get_casts_boolean_type(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.debug', 'value' => '1', 'type' => 'boolean']);

        $this->assertTrue(Setting::get('site.debug'));
    }

    public function test_get_returns_fallback_when_key_missing(): void
    {
        $this->assertSame('default', Setting::get('nonexistent.key', 'default'));
    }

    public function test_set_persists_value(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Old', 'type' => 'string']);

        Setting::set('site.name', 'New');

        $this->assertDatabaseHas('settings', ['key' => 'site.name', 'value' => 'New']);
    }

    public function test_set_busts_cache(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Cached', 'type' => 'string']);

        // Prime the cache
        $service = new SettingService();
        $service->all();
        $this->assertTrue(Cache::has('settings.all'));

        // set() should bust it
        Setting::set('site.name', 'New');
        $this->assertFalse(Cache::has('settings.all'));
    }

    public function test_all_uses_cache(): void
    {
        Setting::create(['group' => 'site', 'key' => 'site.name', 'value' => 'Test', 'type' => 'string']);

        $service = new SettingService();
        $service->all(); // primes cache

        $this->assertTrue(Cache::has('settings.all'));
    }
}
```

**Step 2: Run to verify it fails**

```bash
php artisan test tests/Unit/SettingServiceTest.php
```

Expected: FAIL — class `App\Models\Setting` not found.

**Step 3: Create the `Setting` model**

Create `app/Models/Setting.php`:

```php
<?php

namespace App\Models;

use App\Services\SettingService;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['group', 'key', 'value', 'type'];

    /**
     * Retrieve a setting value by key, casting to the correct type.
     */
    public static function get(string $key, mixed $fallback = null): mixed
    {
        $service  = app(SettingService::class);
        $settings = $service->all();

        if (! $settings->has($key)) {
            return $fallback;
        }

        $setting = $settings->get($key);

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => (bool) $setting->value,
            default   => (string) ($setting->value ?? ''),
        };
    }

    /**
     * Persist a setting value and bust the cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => (string) $value]);
        app(SettingService::class)->bust();
    }
}
```

**Step 4: Create the `SettingService`**

Create `app/Services/SettingService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings.all';
    private const CACHE_TTL = 3600; // 1 hour

    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return \App\Models\Setting::all()->keyBy('key');
        });
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
```

**Step 5: Run the tests to verify they pass**

```bash
php artisan test tests/Unit/SettingServiceTest.php
```

Expected: All 7 tests pass.

**Step 6: Commit**

```bash
git add app/Models/Setting.php app/Services/SettingService.php tests/Unit/SettingServiceTest.php
git commit -m "feat: add Setting model and SettingService with cache and type casting"
```

---

## Task 3: Create the `BootstrapSettings` middleware

**Files:**
- Create: `app/Http/Middleware/BootstrapSettings.php`
- Modify: `bootstrap/app.php`

**Step 1: Create the middleware**

Create `app/Http/Middleware/BootstrapSettings.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class BootstrapSettings implements \Illuminate\Contracts\Http\Kernel
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = \App\Models\Setting::get('locale.timezone', 'UTC');
            date_default_timezone_set($timezone);
            Config::set('app.timezone', $timezone);
        } catch (\Throwable) {
            // Settings table doesn't exist yet (pre-migration). Silently skip.
        }

        return $next($request);
    }
}
```

Wait — `BootstrapSettings` should implement `Middleware`, not `Kernel`. Fix the class signature:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class BootstrapSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $timezone = \App\Models\Setting::get('locale.timezone', 'UTC');
            date_default_timezone_set($timezone);
            Config::set('app.timezone', $timezone);
        } catch (\Throwable) {
            // Settings table doesn't exist yet (pre-migration). Silently skip.
        }

        return $next($request);
    }
}
```

**Step 2: Register in `bootstrap/app.php`**

Open `bootstrap/app.php`. In the `$middleware->web(append: [...])` array, add `BootstrapSettings` **before** `HandleInertiaRequests`:

```php
$middleware->web(append: [
    \App\Http\Middleware\BootstrapSettings::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
    \App\Http\Middleware\TrackLastSeen::class,
]);
```

**Step 3: Smoke-test the app boots**

```bash
php artisan route:list --name=dashboard
```

Expected: The dashboard route is listed with no errors. If you see "Class not found", check the namespace in `BootstrapSettings.php`.

**Step 4: Commit**

```bash
git add app/Http/Middleware/BootstrapSettings.php bootstrap/app.php
git commit -m "feat: add BootstrapSettings middleware to apply timezone at boot"
```

---

## Task 4: Create the `SettingsController` and routes

**Files:**
- Create: `app/Http/Controllers/SettingsController.php`
- Create: `app/Mail/TestMail.php`
- Modify: `routes/web.php`

**Step 1: Write the failing feature test first**

Create `tests/Feature/SettingsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
        $this->seedSettings();
    }

    private function seedSettings(): void
    {
        $defaults = [
            ['group' => 'site',   'key' => 'site.name',          'value' => 'Lambda CMS', 'type' => 'string'],
            ['group' => 'site',   'key' => 'site.url',           'value' => 'http://localhost', 'type' => 'string'],
            ['group' => 'locale', 'key' => 'locale.timezone',    'value' => 'UTC',   'type' => 'string'],
            ['group' => 'locale', 'key' => 'locale.date_format', 'value' => 'Y-m-d', 'type' => 'string'],
            ['group' => 'media',  'key' => 'media.max_upload_mb',    'value' => '10',   'type' => 'integer'],
            ['group' => 'media',  'key' => 'media.resize_max_width', 'value' => '1920', 'type' => 'integer'],
            ['group' => 'mail',   'key' => 'mail.driver',        'value' => 'log',  'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.host',          'value' => '',     'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.port',          'value' => '587',  'type' => 'integer'],
            ['group' => 'mail',   'key' => 'mail.username',      'value' => '',     'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.password',      'value' => '',     'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.from_address',  'value' => 'noreply@example.com', 'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.from_name',     'value' => 'Lambda CMS', 'type' => 'string'],
            ['group' => 'mail',   'key' => 'mail.encryption',    'value' => 'tls',  'type' => 'string'],
        ];

        foreach ($defaults as $row) {
            Setting::create($row);
        }
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_settings(): void
    {
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_settings(): void
    {
        $this->actingAs($this->makeUser())->get('/settings')->assertRedirect(route('dashboard'));
    }

    public function test_administrator_can_access_settings(): void
    {
        $this->actingAs($this->makeAdmin())->get('/settings')->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_administrator_can_update_site_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/site', [
            'site.name' => 'New Site Name',
            'site.url'  => 'https://example.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'site.name', 'value' => 'New Site Name']);
    }

    public function test_site_settings_validation_rejects_invalid_url(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/site', [
            'site.name' => 'Test',
            'site.url'  => 'not-a-url',
        ])->assertSessionHasErrors('site.url');
    }

    public function test_administrator_can_update_media_settings(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/media', [
            'media.max_upload_mb'   => 25,
            'media.resize_max_width' => 2560,
        ])->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'media.max_upload_mb', 'value' => '25']);
    }

    public function test_media_settings_validation_rejects_out_of_range(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->put('/settings/media', [
            'media.max_upload_mb'   => 999,
            'media.resize_max_width' => 1920,
        ])->assertSessionHasErrors('media.max_upload_mb');
    }

    public function test_regular_user_cannot_update_settings(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->put('/settings/site', [
            'site.name' => 'Hacked',
            'site.url'  => 'https://hacked.com',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseMissing('settings', ['key' => 'site.name', 'value' => 'Hacked']);
    }

    // ── Test email ────────────────────────────────────────────────────────────

    public function test_administrator_can_send_test_email(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post('/settings/test-email')->assertRedirect();

        Mail::assertSent(\App\Mail\TestMail::class);
    }

    public function test_regular_user_cannot_send_test_email(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->actingAs($user)->post('/settings/test-email')->assertRedirect(route('dashboard'));

        Mail::assertNothingSent();
    }
}
```

**Step 2: Run to verify it fails**

```bash
php artisan test tests/Feature/SettingsTest.php
```

Expected: FAIL — route `settings.index` not found.

**Step 3: Create `TestMail`**

Create `app/Mail/TestMail.php`:

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Lambda CMS — Test Email');
    }

    public function content(): Content
    {
        return new Content(text: 'mail.test');
    }
}
```

Create the mail view `resources/views/mail/test.blade.php`:

```
This is a test email from Lambda CMS.

If you received this message, your mail settings are configured correctly.
```

**Step 4: Create `SettingsController`**

Create `app/Http/Controllers/SettingsController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        $settings = Setting::all()->keyBy('key')->map(fn ($s) => $s->value);

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(string $group, Request $request): RedirectResponse
    {
        $validated = match ($group) {
            'site'   => $request->validate([
                'site.name' => ['required', 'string', 'max:100'],
                'site.url'  => ['required', 'url', 'max:255'],
            ]),
            'locale' => $request->validate([
                'locale.timezone'    => ['required', 'string', Rule::in(\DateTimeZone::listIdentifiers())],
                'locale.date_format' => ['required', 'string', 'max:20'],
            ]),
            'media'  => $request->validate([
                'media.max_upload_mb'    => ['required', 'integer', 'min:1', 'max:100'],
                'media.resize_max_width' => ['required', 'integer', 'min:320', 'max:8000'],
            ]),
            'mail'   => $request->validate([
                'mail.driver'       => ['required', 'string', Rule::in(['smtp', 'log', 'mailgun'])],
                'mail.host'         => ['nullable', 'string', 'max:255'],
                'mail.port'         => ['nullable', 'integer'],
                'mail.username'     => ['nullable', 'string', 'max:255'],
                'mail.password'     => ['nullable', 'string', 'max:255'],
                'mail.from_address' => ['required', 'email'],
                'mail.from_name'    => ['required', 'string', 'max:100'],
                'mail.encryption'   => ['nullable', Rule::in(['tls', 'ssl', ''])],
            ]),
            default  => abort(404),
        };

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '');
        }

        return back()->with('status', 'Settings saved.');
    }

    public function testEmail(Request $request): RedirectResponse
    {
        // Apply current mail settings at runtime
        $driver = Setting::get('mail.driver', 'log');
        Config::set('mail.default', $driver);
        Config::set('mail.mailers.smtp.host',       Setting::get('mail.host', ''));
        Config::set('mail.mailers.smtp.port',       Setting::get('mail.port', 587));
        Config::set('mail.mailers.smtp.username',   Setting::get('mail.username', ''));
        Config::set('mail.mailers.smtp.password',   Setting::get('mail.password', ''));
        Config::set('mail.mailers.smtp.encryption', Setting::get('mail.encryption', 'tls') ?: null);
        Config::set('mail.from.address', Setting::get('mail.from_address', ''));
        Config::set('mail.from.name',    Setting::get('mail.from_name', ''));

        try {
            Mail::to($request->user()->email)->send(new TestMail());
            return back()->with('mail_status', 'Test email sent successfully to ' . $request->user()->email);
        } catch (\Throwable $e) {
            return back()->with('mail_error', 'Failed to send test email: ' . $e->getMessage());
        }
    }
}
```

**Step 5: Add routes**

Open `routes/web.php`. Add a `use` import at the top:

```php
use App\Http\Controllers\SettingsController;
```

Inside the `Route::middleware(['auth', 'verified', 'role:administrator'])->group(...)` block (after the users resource), add:

```php
Route::get('/settings',              [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings/{group}',      [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/test-email',  [SettingsController::class, 'testEmail'])->name('settings.test-email');
```

**Step 6: Run the tests**

```bash
php artisan test tests/Feature/SettingsTest.php
```

Expected: All tests pass.

**Step 7: Commit**

```bash
git add app/Http/Controllers/SettingsController.php app/Mail/TestMail.php resources/views/mail/test.blade.php routes/web.php tests/Feature/SettingsTest.php
git commit -m "feat: add SettingsController, TestMail, and settings routes"
```

---

## Task 5: Update `config/media.php` to read from `Setting`

**Files:**
- Modify: `config/media.php`

**Step 1: Replace env() calls with Setting::get()**

Open `config/media.php`. Replace the entire file with:

```php
<?php

return [
    /*
     * Maximum upload size in megabytes.
     * Reads from the settings table; falls back to env for pre-install bootstrapping.
     */
    'max_upload_mb' => (function () {
        try {
            return \App\Models\Setting::get('media.max_upload_mb', env('MEDIA_MAX_UPLOAD_MB', 10));
        } catch (\Throwable) {
            return env('MEDIA_MAX_UPLOAD_MB', 10);
        }
    })(),

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
     * Reads from the settings table; falls back to env for pre-install bootstrapping.
     */
    'resize_max_width' => (function () {
        try {
            return \App\Models\Setting::get('media.resize_max_width', 1920);
        } catch (\Throwable) {
            return 1920;
        }
    })(),
];
```

**Step 2: Verify the build still works**

```bash
php artisan config:clear
php artisan route:list --name=media
```

Expected: No errors, media routes listed.

**Step 3: Commit**

```bash
git add config/media.php
git commit -m "fix: read media config from settings table with env fallback"
```

---

## Task 6: Create the `Settings/Index.vue` frontend page

**Files:**
- Create: `resources/js/Pages/Settings/Index.vue`
- Modify: `resources/js/Layouts/AppLayout.vue`

**Step 1: Create the Vue page**

Create `resources/js/Pages/Settings/Index.vue`:

```vue
<template>
  <AppLayout title="Settings">
    <Head title="Settings" />

    <div class="max-w-2xl space-y-6">

      <!-- Page header -->
      <div>
        <h2 class="text-lg font-semibold">Settings</h2>
        <p class="text-sm text-muted-foreground mt-0.5">Manage global application settings.</p>
      </div>

      <!-- Flash banner -->
      <Transition name="fade">
        <div
          v-if="$page.props.flash?.status"
          class="flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
        >
          <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.status }}
        </div>
      </Transition>

      <!-- Panel 1: Site -->
      <form @submit.prevent="submitSite">
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold">Site</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Basic identity of your CMS.</p>
          </div>

          <div class="space-y-1">
            <label for="site_name" class="text-sm font-medium">Site name</label>
            <input
              id="site_name"
              v-model="siteForm['site.name']"
              type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': siteForm.errors['site.name'] }"
            />
            <p v-if="siteForm.errors['site.name']" class="text-xs text-destructive">{{ siteForm.errors['site.name'] }}</p>
          </div>

          <div class="space-y-1">
            <label for="site_url" class="text-sm font-medium">Site URL</label>
            <input
              id="site_url"
              v-model="siteForm['site.url']"
              type="url"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': siteForm.errors['site.url'] }"
            />
            <p v-if="siteForm.errors['site.url']" class="text-xs text-destructive">{{ siteForm.errors['site.url'] }}</p>
          </div>

          <div class="flex justify-end pt-1">
            <button
              type="submit"
              :disabled="siteForm.processing"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
            >
              {{ siteForm.processing ? 'Saving...' : 'Save changes' }}
            </button>
          </div>
        </div>
      </form>

      <!-- Panel 2: Locale -->
      <form @submit.prevent="submitLocale">
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold">Locale</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Timezone and date display preferences.</p>
          </div>

          <div class="space-y-1">
            <label for="timezone" class="text-sm font-medium">Timezone</label>
            <input
              id="timezone"
              v-model="localeForm['locale.timezone']"
              type="text"
              placeholder="UTC"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': localeForm.errors['locale.timezone'] }"
            />
            <p class="text-xs text-muted-foreground">Use a PHP timezone identifier, e.g. <code>Europe/Oslo</code>, <code>America/New_York</code>.</p>
            <p v-if="localeForm.errors['locale.timezone']" class="text-xs text-destructive">{{ localeForm.errors['locale.timezone'] }}</p>
          </div>

          <div class="space-y-1">
            <label for="date_format" class="text-sm font-medium">Date format</label>
            <input
              id="date_format"
              v-model="localeForm['locale.date_format']"
              type="text"
              placeholder="Y-m-d"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': localeForm.errors['locale.date_format'] }"
            />
            <p class="text-xs text-muted-foreground">PHP date format string, e.g. <code>d/m/Y</code>, <code>m-d-Y</code>.</p>
            <p v-if="localeForm.errors['locale.date_format']" class="text-xs text-destructive">{{ localeForm.errors['locale.date_format'] }}</p>
          </div>

          <div class="flex justify-end pt-1">
            <button
              type="submit"
              :disabled="localeForm.processing"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
            >
              {{ localeForm.processing ? 'Saving...' : 'Save changes' }}
            </button>
          </div>
        </div>
      </form>

      <!-- Panel 3: Media -->
      <form @submit.prevent="submitMedia">
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold">Media</h3>
            <p class="text-xs text-muted-foreground mt-0.5">File upload limits and image processing.</p>
          </div>

          <div class="space-y-1">
            <label for="max_upload_mb" class="text-sm font-medium">Max upload size (MB)</label>
            <input
              id="max_upload_mb"
              v-model.number="mediaForm['media.max_upload_mb']"
              type="number"
              min="1"
              max="100"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': mediaForm.errors['media.max_upload_mb'] }"
            />
            <p v-if="mediaForm.errors['media.max_upload_mb']" class="text-xs text-destructive">{{ mediaForm.errors['media.max_upload_mb'] }}</p>
          </div>

          <div class="space-y-1">
            <label for="resize_max_width" class="text-sm font-medium">Image resize max width (px)</label>
            <input
              id="resize_max_width"
              v-model.number="mediaForm['media.resize_max_width']"
              type="number"
              min="320"
              max="8000"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': mediaForm.errors['media.resize_max_width'] }"
            />
            <p class="text-xs text-muted-foreground">Images wider than this will be scaled down on upload. Set to 8000 to effectively disable.</p>
            <p v-if="mediaForm.errors['media.resize_max_width']" class="text-xs text-destructive">{{ mediaForm.errors['media.resize_max_width'] }}</p>
          </div>

          <div class="flex justify-end pt-1">
            <button
              type="submit"
              :disabled="mediaForm.processing"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
            >
              {{ mediaForm.processing ? 'Saving...' : 'Save changes' }}
            </button>
          </div>
        </div>
      </form>

      <!-- Panel 4: Mail -->
      <form @submit.prevent="submitMail">
        <div class="rounded-lg border bg-card p-6 space-y-4">
          <div>
            <h3 class="text-sm font-semibold">Mail</h3>
            <p class="text-xs text-muted-foreground mt-0.5">Outgoing email configuration.</p>
          </div>

          <div class="space-y-1">
            <label for="mail_driver" class="text-sm font-medium">Driver</label>
            <select
              id="mail_driver"
              v-model="mailForm['mail.driver']"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': mailForm.errors['mail.driver'] }"
            >
              <option value="smtp">SMTP</option>
              <option value="log">Log (development)</option>
              <option value="mailgun">Mailgun</option>
            </select>
            <p v-if="mailForm.errors['mail.driver']" class="text-xs text-destructive">{{ mailForm.errors['mail.driver'] }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label for="mail_host" class="text-sm font-medium">SMTP host</label>
              <input
                id="mail_host"
                v-model="mailForm['mail.host']"
                type="text"
                placeholder="smtp.example.com"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mailForm.errors['mail.host'] }"
              />
              <p v-if="mailForm.errors['mail.host']" class="text-xs text-destructive">{{ mailForm.errors['mail.host'] }}</p>
            </div>

            <div class="space-y-1">
              <label for="mail_port" class="text-sm font-medium">Port</label>
              <input
                id="mail_port"
                v-model.number="mailForm['mail.port']"
                type="number"
                placeholder="587"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mailForm.errors['mail.port'] }"
              />
              <p v-if="mailForm.errors['mail.port']" class="text-xs text-destructive">{{ mailForm.errors['mail.port'] }}</p>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label for="mail_username" class="text-sm font-medium">Username</label>
              <input
                id="mail_username"
                v-model="mailForm['mail.username']"
                type="text"
                autocomplete="off"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
              />
            </div>

            <div class="space-y-1">
              <label for="mail_password" class="text-sm font-medium">Password</label>
              <input
                id="mail_password"
                v-model="mailForm['mail.password']"
                type="password"
                autocomplete="new-password"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              />
            </div>
          </div>

          <div class="space-y-1">
            <label for="mail_encryption" class="text-sm font-medium">Encryption</label>
            <select
              id="mail_encryption"
              v-model="mailForm['mail.encryption']"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            >
              <option value="tls">TLS</option>
              <option value="ssl">SSL</option>
              <option value="">None</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label for="mail_from_address" class="text-sm font-medium">From address</label>
              <input
                id="mail_from_address"
                v-model="mailForm['mail.from_address']"
                type="email"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mailForm.errors['mail.from_address'] }"
              />
              <p v-if="mailForm.errors['mail.from_address']" class="text-xs text-destructive">{{ mailForm.errors['mail.from_address'] }}</p>
            </div>

            <div class="space-y-1">
              <label for="mail_from_name" class="text-sm font-medium">From name</label>
              <input
                id="mail_from_name"
                v-model="mailForm['mail.from_name']"
                type="text"
                class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                :class="{ 'border-destructive': mailForm.errors['mail.from_name'] }"
              />
              <p v-if="mailForm.errors['mail.from_name']" class="text-xs text-destructive">{{ mailForm.errors['mail.from_name'] }}</p>
            </div>
          </div>

          <!-- Mail test inline flash -->
          <Transition name="fade">
            <div
              v-if="$page.props.flash?.mail_status"
              class="flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              {{ $page.props.flash.mail_status }}
            </div>
          </Transition>
          <Transition name="fade">
            <div
              v-if="$page.props.flash?.mail_error"
              class="flex items-center gap-2 rounded-md bg-status-error-bg border border-status-error-border px-4 py-3 text-sm text-status-error-fg"
            >
              <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
              {{ $page.props.flash.mail_error }}
            </div>
          </Transition>

          <div class="flex items-center justify-between pt-1">
            <button
              type="button"
              :disabled="testMailForm.processing"
              @click="sendTestEmail"
              class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors disabled:opacity-50"
            >
              {{ testMailForm.processing ? 'Sending...' : 'Send test email' }}
            </button>

            <button
              type="submit"
              :disabled="mailForm.processing"
              class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
            >
              {{ mailForm.processing ? 'Saving...' : 'Save changes' }}
            </button>
          </div>
        </div>
      </form>

    </div>
  </AppLayout>
</template>

<script setup>
import { Head, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";

const props = defineProps({
  settings: {
    type: Object,
    default: () => ({}),
  },
});

const s = props.settings;

// ── Panel 1: Site ──────────────────────────────────────────────────────────
const siteForm = useForm({
  'site.name': s['site.name'] ?? '',
  'site.url':  s['site.url']  ?? '',
});

function submitSite() {
  siteForm.put(route('settings.update', 'site'), { preserveScroll: true });
}

// ── Panel 2: Locale ────────────────────────────────────────────────────────
const localeForm = useForm({
  'locale.timezone':    s['locale.timezone']    ?? 'UTC',
  'locale.date_format': s['locale.date_format'] ?? 'Y-m-d',
});

function submitLocale() {
  localeForm.put(route('settings.update', 'locale'), { preserveScroll: true });
}

// ── Panel 3: Media ─────────────────────────────────────────────────────────
const mediaForm = useForm({
  'media.max_upload_mb':    Number(s['media.max_upload_mb']    ?? 10),
  'media.resize_max_width': Number(s['media.resize_max_width'] ?? 1920),
});

function submitMedia() {
  mediaForm.put(route('settings.update', 'media'), { preserveScroll: true });
}

// ── Panel 4: Mail ──────────────────────────────────────────────────────────
const mailForm = useForm({
  'mail.driver':       s['mail.driver']       ?? 'smtp',
  'mail.host':         s['mail.host']         ?? '',
  'mail.port':         Number(s['mail.port']  ?? 587),
  'mail.username':     s['mail.username']     ?? '',
  'mail.password':     s['mail.password']     ?? '',
  'mail.from_address': s['mail.from_address'] ?? '',
  'mail.from_name':    s['mail.from_name']    ?? '',
  'mail.encryption':   s['mail.encryption']   ?? 'tls',
});

function submitMail() {
  mailForm.put(route('settings.update', 'mail'), { preserveScroll: true });
}

const testMailForm = useForm({});

function sendTestEmail() {
  testMailForm.post(route('settings.test-email'), { preserveScroll: true });
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
```

**Step 2: Add Settings link to the sidebar**

Open `resources/js/Layouts/AppLayout.vue`. In the "Account" section of the sidebar nav (after the Profile `SidebarLink` and before the `v-if="user.role === 'administrator'"` Users link), add:

```html
<SidebarLink
  v-if="user.role === 'administrator'"
  :href="route('settings.index')"
  :active="currentRoute?.startsWith('settings.')"
>
  <template #icon>
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
    </svg>
  </template>
  Settings
</SidebarLink>
```

**Step 3: Build and verify**

```bash
npm run build
```

Expected: Build completes with no errors. No undefined CSS variable warnings.

**Step 4: Commit**

```bash
git add resources/js/Pages/Settings/Index.vue resources/js/Layouts/AppLayout.vue
git commit -m "feat: add Settings/Index.vue page and sidebar link"
```

---

## Task 7: Run the full test suite

**Step 1: Run all tests**

```bash
php artisan test
```

Expected: All tests pass (green). No failures or errors.

**Step 2: If any test fails**

Read the failure output carefully. Common issues:
- `markAsInstalled()` missing from a new test's `setUp()` — add it
- Cache not flushed between tests — add `Cache::flush()` in `setUp()`
- `seedSettings()` missing from `SettingsTest` — already included above

**Step 3: Final commit if fixes were needed**

```bash
git add -p
git commit -m "fix: address test suite failures after settings implementation"
```

---

## Summary of all files

| File | Action |
|---|---|
| `database/migrations/2026_03_04_000001_create_settings_table.php` | Create |
| `database/seeders/SettingsSeeder.php` | Create |
| `database/seeders/DatabaseSeeder.php` | Modify |
| `app/Models/Setting.php` | Create |
| `app/Services/SettingService.php` | Create |
| `app/Http/Middleware/BootstrapSettings.php` | Create |
| `app/Http/Controllers/SettingsController.php` | Create |
| `app/Mail/TestMail.php` | Create |
| `resources/views/mail/test.blade.php` | Create |
| `config/media.php` | Modify |
| `routes/web.php` | Modify |
| `resources/js/Pages/Settings/Index.vue` | Create |
| `resources/js/Layouts/AppLayout.vue` | Modify |
| `tests/Unit/SettingServiceTest.php` | Create |
| `tests/Feature/SettingsTest.php` | Create |
