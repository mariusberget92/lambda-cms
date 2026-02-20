# Installer Wizard & Public Blog Frontend Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a multi-step web installer that configures `.env`, runs migrations, creates the admin account, and marks the app as installed — then replace the placeholder homepage with a public blog frontend (post list, single post, sidebar).

**Architecture:** An `EnsureInstalled` / `EnsureNotInstalled` middleware pair gates all routes. The installer is a 4-step Inertia wizard (Database → Site → Admin → Mail) using its own `InstallLayout`. The blog frontend uses a separate `BlogLayout` with two routes (`/` and `/blog/{slug}`) handled by `BlogController`. The seeder is refactored to seed only roles/permissions/default content — admin creation is done by the installer.

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind CSS 4, Spatie Laravel Permission, PDO for DB connection testing.

---

## Task 1: Middleware — EnsureInstalled & EnsureNotInstalled

**Files:**
- Create: `app/Http/Middleware/EnsureInstalled.php`
- Create: `app/Http/Middleware/EnsureNotInstalled.php`
- Modify: `bootstrap/app.php`

**Step 1: Create EnsureInstalled middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! file_exists(storage_path('app/installed'))) {
            return redirect('/install');
        }

        return $next($request);
    }
}
```

**Step 2: Create EnsureNotInstalled middleware**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (file_exists(storage_path('app/installed'))) {
            return redirect('/');
        }

        return $next($request);
    }
}
```

**Step 3: Register middleware aliases in bootstrap/app.php**

In the `->withMiddleware()` closure, add to the `alias` array:

```php
'installed'     => \App\Http\Middleware\EnsureInstalled::class,
'not_installed' => \App\Http\Middleware\EnsureNotInstalled::class,
```

**Step 4: Commit**

```bash
git add app/Http/Middleware/EnsureInstalled.php app/Http/Middleware/EnsureNotInstalled.php bootstrap/app.php
git commit -m "feat: add EnsureInstalled and EnsureNotInstalled middleware"
```

---

## Task 2: InstallController — skeleton + .env writer

**Files:**
- Create: `app/Http/Controllers/InstallController.php`

**Step 1: Create the controller with the .env writer helper**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use App\Models\User;

class InstallController extends Controller
{
    // ── Show methods ──────────────────────────────────────────────────────────

    public function showDatabase()
    {
        return Inertia::render('Install/Database', ['step' => 1]);
    }

    public function showSite(Request $request)
    {
        return Inertia::render('Install/Site', [
            'step'    => 2,
            'siteUrl' => $request->getSchemeAndHttpHost(),
        ]);
    }

    public function showAdmin()
    {
        return Inertia::render('Install/Admin', ['step' => 3]);
    }

    public function showMail()
    {
        return Inertia::render('Install/Mail', ['step' => 4]);
    }

    // ── Post methods ──────────────────────────────────────────────────────────

    public function database(Request $request)
    {
        $data = $request->validate([
            'driver'   => ['required', 'in:sqlite,mysql'],
            'host'     => ['required_if:driver,mysql', 'nullable', 'string'],
            'port'     => ['required_if:driver,mysql', 'nullable', 'integer'],
            'database' => ['required_if:driver,mysql', 'nullable', 'string'],
            'prefix'   => ['nullable', 'string', 'max:10'],
            'username' => ['required_if:driver,mysql', 'nullable', 'string'],
            'password' => ['nullable', 'string'],
        ]);

        // Test connection
        if ($data['driver'] === 'mysql') {
            try {
                $dsn = "mysql:host={$data['host']};port={$data['port']};dbname={$data['database']}";
                new \PDO($dsn, $data['username'], $data['password'] ?? '');
            } catch (\PDOException $e) {
                return back()->withErrors(['database' => 'Could not connect: ' . $e->getMessage()]);
            }
        }

        $env = ['DB_CONNECTION' => $data['driver']];

        if ($data['driver'] === 'mysql') {
            $env['DB_HOST']     = $data['host'];
            $env['DB_PORT']     = $data['port'];
            $env['DB_DATABASE'] = $data['database'];
            $env['DB_PREFIX']   = $data['prefix'] ?? '';
            $env['DB_USERNAME'] = $data['username'];
            $env['DB_PASSWORD'] = $data['password'] ?? '';
        } else {
            $env['DB_DATABASE'] = database_path('database.sqlite');
        }

        $this->writeEnv($env);
        Artisan::call('config:clear');

        return redirect('/install/site');
    }

