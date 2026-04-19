# Settings Page — Design Document

**Date:** 2026-03-04
**Status:** Approved

---

## Goal

Add an admin-only Settings page to the Lambda CMS dashboard where administrators can manage site identity, locale, media upload limits, and mail configuration — all persisted in a database-backed key-value store with automatic cache invalidation.

---

## Decisions Made

| Question | Decision |
|---|---|
| Who can access settings? | Administrators only (`role:administrator` middleware) |
| What setting groups? | Site, Locale, Media, Mail |
| Storage approach | Key-value table (`settings` with `key`, `value`, `type`, `group`) |
| Effect timing | Immediate — DB-cached with 1-hour TTL, busted on every save |
| Mail test button | Yes — sends a live test email to the authenticated admin, shows inline flash |

---

## Architecture

### Database

A `settings` table with:

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `group` | varchar(50) | e.g. `site`, `locale`, `media`, `mail` |
| `key` | varchar(100) | unique, dot-notated e.g. `mail.host` |
| `value` | text, nullable | always stored as string |
| `type` | enum(`string`, `integer`, `boolean`) | used for casting on read |
| `timestamps` | | |

Seeded with all defaults on migration (so the app always has valid values from first boot).

### Backend Classes

**`app/Models/Setting.php`**
- Eloquent model on `settings` table
- Static `get(string $key, mixed $fallback = null): mixed` — reads from cache, casts by `type`
- Static `set(string $key, mixed $value): void` — persists, busts `settings.all` cache key

**`app/Services/SettingService.php`**
- `all(): Collection` — `Cache::remember('settings.all', 3600, fn() => Setting::all()->keyBy('key'))`
- `bust(): void` — `Cache::forget('settings.all')`

**`app/Http/Controllers/SettingsController.php`**
- `index()` — loads all settings grouped by `group`, passes to Inertia as `$groups`
- `update(string $group, Request $request)` — validates per-group, saves changed keys, busts cache, redirects back with flash
- `testEmail(Request $request)` — builds a `TestMailMailable`, temporarily overrides mailer config via `Config::set()` using current mail settings, attempts send, returns flash success or error message

**`app/Http/Middleware/BootstrapSettings.php`**
- Runs on every web request (added to `web` middleware group)
- Wraps in `try/catch` — silently skips if `settings` table doesn't exist yet
- Calls `date_default_timezone_set(Setting::get('locale.timezone', 'UTC'))`
- Calls `Config::set('app.timezone', Setting::get('locale.timezone', 'UTC'))`

**`app/Mail/TestMail.php`**
- Simple Mailable sent to the authenticated admin's email
- Subject: "Lambda CMS — Test Email"
- Plain text body confirming mail settings are working

### Routes

```php
// routes/web.php — inside auth + role:administrator middleware group
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings/{group}', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
```

### Frontend

**`resources/js/Pages/Settings/Index.vue`**

Single page, four independent panels following the exact `Profile/Index.vue` pattern:
- Each panel has its own `useForm(...)` instance and `@submit.prevent` handler
- Errors displayed inline below each field (`v-if="form.errors.field"`)
- Success flash rendered via `$page.props.flash?.status` with fade transition
- Mail panel has an additional "Send test email" button that posts to `settings.test-email` and shows its own inline flash (separate from the save flash)

**`resources/js/Layouts/AppLayout.vue`**

Add a "Settings" `SidebarLink` under the "Account" section, visible only when `user.role === 'administrator'`:
```html
<SidebarLink
  v-if="user.role === 'administrator'"
  :href="route('settings.index')"
  :active="currentRoute?.startsWith('settings.')"
>
  <!-- gear icon -->
  Settings
</SidebarLink>
```

**`config/media.php`** — updated to read from `Setting::get()` instead of `env()`.

---

## Settings Reference

### Site (`group = 'site'`)

| Key | Type | Default | Validation |
|---|---|---|---|
| `site.name` | string | *(from install)* | required, max:100 |
| `site.url` | string | *(from install)* | required, url, max:255 |

### Locale (`group = 'locale'`)

| Key | Type | Default | Validation |
|---|---|---|---|
| `locale.timezone` | string | `UTC` | required, timezone (PHP list) |
| `locale.date_format` | string | `Y-m-d` | required, max:20 |

### Media (`group = 'media'`)

| Key | Type | Default | Validation |
|---|---|---|---|
| `media.max_upload_mb` | integer | `10` | required, integer, min:1, max:100 |
| `media.resize_max_width` | integer | `1920` | required, integer, min:320, max:8000 |

### Mail (`group = 'mail'`)

| Key | Type | Default | Validation |
|---|---|---|---|
| `mail.driver` | string | `smtp` | required, in:smtp,log,mailgun |
| `mail.host` | string | — | required_if:mail.driver,smtp, max:255 |
| `mail.port` | integer | `587` | required_if:mail.driver,smtp, integer |
| `mail.username` | string | — | nullable, max:255 |
| `mail.password` | string | — | nullable, max:255 |
| `mail.from_address` | string | — | required, email |
| `mail.from_name` | string | — | required, max:100 |
| `mail.encryption` | string | `tls` | in:tls,ssl,null |

---

## Error Handling

- Validation errors returned via Inertia's `$errors` bag, displayed inline per field
- Mail test catches all transport exceptions; error message surfaced as inline flash
- `BootstrapSettings` middleware silently skips if `settings` table doesn't exist (fresh install safety)

---

## Testing

**Unit — `SettingServiceTest`**
- `get()` returns correct cast types (int, bool, string)
- `set()` persists value and busts cache
- Falls back to default when key missing

**Feature — `SettingsControllerTest`**
- Non-administrator gets 403 on all settings routes
- Valid save redirects back with success flash
- Invalid data returns validation errors
- Mail test endpoint returns success flash on fake mailer
- Mail test endpoint returns error flash when transport fails

---

## Files Changed / Created

| File | Action |
|---|---|
| `database/migrations/XXXX_create_settings_table.php` | Create |
| `database/seeders/SettingsSeeder.php` | Create |
| `app/Models/Setting.php` | Create |
| `app/Services/SettingService.php` | Create |
| `app/Http/Controllers/SettingsController.php` | Create |
| `app/Http/Middleware/BootstrapSettings.php` | Create |
| `app/Mail/TestMail.php` | Create |
| `bootstrap/app.php` | Modify — register middleware |
| `routes/web.php` | Modify — add settings routes |
| `config/media.php` | Modify — read from `Setting::get()` |
| `resources/js/Pages/Settings/Index.vue` | Create |
| `resources/js/Layouts/AppLayout.vue` | Modify — add Settings sidebar link |
| `tests/Unit/SettingServiceTest.php` | Create |
| `tests/Feature/SettingsControllerTest.php` | Create |
