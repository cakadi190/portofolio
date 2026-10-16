{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
{!! '<' . '?xml-stylesheet type="text/xsl" href="' . route('sitemaps.style') . '"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
  xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach ($urls as $url)
@php
    $hreflangs = \App\Support\AppLocale::hreflangs($url['loc']);
    $locs = $hreflangs === [] ? [$url['loc']] : collect($hreflangs)->except('x-default')->values()->all();
@endphp
@foreach ($locs as $loc)
  <url>
    <loc>{{ $loc }}</loc>
@foreach ($hreflangs as $hreflang => $href)
    <xhtml:link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}"/>
@endforeach
@if ($url['lastmod'])
    <lastmod>{{ $url['lastmod']->toAtomString() }}</lastmod>
@endif
    <changefreq>{{ $url['changefreq'] }}</changefreq>
    <priority>{{ $url['priority'] }}</priority>
@foreach ($url['images'] as $image)
    <image:image>
      <image:loc>{{ $image['loc'] }}</image:loc>
@if ($image['title'])
      <image:title>{{ $image['title'] }}</image:title>
@endif
    </image:image>
@endforeach
  </url>
@endforeach
@endforeach
</urlset>
