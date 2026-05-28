# Import & Export

Lambda CMS can export your content as a portable ZIP file and re-import it into any other Lambda CMS instance. This is useful for site migrations, staging-to-production syncs, and backups.

::: info Admin only
Import and Export are restricted to users with the **administrator** role.
:::

## What can be exported

| Entity | What's included |
|---|---|
| **Posts** | Title, slug, body, excerpt, status, metadata, block editor content, category/tag relationships, featured image reference |
| **Categories** | Name, slug, description, color, hue |
| **Tags** | Name, slug |
| **Media** | File metadata (filename, MIME type, dimensions, alt text, description). Actual files are optional — see [Including media files](#including-media-files). |
| **Templates** | All block editor templates including type, loop source, status, and blocks. System templates are exported but re-imported as non-system templates. |

Pages, comments, navigation, and settings are not included in the export.

## Exporting

Go to **Export** in the sidebar under the *Data* section.

1. **Select entities** — check the content types you want to include. Record counts are shown next to each option.
2. **Toggle media files** — optionally bundle the actual uploaded files inside the ZIP (see below).
3. Click **Download ZIP**.

The browser will download a file named `lambda-cms-export-YYYY-MM-DD-HHmmss.zip`.

### Including media files

When disabled (default), the export contains only media metadata — filenames, dimensions, alt text, etc. The actual image and document files are not bundled.

When enabled, the `media/` folder inside the ZIP contains the original uploaded files. This can significantly increase the file size for sites with many uploads.

::: tip Same-server migrations
If you're moving between two environments that share the same storage backend (e.g. the same S3 bucket), you can leave media files disabled. The media records will be re-created and will resolve to the existing files automatically.
:::

## Importing

Go to **Import** in the sidebar under the *Data* section.

### Step 1 — Upload the file

Drag and drop your `.zip` export file onto the upload area, or click to browse. The file is uploaded and inspected — you'll see a summary of what it contains before anything is written to the database.

### Step 2 — Configure and run

**Select entities** — uncheck any entity types you want to skip.

**Choose a conflict strategy** — controls what happens when an imported record matches an existing one:

| Strategy | Behaviour |
|---|---|
| **Skip** | Leave existing records untouched. Only create records that don't exist yet. |
| **Overwrite** | Update existing records with the imported data. |
| **Duplicate** | Always create new records. A unique slug (or title, for templates) is generated automatically. |

Click **Run Import**. When complete, a results table shows how many records were created, updated, skipped, or failed per entity.

## Export file format

The ZIP contains a JSON file per entity and a manifest:

```
lambda-cms-export-2026-05-28-120000.zip
├── manifest.json        ← version, export date, entity list, record counts
├── categories.json
├── tags.json
├── media.json
├── templates.json
├── posts.json
└── media/               ← only present when "Include media files" is on
    ├── abc123.jpg
    └── def456.png
```

### manifest.json

```json
{
  "version": "1.0",
  "app": "lambda-cms",
  "exported_at": "2026-05-28T12:00:00Z",
  "entities": ["posts", "categories", "tags", "media", "templates"],
  "include_media_files": false,
  "counts": {
    "posts": 42,
    "categories": 8,
    "tags": 24,
    "media": 137,
    "templates": 5
  }
}
```

### posts.json

Posts reference categories and tags by **slug** and featured images by **filename**, so relationships survive across different database IDs.

```json
[
  {
    "title": "My First Post",
    "slug": "my-first-post",
    "status": "published",
    "categories": ["news", "tech"],
    "tags": ["laravel", "php"],
    "featured_image": "abc123.jpg",
    "blocks": []
  }
]
```

## Import order

Entities are always imported in dependency order regardless of the order they appear in the ZIP:

1. Categories
2. Tags
3. Media
4. Templates
5. Posts *(depends on categories, tags, and media)*

## Known limitations

- **Block editor media references** — blocks can contain references to media records by their internal database ID. If media IDs change after import (e.g. records are created fresh rather than matched), those references may need to be re-linked manually in the block editor.
- **Large imports** — the import runs synchronously in a single HTTP request. Very large sites (thousands of posts with large block content) may hit PHP's execution time limit. If this occurs, consider splitting the export into smaller batches by entity type.
- **Pages, comments, and navigation** — these are not included in the current export scope.