    public function site(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url'  => ['required', 'url', 'max:255'],
        ]);

        $this->writeEnv([
            'APP_NAME' => '"' . $data['site_name'] . '"',
            'APP_URL'  => $data['site_url'],
        ]);
        Artisan::call('config:clear');

        return redirect('/install/admin');
    }

    public function admin(Request $request)
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->session()->put('install_admin', [
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        return redirect('/install/mail');
    }

    public function mail(Request $request)
    {
        $data = $request->validate([
            'mailer'       => ['required', 'in:smtp,log'],
            'host'         => ['required_if:mailer,smtp', 'nullable', 'string'],
            'port'         => ['required_if:mailer,smtp', 'nullable', 'integer'],
            'username'     => ['nullable', 'string'],
            'password'     => ['nullable', 'string'],
            'from_address' => ['required_if:mailer,smtp', 'nullable', 'email'],
            'from_name'    => ['required_if:mailer,smtp', 'nullable', 'string'],
        ]);

        $env = ['MAIL_MAILER' => $data['mailer']];

        if ($data['mailer'] === 'smtp') {
            $env['MAIL_HOST']         = $data['host'];
            $env['MAIL_PORT']         = $data['port'];
            $env['MAIL_USERNAME']     = $data['username'] ?? '';
            $env['MAIL_PASSWORD']     = $data['password'] ?? '';
            $env['MAIL_FROM_ADDRESS'] = $data['from_address'];
            $env['MAIL_FROM_NAME']    = '"' . $data['from_name'] . '"';
        }

        $this->writeEnv($env);
        Artisan::call('config:clear');

        // Run migrations and seed
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);

        // Create admin from session
        $adminData = $request->session()->get('install_admin');
        $user = User::create([
            'name'              => $adminData['name'],
            'email'             => $adminData['email'],
            'password'          => Hash::make($adminData['password']),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('administrator');

        // Mark as installed
        file_put_contents(storage_path('app/installed'), now()->toDateTimeString());

        // Log in and redirect
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('install_admin');

        return redirect('/dashboard')->with('status', 'Lambda CMS installed successfully. Welcome!');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function writeEnv(array $values): void
    {
        $path    = base_path('.env');
        $content = file_exists($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $pattern     = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replacement, $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($path, $content);
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/InstallController.php
git commit -m "feat: add InstallController with 4-step handler and .env writer"
```

---

## Task 3: Refactor DatabaseSeeder — remove hardcoded admin

The seeder currently creates a hardcoded admin user with fixed credentials. The installer creates the real admin, so the seeder must only handle roles/permissions/default content. The seeder receives the admin user via a property set by the installer.

**Files:**
- Modify: `database/seeders/DatabaseSeeder.php`

**Step 1: Rewrite DatabaseSeeder**

```php
<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * The admin user created by the installer.
     * Set before calling run() from InstallController.
     */
    public ?User $adminUser = null;

    public function run(): void
    {
        // ── Roles & Permissions ──────────────────────────────────────────────
        foreach (['manage posts', 'manage categories', 'manage tags', 'manage users'] as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'administrator']);
        $userRole  = Role::firstOrCreate(['name' => 'user']);

        $adminRole->syncPermissions(Permission::all());
        $userRole->syncPermissions(['manage posts', 'manage categories', 'manage tags']);

        // ── Default content (requires admin user) ────────────────────────────
        $admin = $this->adminUser ?? User::first();

        if (! $admin) {
            return; // Nothing to seed without an admin
        }

        $general = Category::firstOrCreate(
            ['slug' => 'general'],
            [
                'name'        => 'General',
                'description' => 'General posts and announcements.',
            ]
        );

        Post::firstOrCreate(
            ['slug' => 'hello-world'],
            [
                'user_id'      => $admin->id,
                'category_id'  => $general->id,
                'title'        => 'Hello World',
                'excerpt'      => 'Welcome to Lambda CMS. This is your first post — feel free to edit or delete it.',
                'body'         => '<h2>Welcome to Lambda CMS!</h2><p>You have successfully installed Lambda CMS. This is your first post. You can edit it, delete it, or use it as a starting point for your content.</p><p>Head to the <a href="/dashboard">dashboard</a> to get started.</p>',
                'status'       => 'published',
                'published_at' => Carbon::now(),
            ]
        );
    }
}
```

**Step 2: Update InstallController to pass admin to seeder**

In `InstallController@mail`, replace the bare `Artisan::call('db:seed', ...)` with:

```php
// Run migrations
Artisan::call('migrate', ['--force' => true]);

// Seed roles/permissions first (no admin needed yet)
Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder', '--force' => true]);

// Create admin from session
$adminData = $request->session()->get('install_admin');
$user = User::create([
    'name'              => $adminData['name'],
    'email'             => $adminData['email'],
    'password'          => Hash::make($adminData['password']),
    'email_verified_at' => now(),
]);
$user->assignRole('administrator');

// Now seed the default post using the real admin
$seeder = new \Database\Seeders\DatabaseSeeder();
$seeder->adminUser = $user;
// Re-run only the content portion by calling run() directly
// (roles/permissions use firstOrCreate so re-running is safe)
$seeder->run();
```

**Step 3: Commit**

```bash
git add database/seeders/DatabaseSeeder.php app/Http/Controllers/InstallController.php
git commit -m "feat: refactor seeder to seed roles only, installer creates admin and default post"
```

---

## Task 4: Install Routes

**Files:**
- Modify: `routes/web.php`

**Step 1: Add install routes and wrap existing routes with `installed` middleware**

At the top of `web.php`, add imports:
```php
use App\Http\Controllers\InstallController;
```

Replace the entire file content with:

```php
<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Installer (only accessible when NOT installed) ────────────────────────────
Route::middleware('not_installed')->prefix('install')->name('install.')->group(function () {
    Route::get('/',        fn () => redirect('/install/database'));
    Route::get('/database', [InstallController::class, 'showDatabase'])->name('database');
    Route::post('/database', [InstallController::class, 'database']);
    Route::get('/site',    [InstallController::class, 'showSite'])->name('site');
    Route::post('/site',   [InstallController::class, 'site']);
    Route::get('/admin',   [InstallController::class, 'showAdmin'])->name('admin');
    Route::post('/admin',  [InstallController::class, 'admin']);
    Route::get('/mail',    [InstallController::class, 'showMail'])->name('mail');
    Route::post('/mail',   [InstallController::class, 'mail']);
});

// ── All routes below require app to be installed ──────────────────────────────
Route::middleware('installed')->group(function () {

    // ── Public blog ───────────────────────────────────────────────────────────
    Route::get('/',              [BlogController::class, 'index'])->name('home');
    Route::get('/blog/{slug}',   [BlogController::class, 'show'])->name('blog.show');

    // ── Guest-only ────────────────────────────────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('/login',  [LoginController::class, 'show'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('auth.login');

        Route::get('/forgot-password',  [ForgotPasswordController::class, 'show'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
        Route::post('/reset-password',        [ResetPasswordController::class, 'reset'])->name('password.update');
    });

    // ── Auth only ─────────────────────────────────────────────────────────────
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

        Route::get('/email/verify', fn () => inertia('Auth/VerifyEmail'))->name('verification.notice');

        Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
            $request->fulfill();
            return redirect()->route('dashboard')->with('status', 'Email verified! Welcome to Lambda CMS.');
        })->middleware('signed')->name('verification.verify');

        Route::post('/email/verification-notification', function (Request $request) {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('status', 'verification-link-sent');
        })->middleware('throttle:6,1')->name('verification.send');
    });

    // ── Auth + verified ───────────────────────────────────────────────────────
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('posts',      PostController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('tags',       TagController::class)->except(['show']);

        Route::get('/profile',           [ProfileController::class, 'show'])->name('profile');
        Route::post('/profile/info',     [ProfileController::class, 'updateInfo'])->name('profile.info');
        Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
        Route::post('/profile/avatar',   [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
        Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar'])->name('profile.avatar.delete');
    });

    // ── Auth + verified + administrator ───────────────────────────────────────
    Route::middleware(['auth', 'verified', 'role:administrator'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

}); // end installed
```

**Step 2: Commit**

```bash
git add routes/web.php
git commit -m "feat: add install routes and wrap all app routes in installed middleware"
```

---

## Task 5: InstallLayout.vue

**Files:**
- Create: `resources/js/Layouts/InstallLayout.vue`

**Step 1: Create the layout**

```vue
<template>
  <div class="min-h-screen bg-muted/30 flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-lg">

      <!-- Logo -->
      <div class="flex items-center justify-center gap-3 mb-8">
        <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center">
          <svg class="w-5 h-5 text-primary-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3L2 7l10 4 10-4-10-4zM2 17l10 4 10-4M2 12l10 4 10-4" />
          </svg>
        </div>
        <span class="text-xl font-bold tracking-tight">Lambda CMS</span>
      </div>

      <!-- Step indicator -->
      <div class="flex items-center justify-center gap-0 mb-8">
        <template v-for="(label, i) in steps" :key="label">
          <!-- Step circle -->
          <div class="flex flex-col items-center">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold border-2 transition-colors"
              :class="stepIndex > i
                ? 'bg-primary border-primary text-primary-foreground'
                : stepIndex === i
                  ? 'border-primary text-primary bg-background'
                  : 'border-border text-muted-foreground bg-background'"
            >
              <svg v-if="stepIndex > i" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
              <span v-else>{{ i + 1 }}</span>
            </div>
            <span
              class="mt-1.5 text-xs font-medium"
              :class="stepIndex >= i ? 'text-foreground' : 'text-muted-foreground'"
            >{{ label }}</span>
          </div>
          <!-- Connector line -->
          <div
            v-if="i < steps.length - 1"
            class="h-0.5 w-12 mb-5 transition-colors"
            :class="stepIndex > i ? 'bg-primary' : 'bg-border'"
          />
        </template>
      </div>

      <!-- Card -->
      <div class="bg-card border rounded-xl shadow-sm p-8">
        <slot />
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  step: { type: Number, required: true }, // 1-indexed
});

