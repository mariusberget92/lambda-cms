@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ url('/') }}</loc>
    <changefreq>daily</changefreq>
    <lastmod>{{ now()->toDateString() }}</lastmod>
  </url>
  @foreach ($posts as $post)
  <url>
    <loc>{{ url("/blog/{$post->slug}") }}</loc>
    <changefreq>monthly</changefreq>
    <lastmod>{{ $post->updated_at->toDateString() }}</lastmod>
  </url>
  @endforeach
  @foreach ($categories as $category)
  <url>
    <loc>{{ url("/blog/category/{$category->slug}") }}</loc>
    <changefreq>weekly</changefreq>
    <lastmod>{{ now()->toDateString() }}</lastmod>
  </url>
  @endforeach
  @foreach ($tags as $tag)
  <url>
    <loc>{{ url("/blog/tag/{$tag->slug}") }}</loc>
    <changefreq>weekly</changefreq>
    <lastmod>{{ now()->toDateString() }}</lastmod>
  </url>
  @endforeach
  @foreach ($pages as $page)
  <url>
    <loc>{{ url("/{$page->slug}") }}</loc>
    <changefreq>monthly</changefreq>
    <lastmod>{{ $page->updated_at->toDateString() }}</lastmod>
  </url>
  @endforeach
</urlset>
