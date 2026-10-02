{!! '<' . '?xml version="1.0" encoding="UTF-8"?' . '>' !!}
{!! '<' . '?xml-stylesheet type="text/xsl" href="' . route('sitemaps.style') . '"?' . '>' !!}
{{--
    Sitemap index. Namespace `custom` milik aplikasi ini dan hanya dibaca oleh
    stylesheet XSL untuk kolom Judul; mesin pencari mengabaikannya.
--}}
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
  xmlns:custom="{{ url('/schemas/sitemap-custom/1.0') }}">
@foreach ($sitemaps as $sitemap)
  <sitemap>
    <custom:title>{{ $sitemap['title'] }}</custom:title>
    <loc>{{ $sitemap['loc'] }}</loc>
@if ($sitemap['lastmod'])
    <lastmod>{{ $sitemap['lastmod']->toAtomString() }}</lastmod>
@endif
  </sitemap>
@endforeach
</sitemapindex>
