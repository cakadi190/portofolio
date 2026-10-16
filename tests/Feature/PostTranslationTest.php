<?php

use App\Enums\ResumeProfile;
use App\Mail\ContactMessageConfirmation;
use App\Mail\ContactMessageReceived;
use App\Mail\ContactReplyEmail;
use App\Models\Award;
use App\Models\Career;
use App\Models\Clarification;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\Note;
use App\Models\Organization;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Service;
use App\Models\Tag;
use App\Models\User;
use App\Services\ResumeService;
use App\Support\AppLocale;
use Illuminate\Support\Facades\Mail;

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

    expect($untranslatedHtml)->toContain('content="noindex,follow"')->not->toContain('<link rel="alternate" hreflang="en"')
        ->and($translatedHtml)->toContain('content="index,follow')->toContain('<link rel="alternate" hreflang="en"');
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

it('translates portfolios and services', function () {
    $portfolio = Portfolio::factory()->withEnglish('Online Shop')->create(['name' => 'Toko Online']);
    $service = Service::factory()->withEnglish('Web Development')->create(['name' => 'Pengembangan Web']);

    $this->get("/en/portfolio/{$portfolio->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('portfolio.name', 'Online Shop')->where('portfolio.shortDesc', 'English summary'));

    $this->get("/en/services/{$service->slug}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('service.name', 'Web Development'));

    expect($portfolio->translatedLocales())->toBe(['id', 'en'])
        ->and($portfolio->slug)->toBe('toko-online');
});

it('shows the English author biography on /en author pages', function () {
    $author = User::factory()->admin()->create(['username' => 'cakadi', 'bio' => '<p>Halo</p>', 'bio_en' => '<p>Hello</p>']);

    $this->get('/en/blog/@cakadi')->assertOk()->assertInertia(fn ($page) => $page->where('author.bio', '<p>Hello</p>'));

    request()->attributes->remove(AppLocale::URL_LOCALE_ATTRIBUTE);

    $this->get('/blog/@cakadi')->assertInertia(fn ($page) => $page->where('author.bio', '<p>Halo</p>'));

    expect($author->translatedLocales())->toBe(['id', 'en']);
});

it('saves the English biography from the profile form', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'bio' => '<p>Halo</p>',
        'bio_en' => '<p>Hello</p><script>x()</script>',
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->translationPayload()['bio'])->toBe(['id' => '<p>Halo</p>', 'en' => '<p>Hello</p>']);
});

it('shows English short texts on /en pages and keeps Indonesian elsewhere', function () {
    Award::query()->create(['event_name' => 'Lomba', 'title' => 'Juara 1', 'title_en' => '1st Place', 'type' => 'competition', 'year' => 2024]);
    Career::query()->create(['position' => 'Pengembang', 'position_en' => 'Developer', 'company' => 'PT X', 'location' => 'Ngawi', 'start_date' => '2020-01-01']);
    Organization::query()->create(['name' => 'Himpunan', 'description' => 'Deskripsi', 'description_en' => 'Description', 'start_date' => '2020-01-01']);

    $this->get('/en/awards')->assertInertia(fn ($page) => $page->where('awards.0.title', '1st Place'));
    $this->get('/en/career')->assertInertia(fn ($page) => $page->where('careers.0.position', 'Developer'));
    $this->get('/en/education')->assertInertia(fn ($page) => $page->where('organizations.0.description', 'Description'));

    request()->attributes->remove(AppLocale::URL_LOCALE_ATTRIBUTE);

    $this->get('/awards')->assertInertia(fn ($page) => $page->where('awards.0.title', 'Juara 1'));
});

it('localizes tag and category names on article cards', function () {
    $post = Post::factory()->withEnglish()->create();
    $post->tags()->attach(Tag::query()->create(['name' => 'Pemrograman', 'name_en' => 'Programming']));
    $post->categories()->attach(PostCategory::query()->create(['name' => 'Tutorial', 'name_en' => 'Guides']));

    $this->get("/en/blog/{$post->slug}")->assertInertia(fn ($page) => $page
        ->where('post.tags.0.name', 'Programming')
        ->where('post.categories.0.name', 'Guides'));
});

