# Nord Color Token Cleanup Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace all 29 hardcoded Tailwind color classes across 10 Vue files with semantic Nord CSS token classes so dark mode works correctly everywhere.

**Architecture:** Add 5 new CSS custom property tokens to `app.scss` (`:root`, `.dark`, `@theme inline`), then do a targeted find-and-replace in each affected Vue file following the exact mapping table.

**Tech Stack:** Tailwind CSS 4 (`@theme inline`), Vue 3, `resources/scss/app.scss`

**Worktree:** Create a new worktree from master for this work.

---

### Task 1: Add new tokens to app.scss

**Files:**
- Modify: `resources/scss/app.scss`

**Step 1: Add role + online-dot tokens to `:root` block**

In `resources/scss/app.scss`, find the closing `}` of the `:root` block (after `--sidebar-ring: #81a1c1;` and before the status token comment). Insert these lines immediately before the `/* Status tokens — light mode */` comment:

```scss
  /* Role badge tokens — light mode */
  --color-role-admin-bg: color-mix(in srgb, #5e81ac 15%, transparent);
  --color-role-admin-fg: #3b5e8a;
  --color-role-user-bg: color-mix(in srgb, #4c566a 12%, transparent);
  --color-role-user-fg: #4c566a;

  /* Presence dot token */
  --color-online-dot: #a3be8c;
```

**Step 2: Add matching tokens to `.dark` block**

In the `.dark` block, immediately before the `/* Status tokens — dark mode */` comment, insert:

```scss
  /* Role badge tokens — dark mode */
  --color-role-admin-bg: color-mix(in srgb, #88c0d0 15%, transparent);
  --color-role-admin-fg: #88c0d0;
  --color-role-user-bg: color-mix(in srgb, #4c566a 25%, transparent);
  --color-role-user-fg: #9ca3af;

  /* Presence dot token */
  --color-online-dot: #a3be8c;
```

**Step 3: Register the new tokens in `@theme inline`**

In the `@theme inline` block, after the last `--color-status-*` line (`--color-status-error-border: var(--color-error-border);`), insert:

```scss
  --color-role-admin-bg: var(--color-role-admin-bg);
  --color-role-admin-fg: var(--color-role-admin-fg);
  --color-role-user-bg: var(--color-role-user-bg);
  --color-role-user-fg: var(--color-role-user-fg);
  --color-online-dot: var(--color-online-dot);
```

**Step 4: Build to verify no compilation errors**

```bash
npm run build
```

Expected: `✓ built in X.XXs` with no errors.

**Step 5: Commit**

```bash
git add resources/scss/app.scss
git commit -m "feat: add role badge and online-dot CSS tokens to Nord palette"
```

---

### Task 2: Fix Dashboard/Index.vue

**Files:**
- Modify: `resources/js/Pages/Dashboard/Index.vue`

**Replacements to make:**

1. Line 9 — error flash banner:
   - Old: `class="flex items-center gap-2 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 mb-6"`
   - New: `class="flex items-center gap-2 rounded-md bg-status-error-bg border border-status-error-border px-4 py-3 text-sm text-status-error-fg mb-6"`

2. Line 41 — Published icon bg:
   - Old: `class="w-8 h-8 rounded-md bg-green-100 flex items-center justify-center"`
   - New: `class="w-8 h-8 rounded-md bg-status-success-bg flex items-center justify-center"`

3. Line 42 — Published icon colour:
   - Old: `class="w-4 h-4 text-green-600"`
   - New: `class="w-4 h-4 text-status-success-fg"`

4. Line 53 — Drafts icon bg:
   - Old: `class="w-8 h-8 rounded-md bg-amber-100 flex items-center justify-center"`
   - New: `class="w-8 h-8 rounded-md bg-status-warning-bg flex items-center justify-center"`

5. Line 54 — Drafts icon colour:
   - Old: `class="w-4 h-4 text-amber-600"`
   - New: `class="w-4 h-4 text-status-warning-fg"`

**Step 1: Make all replacements** (edit the file directly).

**Step 2: Build**

```bash
npm run build
```

Expected: clean build.

**Step 3: Commit**

```bash
git add resources/js/Pages/Dashboard/Index.vue
git commit -m "fix: replace hardcoded colors in Dashboard with Nord tokens"
```

---

### Task 3: Fix Posts/Index.vue

**Files:**
- Modify: `resources/js/Pages/Posts/Index.vue`

**Replacements to make:**

1. Line 26 — flash banner (3 classes):
   - Old: `class="mb-4 flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700"`
   - New: `class="mb-4 flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg"`

2. Lines 99–101 — status badge `:class` binding. Replace the entire `:class` expression:
   - Old:
     ```
     :class="post.status === 'published'
       ? 'bg-green-100 text-green-700'
       : 'bg-amber-100 text-amber-700'"
     ```
   - New:
     ```
     :class="post.status === 'published'
       ? 'bg-status-success-bg text-status-success-fg'
       : 'bg-status-warning-bg text-status-warning-fg'"
     ```

