<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Setting;
use Inertia\Inertia;

class PublicPageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->firstOrFail();

        $separator = Setting::get('seo.title_separator', ' | ');
        $siteName  = Setting::get('site.name', config('app.name'));

        $seo = [
            'title'       => ($page->meta_title ?: $page->title) . $separator . $siteName,
            'description' => $page->meta_description ?: Setting::get('seo.default_description', ''),
            'image'       => Setting::get('seo.default_og_image_url', ''),
            'canonical'   => url("/{$page->slug}"),
            'type'        => 'website',
            'keywords'    => $page->meta_keywords ?: Setting::get('seo.default_keywords', ''),
        ];

        return Inertia::render('Blog/Page', [
            'page' => [
                'title'  => $page->title,
                'slug'   => $page->slug,
                'blocks' => $page->blocks,
            ],
            'seo' => $seo,
        ]);
    }
}
