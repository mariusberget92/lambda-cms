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
            ->with('author:id,name')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $siteName        = Setting::get('site.name', config('app.name'));
        $siteUrl         = Setting::get('site.url', config('app.url'));
        $siteDescription = Setting::get('seo.default_description', '');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>' . htmlspecialchars($siteName, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</title>' . "\n";
        $xml .= '    <link>' . htmlspecialchars(rtrim($siteUrl, '/'), ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</link>' . "\n";
        $xml .= '    <description>' . htmlspecialchars($siteDescription, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</description>' . "\n";
        $xml .= '    <language>en-us</language>' . "\n";
        $xml .= '    <lastBuildDate>' . now()->toRfc2822String() . '</lastBuildDate>' . "\n";
        $xml .= '    <atom:link href="' . htmlspecialchars(url('/feed'), ENT_XML1 | ENT_COMPAT, 'UTF-8') . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($posts as $post) {
            $link        = url("/blog/{$post->slug}");
            $title       = htmlspecialchars($post->title, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $description = $post->excerpt
                ? htmlspecialchars($post->excerpt, ENT_XML1 | ENT_COMPAT, 'UTF-8')
                : htmlspecialchars(mb_substr(strip_tags($post->body ?? ''), 0, 200), ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $pubDate     = $post->published_at->toRfc2822String();
            $author      = htmlspecialchars($post->author->name ?? '', ENT_XML1 | ENT_COMPAT, 'UTF-8');
            $linkEscaped = htmlspecialchars($link, ENT_XML1 | ENT_COMPAT, 'UTF-8');

            $xml .= '    <item>' . "\n";
            $xml .= '      <title><![CDATA[' . $post->title . ']]></title>' . "\n";
            $xml .= '      <link>' . $linkEscaped . '</link>' . "\n";
            $xml .= '      <description><![CDATA[' . ($post->excerpt ?: mb_substr(strip_tags($post->body ?? ''), 0, 200)) . ']]></description>' . "\n";
            $xml .= '      <pubDate>' . $pubDate . '</pubDate>' . "\n";
            $xml .= '      <author>' . $author . '</author>' . "\n";
            $xml .= '      <guid isPermaLink="true">' . $linkEscaped . '</guid>' . "\n";
            $xml .= '    </item>' . "\n";
        }

        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>' . "\n";

        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }
}
