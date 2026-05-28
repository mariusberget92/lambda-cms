# Image Editor

Lambda CMS includes a browser-based image editor that opens automatically when you upload an image and is also available for images already in the media library.

## Opening the Editor

**On upload** — drag files into the media library or click **Upload**. For every JPEG, PNG, WebP, or GIF file you select, the editor opens before the file is sent to the server. You can edit the image or click **Skip** to upload the original without changes.

**On an existing image** — click any image in the media library to open its detail panel, then click **Edit image**. SVG files cannot be edited in the browser and will not show the button.

## Tools

### Crop

The **Crop** tab is active by default. A selection box appears over the image; drag its handles to define the crop area.

**Aspect ratio** — choose a preset to lock the crop proportions:

| Preset | Use case |
|---|---|
| Free | Unconstrained — drag to any shape |
| 1 : 1 | Square thumbnails, avatars |
| 4 : 3 | Standard photos, presentations |
| 3 : 2 | Classic 35 mm photography |
| 16 : 9 | Widescreen, hero images |
| 21 : 9 | Cinematic / ultra-wide banners |
| 9 : 16 | Portrait / mobile stories |
| 3 : 4 / 2 : 3 | Portrait formats |

**Transform** — rotate 90° clockwise or counter-clockwise; flip horizontally or vertically.

**Output size** — the **W** and **H** fields show the crop dimensions in pixels at full image resolution. You can type custom values to resize the output. Toggle **Aspect locked** / **Free size** to control whether width and height resize together.

### Filter

The **Filter** tab shows eight one-click presets. A thumbnail previews each filter before you apply it.

| Preset | Effect |
|---|---|
| Normal | No filter |
| Vivid | Boosted colour and contrast |
| Muted | Desaturated, slightly brighter |
| B&W | Full greyscale |
| Warm | Slight sepia tint with warm tones |
| Cool | Slight blue-green shift |
| Fade | Soft, low-contrast look |
| Drama | High contrast, rich colour |

Selecting a preset replaces any previously applied preset. Adjustments (see below) stack on top.

### Adjust

The **Adjust** tab gives you individual sliders:

| Control | Range | Default |
|---|---|---|
| Brightness | −100 → +100 | 0 |
| Contrast | −100 → +100 | 0 |
| Saturation | −100 → +100 | 0 |
| Blur | 0 → 20 px | 0 |

Click **Reset adjustments** to return all sliders to their defaults without clearing the active filter preset.

## Applying and Saving

Click **Apply** to process the image and upload it.

- **Upload flow**: the edited file is uploaded to the media library. The original file is discarded.
- **Edit existing image**: the stored file is replaced with the edited version. The media record's dimensions and file size are updated automatically. Alt text and description are preserved.

The output format matches the original (JPEG → JPEG, PNG → PNG, WebP → WebP). JPEG and WebP are saved at 92 % quality.

## Skipping the Editor

During upload you can click **Skip** to send the original file unchanged and open the editor for the next file in the queue. Non-image files (PDFs, videos, audio) always bypass the editor.
