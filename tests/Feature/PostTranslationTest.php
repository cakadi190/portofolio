<?php

use App\Models\Clarification;
use App\Models\Note;
use App\Models\Post;
use App\Models\User;
use App\Support\AppLocale;

it('shows the English text of a translated post under /en and Indonesian elsewhere', function () {
    $post = Post::factory()->withEnglish('Hello World')->create(['title' => 'Halo Dunia']);
    $indonesianUrl = "/blog/{$post->slug}";

    $this->get("/en/blog/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('post.title', 'Hello World')
            ->where('post.excerpt', 'English excerpt')
            ->where('post.content', '<p>English body</p>'));

    // The test client builds URLs from the previous request, so reset its language prefix.
    request()->attributes->remove(AppLocale::URL_LOCALE_ATTRIBUTE);

    $this->get($indonesianUrl)
        ->assertInertia(fn ($page) => $page->where('post.title', 'Halo Dunia'));
});

it('falls back to Indonesian for a post without an English translation', function () {
    $post = Post::factory()->create(['title' => 'Hanya Indonesia']);

    $this->get("/en/blog/{$post->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('post.title', 'Hanya Indonesia'));
});

it('falls back per field when only some English fields are filled', function () {
    $post = Post::factory()->create(['title' => 'Judul', 'excerpt' => 'Ringkas', 'title_en' => 'Title']);

    $this->get("/en/blog/{$post->slug}")
        ->assertInertia(fn ($page) => $page->where('post.title', 'Title')->where('post.excerpt', 'Ringkas'));
});

it('keeps untranslated posts out of the English index and gives translated ones hreflang', function () {
    config(['app.indexable_locales' => ['id', 'en']]);

    $untranslated = Post::factory()->create();
    $translated = Post::factory()->withEnglish()->create();

    $untranslatedHtml = $this->get("/en/blog/{$untranslated->slug}")->getContent();
    $translatedHtml = $this->get("/en/blog/{$translated->slug}")->getContent();

    expect($untranslatedHtml)->toContain('content="noindex,follow"')->not->toContain('hreflang="en"')
        ->and($translatedHtml)->toContain('content="index,follow')->toContain('hreflang="en"');
});

it('lists the English URL of a post in the sitemap only when it is translated', function () {
    config(['app.indexable_locales' => ['id', 'en']]);

    $untranslated = Post::factory()->create();
    $translated = Post::factory()->withEnglish()->create();

    $xml = $this->get(route('sitemaps.posts'))->getContent();

    expect($xml)->toContain("/en/blog/{$translated->slug}</loc>")
        ->and($xml)->not->toContain("/en/blog/{$untranslated->slug}</loc>")
        ->and($xml)->toContain("/blog/{$untranslated->slug}</loc>");
});

it('saves both languages from the admin form and clears the English text', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.posts.store'), [
        'title' => 'Judul',
        'title_en' => 'Title',
        'excerpt' => 'Ringkas',
        'excerpt_en' => '',
        'content' => '<p>Isi</p>',
        'content_en' => '<p>Body</p>',
    ])->assertSessionHasNoErrors();

    $post = Post::query()->firstOrFail();

    expect($post->translationPayload())->toBe([
        'title' => ['id' => 'Judul', 'en' => 'Title'],
        'excerpt' => ['id' => 'Ringkas', 'en' => ''],
        'content' => ['id' => '<p>Isi</p>', 'en' => '<p>Body</p>'],
    ])->and($post->slug)->toBe('judul');

    $this->actingAs($admin)->put(route('admin.posts.update', $post), [
        'title' => 'Judul',
        'title_en' => '',
        'content' => '<p>Isi</p>',
        'content_en' => '',
    ])->assertSessionHasNoErrors();

    expect($post->fresh()->isTranslatedTo('en'))->toBeFalse()
        ->and($post->fresh()->translatedLocales())->toBe(['id']);
});

it('builds the slug from the Indonesian title even when the English one is saved first', function () {
    $post = Post::query()->create(['title' => 'Judul Utama', 'title_en' => 'Main Title', 'content' => '<p>x</p>']);

    expect($post->slug)->toBe('judul-utama');
});

it('requires the Indonesian title and content but not the English ones', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), ['title' => '', 'content' => '', 'title_en' => 'Only English'])
        ->assertSessionHasErrors(['title', 'content']);
});

it('hands the editor the raw text of every language', function () {
    $post = Post::factory()->withEnglish('Hello')->create(['title' => 'Halo']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.posts.edit', $post))
        ->assertInertia(fn ($page) => $page
            ->where('post.translations.title', ['id' => 'Halo', 'en' => 'Hello'])
            ->where('post.title', 'Halo'));
});

it('translates notes the same way', function () {
    $note = Note::factory()->withEnglish('Quick note')->create(['title' => 'Catatan cepat']);

    $this->get("/en/notes/{$note->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('note.title', 'Quick note')->where('note.content', '<p>English body</p>'));

    expect($note->translatedLocales())->toBe(['id', 'en']);
});

it('translates clarifications across all their text fields', function () {
    $clarification = Clarification::factory()->withEnglish('Not true')->create(['title' => 'Tidak benar']);

    $this->get("/en/clarifications/{$clarification->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('clarification.title', 'Not true')
            ->where('clarification.claim', 'English claim')
            ->where('clarification.content', '<p>English body</p>'));

    expect($clarification->translatedLocales())->toBe(['id', 'en']);
});

it('keeps the Indonesian apology required and the English one optional', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.clarifications.store'), [
            'title' => 'Judul', 'claim' => 'Klaim', 'verdict' => 'true_with_apology', 'content' => '<p>x</p>',
            'is_published' => '0', 'apology_en' => 'English apology',
        ])
        ->assertSessionHasErrors('apology')
        ->assertSessionDoesntHaveErrors('apology_en');
});
