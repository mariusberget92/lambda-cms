<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExportService
{
    public function generate(array $entities, bool $includeMediaFiles): string
    {
        $dir = storage_path('app/private/exports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $tmpPath = $dir . '/' . Str::uuid() . '.zip';

        $zip = new ZipArchive();
        $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $manifest = [
            'version'              => '1.0',
            'app'                  => 'lambda-cms',
            'exported_at'          => now()->toISOString(),
            'entities'             => $entities,
            'include_media_files'  => $includeMediaFiles,
            'counts'               => [],
        ];

        if (in_array('categories', $entities)) {
            $data = $this->exportCategories();
            $manifest['counts']['categories'] = count($data);
            $zip->addFromString('categories.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if (in_array('tags', $entities)) {
            $data = $this->exportTags();
            $manifest['counts']['tags'] = count($data);
            $zip->addFromString('tags.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        if (in_array('media', $entities)) {
            $data = $this->exportMedia();
            $manifest['counts']['media'] = count($data);
            $zip->addFromString('media.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            if ($includeMediaFiles) {
                foreach ($data as $item) {
                    $disk = $item['disk'] === 'public' ? 'public' : 'local';
                    if (Storage::disk($disk)->exists($item['path'])) {
                        $zip->addFromString(
                            'media/' . $item['filename'],
                            Storage::disk($disk)->get($item['path'])
                        );
                    }
                }
            }
        }

        if (in_array('posts', $entities)) {
            $data = $this->exportPosts();
            $manifest['counts']['posts'] = count($data);
            $zip->addFromString('posts.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }

        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();

        return $tmpPath;
    }

    private function exportCategories(): array
    {
        return Category::all()->map(fn ($c) => [
            'name'        => $c->name,
            'slug'        => $c->slug,
            'description' => $c->description,
            'color'       => $c->color,
            'hue'         => $c->hue,
        ])->values()->toArray();
    }

    private function exportTags(): array
    {
        return Tag::all()->map(fn ($t) => [
            'name' => $t->name,
            'slug' => $t->slug,
        ])->values()->toArray();
    }

    private function exportMedia(): array
    {
        return Media::all()->map(fn ($m) => [
            'id'                => $m->id,
            'filename'          => $m->filename,
            'original_filename' => $m->original_filename,
            'disk'              => $m->disk,
            'path'              => $m->path,
            'mime_type'         => $m->mime_type,
            'type'              => $m->type,
            'size'              => $m->size,
            'width'             => $m->width,
            'height'            => $m->height,
            'alt'               => $m->alt,
            'description'       => $m->description,
        ])->values()->toArray();
    }

    private function exportPosts(): array
    {
        return Post::with(['categories', 'tags', 'featuredImage'])->get()->map(fn ($p) => [
            'title'             => $p->title,
            'slug'              => $p->slug,
            'excerpt'           => $p->excerpt,
            'body'              => $p->body,
            'body_format'       => $p->body_format,
            'status'            => $p->status,
            'featured'          => (bool) $p->featured,
            'published_at'      => $p->published_at?->toISOString(),
            'comments_enabled'  => (bool) $p->comments_enabled,
            'use_block_editor'  => (bool) $p->use_block_editor,
            'blocks'            => $p->blocks ?? [],
            'meta_title'        => $p->meta_title,
            'meta_description'  => $p->meta_description,
            'meta_keywords'     => $p->meta_keywords,
            'custom_js'         => $p->custom_js,
            'categories'        => $p->categories->pluck('slug')->values()->toArray(),
            'tags'              => $p->tags->pluck('slug')->values()->toArray(),
            'featured_image'    => $p->featuredImage?->filename,
        ])->values()->toArray();
    }
}