const steps = ['Database', 'Site', 'Admin', 'Mail'];
const stepIndex = computed(() => props.step - 1);
</script>
```

**Step 2: Commit**

```bash
git add resources/js/Layouts/InstallLayout.vue
git commit -m "feat: add InstallLayout with step progress indicator"
```

---

## Task 6: Install Vue pages (4 steps)

**Files:**
- Create: `resources/js/Pages/Install/Database.vue`
- Create: `resources/js/Pages/Install/Site.vue`
- Create: `resources/js/Pages/Install/Admin.vue`
- Create: `resources/js/Pages/Install/Mail.vue`

**Step 1: Create Install/Database.vue**

```vue
<template>
  <InstallLayout :step="step">
    <Head title="Install — Database" />
    <h2 class="text-lg font-semibold mb-1">Database connection</h2>
    <p class="text-sm text-muted-foreground mb-6">Choose your database driver and enter the connection details.</p>

    <form @submit.prevent="submit" class="space-y-4">

      <!-- Driver -->
      <div class="space-y-1">
        <label class="text-sm font-medium">Database driver</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="form.driver" value="sqlite" class="accent-primary" />
            <span class="text-sm">SQLite</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="form.driver" value="mysql" class="accent-primary" />
            <span class="text-sm">MySQL / MariaDB</span>
          </label>
        </div>
      </div>

      <!-- SQLite note -->
      <div v-if="form.driver === 'sqlite'" class="rounded-md bg-muted px-4 py-3 text-sm text-muted-foreground">
        SQLite will be stored at <code class="font-mono text-xs">database/database.sqlite</code>. No further configuration needed.
      </div>

      <!-- MySQL fields -->
      <template v-if="form.driver === 'mysql'">
        <div class="grid grid-cols-3 gap-3">
          <div class="col-span-2 space-y-1">
            <label class="text-sm font-medium">Host</label>
            <input v-model="form.host" type="text" placeholder="127.0.0.1"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.host }" />
            <p v-if="form.errors.host" class="text-xs text-destructive">{{ form.errors.host }}</p>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Port</label>
            <input v-model="form.port" type="number" placeholder="3306"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.port }" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="text-sm font-medium">Database name</label>
            <input v-model="form.database" type="text" placeholder="lambda_cms"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.database }" />
            <p v-if="form.errors.database" class="text-xs text-destructive">{{ form.errors.database }}</p>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Table prefix <span class="text-muted-foreground">(optional)</span></label>
            <input v-model="form.prefix" type="text" placeholder="lc_"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="text-sm font-medium">Username</label>
            <input v-model="form.username" type="text" autocomplete="off"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.username }" />
            <p v-if="form.errors.username" class="text-xs text-destructive">{{ form.errors.username }}</p>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Password</label>
            <input v-model="form.password" type="password" autocomplete="off"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
        </div>
        <p v-if="form.errors.database" class="text-xs text-destructive">{{ form.errors.database }}</p>
      </template>

      <div class="flex justify-end pt-2">
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Testing connection…' : 'Next →' }}
        </button>
      </div>
    </form>
  </InstallLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InstallLayout from '@/Layouts/InstallLayout.vue';

