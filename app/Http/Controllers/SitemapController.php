<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->orderByDesc('published_at')
            ->get(['slug', 'updated_at']);

        $categories = Category::withCount(['posts' => fn ($q) => $q->published()])
            ->get(['id', 'slug', 'updated_at'])
            ->filter(fn ($c) => $c->posts_count > 0);

        $tags = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->get(['id', 'slug', 'updated_at'])
            ->filter(fn ($t) => $t->posts_count > 0);

        $xml = view('sitemap', compact('posts', 'categories', 'tags'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
