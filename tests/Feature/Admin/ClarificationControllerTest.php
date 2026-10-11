<?php

use App\Models\Clarification;
use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;

function clarificationPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Klarifikasi Contoh',
        'claim' => 'Klaim yang beredar di media sosial.',
        'claim_origin' => 'Unggahan media sosial, 1 Januari 2026',
        'verdict' => 'false',
        'summary' => 'Klaim tersebut tidak benar.',
        'content' => '<p>Penjelasan lengkap.</p>',
        'sources' => [['title' => 'Pernyataan resmi', 'url' => 'https://example.com/pernyataan']],
        'is_published' => '1',
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.clarifications.index'))->assertRedirect(route('login'));
});

test('only admins can manage clarifications', function (string $role) {
    $user = $role === 'user' ? User::factory()->create() : User::factory()->redaktur()->create();
    $clarification = Clarification::factory()->create();

    $this->actingAs($user)->get(route('admin.clarifications.index'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.clarifications.store'), clarificationPayload())->assertForbidden();
    $this->actingAs($user)->put(route('admin.clarifications.update', $clarification), clarificationPayload())->assertForbidden();
    $this->actingAs($user)->delete(route('admin.clarifications.destroy', $clarification))->assertForbidden();

    expect(Clarification::query()->count())->toBe(1);
})->with(['redaktur', 'user']);

test('a clarification can be created and gets a slug and a publish date', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.clarifications.store'), clarificationPayload())
        ->assertRedirect();

    $clarification = Clarification::query()->firstOrFail();

    expect($clarification)
        ->slug->toBe('klarifikasi-contoh')
        ->is_published->toBeTrue()
        ->published_at->not->toBeNull()
        ->verdict->value->toBe('false')
        ->sources->toBe([['title' => 'Pernyataan resmi', 'url' => 'https://example.com/pernyataan']]);
});

test('a draft can be saved without sources and keeps no publish date', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.clarifications.store'), clarificationPayload(['is_published' => '0', 'sources' => []]))
        ->assertSessionHasNoErrors();

    expect(Clarification::query()->firstOrFail())->is_published->toBeFalse()->published_at->toBeNull();
});

test('publishing requires at least one source', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.clarifications.store'), clarificationPayload(['sources' => []]))
        ->assertSessionHasErrors('sources');
});

test('blank source and update rows are ignored', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.clarifications.store'), clarificationPayload([
            'sources' => [
                ['title' => 'Pernyataan resmi', 'url' => 'https://example.com/a'],
                ['title' => '', 'url' => ''],
            ],
            'updates' => [['date' => '', 'note' => '']],
        ]))
        ->assertSessionHasNoErrors();

    $clarification = Clarification::query()->firstOrFail();

    expect($clarification->sources)->toHaveCount(1)->and($clarification->updates)->toBe([]);
});

test('sources and updates are validated row by row', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.clarifications.store'), clarificationPayload([
            'sources' => [['title' => 'Tanpa tautan', 'url' => 'bukan-url']],
            'updates' => [['date' => '2026-10-11', 'note' => '']],
        ]))
        ->assertSessionHasErrors(['sources.0.url', 'updates.0.note']);
});

test('creating validates required fields and the verdict enum', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('admin.clarifications.store'), [])
        ->assertSessionHasErrors(['title', 'claim', 'verdict', 'content', 'is_published']);

    $this->actingAs($user)
        ->post(route('admin.clarifications.store'), clarificationPayload(['verdict' => 'wizard']))
        ->assertSessionHasErrors('verdict');
});

test('an apology is required when the verdict is true with apology', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('admin.clarifications.store'), clarificationPayload(['verdict' => 'true_with_apology']))
        ->assertSessionHasErrors('apology');

    $this->actingAs($user)
        ->post(route('admin.clarifications.store'), clarificationPayload(['verdict' => 'true_with_apology', 'apology' => 'Saya meminta maaf.']))
        ->assertSessionHasNoErrors();

    expect(Clarification::query()->firstOrFail()->apology)->toBe('Saya meminta maaf.');
});

test('the slug must be unique but may be kept on update', function () {
    $user = User::factory()->admin()->create();
    Clarification::factory()->create(['slug' => 'sudah-ada']);
    $own = Clarification::factory()->create(['slug' => 'milik-saya']);

    $this->actingAs($user)
        ->put(route('admin.clarifications.update', $own), clarificationPayload(['slug' => 'sudah-ada']))
        ->assertSessionHasErrors('slug');

    $this->actingAs($user)
        ->put(route('admin.clarifications.update', $own), clarificationPayload(['slug' => 'milik-saya']))
        ->assertSessionHasNoErrors();
});

test('the cover image is optional but must come from the media library when given', function () {
    $user = User::factory()->admin()->create();
    $cover = Media::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.clarifications.store'), clarificationPayload(['cover_image' => $cover->path]))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post(route('admin.clarifications.store'), clarificationPayload(['title' => 'Lain', 'cover_image' => 'media/unknown.webp']))
        ->assertSessionHasErrors('cover_image');
});

test('a clarification can be updated and deleted', function () {
    $user = User::factory()->admin()->create();
    $clarification = Clarification::factory()->create();

    $this->actingAs($user)
        ->put(route('admin.clarifications.update', $clarification), clarificationPayload([
            'title' => 'Judul Baru',
            'updates' => [['date' => '2026-10-11', 'note' => 'Menambahkan bukti baru.']],
        ]))
        ->assertRedirect(route('admin.clarifications.edit', $clarification));

    expect($clarification->refresh())->title->toBe('Judul Baru')
        ->and($clarification->updates)->toBe([['date' => '2026-10-11', 'note' => 'Menambahkan bukti baru.']]);

    $this->actingAs($user)
        ->delete(route('admin.clarifications.destroy', $clarification))
        ->assertRedirect(route('admin.clarifications.index'));

    $this->assertModelMissing($clarification);
});

test('the index lists clarifications and the editor receives the verdict options', function () {
    Clarification::factory()->count(2)->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->get(route('admin.clarifications.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/clarifications/index')
            ->has('clarifications.data', 2));

    $this->actingAs($user)
        ->get(route('admin.clarifications.create'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/clarifications/form')
            ->where('clarification', null)
            ->has('verdicts', 5));
});

test('a cover image in use cannot be deleted from the media library', function () {
    $cover = Media::factory()->create();
    Clarification::factory()->create(['cover_image' => $cover->path]);

    expect(app(MediaService::class)->usageCount($cover))->toBe(1);
});