defineOptions({ layout: null });

const props = defineProps({ step: Number });

const form = useForm({
  driver: 'sqlite',
  host: '127.0.0.1',
  port: 3306,
  database: '',
  prefix: '',
  username: '',
  password: '',
});

function submit() {
  form.post('/install/database');
}
</script>
```

**Step 2: Create Install/Site.vue**

```vue
<template>
  <InstallLayout :step="step">
    <Head title="Install — Site" />
    <h2 class="text-lg font-semibold mb-1">Site settings</h2>
    <p class="text-sm text-muted-foreground mb-6">Give your site a name and confirm its URL.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="space-y-1">
        <label class="text-sm font-medium">Site name</label>
        <input v-model="form.site_name" type="text" placeholder="My Blog" autofocus
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.site_name }" />
        <p v-if="form.errors.site_name" class="text-xs text-destructive">{{ form.errors.site_name }}</p>
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Site URL</label>
        <input v-model="form.site_url" type="url"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.site_url }" />
        <p v-if="form.errors.site_url" class="text-xs text-destructive">{{ form.errors.site_url }}</p>
      </div>

      <div class="flex justify-between pt-2">
        <a href="/install/database" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">← Back</a>
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Saving…' : 'Next →' }}
        </button>
      </div>
    </form>
  </InstallLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InstallLayout from '@/Layouts/InstallLayout.vue';

