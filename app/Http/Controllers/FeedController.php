<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::published()
            ->orderByDesc('published_at')
            ->limit(20)
            ->get(['id', 'title', 'slug', 'excerpt', 'published_at', 'updated_at']);

        $siteName        = Setting::get('site.name', config('app.name'));
        $siteDescription = Setting::get('seo.default_description', '');

        $xml = view('feed', compact('posts', 'siteName', 'siteDescription'))->render();

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }
}
