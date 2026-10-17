<?php

it('permanently redirects legacy Indonesian URLs to the English ones', function (string $old, string $new) {
    $this->get($old)->assertStatus(301)->assertRedirect($new);
})->with([
    ['/karir', '/id/career'],
    ['/pendidikan', '/id/education'],
    ['/penghargaan', '/id/awards'],
    ['/kontak', '/id/contact'],
    ['/layanan', '/id/services'],
    ['/layanan/web-development', '/id/services/web-development'],
    ['/portofolio', '/id/portfolio'],
    ['/portofolio/proyek-a', '/id/portfolio/proyek-a'],
    ['/tentang/saya', '/id/about'],
    ['/about/me', '/id/about'],
    ['/tentang/situs', '/id/about/site'],
    ['/tentang/skill', '/id/about/skills'],
    ['/sumber-daya/tempat-ngopi', '/id/resources/coffee-shops'],
]);

it('lists only English URLs in the pages sitemap', function () {
    $xml = $this->get(route('sitemaps.pages'))->getContent();

    expect($xml)->toContain('/career')->not->toContain('/karir')->not->toContain('/portofolio');
});

it('keeps the query string when redirecting a legacy URL', function () {
    $this->get('/sumber-daya/tempat-ngopi?region=Ngawi&search=kopi')
        ->assertStatus(301)
        ->assertRedirect('/id/resources/coffee-shops?region=Ngawi&search=kopi');

    $this->get('/layanan/web-development?utm_source=mail')
        ->assertStatus(301)
        ->assertRedirect('/id/services/web-development?utm_source=mail');
});

it('redirects without a dangling question mark or an unsafe parameter', function () {
    expect($this->get('/karir')->headers->get('Location'))->not->toContain('?');

    $this->get('/portofolio/a b')->assertStatus(301)->assertRedirect('/id/portfolio/a%20b');
});