defineOptions({ layout: null });

const props = defineProps({ step: Number, siteUrl: String });

const form = useForm({
  site_name: 'Lambda CMS',
  site_url: props.siteUrl ?? '',
});

function submit() {
  form.post('/install/site');
}
</script>
```

**Step 3: Create Install/Admin.vue**

```vue
<template>
  <InstallLayout :step="step">
    <Head title="Install — Admin account" />
    <h2 class="text-lg font-semibold mb-1">Administrator account</h2>
    <p class="text-sm text-muted-foreground mb-6">This will be the primary admin account for Lambda CMS.</p>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="space-y-1">
        <label class="text-sm font-medium">Full name</label>
        <input v-model="form.name" type="text" autofocus
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.name }" />
        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Email address</label>
        <input v-model="form.email" type="email" autocomplete="email"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.email }" />
        <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Password</label>
        <input v-model="form.password" type="password" autocomplete="new-password"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': form.errors.password }" />
        <p v-if="form.errors.password" class="text-xs text-destructive">{{ form.errors.password }}</p>
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Confirm password</label>
        <input v-model="form.password_confirmation" type="password" autocomplete="new-password"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
      </div>

      <div class="flex justify-between pt-2">
        <a href="/install/site" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">← Back</a>
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Saving…' : 'Next →' }}
        </button>
      </div>
    </form>
  </InstallLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InstallLayout from '@/Layouts/InstallLayout.vue';

defineOptions({ layout: null });

const props = defineProps({ step: Number });

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

function submit() {
  form.post('/install/admin');
}
</script>
```

**Step 4: Create Install/Mail.vue**

```vue
<template>
  <InstallLayout :step="step">
    <Head title="Install — Mail" />
    <h2 class="text-lg font-semibold mb-1">Mail settings</h2>
    <p class="text-sm text-muted-foreground mb-6">Configure how Lambda CMS sends emails.</p>

    <form @submit.prevent="submit" class="space-y-4">

      <!-- Mailer -->
      <div class="space-y-1">
        <label class="text-sm font-medium">Mailer</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="form.mailer" value="smtp" class="accent-primary" />
            <span class="text-sm">SMTP</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" v-model="form.mailer" value="log" class="accent-primary" />
            <span class="text-sm">Log (local dev)</span>
          </label>
        </div>
      </div>

      <!-- Log note -->
      <div v-if="form.mailer === 'log'" class="rounded-md bg-muted px-4 py-3 text-sm text-muted-foreground">
        Emails will be written to <code class="font-mono text-xs">storage/logs/laravel.log</code> instead of being sent. Useful for local development.
      </div>

      <!-- SMTP fields -->
      <template v-if="form.mailer === 'smtp'">
        <div class="grid grid-cols-3 gap-3">
          <div class="col-span-2 space-y-1">
            <label class="text-sm font-medium">SMTP host</label>
            <input v-model="form.host" type="text" placeholder="smtp.example.com"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.host }" />
            <p v-if="form.errors.host" class="text-xs text-destructive">{{ form.errors.host }}</p>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Port</label>
            <input v-model="form.port" type="number" placeholder="587"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="text-sm font-medium">Username</label>
            <input v-model="form.username" type="text" autocomplete="off"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">Password</label>
            <input v-model="form.password" type="password" autocomplete="off"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring" />
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="text-sm font-medium">From address</label>
            <input v-model="form.from_address" type="email" placeholder="hello@example.com"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.from_address }" />
            <p v-if="form.errors.from_address" class="text-xs text-destructive">{{ form.errors.from_address }}</p>
          </div>
          <div class="space-y-1">
            <label class="text-sm font-medium">From name</label>
            <input v-model="form.from_name" type="text"
              class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
              :class="{ 'border-destructive': form.errors.from_name }" />
            <p v-if="form.errors.from_name" class="text-xs text-destructive">{{ form.errors.from_name }}</p>
          </div>
        </div>
      </template>

      <div class="flex justify-between pt-2">
        <a href="/install/admin" class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-accent transition-colors">← Back</a>
        <button type="submit" :disabled="form.processing"
          class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 transition-colors">
          {{ form.processing ? 'Installing…' : 'Install Lambda CMS' }}
        </button>
      </div>
    </form>
  </InstallLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InstallLayout from '@/Layouts/InstallLayout.vue';

