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
    config(['app.indexable_locales' => ['id']]);

    $base = url('/');
    $html = $this->get('/en/contact')->getContent();

    expect($html)
        ->toContain('<link rel="canonical" href="'.$base.'/en/contact'.'">')
        ->toContain('content="noindex,follow"')
        ->toContain('content="en_US"');
});

it('keeps Indonesian pages indexable with a plain canonical', function () {
    config(['app.indexable_locales' => ['id']]);

    $base = url('/');
    $html = $this->get('/contact')->getContent();

    expect($html)
        ->toContain('<link rel="canonical" href="'.$base.'/contact'.'">')
        ->toContain('content="index,follow')
        ->toContain('content="id_ID"')
        ->not->toContain('<link rel="alternate" hreflang="en"');
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

it('maps routes to the language groups their page needs', function (?string $route, array $contains, array $excludes) {
    $groups = AppLocale::groupsFor($route);

    expect($groups)->toContain(...$contains)->not->toContain(...$excludes);
})->with([
    'public' => ['blog.show', ['blog'], ['admin', 'auth']],
    'resume shares certification labels' => ['resume.show', ['resume', 'certifications'], ['admin']],
    'admin page borrows shared labels' => ['admin.posts.index', ['admin', 'admin-posts', 'admin-media', 'admin-certifications', 'security'], ['blog', 'auth']],
    'dashboard' => ['dashboard', ['admin', 'dashboard', 'admin-awards'], ['blog']],
    'settings' => ['profile.edit', ['admin', 'profile', 'security'], ['blog']],
    'auth' => ['two-factor.login', ['auth', 'security'], ['admin']],
]);

it('maps no route to no language groups', function () {
    expect(AppLocale::groupsFor(null))->toBe([]);
});

it('suggests the browser language to first-time visitors without redirecting', function (array $headers, array $cookies, string $uri, ?string $expected) {
    $response = $this->withHeaders($headers)->withUnencryptedCookies($cookies)->get($uri)->assertOk();

    $response->assertInertia(fn ($page) => $expected === null
        ? $page->where('languageSuggestion', null)
        : $page->where('languageSuggestion.locale', $expected)->where('languageSuggestion.url', '/en'.($uri === '/' ? '' : $uri))
            ->where('languageSuggestion.messages.accept', 'View in English'));
})->with([
    'english browser' => [['Accept-Language' => 'en-US,en;q=0.9,id;q=0.5', 'User-Agent' => 'Mozilla/5.0 Firefox'], [], '/contact', 'en'],
    'indonesian browser' => [['Accept-Language' => 'id-ID,id;q=0.9,en;q=0.4', 'User-Agent' => 'Mozilla/5.0 Firefox'], [], '/contact', null],
    'crawler' => [['Accept-Language' => 'en-US', 'User-Agent' => 'Googlebot/2.1'], [], '/contact', null],
    'foreign country without a language header' => [['Accept-Language' => '', 'CF-IPCountry' => 'US', 'User-Agent' => 'Mozilla/5.0 Firefox'], [], '/', 'en'],
    'home country without a language header' => [['Accept-Language' => '', 'CF-IPCountry' => 'ID', 'User-Agent' => 'Mozilla/5.0 Firefox'], [], '/', null],
    'private pages' => [['Accept-Language' => 'en-US', 'User-Agent' => 'Mozilla/5.0 Firefox'], [], '/login', null],
]);

it('does not suggest again once a language was chosen or on prefixed pages', function () {
    $headers = ['Accept-Language' => 'en-US', 'User-Agent' => 'Mozilla/5.0 Firefox'];

    $this->withHeaders($headers)->withCookie(AppLocale::COOKIE, 'id')->get('/contact')
        ->assertInertia(fn ($page) => $page->where('languageSuggestion', null));

    request()->attributes->remove(AppLocale::URL_LOCALE_ATTRIBUTE);

    $this->withHeaders($headers)->get('/en/contact')
        ->assertInertia(fn ($page) => $page->where('languageSuggestion', null));
});

it('indexes English by default and lists its URLs with hreflang alternates in the sitemap', function () {
    expect(config('app.indexable_locales'))->toBe(['id', 'en']);

    $xml = $this->get(route('sitemaps.pages'))->getContent();

    expect($xml)
        ->toContain('<loc>'.url('/en/career').'</loc>')
        ->toContain('hreflang="x-default"');
});

it('shows the language of each sitemap URL in the human readable stylesheet', function () {
    $xsl = $this->get(route('sitemaps.style'))->getContent();

    expect($xsl)->toContain('xmlns:xhtml="http://www.w3.org/1999/xhtml"')
        ->toContain('xhtml:link');
});
