@php
    $seo = app(\App\Services\SeoService::class)->tags();
@endphp
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<meta name="author" content="{{ $seo['author'] }}">
<link rel="canonical" href="{{ $seo['canonical'] }}">

<meta property="og:site_name" content="{{ $seo['siteName'] }}">
<meta property="og:locale" content="{{ $seo['locale'] }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">
@if ($seo['imageSize'])
    <meta property="og:image:width" content="{{ $seo['imageSize']['width'] }}">
    <meta property="og:image:height" content="{{ $seo['imageSize']['height'] }}">
@endif
@if ($seo['type'] === 'article')
    <meta property="article:author" content="{{ $seo['author'] }}">
    @if ($seo['publishedTime'])
        <meta property="article:published_time" content="{{ $seo['publishedTime'] }}">
    @endif
    @if ($seo['modifiedTime'])
        <meta property="article:modified_time" content="{{ $seo['modifiedTime'] }}">
    @endif
    @if ($seo['section'])
        <meta property="article:section" content="{{ $seo['section'] }}">
    @endif
    @foreach ($seo['tags'] as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="{{ $seo['twitter'] }}">
<meta name="twitter:creator" content="{{ $seo['twitter'] }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
<meta name="twitter:image:alt" content="{{ $seo['imageAlt'] }}">

@foreach ($seo['jsonLd'] as $schema)
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endforeach
