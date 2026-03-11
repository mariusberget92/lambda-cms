@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{{ $siteName }}</title>
    <link>{{ url('/') }}</link>
    <description>{{ $siteDescription }}</description>
    <language>en-us</language>
    <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>
    <atom:link href="{{ url('/feed') }}" rel="self" type="application/rss+xml" />
    @foreach ($posts as $post)
    <item>
      <title><![CDATA[{{ $post->title }}]]></title>
      <link>{{ url("/blog/{$post->slug}") }}</link>
      <description><![CDATA[{{ $post->excerpt }}]]></description>
      <pubDate>{{ $post->published_at->toRfc2822String() }}</pubDate>
      <guid isPermaLink="true">{{ url("/blog/{$post->slug}") }}</guid>
    </item>
    @endforeach
  </channel>
</rss>
