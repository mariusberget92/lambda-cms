<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ImportService
{
    public function preview(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open ZIP file.');
        }

        $manifestJson = $zip->getFromName('manifest.json');
        if (!$manifestJson) {
            $zip->close();
            throw new \RuntimeException('Invalid export file: missing manifest.json');
        }

        $manifest = json_decode($manifestJson, true);

        if (($manifest['app'] ?? '') !== 'lambda-cms') {
            $zip->close();
            throw new \RuntimeException('This file was not exported from Lambda CMS.');
        }

        $available = [];
        foreach (['categories', 'tags', 'media', 'posts'] as $entity) {
            if ($zip->locateName("{$entity}.json") !== false) {
                $data = json_decode($zip->getFromName("{$entity}.json"), true);
                $available[$entity] = count($data ?? []);
            }
        }

        $zip->close();

        return [
            'exported_at'         => $manifest['exported_at'] ?? null,
            'version'             => $manifest['version'] ?? null,
            'entities'            => $available,
            'include_media_files' => $manifest['include_media_files'] ?? false,
        ];
    }

    public function import(string $zipPath, array $entities, string $conflictStrategy, int $userId): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Could not open ZIP file.');
        }

        $results = [];

        if (in_array('categories', $entities) && $zip->locateName('categories.json') !== false) {
            $data = json_decode($zip->getFromName('categories.json'), true) ?? [];
            $results['categories'] = $this->importCategories($data, $conflictStrategy);
        }

        if (in_array('tags', $entities) && $zip->locateName('tags.json') !== false) {
            $data = json_decode($zip->getFromName('tags.json'), true) ?? [];
            $results['tags'] = $this->importTags($data, $conflictStrategy);
        }

        if (in_array('media', $entities) && $zip->locateName('media.json') !== false) {
            $data = json_decode($zip->getFromName('media.json'), true) ?? [];
            $results['media'] = $this->importMedia($data, $zip, $conflictStrategy, $userId);
        }

        if (in_array('posts', $entities) && $zip->locateName('posts.json') !== false) {
            $data = json_decode($zip->getFromName('posts.json'), true) ?? [];
            $results['posts'] = $this->importPosts($data, $conflictStrategy, $userId);
        }

        $zip->close();

        return $results;
    }

    private function importCategories(array $data, string $strategy): array
    {
        $created = $updated = $skipped = $failed = 0;

        foreach ($data as $item) {
            try {
                $existing = Category::where('slug', $item['slug'])->first();

                if ($existing) {
                    if ($strategy === 'skip') {
                        $skipped++;
                    } elseif ($strategy === 'overwrite') {
                        $existing->update([
                            'name'        => $item['name'],
                            'description' => $item['description'] ?? null,
                            'color'       => $item['color'] ?? null,
                            'hue'         => $item['hue'] ?? null,
                        ]);
                        $updated++;
                    } elseif ($strategy === 'duplicate') {
                        Category::create([
                            'name'        => $item['name'],
                            'slug'        => Category::generateSlug($item['name']),
                            'description' => $item['description'] ?? null,
                            'color'       => $item['color'] ?? null,
                            'hue'         => $item['hue'] ?? null,
                        ]);
                        $created++;
                    }
                } else {
                    Category::create([
                        'name'        => $item['name'],
                        'slug'        => $item['slug'],
                        'description' => $item['description'] ?? null,
                        'color'       => $item['color'] ?? null,
                        'hue'         => $item['hue'] ?? null,
                    ]);
                    $created++;
                }
            } catch (\Throwable) {
                $failed++;
            }
        }

        return compact('created', 'updated', 'skipped', 'failed');
    }

    private function importTags(array $data, string $strategy): array
    {
        $created = $updated = $skipped = $failed = 0;

        foreach ($data as $item) {
            try {
                $existing = Tag::where('slug', $item['slug'])->first();

                if ($existing) {
                    if ($strategy === 'skip') {
                        $skipped++;
                    } elseif ($strategy === 'overwrite') {
                        $existing->update(['name' => $item['name']]);
                        $updated++;
                    } elseif ($strategy === 'duplicate') {
                        Tag::create([
                            'name' => $item['name'],
                            'slug' => Tag::generateSlug($item['name']),
                        ]);
                        $created++;
                    }
                } else {
                    Tag::create([
                        'name' => $item['name'],
                        'slug' => $item['slug'],
                    ]);
                    $created++;
                }
            } catch (\Throwable) {
                $failed++;
            }
        }

        return compact('created', 'updated', 'skipped', 'failed');
    }

    private function importMedia(array $data, ZipArchive $zip, string $strategy, int $userId): array
    {
        $created = $updated = $skipped = $failed = 0;

        foreach ($data as $item) {
            try {
                $existing = Media::where('filename', $item['filename'])->first();

                if ($existing) {
                    if ($strategy === 'skip') {
                        $skipped++;
                        continue;
                    } elseif ($strategy === 'overwrite') {
                        $existing->update([
                            'alt'         => $item['alt'] ?? null,
                            'description' => $item['description'] ?? null,
                        ]);
                        $updated++;
                        continue;
                    }
                    // duplicate falls through to create a new record
                }

                $fileContent = $zip->getFromName('media/' . $item['filename']);
                if ($fileContent !== false) {
                    Storage::disk('public')->put($item['path'], $fileContent);
                }

                Media::create([
                    'user_id'           => $userId,
                    'filename'          => $item['filename'],
                    'original_filename' => $item['original_filename'],
                    'disk'              => $item['disk'],
                    'path'              => $item['path'],
                    'mime_type'         => $item['mime_type'],
                    'type'              => $item['type'],
                    'size'              => $item['size'],
                    'width'             => $item['width'] ?? null,
                    'height'            => $item['height'] ?? null,
                    'alt'               => $item['alt'] ?? null,
                    'description'       => $item['description'] ?? null,
                ]);
                $created++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return compact('created', 'updated', 'skipped', 'failed');
    }

    private function importPosts(array $data, string $strategy, int $userId): array
    {
        $created = $updated = $skipped = $failed = 0;

        foreach ($data as $item) {
            try {
                $existing = Post::where('slug', $item['slug'])->first();

                if ($existing) {
                    if ($strategy === 'skip') {
                        $skipped++;
                        continue;
                    } elseif ($strategy === 'overwrite') {
                        $existing->update($this->postFields($item, $userId));
                        $this->syncPostRelations($existing, $item);
                        $updated++;
                        continue;
                    }
                    // duplicate: generate a unique slug
                    $item['slug'] = Post::generateSlug($item['title']);
                }

                $post = Post::create(array_merge(
                    $this->postFields($item, $userId),
                    ['slug' => $item['slug']]
                ));
                $this->syncPostRelations($post, $item);
                $created++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        return compact('created', 'updated', 'skipped', 'failed');
    }

    private function postFields(array $item, int $userId): array
    {
        return [
            'user_id'           => $userId,
            'title'             => $item['title'],
            'excerpt'           => $item['excerpt'] ?? null,
            'body'              => $item['body'] ?? null,
            'body_format'       => $item['body_format'] ?? 'html',
            'status'            => $item['status'] ?? 'draft',
            'featured'          => $item['featured'] ?? false,
            'published_at'      => isset($item['published_at']) ? Carbon::parse($item['published_at']) : null,
            'comments_enabled'  => $item['comments_enabled'] ?? true,
            'use_block_editor'  => $item['use_block_editor'] ?? false,
            'blocks'            => $item['blocks'] ?? [],
            'meta_title'        => $item['meta_title'] ?? null,
            'meta_description'  => $item['meta_description'] ?? null,
            'meta_keywords'     => $item['meta_keywords'] ?? null,
            'custom_js'         => $item['custom_js'] ?? null,
            'featured_image_id' => $this->resolveMediaId($item['featured_image'] ?? null),
        ];
    }

    private function syncPostRelations(Post $post, array $item): void
    {
        $categoryIds = Category::whereIn('slug', $item['categories'] ?? [])->pluck('id');
        $tagIds      = Tag::whereIn('slug', $item['tags'] ?? [])->pluck('id');
        $post->categories()->sync($categoryIds);
        $post->tags()->sync($tagIds);
    }

    private function resolveMediaId(?string $filename): ?int
    {
        if (!$filename) {
            return null;
        }
        return Media::where('filename', $filename)->value('id');
    }
}