3. Line 103 — status dot `:class` binding:
   - Old: `:class="post.status === 'published' ? 'bg-green-500' : 'bg-amber-500'"`
   - New: `:class="post.status === 'published' ? 'bg-status-success-fg' : 'bg-status-warning-fg'"`

**Step 1: Make all replacements.**

**Step 2: Build**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Posts/Index.vue
git commit -m "fix: replace hardcoded colors in Posts index with Nord tokens"
```

---

### Task 4: Fix Users/Index.vue

**Files:**
- Modify: `resources/js/Pages/Users/Index.vue`

**Replacements to make:**

1. Lines 25–31 — success flash banner:
   - Old: `class="flex items-center gap-2 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 mb-4"`
   - New: `class="flex items-center gap-2 rounded-md bg-status-success-bg border border-status-success-border px-4 py-3 text-sm text-status-success-fg mb-4"`

2. Line ~60 — online presence dot:
   - Old: `class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-green-500 ring-2 ring-card"`
   - New: `class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-online-dot ring-2 ring-card"`

3. Lines ~82–83 — email verified checkmark:
   - Old: `class="text-green-600"`
   - New: `class="text-status-success-fg"`

4. Lines 73–75 — role badge `:class` binding:
   - Old:
     ```
     :class="user.role === 'administrator'
       ? 'bg-indigo-100 text-indigo-700'
       : 'bg-slate-100 text-slate-600'"
     ```
   - New:
     ```
     :class="user.role === 'administrator'
       ? 'bg-role-admin-bg text-role-admin-fg'
       : 'bg-role-user-bg text-role-user-fg'"
     ```

**Step 1: Make all replacements.**

**Step 2: Build**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Users/Index.vue
git commit -m "fix: replace hardcoded colors in Users index with Nord tokens"
```

---

### Task 5: Fix Users/Form.vue, Categories/Index.vue, Tags/Index.vue

**Files:**
- Modify: `resources/js/Pages/Users/Form.vue`
- Modify: `resources/js/Pages/Categories/Index.vue`
- Modify: `resources/js/Pages/Tags/Index.vue`

**Users/Form.vue:**
- Find the warning text about the last administrator (contains `text-amber-700`).
- Replace `text-amber-700` → `text-status-warning-fg`

**Categories/Index.vue:**
1. Flash banner: `bg-green-50 border-green-200 text-green-700` → `bg-status-success-bg border-status-success-border text-status-success-fg`
2. Warning text about posts becoming uncategorised: `text-amber-600` → `text-status-warning-fg`

**Tags/Index.vue:**
1. Flash banner: `bg-green-50 border-green-200 text-green-700` → `bg-status-success-bg border-status-success-border text-status-success-fg`

**Step 1: Make all replacements across all three files.**

**Step 2: Build**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Users/Form.vue resources/js/Pages/Categories/Index.vue resources/js/Pages/Tags/Index.vue
git commit -m "fix: replace hardcoded colors in Users form, Categories, Tags with Nord tokens"
```

---

### Task 6: Fix Profile/Index.vue, Media/Index.vue, Settings/Index.vue

**Files:**
- Modify: `resources/js/Pages/Profile/Index.vue`
- Modify: `resources/js/Pages/Media/Index.vue`
- Modify: `resources/js/Pages/Settings/Index.vue`

**Profile/Index.vue:**
- Flash banner: `bg-green-50 border-green-200 text-green-700` → `bg-status-success-bg border-status-success-border text-status-success-fg`

**Media/Index.vue:**
- Find `text-green-500` used on the file-saved checkmark icon.
- Replace `text-green-500` → `text-status-success-fg`

**Settings/Index.vue:**
- Find all flash banner instances:
  - Success flash (`flash.status`, `flash.mail_status`): `bg-green-50 border-green-200 text-green-700` → `bg-status-success-bg border-status-success-border text-status-success-fg`
  - Error flash (`flash.mail_error`): `bg-red-50 border-red-200 text-red-700` → `bg-status-error-bg border-status-error-border text-status-error-fg`

**Step 1: Make all replacements across all three files.**

**Step 2: Build**

```bash
npm run build
```

**Step 3: Commit**

```bash
git add resources/js/Pages/Profile/Index.vue resources/js/Pages/Media/Index.vue resources/js/Pages/Settings/Index.vue
git commit -m "fix: replace hardcoded colors in Profile, Media, Settings with Nord tokens"
```

---

### Task 7: Final verification and PR

**Step 1: Run full build one more time**

```bash
npm run build
```

Expected: clean build, no errors.

**Step 2: Run tests**

```bash
php artisan test
```

Expected: all tests pass (no backend changes were made, so count should be identical to pre-task).

**Step 3: Invoke finishing-a-development-branch skill**

Use `superpowers:finishing-a-development-branch` to push and create a PR targeting `master`.
