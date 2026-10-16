<?php

use App\Models\Post;
use App\Models\User;
use App\Support\AppLocale;
use App\Support\TranslationMigration;
use Illuminate\Support\Facades\DB;

it('shows the English text of a translated post under /en and Indonesian elsewhere', function () {
    $post = Post::factory()->withEnglish('Hello World', 'Halo Dunia')->create();
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

it('saves both languages from the admin form and drops a cleared translation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.posts.store'), [
        'title' => ['id' => 'Judul', 'en' => 'Title'],
        'excerpt' => ['id' => 'Ringkas', 'en' => ''],
        'content' => ['id' => '<p>Isi</p>', 'en' => '<p>Body</p>'],
    ])->assertSessionHasNoErrors();

    $post = Post::query()->firstOrFail();

    expect($post->getTranslations('title'))->toBe(['id' => 'Judul', 'en' => 'Title'])
        ->and($post->getTranslations('excerpt'))->toBe(['id' => 'Ringkas'])
        ->and($post->slug)->toBe('judul');

    $this->actingAs($admin)->put(route('admin.posts.update', $post), [
        'title' => ['id' => 'Judul', 'en' => ''],
        'content' => ['id' => '<p>Isi</p>', 'en' => ''],
    ])->assertSessionHasNoErrors();

    expect($post->fresh()->getTranslations('title'))->toBe(['id' => 'Judul'])
        ->and($post->fresh()->isTranslatedTo('en'))->toBeFalse();
});

it('requires the Indonesian title and content', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), [
            'title' => ['id' => '', 'en' => 'Only English'],
            'content' => ['id' => '', 'en' => '<p>Body</p>'],
        ])
        ->assertSessionHasErrors(['title.id', 'content.id']);
});

it('hands the editor every language of the post', function () {
    $post = Post::factory()->withEnglish('Hello', 'Halo')->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.posts.edit', $post))
        ->assertInertia(fn ($page) => $page
            ->where('post.translations.title', ['id' => 'Halo', 'en' => 'Hello'])
            ->where('post.title', 'Halo'));
});

it('wraps existing plain values into the default language once', function () {
    DB::table('posts')->insert([
        ['title' => 'Plain', 'slug' => 'plain', 'excerpt' => null, 'content' => '<p>x</p>', 'created_at' => now(), 'updated_at' => now()],
        ['title' => '{"id":"Done","en":"Selesai"}', 'slug' => 'done', 'excerpt' => '123', 'content' => '{"id":"y"}', 'created_at' => now(), 'updated_at' => now()],
    ]);

    TranslationMigration::wrap('posts', ['title', 'excerpt', 'content']);
    TranslationMigration::wrap('posts', ['title', 'excerpt', 'content']);

    $plain = DB::table('posts')->where('slug', 'plain')->first();
    $done = DB::table('posts')->where('slug', 'done')->first();

    expect($plain->title)->toBe('{"id":"Plain"}')
        ->and($plain->excerpt)->toBeNull()
        ->and($done->title)->toBe('{"id":"Done","en":"Selesai"}')
        ->and($done->excerpt)->toBe('{"id":"123"}');
});
