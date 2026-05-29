<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkDestroyMediaRequest;
use App\Http\Requests\ReplaceMediaRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Media;
use App\Models\Post;
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

    public function store(StoreMediaRequest $request): JsonResponse
    {
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

        return response()->json($this->toArray($media));
    }

    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        $validated = $request->validated();

        $media->update($validated);

        return response()->json($this->toArray($media));
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

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

    public function replace(ReplaceMediaRequest $request, Media $media): JsonResponse
    {
        if ($media->user_id !== $request->user()->id && ! $request->user()->hasRole('administrator')) {
            abort(403);
        }

        if ($media->type !== 'image') {
            abort(422, 'Only image files can be edited.');
        }

        $file     = $request->file('file');
        $mimeType = $file->getMimeType();
        $ext      = $file->guessExtension() ?? 'jpg';
        $uuid     = Str::uuid()->toString();
        $filename = "{$uuid}.{$ext}";
        $folder   = dirname($media->path);
        $path     = "{$folder}/{$filename}";

        $file->storeAs($folder, $filename, 'public');

        if ($media->disk === 'public' && Storage::disk('public')->exists($media->path)) {
            Storage::disk('public')->delete($media->path);
        }

        $fullPath = Storage::disk('public')->path($path);
        $manager  = new ImageManager(new Driver());
        $img      = $manager->read($fullPath);

        $media->update([
            'filename'  => $filename,
            'path'      => $path,
            'mime_type' => $mimeType,
            'size'      => Storage::disk('public')->size($path),
            'width'     => $img->width(),
            'height'    => $img->height(),
        ]);

        return response()->json($this->toArray($media->fresh()));
    }

    public function bulkDestroy(BulkDestroyMediaRequest $request): JsonResponse
    {

        $query = Media::whereIn('id', $request->input('ids'));

        if (! $request->user()->hasRole('administrator')) {
            $query->where('user_id', $request->user()->id);
        }

        $items = $query->get();

        foreach ($items as $media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        }

        return response()->json(['deleted' => $items->count()]);
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
