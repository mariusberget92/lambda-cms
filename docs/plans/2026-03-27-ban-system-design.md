# Ban System — Design

**Date:** 2026-03-27
**Stack:** Laravel 12 + Inertia 2 + Vue 3

---

## Overview

Admins can ban regular users (not other admins) with a required reason and an optional expiry. Banned users are immediately kicked from active sessions and see a clear error on login. Timed bans auto-lift on expiry via middleware — no cron required.

---

## Data & Model

### Migration — add to `users` table

| Column | Type | Nullable | Meaning |
|--------|------|----------|---------|
| `banned_at` | datetime | yes | Set when banned; null = not banned |
| `banned_until` | datetime | yes | Expiry; null = permanent ban |
| `ban_reason` | string(255) | yes | Required text supplied by admin |

### User model helpers

```php
// True if banned and ban has not expired
public function isBanned(): bool
{
    return $this->banned_at !== null
        && ($this->banned_until === null || $this->banned_until->isFuture());
}

// Clears ban columns if timed ban has expired; returns true if lifted
public function liftExpiredBan(): bool
{
    if ($this->banned_at !== null && $this->banned_until !== null && $this->banned_until->isPast()) {
        $this->update(['banned_at' => null, 'banned_until' => null, 'ban_reason' => null]);
        return true;
    }
    return false;
}
```

---

## Middleware & Login

### `EnsureUserIsNotBanned` middleware

Registered on every `auth`-protected route.

1. Call `$user->liftExpiredBan()` — auto-clears expired bans
2. If `$user->isBanned()` → `Auth::logout()`, invalidate session, redirect to `/login` with error:
   `"Your account has been suspended: {reason}"`

### LoginController — after `Auth::attempt()` succeeds

1. Call `$user->liftExpiredBan()`
2. If still `isBanned()` → `Auth::logout()` + return validation error on `email` field:
   `"Your account has been suspended: {reason}"`

---

## Routes & Controller

```
POST   /users/{user}/ban    → BanController@ban     (admin only)
DELETE /users/{user}/ban    → BanController@unban   (admin only)
```

### BanController@ban

**Validation:**
- `reason` — required, string, max 255
- `duration` — required, one of: `1h`, `6h`, `24h`, `7d`, `30d`, `permanent`

**Guards:**
- Cannot ban yourself
- Cannot ban an administrator
- Cannot ban an already-banned user

**Duration → `banned_until` mapping:**

| Value | `banned_until` |
|-------|----------------|
| `1h` | `now()->addHour()` |
| `6h` | `now()->addHours(6)` |
| `24h` | `now()->addDay()` |
| `7d` | `now()->addWeek()` |
| `30d` | `now()->addMonth()` |
| `permanent` | `null` |

Sets `banned_at = now()`, `banned_until`, `ban_reason`, redirects back with flash status.

### BanController@unban

Clears `banned_at`, `banned_until`, `ban_reason` to null. Redirects back with flash status.

---

## UI

### Users/Index table

- Ban status badge next to name for banned users:
  - Timed: `Banned · 3d left`
  - Permanent: `Banned · Permanent`
  - Styled in destructive red (same token as existing error states)
- **Not banned row**: "Ban" button (ghost/destructive) → opens modal
- **Banned row**: "Unban" button (ghost) → immediate unban, no modal

### Ban modal (from Users/Index)

- Heading: `Ban [user name]`
- `Reason` — textarea, required
- `Duration` — select: 1 hour / 6 hours / 24 hours / 7 days / 30 days / Permanent
- Submit: "Ban user" (destructive)
- Cancel: closes modal

### Users/Form (edit page) — new "Account Status" card

- **If not banned**: inline ban form with same reason + duration fields
- **If banned**: shows current ban info (reason, expiry or "Permanent") + "Unban" button

### Login page

No UI changes. The existing `form.errors.email` display already handles the error message surfaced by the middleware/LoginController.

---

## Guards Summary

| Action | Blocked when |
|--------|-------------|
| Ban | Target is admin |
| Ban | Target is yourself |
| Ban | Target is already banned |
| Unban | — (always allowed for admins) |
| Middleware kick | Ban expired (auto-lifted first) |
