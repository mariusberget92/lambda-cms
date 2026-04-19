# Ban System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Allow admins to ban regular users with a reason and optional expiry, with immediate session invalidation and auto-lift on expiry.

**Architecture:** Three nullable columns on `users` (`banned_at`, `banned_until`, `ban_reason`), two model helpers (`isBanned`, `liftExpiredBan`), a middleware that kicks banned active sessions, a login-gate check, a `BanController` with ban/unban actions, and UI changes to Users/Index (modal) and Users/Form (inline card).

**Tech Stack:** Laravel 12, Inertia 2, Vue 3, Tailwind 4, Spatie Permission (already installed)

---

### Task 1: Migration — add ban columns to users

**Files:**
- Create: `database/migrations/2026_03_27_000001_add_ban_columns_to_users_table.php`

**Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('last_seen_at');
            $table->timestamp('banned_until')->nullable()->after('banned_at');
            $table->string('ban_reason')->nullable()->after('banned_until');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_at', 'banned_until', 'ban_reason']);
        });
    }
};
```

**Step 2: Run migration**

```bash
php artisan migrate
```

Expected: `Migrating: 2026_03_27_000001_add_ban_columns_to_users_table` then `Migrated`.

**Step 3: Commit**

```bash
git add database/migrations/2026_03_27_000001_add_ban_columns_to_users_table.php
git commit -m "feat: add banned_at, banned_until, ban_reason columns to users"
```

---

### Task 2: User model — helpers + casts

**Files:**
- Modify: `app/Models/User.php`

**Step 1: Write failing tests**

Add to `tests/Feature/UserTest.php` inside the class (before the last closing brace):

```php
// ── Ban helpers ───────────────────────────────────────────────────────────────

public function test_is_banned_returns_false_when_not_banned(): void
{
    $user = $this->makeUser();
    $this->assertFalse($user->isBanned());
}

public function test_is_banned_returns_true_for_permanent_ban(): void
{
    $user = $this->makeUser();
    $user->update(['banned_at' => now(), 'banned_until' => null, 'ban_reason' => 'spam']);
    $this->assertTrue($user->isBanned());
}

public function test_is_banned_returns_true_for_active_timed_ban(): void
{
    $user = $this->makeUser();
    $user->update(['banned_at' => now(), 'banned_until' => now()->addDay(), 'ban_reason' => 'spam']);
    $this->assertTrue($user->isBanned());
}

public function test_is_banned_returns_false_for_expired_ban(): void
{
    $user = $this->makeUser();
    $user->update(['banned_at' => now()->subDay(), 'banned_until' => now()->subHour(), 'ban_reason' => 'spam']);
    $this->assertFalse($user->isBanned());
}

public function test_lift_expired_ban_clears_columns(): void
{
    $user = $this->makeUser();
    $user->update(['banned_at' => now()->subDay(), 'banned_until' => now()->subHour(), 'ban_reason' => 'spam']);
    $lifted = $user->liftExpiredBan();
    $this->assertTrue($lifted);
    $user->refresh();
    $this->assertNull($user->banned_at);
    $this->assertNull($user->banned_until);
    $this->assertNull($user->ban_reason);
}

