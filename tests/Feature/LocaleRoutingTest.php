<?php

use App\Models\User;
use App\Support\AppLocale;

it('serves public pages on plain URLs in Indonesian', function () {
    $this->get('/contact')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'id')->where('localeAlternates.en', '/en/contact'));
});

it('serves the English version under the /en prefix', function () {
    $this->get('/en/contact')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('locale', 'en')
            ->where('localeAlternates.id', '/contact')
            ->where('localeAlternates.en', '/en/contact'));
});

it('serves the English homepage and keeps the query string', function () {
    $this->get('/en')->assertOk()->assertInertia(fn ($page) => $page->where('locale', 'en'));

    $this->get('/en/blog?search=laravel')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'en')->where('localeAlternates.id', '/blog?search=laravel'));
});

it('keeps the language prefix in the URL Inertia reports to the browser', function () {
    $this->get('/en/contact')->assertInertia(fn ($page) => $page->url('/en/contact'));
});

it('generates prefixed links on English pages only', function () {
    request()->attributes->set(AppLocale::URL_LOCALE_ATTRIBUTE, 'en');
    expect(route('blog.index', absolute: false))->toBe('/en/blog')
        ->and(route('home', absolute: false))->toBe('/en')
        ->and(route('login', absolute: false))->toBe('/login');

    request()->attributes->remove(AppLocale::URL_LOCALE_ATTRIBUTE);
    expect(route('blog.index', absolute: false))->toBe('/blog');
});

it('redirects the default language prefix to the plain URL', function () {
    $this->get('/id/blog?page=2')->assertRedirect('/blog?page=2')->assertStatus(301);
});

it('does not localize admin, settings or auth pages', function (string $path) {
    $this->get('/en'.$path)->assertNotFound();
})->with(['/admin', '/settings/profile', '/login']);

it('follows the cookie on pages without a language prefix', function () {
    $this->withCookie(AppLocale::COOKIE, 'en')
        ->get('/login')
        ->assertInertia(fn ($page) => $page->where('locale', 'en')->where('localeAlternates', null));
});

it('follows the saved language of the signed-in user in the admin area', function () {
    $user = User::factory()->admin()->create(['locale' => 'en']);

    $this->actingAs($user)->get('/admin')
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('stores the chosen language in a cookie and on the account', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->from('/admin')->post(route('locale.update'), ['locale' => 'en'])
        ->assertRedirect('/admin')
        ->assertCookie(AppLocale::COOKIE);

    expect($user->fresh()->locale)->toBe('en');
});

it('rejects unknown languages', function () {
    $this->post(route('locale.update'), ['locale' => 'fr'])->assertSessionHasErrors('locale');
});

it('points the canonical of an English page at its own prefixed URL and keeps it out of the index', function () {
    $base = url('/');
    $html = $this->get('/en/contact')->getContent();

    expect($html)
        ->toContain('<link rel="canonical" href="'.$base.'/en/contact'.'">')
        ->toContain('content="noindex,follow"')
        ->toContain('content="en_US"');
});

it('keeps Indonesian pages indexable with a plain canonical', function () {
    $base = url('/');
    $html = $this->get('/contact')->getContent();

    expect($html)
        ->toContain('<link rel="canonical" href="'.$base.'/contact'.'">')
        ->toContain('content="index,follow')
        ->toContain('content="id_ID"')
        ->not->toContain('hreflang="en"');
});

it('emits hreflang alternates and indexes English once the language is enabled', function () {
    config(['app.indexable_locales' => ['id', 'en']]);

    $base = url('/');
    $html = $this->get('/en/contact')->getContent();

    expect($html)
        ->toContain('hreflang="id" href="'.$base.'/contact'.'"')
        ->toContain('hreflang="en" href="'.$base.'/en/contact'.'"')
        ->toContain('hreflang="x-default" href="'.$base.'/contact'.'"')
        ->toContain('content="index,follow');
});

it('lists every enabled language in the pages sitemap', function () {
    config(['app.indexable_locales' => ['id', 'en']]);
    $base = url('/');

    $xml = $this->get(route('sitemaps.pages'))->getContent();

    expect($xml)
        ->toContain('<loc>'.$base.'/en/career'.'</loc>')
        ->toContain('<xhtml:link rel="alternate" hreflang="en" href="'.$base.'/en/career'.'"/>');
});
