<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\MarkdownService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['integer', 'min:1', 'max:100'],
            'category' => ['nullable', 'string'],
            'tag'      => ['nullable', 'string'],
            'search'   => ['nullable', 'string', 'max:100'],
        ]);

        $posts = Post::published()
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt,width,height'])
            ->when($request->input('category'), fn ($q, $slug) => $q->whereHas('categories', fn ($c) => $c->where('slug', $slug)))
            ->when($request->input('tag'), fn ($q, $slug) => $q->whereHas('tags', fn ($t) => $t->where('slug', $slug)))
            ->when($request->input('search'), fn ($q, $term) => $q->search($term))
            ->latest('published_at')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString()
            ->through(fn (Post $post) => $this->toArray($post));

        return response()->json($posts);
    }

    public function show(string $slug): JsonResponse
    {
        $post = Post::published()
            ->with(['author:id,name', 'categories:id,name,slug', 'tags:id,name,slug', 'featuredImage:id,path,disk,alt,description,width,height'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json($this->toArray($post, full: true));
    }

    private function toArray(Post $post, bool $full = false): array
    {
        $data = [
            'id'             => $post->id,
            'title'          => $post->title,
            'slug'           => $post->slug,
            'excerpt'        => $post->excerpt,
            'status'         => $post->status,
            'published_at'   => $post->published_at?->toIso8601String(),
            'author'         => $post->author ? ['id' => $post->author->id, 'name' => $post->author->name] : null,
            'categories'     => $post->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug])->values(),
            'tags'           => $post->tags->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'slug' => $t->slug])->values(),
            'featured_image' => $this->imageArray($post->featuredImage),
        ];

        if ($full) {
            $body = $post->body_format === 'markdown'
                ? app(MarkdownService::class)->toHtml($post->body ?? '')
                : $post->body;

            $data['body']        = $body;
            $data['body_format'] = $post->body_format ?? 'html';
        }

        return $data;
    }

    private function imageArray(?object $image): ?array
    {
        if (! $image) {
            return null;
        }

        return [
            'url'    => $image->url,
            'alt'    => $image->alt,
            'width'  => $image->width,
            'height' => $image->height,
        ];
    }
}