defineOptions({ layout: null });

const props = defineProps({ step: Number });

const form = useForm({
  mailer: 'log',
  host: '',
  port: 587,
  username: '',
  password: '',
  from_address: '',
  from_name: '',
});

function submit() {
  form.post('/install/mail');
}
</script>
```

**Step 5: Commit**

```bash
git add resources/js/Pages/Install/
git commit -m "feat: add 4-step installer Vue pages"
```

---

## Task 7: BlogController

**Files:**
- Create: `app/Http/Controllers/BlogController.php`

**Step 1: Create the controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Post::with('author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug')
            ->published()
            ->latest('published_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($post) => $this->formatPost($post));

        return Inertia::render('Blog/Index', [
            'posts'   => $posts,
            'sidebar' => $this->sidebarData(),
        ]);
    }

    public function show(string $slug)
    {
        $post = Post::with('author:id,name,avatar', 'category:id,name,slug', 'tags:id,name,slug')
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Blog/Show', [
            'post'    => $this->formatPost($post, withBody: true),
            'sidebar' => $this->sidebarData(),
        ]);
    }

    private function formatPost(Post $post, bool $withBody = false): array
    {
        $data = [
            'id'           => $post->id,
            'title'        => $post->title,
            'slug'         => $post->slug,
            'excerpt'      => $post->excerpt,
            'published_at' => $post->published_at?->toDateString(),
            'author'       => [
                'name'       => $post->author->name,
                'avatar_url' => $post->author->avatar_url,
            ],
            'category' => $post->category ? [
                'name' => $post->category->name,
                'slug' => $post->category->slug,
            ] : null,
            'tags' => $post->tags->map(fn ($t) => ['name' => $t->name, 'slug' => $t->slug]),
        ];

        if ($withBody) {
            $data['body'] = $post->body;
        }

        return $data;
    }

    private function sidebarData(): array
    {
        return [
            'categories'  => Category::withCount(['posts' => fn ($q) => $q->published()])
                ->having('posts_count', '>', 0)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'tags'        => Tag::withCount(['posts' => fn ($q) => $q->published()])
                ->having('posts_count', '>', 0)
                ->orderBy('name')
                ->get(['id', 'name', 'slug']),
            'recentPosts' => Post::published()
                ->latest('published_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'published_at'])
                ->map(fn ($p) => [
                    'title'        => $p->title,
                    'slug'         => $p->slug,
                    'published_at' => $p->published_at?->toDateString(),
                ]),
        ];
    }
}
```

**Step 2: Commit**

```bash
git add app/Http/Controllers/BlogController.php
git commit -m "feat: add BlogController with index and show methods"
```

---

## Task 8: BlogLayout.vue

**Files:**
- Create: `resources/js/Layouts/BlogLayout.vue`

**Step 1: Create the layout**

```vue
<template>
  <div class="min-h-screen bg-background flex flex-col">

    <!-- Top nav -->
    <header class="border-b bg-background/95 backdrop-blur-sm sticky top-0 z-10">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 flex items-center justify-between h-14">
        <a href="/" class="font-semibold text-sm tracking-tight hover:opacity-80 transition-opacity">
          {{ appName }}
        </a>
        <a
          :href="route('login')"
          class="text-sm text-muted-foreground hover:text-foreground transition-colors"
        >
          Sign in
        </a>
      </div>
    </header>

    <!-- Hero -->
    <div class="border-b bg-muted/30">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12 text-center">
        <h1 class="text-3xl font-bold tracking-tight mb-2">{{ appName }}</h1>
        <p class="text-muted-foreground">A place for thoughts, ideas, and stories.</p>
      </div>
    </div>

    <!-- Main content -->
    <main class="flex-1">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 py-10">
        <slot />
      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t py-6">
      <p class="text-center text-xs text-muted-foreground">
        &copy; {{ year }} {{ appName }}
      </p>
    </footer>

  </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page    = usePage();
const appName = computed(() => page.props.appName ?? 'Lambda CMS');
const year    = new Date().getFullYear();
</script>
```

