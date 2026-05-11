# Media

The Media Library stores all uploaded images and files used in posts, pages, and templates.

## Uploading files

Go to **Media → Upload** or click the upload area in the media picker. Supported file types are configured in `config/media.php` and default to common image formats (JPEG, PNG, GIF, WebP, SVG) plus PDFs.

### Upload limits

| Setting | Default | Configurable in |
|---|---|---|
| Max file size | 10 MB | Settings → Media or `MEDIA_MAX_UPLOAD_MB` env var |
| Max image width | 1920 px | Settings → Media |

Images wider than the configured resize width are automatically downscaled on upload using Intervention Image. The original aspect ratio is preserved. SVGs and GIFs are not resized.

## Media details

Each file in the library has:

- **Filename** — the UUID-based storage filename (not the original name)
- **Alt text** — used as the `alt` attribute when the image is rendered in a block
- **Description** — optional note for internal reference
- **MIME type**, **dimensions**, and **file size** — shown in the detail panel
- **Uploaded by** — the user who uploaded the file

## Using media in content

The media picker appears whenever you choose an image in the block editor, the post featured image field, or any image input in settings. From the picker you can:

- Browse and search the full library
- Upload a new file inline
- Enter an **external URL** directly (bypasses storage; the URL is stored as-is)

## Deleting media

Delete files from the media library detail panel. Deleting a file removes it from storage; any content blocks or post featured images that referenced it will show a broken image. There is no undo.

## Storage

Files are stored under `storage/app/media/` by default (the `local` filesystem disk). To use a different disk (e.g. S3), update `FILESYSTEM_DISK` in `.env` and configure the corresponding driver in `config/filesystems.php`.