public function test_lift_expired_ban_does_nothing_for_active_ban(): void
{
    $user = $this->makeUser();
    $user->update(['banned_at' => now(), 'banned_until' => now()->addDay(), 'ban_reason' => 'spam']);
    $lifted = $user->liftExpiredBan();
    $this->assertFalse($lifted);
    $user->refresh();
    $this->assertNotNull($user->banned_at);
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=test_is_banned
php artisan test --filter=test_lift_expired_ban
```

Expected: FAIL — `Call to undefined method App\Models\User::isBanned()`

**Step 3: Update User model**

In `app/Models/User.php`:

Add `'banned_at'` and `'banned_until'` to `casts()`:

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
        'last_seen_at'      => 'datetime',
        'banned_at'         => 'datetime',
        'banned_until'      => 'datetime',
    ];
}
```

Add `ban_reason` to `$fillable`:

```php
protected $fillable = [
    'name',
    'email',
    'password',
    'avatar',
    'banned_at',
    'banned_until',
    'ban_reason',
];
```

Add the two helpers after `isOnline()`:

```php
/**
 * Whether the user is currently banned (and ban has not expired).
 */
public function isBanned(): bool
{
    return $this->banned_at !== null
        && ($this->banned_until === null || $this->banned_until->isFuture());
}

/**
 * If the timed ban has expired, clear the ban columns.
 * Returns true if a ban was lifted, false otherwise.
 */
public function liftExpiredBan(): bool
{
    if (
        $this->banned_at !== null
        && $this->banned_until !== null
        && $this->banned_until->isPast()
    ) {
        $this->update(['banned_at' => null, 'banned_until' => null, 'ban_reason' => null]);
        return true;
    }

    return false;
}
```

**Step 4: Run tests to verify they pass**

```bash
php artisan test --filter=test_is_banned
php artisan test --filter=test_lift_expired_ban
```

Expected: all 6 pass.

**Step 5: Commit**

```bash
git add app/Models/User.php tests/Feature/UserTest.php
git commit -m "feat: User — isBanned() and liftExpiredBan() helpers"
```

---

### Task 3: Middleware — EnsureUserIsNotBanned

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsNotBanned.php`
- Modify: `bootstrap/app.php`

**Step 1: Write failing test**

Add to `tests/Feature/UserTest.php`:

```php
// ── Ban middleware ─────────────────────────────────────────────────────────────

public function test_banned_user_is_kicked_from_authenticated_routes(): void
{
    $user = $this->makeUser();
    $user->update(['banned_at' => now(), 'banned_until' => null, 'ban_reason' => 'Spamming']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertRedirect('/login');
}

public function test_user_with_expired_ban_can_access_authenticated_routes(): void
{
    $user = $this->makeUser();
    $user->update([
        'banned_at'    => now()->subDay(),
        'banned_until' => now()->subHour(),
        'ban_reason'   => 'Old ban',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $user->refresh();
    $this->assertNull($user->banned_at); // ban was auto-lifted
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=test_banned_user_is_kicked
php artisan test --filter=test_user_with_expired_ban
```

Expected: FAIL — banned user can still access dashboard.

**Step 3: Create middleware**

Create `app/Http/Middleware/EnsureUserIsNotBanned.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        // Auto-lift expired timed bans
        $user->liftExpiredBan();

        if ($user->isBanned()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your account has been suspended: ' . $user->ban_reason]);
        }

        return $next($request);
    }
}
```

**Step 4: Register middleware in `bootstrap/app.php`**

Find the `->withMiddleware(function (Middleware $middleware): void {` block and add to the `web` append list:

```php
$middleware->web(append: [
    \App\Http\Middleware\BootstrapSettings::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
    \App\Http\Middleware\TrackLastSeen::class,
    \App\Http\Middleware\EnsureUserIsNotBanned::class,
]);
```

**Step 5: Run tests to verify they pass**

```bash
php artisan test --filter=test_banned_user_is_kicked
php artisan test --filter=test_user_with_expired_ban
```

Expected: both pass.

**Step 6: Commit**

```bash
git add app/Http/Middleware/EnsureUserIsNotBanned.php bootstrap/app.php tests/Feature/UserTest.php
git commit -m "feat: EnsureUserIsNotBanned middleware — kicks banned sessions"
```

---

### Task 4: LoginController — ban gate

**Files:**
- Modify: `app/Http/Controllers/Auth/LoginController.php`

**Step 1: Write failing test**

Add to `tests/Feature/UserTest.php`:

```php
// ── Ban login gate ─────────────────────────────────────────────────────────────

public function test_banned_user_cannot_login(): void
{
    $user = User::factory()->create([
        'password'     => bcrypt('password'),
        'banned_at'    => now(),
        'banned_until' => null,
        'ban_reason'   => 'Abuse',
    ]);
    $user->assignRole('user');

    $response = $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
}

public function test_user_with_expired_ban_can_login(): void
{
    $user = User::factory()->create([
        'password'     => bcrypt('password'),
        'banned_at'    => now()->subDay(),
        'banned_until' => now()->subHour(),
        'ban_reason'   => 'Old ban',
    ]);
    $user->assignRole('user');

    $this->post('/login', [
        'email'    => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $user->refresh();
    $this->assertNull($user->banned_at); // auto-lifted
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test --filter=test_banned_user_cannot_login
php artisan test --filter=test_user_with_expired_ban_can_login
```

Expected: FAIL — banned user logs in successfully.

**Step 3: Update LoginController**

In `app/Http/Controllers/Auth/LoginController.php`, after `Auth::attempt` succeeds, add the ban check. Replace:

```php
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }
```

With:

```php
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();
            $user->liftExpiredBan();

            if ($user->isBanned()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account has been suspended: ' . $user->ban_reason,
                ])->onlyInput('email');
            }

            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }
```

**Step 4: Run tests to verify they pass**

```bash
php artisan test --filter=test_banned_user_cannot_login
php artisan test --filter=test_user_with_expired_ban_can_login
```

Expected: both pass.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Auth/LoginController.php tests/Feature/UserTest.php
git commit -m "feat: LoginController — reject banned users at login gate"
```

---

### Task 5: BanController — ban and unban actions

**Files:**
- Create: `app/Http/Controllers/BanController.php`
- Create: `tests/Feature/BanTest.php`

**Step 1: Write failing tests**

Create `tests/Feature/BanTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markAsInstalled();
        $this->seedRolesAndPermissions();
    }

    private function makeUser(): User
    {
        return User::factory()->create()->assignRole('user');
    }

    private function makeAdmin(): User
    {
        return User::factory()->create()->assignRole('administrator');
    }

    // ── Ban ───────────────────────────────────────────────────────────────────

    public function test_admin_can_ban_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)->post("/users/{$user->id}/ban", [
            'reason'   => 'Spamming',
            'duration' => '24h',
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->banned_at);
        $this->assertEquals('Spamming', $user->ban_reason);
        $this->assertNotNull($user->banned_until);
    }

    public function test_admin_can_permanently_ban_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)->post("/users/{$user->id}/ban", [
            'reason'   => 'Repeated violations',
            'duration' => 'permanent',
        ])->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->banned_at);
        $this->assertNull($user->banned_until);
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin  = $this->makeAdmin();
        $target = $this->makeAdmin();

        $this->actingAs($admin)->post("/users/{$target->id}/ban", [
            'reason'   => 'Test',
            'duration' => '24h',
        ])->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertNull($target->banned_at);
    }

    public function test_admin_cannot_ban_themselves(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post("/users/{$admin->id}/ban", [
            'reason'   => 'Test',
            'duration' => '24h',
        ])->assertRedirect(route('users.index'));

        $admin->refresh();
        $this->assertNull($admin->banned_at);
    }

    public function test_regular_user_cannot_ban(): void
    {
        $user   = $this->makeUser();
        $target = $this->makeUser();

        $this->actingAs($user)->post("/users/{$target->id}/ban", [
            'reason'   => 'Test',
            'duration' => '24h',
        ])->assertRedirect(route('dashboard'));
    }

    // ── Unban ─────────────────────────────────────────────────────────────────

    public function test_admin_can_unban_a_user(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();
        $user->update(['banned_at' => now(), 'banned_until' => null, 'ban_reason' => 'Spam']);

        $this->actingAs($admin)->delete("/users/{$user->id}/ban")->assertRedirect();

        $user->refresh();
        $this->assertNull($user->banned_at);
        $this->assertNull($user->ban_reason);
    }

    public function test_regular_user_cannot_unban(): void
    {
        $user   = $this->makeUser();
        $target = $this->makeUser();
        $target->update(['banned_at' => now(), 'banned_until' => null, 'ban_reason' => 'Spam']);

        $this->actingAs($user)->delete("/users/{$target->id}/ban")->assertRedirect(route('dashboard'));

        $target->refresh();
        $this->assertNotNull($target->banned_at);
    }

    public function test_ban_validates_required_fields(): void
    {
        $admin = $this->makeAdmin();
        $user  = $this->makeUser();

        $this->actingAs($admin)->post("/users/{$user->id}/ban", [])
            ->assertSessionHasErrors(['reason', 'duration']);
    }
}
```

**Step 2: Run tests to verify they fail**

```bash
php artisan test tests/Feature/BanTest.php
```

Expected: FAIL — route not found (404).

**Step 3: Create BanController**

Create `app/Http/Controllers/BanController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class BanController extends Controller
{
    private const DURATIONS = [
        '1h'        => ['addHour',    []],
        '6h'        => ['addHours',   [6]],
        '24h'       => ['addDay',     []],
        '7d'        => ['addWeek',    []],
        '30d'       => ['addMonth',   []],
        'permanent' => null,
    ];

    public function ban(Request $request, User $user): \Illuminate\Http\RedirectResponse
    {
        // Guard: cannot ban yourself or another admin
        if ($user->id === $request->user()->id || $user->hasRole('administrator')) {
            return redirect()->route('users.index')
                ->with('error', 'This user cannot be banned.');
        }

        $validated = $request->validate([
            'reason'   => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'in:' . implode(',', array_keys(self::DURATIONS))],
        ]);

        $bannedUntil = null;
        if ($validated['duration'] !== 'permanent') {
            [$method, $args] = self::DURATIONS[$validated['duration']];
            $bannedUntil = now()->{$method}(...$args);
        }

        $user->update([
            'banned_at'    => now(),
            'banned_until' => $bannedUntil,
            'ban_reason'   => $validated['reason'],
        ]);

        return redirect()->route('users.index')
            ->with('status', "{$user->name} has been banned.");
    }

    public function unban(User $user): \Illuminate\Http\RedirectResponse
    {
        $user->update([
            'banned_at'    => null,
            'banned_until' => null,
            'ban_reason'   => null,
        ]);

        return redirect()->route('users.index')
            ->with('status', "{$user->name} has been unbanned.");
    }
}
```

**Step 4: Run tests to verify they fail (still — no routes yet)**

```bash
php artisan test tests/Feature/BanTest.php
```

Expected: still failing (404). Routes come in Task 6.

**Step 5: Commit**

```bash
git add app/Http/Controllers/BanController.php tests/Feature/BanTest.php
git commit -m "feat: BanController — ban and unban actions + tests"
```

---

### Task 6: Routes

**Files:**
- Modify: `routes/web.php`

**Step 1: Add routes**

In `routes/web.php`, inside the auth+verified+installed group, directly after:
```php
Route::resource('users', UserController::class)->except(['show']);
```

Add:
```php
Route::middleware('role:administrator')->group(function () {
    Route::post('/users/{user}/ban',    [BanController::class, 'ban'])->name('users.ban');
    Route::delete('/users/{user}/ban',  [BanController::class, 'unban'])->name('users.unban');
});
```

Also add the import at the top of the file with other controller imports:
```php
use App\Http\Controllers\BanController;
```

**Step 2: Run ban tests to verify they pass**

```bash
php artisan test tests/Feature/BanTest.php
```

Expected: all 8 tests pass.

**Step 3: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

**Step 4: Commit**

```bash
git add routes/web.php
git commit -m "feat: routes — POST/DELETE /users/{user}/ban for admin"
```

---

### Task 7: UserController — pass ban data to views

**Files:**
- Modify: `app/Http/Controllers/UserController.php`

**Step 1: Update `index()` to include ban fields**

In the `->through()` callback, add:

```php
'is_banned'    => $user->isBanned(),
'ban_reason'   => $user->ban_reason,
'banned_until' => $user->banned_until?->toISOString(),
```

**Step 2: Update `edit()` to pass ban data**

In `edit()`, add to the user array:

```php
'is_banned'    => $user->isBanned(),
'ban_reason'   => $user->ban_reason,
'banned_until' => $user->banned_until?->toISOString(),
```

**Step 3: Run tests**

```bash
php artisan test
```

Expected: all pass.

**Step 4: Commit**

```bash
git add app/Http/Controllers/UserController.php
git commit -m "feat: UserController — expose is_banned, ban_reason, banned_until to views"
```

---

### Task 8: Users/Index — ban badge + ban/unban buttons + modal

**Files:**
- Modify: `resources/js/Pages/Users/Index.vue`

**Step 1: Read the current file and locate the user row template**

The actions column (near end of `<tr>`) currently has Edit + Delete buttons. The user name column shows name + email.

**Step 2: Apply changes**

**A — Add ban badge next to user name** (in the name `<div>` block, after `<p class="font-medium">{{ user.name }}</p>`):

```html
<span
  v-if="user.is_banned"
  class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium bg-destructive/10 text-destructive border border-destructive/20 ml-1"
>
  {{ user.banned_until ? 'Banned · ' + timeLeft(user.banned_until) : 'Banned · Permanent' }}
</span>
```

**B — Add Ban / Unban buttons to the actions column** (before the delete button):

```html
<!-- Unban -->
<button
  v-if="user.is_banned"
  type="button"
  @click="handleUnban(user)"
  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground transition-colors"
  :aria-label="'Unban ' + user.name"
  title="Unban"
>
  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
  </svg>
</button>
<!-- Ban -->
<button
  v-else-if="user.role !== 'administrator' && user.id !== currentUserId"
  type="button"
  @click="openBanModal(user)"
  class="inline-flex items-center justify-center w-8 h-8 rounded-md text-muted-foreground hover:bg-destructive/10 hover:text-destructive transition-colors"
  :aria-label="'Ban ' + user.name"
  title="Ban"
>
  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
  </svg>
</button>
```

**C — Add ban modal** (before the closing `</AppLayout>` tag, after the existing delete-confirm modal):

```html
<!-- Ban modal -->
<Transition
  enter-active-class="transition ease-out duration-150"
  enter-from-class="opacity-0"
  enter-to-class="opacity-100"
  leave-active-class="transition ease-in duration-100"
  leave-from-class="opacity-100"
  leave-to-class="opacity-0"
>
  <div v-if="banModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="banModal.open = false">
    <div class="w-full max-w-sm rounded-lg border bg-card shadow-xl p-6 space-y-4">
      <h3 class="font-semibold">Ban {{ banModal.user?.name }}</h3>

      <div class="space-y-1">
        <label class="text-sm font-medium">Reason <span class="text-destructive">*</span></label>
        <textarea
          v-model="banForm.reason"
          rows="3"
          placeholder="Explain why this user is being banned…"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
          :class="{ 'border-destructive': banForm.errors.reason }"
        />
      </div>

      <div class="space-y-1">
        <label class="text-sm font-medium">Duration <span class="text-destructive">*</span></label>
        <select
          v-model="banForm.duration"
          class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
          :class="{ 'border-destructive': banForm.errors.duration }"
        >
          <option value="">— Select duration —</option>
          <option value="1h">1 hour</option>
          <option value="6h">6 hours</option>
          <option value="24h">24 hours</option>
          <option value="7d">7 days</option>
          <option value="30d">30 days</option>
          <option value="permanent">Permanent</option>
        </select>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors" @click="banModal.open = false">Cancel</button>
        <button type="button" class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-white hover:bg-destructive/90 transition-colors" :disabled="banForm.processing" @click="submitBan">Ban user</button>
      </div>
    </div>
  </div>
</Transition>
```

**D — Add script logic** in `<script setup>`:

```js
import { useForm } from '@inertiajs/vue3'

// Ban modal state
const banModal = ref({ open: false, user: null })
const banForm  = useForm({ reason: '', duration: '' })

function openBanModal(user) {
  banModal.value = { open: true, user }
  banForm.reset()
}

function submitBan() {
  banForm.post(route('users.ban', banModal.value.user.id), {
    onSuccess: () => { banModal.value.open = false },
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

function handleUnban(user) {
  useForm({}).delete(route('users.unban', user.id), {
    onError: () => notify('Failed to unban user.', 'error'),
  })
}

// Time-left helper: returns "3d left", "2h left", "5m left"
function timeLeft(isoString) {
  const until = new Date(isoString)
  const diffMs = until - Date.now()
  if (diffMs <= 0) return 'expired'
  const mins  = Math.floor(diffMs / 60000)
  const hours = Math.floor(mins / 60)
  const days  = Math.floor(hours / 24)
  if (days > 0)  return `${days}d left`
  if (hours > 0) return `${hours}h left`
  return `${mins}m left`
}
```

Also add `useNotifications` import and composable usage if not already present:
```js
import { useNotifications } from '@/composables/useNotifications'
const { notify } = useNotifications()
```

**Step 3: Verify build compiles**

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in Xs`

**Step 4: Commit**

```bash
git add resources/js/Pages/Users/Index.vue
git commit -m "feat: Users/Index — ban badge, ban modal, unban button"
```

---

### Task 9: Users/Form — Account Status card

**Files:**
- Modify: `resources/js/Pages/Users/Form.vue`

**Step 1: Read current file and locate the `defineProps` call**

The `user` prop currently has `{ id, name, email, role }`. We need to also pass `is_banned`, `ban_reason`, `banned_until`.

**Step 2: Update `defineProps`**

Add to the `user` object shape (these will be undefined on create, so handle with `??`):

```js
// user prop already includes: id, name, email, role
// After Task 7, it also includes: is_banned, ban_reason, banned_until
```

No prop definition change needed — Vue passes them through as-is.

**Step 3: Add Account Status card to the template**

Add this card after the existing form card (before the submit button row or as a separate section below), but **only when editing** (`v-if="isEditing"`):

```html
<!-- Account Status (edit only) -->
<div v-if="isEditing" class="rounded-lg border bg-card p-6 space-y-4 mt-4">
  <h3 class="text-sm font-semibold">Account Status</h3>

  <!-- Currently banned -->
  <template v-if="user.is_banned">
    <div class="rounded-md bg-destructive/5 border border-destructive/20 px-4 py-3 space-y-1">
      <p class="text-sm font-medium text-destructive">Banned</p>
      <p class="text-xs text-muted-foreground">Reason: {{ user.ban_reason }}</p>
      <p class="text-xs text-muted-foreground">
        {{ user.banned_until ? 'Expires: ' + new Date(user.banned_until).toLocaleString() : 'Permanent ban' }}
      </p>
    </div>
    <button
      type="button"
      class="rounded-md border px-4 py-2 text-sm hover:bg-accent transition-colors"
      @click="submitUnban"
    >
      Unban user
    </button>
  </template>

  <!-- Not banned — show ban form -->
  <template v-else>
    <div class="space-y-1">
      <label class="text-sm font-medium">Reason</label>
      <textarea
        v-model="banForm.reason"
        rows="2"
        placeholder="Reason for ban…"
        class="w-full rounded-md border bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring resize-none"
      />
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Duration</label>
      <select
        v-model="banForm.duration"
        class="w-full rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
      >
        <option value="">— Select duration —</option>
        <option value="1h">1 hour</option>
        <option value="6h">6 hours</option>
        <option value="24h">24 hours</option>
        <option value="7d">7 days</option>
        <option value="30d">30 days</option>
        <option value="permanent">Permanent</option>
      </select>
    </div>
    <button
      type="button"
      class="rounded-md bg-destructive px-4 py-2 text-sm font-medium text-white hover:bg-destructive/90 transition-colors"
      :disabled="banForm.processing"
      @click="submitBan"
    >
      Ban user
    </button>
  </template>
</div>
```

**Step 4: Add script logic**

In `<script setup>`, add:

```js
import { useNotifications } from '@/composables/useNotifications'

const { notify } = useNotifications()

const banForm = useForm({ reason: '', duration: '' })

function submitBan() {
  banForm.post(route('users.ban', props.user.id), {
    onSuccess: () => banForm.reset(),
    onError: (errors) => notify('Please fix the following:', 'error', { items: Object.values(errors) }),
  })
}

function submitUnban() {
  useForm({}).delete(route('users.unban', props.user.id), {
    onError: () => notify('Failed to unban user.', 'error'),
  })
}
```

**Step 5: Verify build compiles**

```bash
npm run build 2>&1 | tail -5
```

Expected: `✓ built in Xs`

**Step 6: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass.

**Step 7: Commit**

```bash
git add resources/js/Pages/Users/Form.vue
git commit -m "feat: Users/Form — Account Status card with inline ban/unban"
```

---

### Task 10: Final build and full test run

**Step 1: Run full test suite**

```bash
php artisan test
```

Expected: all tests pass (should be 381+ now).

**Step 2: Production build**

```bash
npm run build 2>&1 | tail -8
```

Expected: `✓ built in Xs` with no new errors.

**Step 3: Smoke-check the feature mentally**
- Admin visits Users list → sees Ban button on non-admin rows
- Admin clicks Ban → modal opens → fills reason + duration → submits → user shows "Banned · Xd left" badge
- Banned user visits any page → kicked to login with error message
- Banned user tries to log in → sees error on email field
- Admin visits Users list → sees Unban button on banned row → clicks → user unbanned
- Timed ban expires → user logs in normally, ban columns cleared automatically

**Step 4: Done** ✅