**Step 2: Share `appName` in HandleInertiaRequests**

In `app/Http/Middleware/HandleInertiaRequests.php`, add to the `share()` return array:

```php
'appName' => config('app.name'),
```

**Step 3: Commit**

```bash
git add resources/js/Layouts/BlogLayout.vue app/Http/Middleware/HandleInertiaRequests.php
git commit -m "feat: add BlogLayout and share appName via Inertia"
```

---

## Task 9: Blog/Index.vue

**Files:**
- Create: `resources/js/Pages/Blog/Index.vue`

**Step 1: Create the page**

```vue
<template>
  <BlogLayout>
    <Head title="Blog" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

      <!-- Main: post list -->
      <div class="lg:col-span-2 space-y-8">

        <!-- Empty state -->
        <div v-if="posts.data.length === 0" class="text-center py-20 text-muted-foreground">
          <svg class="w-10 h-10 mx-auto mb-4 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          <p class="font-medium">No posts yet.</p>
          <p class="text-sm mt-1">Check back soon.</p>
        </div>

        <!-- Post cards -->
        <article
          v-for="post in posts.data"
          :key="post.id"
          class="group border-b pb-8 last:border-0"
        >
          <!-- Category badge -->
          <div v-if="post.category" class="mb-2">
            <span class="text-xs font-medium uppercase tracking-wider text-primary">{{ post.category.name }}</span>
          </div>

          <h2 class="text-xl font-bold tracking-tight mb-2 group-hover:text-primary transition-colors">
            <a :href="`/blog/${post.slug}`">{{ post.title }}</a>
          </h2>

          <p v-if="post.excerpt" class="text-muted-foreground text-sm leading-relaxed mb-4">{{ post.excerpt }}</p>

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
              <!-- Author avatar -->
              <div class="w-6 h-6 rounded-full bg-muted overflow-hidden flex items-center justify-center text-xs font-semibold uppercase shrink-0">
                <img v-if="post.author.avatar_url" :src="post.author.avatar_url" :alt="post.author.name" class="w-full h-full object-cover" />
                <span v-else>{{ post.author.name[0] }}</span>
              </div>
              <span>{{ post.author.name }}</span>
              <span>·</span>
              <span>{{ post.published_at }}</span>
            </div>
            <a :href="`/blog/${post.slug}`" class="text-xs font-medium text-primary hover:underline">Read more →</a>
          </div>
        </article>

        <!-- Pagination -->
        <div v-if="posts.last_page > 1" class="flex gap-1 pt-4">
          <a
            v-for="link in posts.links"
            :key="link.label"
            :href="link.url ?? undefined"
            class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm border transition-colors"
            :class="link.active
              ? 'bg-primary text-primary-foreground border-primary'
              : link.url
                ? 'hover:bg-accent text-foreground border-border'
                : 'text-muted-foreground/40 border-border cursor-not-allowed pointer-events-none'"
          >{{ decodeHtmlEntities(link.label) }}</a>
        </div>
      </div>

      <!-- Sidebar -->
      <BlogSidebar :sidebar="sidebar" />
    </div>
  </BlogLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import BlogLayout from '@/Layouts/BlogLayout.vue';
import BlogSidebar from '@/Components/BlogSidebar.vue';

defineProps({
  posts:   { type: Object, required: true },
  sidebar: { type: Object, required: true },
});

function decodeHtmlEntities(str) {
  const txt = document.createElement('textarea');
  txt.innerHTML = str;
  return txt.value;
}
</script>
```

**Step 2: Create BlogSidebar component**

Create `resources/js/Components/BlogSidebar.vue`:

