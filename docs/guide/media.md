# Media Library

The media library stores and manages all uploaded files at `/media`.

## Uploading

Supported file types are configured in Settings (default: images, documents, video, audio). Maximum file size defaults to 10 MB per file.

Images wider than the configured resize limit (default: 1920 px) are automatically resized on upload. The aspect ratio is preserved.

## File Storage

Files are stored in `storage/app/public/media/{year}/{month}/` with UUID-based filenames. Run `php artisan storage:link` to make them publicly accessible.

## Alt Text & Description

Each file has an `alt` field (used when the image is inserted into a block) and a `description` field for internal notes. Both are editable from the media library.

## Permissions

- **Administrators** see all uploaded files
- **Users** see only their own uploads

## Deleting Files

Select one or more files and click **Delete**. Deleting a file from the library does not automatically remove it from posts or blocks that reference it — those will show a broken image. Clean up references before deleting.

## Image Editor

Images can be cropped, filtered, and adjusted directly in the browser — both during upload and for files already in the library.

- **On upload**: the editor opens for each JPEG, PNG, WebP, or GIF before the file is sent. Click **Skip** to upload the original unchanged.
- **On an existing image**: open its detail panel and click **Edit image** to replace the stored file with an edited version.

See the [Image Editor guide](./image-editor) for a full walkthrough of all tools.

## External Files

Lambda CMS supports external media records (e.g. images hosted on a CDN). Set `disk = external` and store the full URL in `path`. These files are never uploaded to or served from local storage.
