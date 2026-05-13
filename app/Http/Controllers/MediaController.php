<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Post;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MediaController extends Controller
{
    public function index(Request $request): Response|JsonResponse
    {
        $query = Media::with('uploader:id,name')
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('search'), fn ($q, $search) => $q->where('original_filename', 'like', "%{$search}%"))
            ->latest();

        if (! $request->user()->hasRole('administrator')) {
            $query->where('user_id', $request->user()->id);
        }

        $media = $query->paginate(40)->withQueryString()->through(fn (Media $m) => $this->toArray($m));

        if ($request->wantsJson() && ! $request->header('X-Inertia')) {
            return response()->json($media);
        }

        $mimeToExt = [
            'image/jpeg'       => 'jpg',
            'image/png'        => 'png',
            'image/gif'        => 'gif',
            'image/webp'       => 'webp',
            'image/svg+xml'    => 'svg',
            'application/pdf'  => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'video/mp4'        => 'mp4',
            'video/webm'       => 'webm',
            'audio/mpeg'       => 'mp3',
            'audio/wav'        => 'wav',
        ];
        $allowedMimes      = config('media.allowed_mimes', []);
        $allowedExtensions = implode(', ', array_unique(array_filter(array_map(fn ($m) => $mimeToExt[$m] ?? null, $allowedMimes))));

        return Inertia::render('Media/Index', [
            'media'             => $media,
            'filters'           => $request->only('type', 'search'),
            'maxUploadMb'       => (int) config('media.max_upload_mb', 10),
            'allowedExtensions' => $allowedExtensions,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // Force JSON response for validation errors (upload endpoint is always JSON)
        $request->headers->set('Accept', 'application/json');

        $maxKb = (int) (config('media.max_upload_mb', 10) * 1024);

        $allMimes = collect(config('media.allowed_mimes', []))->flatten()->implode(',');

        $request->validate([
            'file' => [
                'required',
                'file',
                "max:{$maxKb}",
                "mimetypes:{$allMimes}",
            ],
        ]);

        $file     = $request->file('file');
        $mimeType = $file->getMimeType();
        $type     = Media::typeFromMime($mimeType);
        $ext      = $file->guessExtension() ?? 'bin';
        $uuid     = Str::uuid()->toString();
        $filename = "{$uuid}.{$ext}";
        $folder   = 'media/' . now()->format('Y/m');
        $path     = "{$folder}/{$filename}";

        $file->storeAs($folder, $filename, 'public');

        $width  = null;
        $height = null;

        // Resize images (skip SVG)
        if ($type === 'image' && $mimeType !== 'image/svg+xml') {
            $fullPath = Storage::disk('public')->path($path);
            $maxWidth = config('media.resize_max_width', 1920);

            try {
                $manager = new ImageManager(new Driver());
                $img     = $manager->read($fullPath);

                if ($img->width() > $maxWidth) {
                    $img->scaleDown(width: $maxWidth);
                    $img->save($fullPath);
                }

                $width  = $img->width();
                $height = $img->height();
            } catch (\Throwable $e) {
                // If image reading fails (e.g. fake file in tests), skip dimension detection
                // The file is already stored; just leave width/height as null
            }
        }

        $media = Media::create([
            'user_id'           => $request->user()->id,
            'filename'          => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'disk'              => 'public',
            'path'              => $path,
            'mime_type'         => $mimeType,
            'type'              => $type,
            'size'              => Storage::disk('public')->size($path),
            'width'             => $width,
            'height'            => $height,
            'alt'               => null,
            'description'       => null,
        ]);

        ActivityLogger::log('created', "Uploaded media '{$media->original_filename}'", 'Media', $media->id);

        return response()->json($this->toArray($media));
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validate([
            'alt'         => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $media->update($validated);

        return response()->json($this->toArray($media));
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        ActivityLogger::log('deleted', "Deleted media '{$media->original_filename}'", 'Media', $media->id);

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return response()->json(['deleted' => true]);
    }

    public function usage(Request $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $posts = Post::where('featured_image_id', $media->id)
            ->select('id', 'title', 'slug')
            ->get();

        return response()->json(['posts' => $posts]);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $query = Media::whereIn('id', $request->input('ids'));

        if (! $request->user()->hasRole('administrator')) {
            $query->where('user_id', $request->user()->id);
        }

        $items = $query->get();

        foreach ($items as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        $deletedCount = $items->count();

        ActivityLogger::log('deleted', "Bulk deleted {$deletedCount} media file" . ($deletedCount === 1 ? '' : 's'), 'Media');

        return response()->json(['deleted' => $deletedCount]);
    }

    private function toArray(Media $media): array
    {
        return [
            'id'                => $media->id,
            'url'               => $media->url,
            'filename'          => $media->filename,
            'original_filename' => $media->original_filename,
            'mime_type'         => $media->mime_type,
            'type'              => $media->type,
            'size'              => $media->size,
            'formatted_size'    => $media->formatted_size,
            'width'             => $media->width,
            'height'            => $media->height,
            'alt'               => $media->alt,
            'description'       => $media->description,
            'created_at'        => $media->created_at->toDateTimeString(),
            'uploader'          => $media->uploader ? $media->uploader->name : null,
        ];
    }
}
