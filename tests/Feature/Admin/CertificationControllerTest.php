<?php

use App\Models\Certification;
use App\Models\Media;
use App\Models\User;

function certificationPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Laravel Certified',
        'issuer' => 'Laravel',
        'issued_at' => '2025-01-15',
        'file' => Media::factory()->pdf()->create()->path,
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.certifications.index'))->assertRedirect(route('login'));
});

test('a certification can be created with a media library file', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.certifications.store'), certificationPayload());

    $response->assertRedirect(route('admin.certifications.index'));

    expect(Certification::query()->firstOrFail()->is_pdf)->toBeTrue();
});

test('creating a certification requires a file from the media library', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->post(route('admin.certifications.store'), certificationPayload(['file' => null]))
        ->assertSessionHasErrors('file');

    $this->actingAs($user)
        ->post(route('admin.certifications.store'), certificationPayload(['file' => 'media/unknown.pdf']))
        ->assertSessionHasErrors('file');
});

test('a certification can be updated keeping or swapping its file', function () {
    $certification = Certification::factory()->create();
    $user = User::factory()->admin()->create();
    $payload = ['title' => 'Judul Baru', 'issuer' => $certification->issuer, 'issued_at' => '2025-01-15'];

    $this->actingAs($user)
        ->put(route('admin.certifications.update', $certification), [...$payload, 'file' => $certification->file])
        ->assertRedirect(route('admin.certifications.index'));

    expect($certification->refresh())->title->toBe('Judul Baru')->file->toStartWith('certifications/');

    $replacement = Media::factory()->pdf()->create();
    $this->actingAs($user)->put(route('admin.certifications.update', $certification), [...$payload, 'file' => $replacement->path]);

    expect($certification->refresh()->file)->toBe($replacement->path);
});

test('a certification can be deleted', function () {
    $certification = Certification::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.certifications.destroy', $certification))
        ->assertRedirect(route('admin.certifications.index'));

    $this->assertModelMissing($certification);
});

test('the about page lists certifications', function () {
    Certification::factory()->create(['title' => 'AWS Cloud Practitioner']);

    $this->get('/about')
        ->assertInertia(fn ($page) => $page->has('certifications', 1)->where('certifications.0.title', 'AWS Cloud Practitioner'));
});
