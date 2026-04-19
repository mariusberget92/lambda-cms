# Features Design — FIX.txt Items 6, 7, 8, 9, 11, 12

Date: 2026-04-02
Status: Approved

---

## Feature 6 — Navigation Block (Block Editor)

### Decision
Inline links defined within the block itself (independent of the `/navigation` admin). Could eventually replace the nav admin entirely.

### Design
- New block type: `navigation` (palette group: Layout)
- **Settings** (`NavigationSettings.vue`):
  - Link manager: list of rows — Label, URL (`DynamicField` for loop binding), open-in-new-tab toggle
  - Add / remove / reorder via VueDraggable
  - Style: Horizontal / Vertical / Pills / Minimal
  - Alignment: left / center / right
- **Renderer** (`resources/js/components/Blocks/NavigationBlock.vue`):
  - Renders `<nav>` with `<a>` tags styled per variant + alignment
- **Editor canvas** (`EditorNavigationBlock.vue`):
  - Read-only link list preview + "Edit links" affordance
- **Registration**: `BlockTypePanel`, `BlockCanvas`, `BlockLayers`, `EditorLoopBlock`, `BlockRenderer` — same pattern as existing blocks

### Files
- `resources/js/components/Blocks/NavigationBlock.vue` (new)
- `resources/js/Components/BlockEditor/blocks/NavigationSettings.vue` (new)
- `resources/js/Components/BlockEditor/EditorNavigationBlock.vue` (new)
- Update: `BlockTypePanel.vue`, `BlockCanvas.vue`, `BlockLayers.vue`, `EditorLoopBlock.vue`, `BlockRenderer.vue`

---

## Feature 7 — Front Page Redesign

### Decision
Clean editorial style — richer visual treatment without structural changes.

### Design
**`BlogLayout.vue`**:
- Hero strip: add a 3px left-border accent bar in `--primary`, style the description with `text-muted-foreground`
- Header: nav link hover gets a `border-b-2 border-primary` underline on hover

**`PostCard.vue`**:
- Featured image: wrap in `overflow-hidden group`, add `group-hover:scale-105 transition-transform duration-300` on `<img>`
- Category badges: use category color system (see Feature 8) — `style="background-color: {color}20; color: {color}"` when set, fallback to `bg-primary/10 text-primary`
- Reading time: compute `Math.ceil(wordCount / 200)` from `post.excerpt` or `post.body`, show as `"N min read"` badge next to date
- Card: add `hover:border-primary/40 hover:shadow-md` transition

**`BlogSidebar.vue`**:
- Section headers ("Categories", "Recent Posts", "Tags"): add a small `w-1 h-4 bg-primary rounded-full` left bar accent inline with the heading

### Files
- `resources/js/Layouts/BlogLayout.vue` (update)
- `resources/js/Components/PostCard.vue` (update)
- `resources/js/Components/BlogSidebar.vue` (update)

---

## Feature 8 — Category Colorization

### Decision
`color` column on categories, Nord palette swatches + custom color via dropdown popover.

### Design
**Migration**: add `color VARCHAR(7) NULL` to `categories` table.

**`ColorPickerPopover.vue`** (new shared component, `resources/js/Components/`):
- Trigger: a small colored square (or grey ring if unset)
- Opens a floating popover panel containing:
  - 2-row grid of 10 Nord swatches (Nord polar night + frost + aurora subset)
  - A "Custom" swatch that reveals `<input type="color">`
  - Selected hex displayed below; "Clear" link to unset
- Emits `update:modelValue` with hex string or null
- Self-contained, no external dependencies beyond Tailwind

**Nord swatches to include**:
`#5e81ac`, `#88c0d0`, `#8fbcbb`, `#81a1c1`, `#a3be8c`, `#ebcb8b`, `#d08770`, `#bf616a`, `#b48ead`, `#4c566a`

**Backend**:
- `Category` model: add `color` to `$fillable`
- `CategoryController`: include `color` in store/update validation (`nullable|string|max:7|regex:/^#[0-9a-fA-F]{6}$/`)
- Category resource/API responses: include `color`

**Frontend usage**:
- `Categories/Create.vue` + `Categories/Edit.vue`: add `<ColorPickerPopover v-model="form.color" />`
- `PostCard.vue`, `Blog/Show.vue`, `Blog/Archive.vue`, admin category pills: apply color as inline style when present

### Files
- `database/migrations/TIMESTAMP_add_color_to_categories_table.php` (new)
- `resources/js/Components/ColorPickerPopover.vue` (new)
- `app/Http/Controllers/CategoryController.php` (update)
- `app/Models/Category.php` (update)
- `resources/js/Pages/Categories/Create.vue` (update)
- `resources/js/Pages/Categories/Edit.vue` (update)
- `resources/js/Components/PostCard.vue` (update — color badges)
- `resources/js/Pages/Blog/Show.vue` (update — color badges)
- `resources/js/Pages/Blog/Archive.vue` (update — color badges)

---

## Feature 9 — Parallax / Background Settings on Section & Container Blocks

### Decision
New "Background" tab in Section and Container block settings panels. CSS `background-attachment: fixed` for parallax.