```vue
<template>
  <aside class="space-y-8">

    <!-- Categories -->
    <div v-if="sidebar.categories.length">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Categories</h3>
      <ul class="space-y-1.5">
        <li v-for="cat in sidebar.categories" :key="cat.id">
          <a :href="`/?category=${cat.slug}`" class="flex items-center justify-between text-sm hover:text-primary transition-colors">
            <span>{{ cat.name }}</span>
            <span class="text-xs text-muted-foreground">{{ cat.posts_count }}</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Tags -->
    <div v-if="sidebar.tags.length">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Tags</h3>
      <div class="flex flex-wrap gap-2">
        <a
          v-for="tag in sidebar.tags"
          :key="tag.id"
          :href="`/?tag=${tag.slug}`"
          class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium hover:bg-accent transition-colors"
        >
          {{ tag.name }}
        </a>
      </div>
    </div>

    <!-- Recent posts -->
    <div v-if="sidebar.recentPosts.length">
      <h3 class="text-sm font-semibold uppercase tracking-wider text-muted-foreground mb-3">Recent Posts</h3>
      <ul class="space-y-2">
        <li v-for="post in sidebar.recentPosts" :key="post.slug">
          <a :href="`/blog/${post.slug}`" class="block text-sm hover:text-primary transition-colors leading-snug">
            {{ post.title }}
          </a>
          <span class="text-xs text-muted-foreground">{{ post.published_at }}</span>
        </li>
      </ul>
    </div>

  </aside>
</template>

<script setup>
defineProps({
  sidebar: { type: Object, required: true },
});
</script>
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Blog/Index.vue resources/js/Components/BlogSidebar.vue
git commit -m "feat: add Blog/Index page and BlogSidebar component"
```

---

## Task 10: Blog/Show.vue

**Files:**
- Create: `resources/js/Pages/Blog/Show.vue`

**Step 1: Create the page**

```vue
<template>
  <BlogLayout>
    <Head :title="post.title" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

      <!-- Main: post content -->
      <article class="lg:col-span-2">

        <!-- Back link -->
        <a href="/" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground mb-6 transition-colors">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
          </svg>
          Back to posts
        </a>

        <!-- Category -->
        <div v-if="post.category" class="mb-3">
          <span class="text-xs font-medium uppercase tracking-wider text-primary">{{ post.category.name }}</span>
        </div>

        <!-- Title -->
        <h1 class="text-3xl font-bold tracking-tight mb-4">{{ post.title }}</h1>

        <!-- Meta -->
        <div class="flex items-center gap-3 mb-8 pb-8 border-b">
          <div class="w-9 h-9 rounded-full bg-muted overflow-hidden flex items-center justify-center text-sm font-semibold uppercase shrink-0">
            <img v-if="post.author.avatar_url" :src="post.author.avatar_url" :alt="post.author.name" class="w-full h-full object-cover" />
            <span v-else>{{ post.author.name[0] }}</span>
          </div>
          <div>
            <p class="text-sm font-medium">{{ post.author.name }}</p>
            <p class="text-xs text-muted-foreground">{{ post.published_at }}</p>
          </div>
        </div>

        <!-- Body -->
        <div class="prose prose-neutral max-w-none" v-html="post.body" />

        <!-- Tags -->
        <div v-if="post.tags.length" class="flex flex-wrap gap-2 mt-8 pt-8 border-t">
          <a
            v-for="tag in post.tags"
            :key="tag.slug"
            :href="`/?tag=${tag.slug}`"
            class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium hover:bg-accent transition-colors"
          >
            {{ tag.name }}
          </a>
        </div>
      </article>

      <!-- Sidebar -->
      <BlogSidebar :sidebar="sidebar" />
    </div>
  </BlogLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import BlogLayout from '@/Layouts/BlogLayout.vue';
import BlogSidebar from '@/Components/BlogSidebar.vue';

defineProps({
  post:    { type: Object, required: true },
  sidebar: { type: Object, required: true },
});
</script>
```

**Step 2: Commit**

```bash
git add resources/js/Pages/Blog/Show.vue
git commit -m "feat: add Blog/Show single post page"
```

---

## Task 11: Cleanup — remove old Index.vue, push to GitHub

**Files:**
- Delete: `resources/js/Pages/Index.vue`

**Step 1: Delete the old placeholder page**

```bash
git rm resources/js/Pages/Index.vue
```

**Step 2: Final commit and push**

```bash
git commit -m "feat: remove placeholder Index.vue, replaced by Blog/Index"
git push origin master
```

---

## Manual verification checklist

After implementation, verify the following manually:

1. **Fresh install flow**: Delete `storage/app/installed`, visit `/` → should redirect to `/install/database`
2. **SQLite path**: Choose SQLite in step 1, complete wizard → check `database/database.sqlite` was created
3. **MySQL path**: Use a real MySQL DB, test bad credentials → should show "Could not connect" error
4. **Step locking**: After install, visiting `/install/database` should redirect to `/`
5. **Default post visible**: After install, visit `/` → see "Hello World" post in the blog list
6. **Single post**: Click "Read more →" → `/blog/hello-world` shows full post
7. **Dashboard redirect**: After install, land on `/dashboard` with welcome flash message
8. **Sign in link**: Click "Sign in" in blog nav → goes to `/login`
9. **Sidebar**: Categories, tags, and recent posts show in sidebar on both blog pages
