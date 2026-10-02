{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
{!! '<' . '?xml-stylesheet type="text/xsl" href="' . route('sitemaps.style') . '"?' . '>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
  xmlns:custom="{{ url('/schemas/sitemap-custom/1.0') }}">
@foreach ($urls as $url)
  <url>
    <custom:title>{{ $url['title'] }}</custom:title>
    <loc>{{ $url['loc'] }}</loc>
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
</urlset>
