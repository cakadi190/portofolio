<?php

use App\Models\Portfolio;
use App\Models\PortfolioGallery;
use App\Models\Post;
use App\Models\SystemSetting;

test('the sitemap index lists every child sitemap', function () {
    $response = $this->get(route('sitemaps.index'))->assertOk();

    foreach (['pages', 'posts', 'portfolios'] as $name) {
        $response->assertSee(route("sitemaps.{$name}"), escape: false);
    }
});

test('the posts sitemap only lists published posts with their cover image', function () {
    $published = Post::factory()->create(['cover_image' => 'media/cover.webp']);
    $draft = Post::factory()->create(['is_published' => false]);
    $scheduled = Post::factory()->create(['published_at' => now()->addDay()]);

    $response = $this->get(route('sitemaps.posts'))->assertOk();

    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
    $response
        ->assertSee(route('blog.show', $published), escape: false)
        ->assertSee(url('/storage/media/cover.webp'), escape: false)
        ->assertDontSee(route('blog.show', $draft), escape: false)
        ->assertDontSee(route('blog.show', $scheduled), escape: false);
});

test('the portfolios sitemap includes gallery images', function () {
    $portfolio = Portfolio::factory()->create();
    PortfolioGallery::query()->create(['portfolio_id' => $portfolio->id, 'image_url' => 'media/shot.webp', 'description' => 'Beranda']);

    $this->get(route('sitemaps.portfolios'))
        ->assertOk()
        ->assertSee(route('portfolios.show', $portfolio), escape: false)
        ->assertSee(url('/storage/media/shot.webp'), escape: false);
});

test('the pages sitemap lists the static public pages', function () {
    $this->get(route('sitemaps.pages'))
        ->assertOk()
        ->assertSee(route('home'), escape: false)
        ->assertSee(route('contact.index'), escape: false);
});

test('robots.txt points to the sitemap and hides the admin area', function () {
    $this->get(route('robots'))
        ->assertOk()
        ->assertSee('Disallow: /admin')
        ->assertSee('Sitemap: '.route('sitemaps.index'), escape: false);
});

test('a published post renders article open graph and structured data', function () {
    $post = Post::factory()->create(['title' => 'Belajar Laravel', 'excerpt' => 'Ringkasan artikel', 'cover_image' => 'media/cover.webp']);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('<meta property="og:type" content="article">', escape: false)
        ->assertSee('<meta property="og:image" content="'.url('/storage/media/cover.webp').'">', escape: false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false)
        ->assertSee('<link rel="canonical" href="'.route('blog.show', $post).'">', escape: false)
        ->assertSee('"@type":"BlogPosting"', escape: false)
        ->assertSee('Ringkasan artikel');
});

test('pages without an image fall back to the default 1200x630 open graph image', function () {
    $this->get(route('home'))
        ->assertSee('<meta property="og:image" content="'.url('/images/og-default.png').'">', escape: false)
        ->assertSee('<meta property="og:image:width" content="1200">', escape: false)
        ->assertSee('"@type":"WebSite"', escape: false);

    expect(getimagesize(public_path('images/og-default.png'))[0])->toBe(1200);
});

test('non public pages are served with noindex', function () {
    $this->get(route('login'))->assertSee('content="noindex,nofollow"', escape: false);
});

test('an unpublished post is not found', function () {
    $draft = Post::factory()->create(['is_published' => false]);

    $this->get(route('blog.show', $draft))->assertNotFound();
});

test('the sitemap stylesheet renders into an html page with the project logo', function () {
    $response = $this->get(route('sitemaps.style'))->assertOk();

    expect($response->getContent())
        ->toContain('/images/brands/logo-white.svg')
        ->toContain('Signika');

    $xsl = new XSLTProcessor;
    $xsl->importStylesheet(simplexml_load_string($response->getContent()));
    $html = $xsl->transformToXml(simplexml_load_string($this->get(route('sitemaps.pages'))->getContent()));

    expect($html)->toContain('XML Sitemap')->toContain('/kontak');
})->skip(! class_exists(XSLTProcessor::class), 'ext-xsl is not installed');

test('seo system settings drive keywords, verification tags, author and social profiles', function () {
    SystemSetting::factory()->create(['key' => 'seo_keywords', 'value' => 'laravel, svelte']);
    SystemSetting::factory()->create(['key' => 'seo_author', 'value' => 'Cak Adi Test']);
    SystemSetting::factory()->create(['key' => 'google_site_verification', 'value' => 'tokengoogle']);
    SystemSetting::factory()->create(['key' => 'bing_site_verification', 'value' => 'tokenbing']);
    SystemSetting::factory()->create(['key' => 'facebook_domain_verification', 'value' => 'tokenfb']);
    SystemSetting::factory()->create(['key' => 'pinterest_site_verification', 'value' => 'tokenpin']);
    SystemSetting::factory()->create(['key' => 'social_instagram', 'value' => 'https://instagram.com/db-profile']);

    $this->get(route('home'))
        ->assertSee('<meta name="keywords" content="laravel, svelte">', escape: false)
        ->assertSee('<meta name="author" content="Cak Adi Test">', escape: false)
        ->assertSee('<meta name="google-site-verification" content="tokengoogle">', escape: false)
        ->assertSee('<meta name="msvalidate.01" content="tokenbing">', escape: false)
        ->assertSee('<meta name="facebook-domain-verification" content="tokenfb">', escape: false)
        ->assertSee('<meta name="p:domain_verify" content="tokenpin">', escape: false)
        ->assertSee('hreflang="x-default"', escape: false)
        ->assertSee('instagram.com/db-profile', escape: false);
});

test('inner static pages expose a breadcrumb trail', function () {
    $this->get(route('blog.index'))
        ->assertSee('"@type":"BreadcrumbList"', escape: false)
        ->assertSee('"name":"Artikel"', escape: false);
});

test('dotted route names resolve their own title and description', function () {
    $this->get(route('blog.index'))
        ->assertSee('<title>Artikel • '.config('app.name').'</title>', escape: false)
        ->assertSee('Kumpulan artikel Cak Adi', escape: false);
});