it('hands admin lists the raw text of every language', function () {
    Award::query()->create(['event_name' => 'Lomba', 'title' => 'Juara 1', 'title_en' => '1st Place', 'type' => 'competition', 'year' => 2024]);

    $this->actingAs(User::factory()->admin()->create(['locale' => 'en']))
        ->get(route('admin.awards.index'))
        ->assertInertia(fn ($page) => $page
            ->where('awards.data.0.title', '1st Place')
            ->where('awards.data.0.translations.title', ['id' => 'Juara 1', 'en' => '1st Place']));
});

it('saves English short texts from the admin forms', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.awards.store'), [
        'event_name' => 'Lomba', 'title' => 'Juara 1', 'title_en' => '1st Place', 'type' => 'competition', 'year' => 2024,
    ])->assertSessionHasNoErrors();

    expect(Award::query()->firstOrFail()->translationPayload()['title'])->toBe(['id' => 'Juara 1', 'en' => '1st Place']);
});

it('builds the CV in the active language', function () {
    Career::query()->create(['position' => 'Pengembang', 'company' => 'PT X', 'location' => 'Ngawi', 'start_date' => '2020-01-01']);

    app()->setLocale('en');
    $english = collect(app(ResumeService::class)->build(ResumeProfile::Technical)['sections']);

    app()->setLocale('id');
    $indonesian = collect(app(ResumeService::class)->build(ResumeProfile::Technical)['sections']);

    expect($english->firstWhere('key', 'summary')['title'])->toBe('Professional Summary')
        ->and($english->firstWhere('key', 'career')['title'])->toBe('Work Experience')
        ->and($english->firstWhere('key', 'career')['entries'][0]['period'])->toBe('Jan 2020 – Present')
        ->and($indonesian->firstWhere('key', 'summary')['title'])->toBe('Ringkasan Profesional')
        ->and($indonesian->firstWhere('key', 'career')['entries'][0]['period'])->toBe('Jan 2020 – Sekarang');
});

it('serves the English CV viewer and its prefixed PDF', function () {
    $this->get('/en/resume')->assertOk()->assertInertia(fn ($page) => $page->where('locale', 'en'));

    request()->attributes->remove(AppLocale::URL_LOCALE_ATTRIBUTE);

    $this->get('/en/resume.pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('emails the visitor in the language they wrote in, and the owner always in Indonesian', function () {
    Mail::fake();

    $this->post('/en/contact', ['name' => 'Alex', 'email' => 'alex@example.com', 'reason' => 'general', 'message' => '<p>Hello there</p>'])
        ->assertSessionHasNoErrors();

    $message = ContactMessage::query()->firstOrFail();

    expect($message->locale)->toBe('en');

    Mail::assertSent(ContactMessageConfirmation::class, function ($mail): bool {
        $mail->assertSeeInHtml('We have received your message');
        $mail->assertSeeInHtml('Thank you for contacting');
        $mail->assertSeeInText('A copy of your message:');

        return $mail->hasSubject('We have received your message');
    });

    Mail::assertSent(ContactMessageReceived::class, function ($mail): bool {
        $mail->assertSeeInHtml('Pesan baru');

        return true;
    });
});

it('keeps Indonesian emails for Indonesian visitors and old messages', function () {
    $message = ContactMessage::query()->create(['name' => 'Budi', 'email' => 'b@example.com', 'reason' => 'general', 'message' => '<p>Halo</p>']);

    $mail = new ContactMessageConfirmation($message);

    $mail->assertSeeInHtml('Pesan Anda sudah kami terima');
    expect($mail->hasSubject('Pesan Anda sudah kami terima'))->toBeTrue();
});

it('sends the reply chrome in the visitor language', function () {
    $message = ContactMessage::query()->create(['name' => 'Alex', 'email' => 'a@example.com', 'reason' => 'general', 'message' => '<p>Hi</p>', 'locale' => 'en']);
    $reply = ContactMessageReply::query()->create(['contact_message_id' => $message->id, 'subject' => 'Re: Hi', 'body' => '<p>Answer</p>', 'status' => 'queued']);

    (new ContactReplyEmail($reply->load('contactMessage')))->assertSeeInHtml('Your previous message');
});
