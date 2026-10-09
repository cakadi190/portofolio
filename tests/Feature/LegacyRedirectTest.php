<?php

it('permanently redirects legacy Indonesian URLs to the English ones', function (string $old, string $new) {
    $this->get($old)->assertStatus(301)->assertRedirect($new);
})->with([
    ['/karir', '/career'],
    ['/pendidikan', '/education'],
    ['/penghargaan', '/awards'],
    ['/kontak', '/contact'],
    ['/layanan', '/services'],
    ['/layanan/web-development', '/services/web-development'],
    ['/portofolio', '/portfolio'],
    ['/portofolio/proyek-a', '/portfolio/proyek-a'],
    ['/tentang/saya', '/about'],
    ['/about/me', '/about'],
    ['/tentang/situs', '/about/site'],
    ['/tentang/skill', '/about/skills'],
    ['/sumber-daya/tempat-ngopi', '/resources/coffee-shops'],
]);

it('lists only English URLs in the pages sitemap', function () {
    $xml = $this->get(route('sitemaps.pages'))->getContent();

    expect($xml)->toContain('/career')->not->toContain('/karir')->not->toContain('/portofolio');
});