### Design
**New "Background" tab** added to `SectionSettings.vue` and `ContainerSettings.vue`:
- Background color: `<input type="color">` + "Clear" button (stores as hex or null)
- Background image URL: text input + "Pick from media library" button (opens existing media picker)
- Background size: SelectBox — Cover / Contain / Auto
- Background position: SelectBox — Center / Top / Bottom / Left / Right
- Parallax: checkbox toggle — when enabled, adds `background-attachment: fixed`

**Data shape** (stored in `block.data.bg`):
```json
{
  "bg": {
    "color": "#2e3440",
    "image": "https://...",
    "size": "cover",
    "position": "center",
    "parallax": true
  }
}
```

**Renderers** (`SectionBlock.vue`, `ContainerBlock.vue`):
- Compute `bgStyle` from `block.data.bg` and apply as `:style` on the root element
- Example: `{ backgroundColor, backgroundImage: 'url(...)', backgroundSize, backgroundPosition, backgroundAttachment }`

**Editor canvas**: shows faint background color/image preview on the block's wrapper div.

### Files
- `resources/js/Components/BlockEditor/blocks/SectionSettings.vue` (update — add Background tab)
- `resources/js/Components/BlockEditor/blocks/ContainerSettings.vue` (update — add Background tab)
- `resources/js/components/Blocks/SectionBlock.vue` (update — apply bg styles)
- `resources/js/components/Blocks/ContainerBlock.vue` (update — apply bg styles)

---

## Feature 11 — Media Allowed Types (Settings)

### Decision
Category toggles (Images / Documents / Video / Audio) + custom MIME tag-input. Stored in settings table.

### Design
**New settings keys**:
- `media.allowed_categories`: JSON array of enabled group keys, e.g. `["image","document"]`. Default: all four.
- `media.custom_mimes`: JSON array of custom MIME type strings. Default: `[]`.

**`config/media.php`** updated:
- Reads `media.allowed_categories` to filter the existing MIME groups
- Appends `media.custom_mimes` to the combined list
- `allowed_mimes` is now a flat array of all enabled MIME type strings

**Settings > Media tab** (new UI section):
- Heading: "Allowed file types"
- 4 checkboxes: Images, Documents, Video, Audio (each maps to a group key)
- Tag-input field (same component pattern as post tags) for custom MIME types
- Saved via existing `submitMedia()` form handler with the new fields added

**`MediaController@index`**: passes `allowedExtensions` prop — a human-readable list of extensions derived from the enabled MIME types (e.g. `jpg, png, gif, webp, svg, pdf`).

**`Media/Index.vue`**: replaces the hardcoded extensions hint with the `allowedExtensions` prop.

### MIME → extension mapping (for display)
```
image/jpeg → jpg, image/png → png, image/gif → gif, image/webp → webp,
image/svg+xml → svg, application/pdf → pdf, application/msword → doc,
application/vnd.openxmlformats-... → docx, video/mp4 → mp4,
video/webm → webm, audio/mpeg → mp3, audio/wav → wav
```

### Files
- `resources/js/Pages/Settings/Index.vue` (update — media tab)
- `config/media.php` (update — dynamic allowed_mimes)
- `app/Http/Controllers/SettingsController.php` (update — save new keys)
- `app/Http/Controllers/MediaController.php` (update — pass allowedExtensions prop)
- `resources/js/Pages/Media/Index.vue` (update — use allowedExtensions prop)

---

## Feature 12 — Accent Color Theming

### Decision
DB-backed setting `site.accent_color`, Nord aurora swatches, injected as CSS custom property override in blade template.

### Design
**Swatches** (6 options):
| Name | Hex | Dark variant (hover) |
|------|-----|----------------------|
| Frost Blue (default) | `#5e81ac` | `#4a6d92` |
| Nord Green | `#a3be8c` | `#8aaa70` |
| Nord Yellow | `#ebcb8b` | `#d4b06a` |
| Nord Orange | `#d08770` | `#bb6f58` |
| Nord Red | `#bf616a` | `#a84d56` |
| Nord Purple | `#b48ead` | `#9d7596` |

**Storage**: `site.accent_color` in `settings` table (stores hex string, e.g. `#a3be8c`). `null` / missing = default frost blue.

**Serving**:
- `HandleInertiaRequests::share()` adds `accentColor` from `Setting::get('site.accent_color')`
- `app/Http/Middleware/HandleInertiaRequests.php` updated

**Injection**:
- `resources/views/app.blade.php`: adds a `<style>` block reading from a blade variable: `--primary` and `--primary-hover` overridden when set
- The blade variable is set from the Setting in `HandleInertiaRequests` or a view composer

**Settings UI**:
- New "Appearance" tab in `Settings/Index.vue`
- Swatch picker: 6 colored circles, selected one gets a ring + checkmark
- Saved via new `submitAppearance()` form handler (same pattern as other settings tabs)

### Files
- `resources/views/app.blade.php` (update — inject accent CSS variable)
- `app/Http/Middleware/HandleInertiaRequests.php` (update — share accentColor)
- `resources/js/Pages/Settings/Index.vue` (update — Appearance tab + swatch picker)
- `app/Http/Controllers/SettingsController.php` (update — save appearance settings)
