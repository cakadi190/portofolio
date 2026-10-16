<?php

use App\Models\Post;
use App\Models\Service;
use App\Models\User;

const HOSTILE_HTML = '<p onclick="steal()">Aman</p><script>alert(1)</script><img src="x" onerror="alert(2)"><a href="javascript:alert(3)">klik</a>';

it('stores post content without scripts, event handlers or javascript links', function () {
    $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
        'title' => 'Judul',
        'content' => HOSTILE_HTML,
        'content_en' => HOSTILE_HTML,
    ])->assertSessionHasNoErrors();

    $post = Post::query()->firstOrFail();

    foreach ([$post->content, $post->translationPayload()['content']['en']] as $stored) {
        expect($stored)->toContain('Aman')
            ->not->toContain('<script')
            ->not->toContain('onclick')
            ->not->toContain('onerror')
            ->not->toContain('javascript:');
    }
});

it('keeps legitimate editor markup when saving', function () {
    $html = '<p>Teks <strong>tebal</strong></p><figure class="wp-block-image is-align-center"><img src="/storage/a.jpg" alt="a"><figcaption>Keterangan</figcaption></figure>';

    $this->actingAs(User::factory()->admin()->create())->post(route('admin.posts.store'), [
        'title' => 'Judul',
        'content' => $html,
    ])->assertSessionHasNoErrors();

    expect(Post::query()->firstOrFail()->content)
        ->toContain('<strong>tebal</strong>')
        ->toContain('class="wp-block-image is-align-center"')
        ->toContain('<figcaption>Keterangan</figcaption>');
});

it('sanitizes service descriptions when saving', function () {
    $service = Service::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->put(route('admin.services.update', $service), [
        'name' => 'Layanan',
        'slug' => 'layanan',
        'color' => '#112233',
        'description' => HOSTILE_HTML,
        'description_en' => HOSTILE_HTML,
    ])->assertSessionHasNoErrors();

    expect($service->fresh()->description)->toContain('Aman')->not->toContain('<script')->not->toContain('onerror');
});
