<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Page;
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

        $pages = Page::published()
            ->orderByDesc('updated_at')
            ->get(['slug', 'updated_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        // Blog homepage
        $xml .= '  <url>'."\n";
        $xml .= '    <loc>'.htmlspecialchars(url('/'), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>'."\n";
        $xml .= '    <changefreq>daily</changefreq>'."\n";
        $xml .= '    <priority>1.0</priority>'."\n";
        $xml .= '  </url>'."\n";

        foreach ($posts as $post) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars(url("/blog/{$post->slug}"), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.$post->updated_at->toDateString().'</lastmod>'."\n";
            $xml .= '  </url>'."\n";
        }

        foreach ($categories as $category) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars(url("/blog/category/{$category->slug}"), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.$category->updated_at->toDateString().'</lastmod>'."\n";
            $xml .= '  </url>'."\n";
        }

        foreach ($tags as $tag) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars(url("/blog/tag/{$tag->slug}"), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.$tag->updated_at->toDateString().'</lastmod>'."\n";
            $xml .= '  </url>'."\n";
        }

        foreach ($pages as $page) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars(url("/{$page->slug}"), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc>'."\n";
            $xml .= '    <lastmod>'.$page->updated_at->toDateString().'</lastmod>'."\n";
            $xml .= '  </url>'."\n";
        }

        $xml .= '</urlset>'."\n";

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
