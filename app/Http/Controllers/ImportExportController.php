<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImportExportController extends Controller
{
    public function index()
    {
        $posts = Post::with(['categories:id,slug', 'tags:id,slug'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => [
                'id'           => $p->id,
                'title'        => $p->title,
                'slug'         => $p->slug,
                'status'       => $p->status,
                'published_at' => $p->published_at?->toDateString(),
                'categories'   => $p->categories->pluck('slug'),
                'tags'         => $p->tags->pluck('slug'),
            ]);

        $categories = Category::withCount('posts')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'slug'        => $c->slug,
                'color'       => $c->color,
                'posts_count' => $c->posts_count,
            ]);

        $tags = Tag::withCount('posts')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                'id'          => $t->id,
                'name'        => $t->name,
                'slug'        => $t->slug,
                'posts_count' => $t->posts_count,
            ]);

        return Inertia::render('ImportExport/Index', compact('posts', 'categories', 'tags'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'post_ids'     => ['array'],
            'post_ids.*'   => ['integer'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer'],
            'tag_ids'      => ['array'],
            'tag_ids.*'    => ['integer'],
        ]);

        $postIds     = $request->input('post_ids', []);
        $categoryIds = $request->input('category_ids', []);
        $tagIds      = $request->input('tag_ids', []);

        $posts = Post::with(['categories:id,slug', 'tags:id,slug', 'featuredImage'])
            ->when($postIds, fn ($q) => $q->whereIn('id', $postIds))
            ->get()
            ->map(fn ($p) => [
                'title'              => $p->title,
                'slug'               => $p->slug,
                'excerpt'            => $p->excerpt,
                'body'               => $p->body,
                'status'             => $p->status,
                'featured'           => $p->featured,
                'published_at'       => $p->published_at?->toIso8601String(),
                'comments_enabled'   => $p->comments_enabled,
                'use_block_editor'   => $p->use_block_editor,
                'blocks'             => $p->blocks,
                'meta_title'         => $p->meta_title,
                'meta_description'   => $p->meta_description,
                'meta_keywords'      => $p->meta_keywords,
                'featured_image_url' => $p->featuredImage?->url,
                'categories'         => $p->categories->pluck('slug')->toArray(),
                'tags'               => $p->tags->pluck('slug')->toArray(),
            ]);

        $categories = Category::when($categoryIds, fn ($q) => $q->whereIn('id', $categoryIds))
            ->get()
            ->map(fn ($c) => [
                'name'        => $c->name,
                'slug'        => $c->slug,
                'description' => $c->description,
                'color'       => $c->color,
            ]);

        $tags = Tag::when($tagIds, fn ($q) => $q->whereIn('id', $tagIds))
            ->get()
            ->map(fn ($t) => [
                'name' => $t->name,
                'slug' => $t->slug,
            ]);

        $payload = [
            'version'     => '1.0',
            'exported_at' => now()->toIso8601String(),
            'categories'  => $categories,
            'tags'        => $tags,
            'posts'       => $posts,
        ];

        $filename = 'lambda-cms-export-' . now()->format('Y-m-d') . '.json';

        return response()->json($payload)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:json,txt', 'max:51200'],
            'mode' => ['required', 'in:skip,overwrite'],
        ]);

        $content = $request->file('file')->get();
        $data    = json_decode($content, true);

        if (! is_array($data) || ! isset($data['version'])) {
            return back()->withErrors(['file' => 'Invalid export file. Make sure it was exported from Lambda CMS.']);
        }

        $mode    = $request->input('mode');
        $results = [
            'categories' => ['created' => 0, 'updated' => 0, 'skipped' => 0],
            'tags'       => ['created' => 0, 'skipped' => 0],
            'posts'      => ['created' => 0, 'updated' => 0, 'skipped' => 0],
        ];

        foreach ($data['categories'] ?? [] as $cat) {
            if (empty($cat['slug'])) continue;
            $existing = Category::where('slug', $cat['slug'])->first();

            if ($existing) {
                if ($mode === 'overwrite') {
                    $existing->update([
                        'name'        => $cat['name']        ?? $existing->name,
                        'description' => $cat['description'] ?? $existing->description,
                        'color'       => $cat['color']       ?? $existing->color,
                    ]);
                    $results['categories']['updated']++;
                } else {
                    $results['categories']['skipped']++;
                }
            } else {
                Category::create([
                    'name'        => $cat['name'],
                    'slug'        => $cat['slug'],
                    'description' => $cat['description'] ?? null,
                    'color'       => $cat['color']       ?? null,
                ]);
                $results['categories']['created']++;
            }
        }

        foreach ($data['tags'] ?? [] as $tag) {
            if (empty($tag['slug'])) continue;
            $existing = Tag::where('slug', $tag['slug'])->first();

            if ($existing) {
                $results['tags']['skipped']++;
            } else {
                Tag::create(['name' => $tag['name'], 'slug' => $tag['slug']]);
                $results['tags']['created']++;
            }
        }

        foreach ($data['posts'] ?? [] as $post) {
            if (empty($post['slug'])) continue;
            $existing = Post::where('slug', $post['slug'])->first();

            $fields = [
                'title'            => $post['title']            ?? 'Untitled',
                'slug'             => $post['slug'],
                'excerpt'          => $post['excerpt']          ?? null,
                'body'             => $post['body']             ?? null,
                'status'           => in_array($post['status'] ?? '', ['draft', 'published', 'scheduled'])
                                        ? $post['status'] : 'draft',
                'featured'         => (bool) ($post['featured']         ?? false),
                'published_at'     => $post['published_at']     ?? null,
                'comments_enabled' => (bool) ($post['comments_enabled'] ?? true),
                'use_block_editor' => (bool) ($post['use_block_editor'] ?? false),
                'blocks'           => $post['blocks']           ?? null,
                'meta_title'       => $post['meta_title']       ?? null,
                'meta_description' => $post['meta_description'] ?? null,
                'meta_keywords'    => $post['meta_keywords']    ?? null,
            ];

            if ($existing) {
                if ($mode === 'overwrite') {
                    $existing->update($fields);
                    $this->syncRelations($existing, $post);
                    $results['posts']['updated']++;
                } else {
                    $results['posts']['skipped']++;
                }
            } else {
                $newPost = Post::create($fields);
                $this->syncRelations($newPost, $post);
                $results['posts']['created']++;
            }
        }

        return back()->with([
            'status'         => 'Import complete.',
            'import_results' => $results,
        ]);
    }

    private function syncRelations(Post $post, array $data): void
    {
        $catIds = Category::whereIn('slug', $data['categories'] ?? [])->pluck('id');
        $tagIds = Tag::whereIn('slug', $data['tags']       ?? [])->pluck('id');
        $post->categories()->sync($catIds);
        $post->tags()->sync($tagIds);
    }
}
