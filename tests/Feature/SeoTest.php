<?php

use App\Models\Note;
use App\Models\Portfolio;
use App\Models\PortfolioGallery;
use App\Models\Post;
use App\Models\Service;
use App\Models\SpeakingEngagement;
use App\Models\SystemSetting;
use App\Models\Technology;
use App\Models\User;

test('the sitemap index lists every child sitemap', function () {
    $response = $this->get(route('sitemaps.index'))->assertOk();

    foreach (['pages', 'posts', 'portfolios', 'clarifications', 'notes', 'services'] as $name) {
        $response->assertSee(route("sitemaps.{$name}"), escape: false);
    }
});

test('the posts sitemap lists every live post, skips drafts and scheduled posts, with its cover image', function () {
    $published = Post::factory()->create(['cover_image' => 'media/cover.webp']);
    $draft = Post::factory()->create(['is_published' => false]);
    $dated = Post::factory()->create(['published_at' => now()->addDay()]);
    $undated = Post::factory()->create(['published_at' => null]);

    $response = $this->get(route('sitemaps.posts'))->assertOk();

    expect(simplexml_load_string($response->getContent()))->not->toBeFalse();
    $response
        ->assertSee(route('blog.show', $published), escape: false)
        ->assertSee(url('/storage/media/cover.webp'), escape: false)
        ->assertDontSee(route('blog.show', $draft), escape: false)
        ->assertDontSee(route('blog.show', $dated), escape: false)
        ->assertSee(route('blog.show', $undated), escape: false);
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
        ->assertSee('Disallow: /resume')
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

    expect($html)->toContain('XML Sitemap')->toContain('/contact');
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

test('a post names its author in the page and in the seo tags', function () {
    $post = Post::factory()->for(User::factory()->redaktur()->create(['name' => 'Siti Redaktur']), 'author')->create();

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee('<meta name="author" content="Siti Redaktur">', escape: false)
        ->assertSee('<meta property="article:author" content="Siti Redaktur">', escape: false)
        ->assertInertia(fn ($page) => $page->where('post.author.name', 'Siti Redaktur'));
});

test('the homepage lists speaking engagements, upcoming first', function () {
    $past = SpeakingEngagement::factory()->create(['starts_at' => now()->subWeek(), 'is_published' => true]);
    $upcoming = SpeakingEngagement::factory()->create(['starts_at' => now()->addWeek(), 'is_published' => true]);
    SpeakingEngagement::factory()->create(['is_published' => false]);

    $this->get(route('home'))->assertInertia(fn ($page) => $page
        ->has('speakings', 2)
        ->where('speakings.0.id', $upcoming->id)
        ->where('speakings.1.id', $past->id));
});

test('the homepage title puts the site name before the tagline', function () {
    $name = config('app.name');

    $this->get(route('home'))
        ->assertSee('<title>'.e("{$name} • Catatan Seorang Wirausahawan Muda & Software Engineer").'</title>', escape: false);
});

test('other pages keep the page title before the site name', function () {
    $this->get(route('about.me'))
        ->assertSee('<title>'.e('Tentang Saya • '.config('app.name')).'</title>', escape: false);
});

/**
 * The decoded JSON-LD blocks of a page, keyed by their schema.org type.
 *
 * @return array<string, array<string, mixed>>
 */
function structuredData(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return collect($matches[1])
        ->map(fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR))
        ->keyBy('@type')
        ->all();
}

/**
 * Fetch a page's structured data. SeoService is request-scoped, so reset the scoped
 * instances to make a second request in the same test behave like a fresh one.
 *
 * @return array<string, array<string, mixed>>
 */
function pageStructuredData(string $url): array
{
    app()->forgetScopedInstances();

    return structuredData(test()->get($url)->assertOk()->getContent());
}

test('the notes sitemap lists published notes only and the services sitemap lists services', function () {
    $published = Note::factory()->create();
    $draft = Note::factory()->draft()->create();
    $service = Service::factory()->create();

    $this->get(route('sitemaps.notes'))
        ->assertOk()
        ->assertSee(route('notes.show', $published), escape: false)
        ->assertDontSee(route('notes.show', $draft), escape: false);

    $this->get(route('sitemaps.services'))
        ->assertOk()
        ->assertSee(route('services.show', $service), escape: false);
});

test('the homepage links the website and the person with stable ids, location and skills', function () {
    Technology::factory()->create(['name' => 'Laravel']);

    $data = structuredData($this->get(route('home'))->assertOk()->getContent());

    expect($data['WebSite']['@id'])->toBe(url('/').'#website')
        ->and($data['WebSite']['publisher'])->toBe(['@id' => url('/').'#person'])
        ->and($data['Person']['@id'])->toBe(url('/').'#person')
        ->and($data['Person']['address']['addressCountry'])->toBe('ID')
        ->and($data['Person']['knowsAbout'])->toContain('Laravel');
});

test('child pages get a parent breadcrumb and a typed web page', function () {
    $data = pageStructuredData(route('about.skills'));

    expect(collect($data['BreadcrumbList']['itemListElement'])->pluck('name')->all())->toBe(['Beranda', 'Tentang Saya', 'Keahlian'])
        ->and($data['WebPage']['isPartOf']['@id'])->toBe(url('/').'#website');

    $about = pageStructuredData(route('about.me'));
    expect($about['ProfilePage']['mainEntity']['@type'])->toBe('Person');

    $contact = pageStructuredData(route('contact.index'));
    expect($contact)->toHaveKey('ContactPage');
});

test('service pages describe the service and their place in the site', function () {
    $service = Service::factory()->create(['name' => 'Pembuatan Website']);

    $index = pageStructuredData(route('services.index'));
    expect($index['ItemList']['itemListElement'][0]['url'])->toBe(route('services.show', $service))
        ->and($index)->toHaveKeys(['CollectionPage', 'BreadcrumbList']);

    $show = pageStructuredData(route('services.show', $service));
    expect($show['Service']['provider']['@type'])->toBe('Person')
        ->and(collect($show['BreadcrumbList']['itemListElement'])->pluck('name')->all())->toBe(['Beranda', 'Layanan', 'Pembuatan Website']);
});

test('a published note is marked up as an article with a breadcrumb trail', function () {
    $note = Note::factory()->create(['title' => 'Trik Git']);

    $data = structuredData($this->get(route('notes.show', $note))->assertOk()->getContent());

    expect($data['Article']['headline'])->toBe('Trik Git')
        ->and($data['Article']['mainEntityOfPage'])->toBe(route('notes.show', $note))
        ->and(collect($data['BreadcrumbList']['itemListElement'])->pluck('name')->all())->toBe(['Beranda', 'Catatan Singkat', 'Trik Git']);
});

test('the speaking page lists published engagements as events', function () {
    $engagement = SpeakingEngagement::factory()->create(['title' => 'Belajar Keamanan Web']);

    $data = structuredData($this->get(route('speaking.index'))->assertOk()->getContent());

    expect($data['Event']['name'])->toBe('Belajar Keamanan Web')
        ->and($data['Event']['startDate'])->toBe($engagement->starts_at->toAtomString())
        ->and($data['Event']['eventAttendanceMode'])->toStartWith('https://schema.org/');
});
