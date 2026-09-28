<?php

use App\Models\Certification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function certificationPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Laravel Certified',
        'issuer' => 'Laravel',
        'issued_at' => '2025-01-15',
        'file' => UploadedFile::fake()->create('sertifikat.pdf', 200, 'application/pdf'),
    ], $overrides);
}

test('guests are redirected to the login page', function () {
    $this->get(route('admin.certifications.index'))->assertRedirect(route('login'));
});

test('a certification can be created with a pdf', function () {
    $response = $this->actingAs(User::factory()->create())
        ->post(route('admin.certifications.store'), certificationPayload());

    $response->assertRedirect(route('admin.certifications.index'));

    $certification = Certification::query()->firstOrFail();
    expect($certification->is_pdf)->toBeTrue();
    Storage::disk('public')->assertExists($certification->file);
});

test('a certification can be created with an image', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.certifications.store'), certificationPayload([
        'file' => UploadedFile::fake()->image('sertifikat.png', 800, 600),
    ]))->assertRedirect(route('admin.certifications.index'));

    $certification = Certification::query()->firstOrFail();
    expect($certification->is_pdf)->toBeFalse();
    Storage::disk('public')->assertExists($certification->file);
});

test('creating a certification requires a valid file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('admin.certifications.store'), certificationPayload(['file' => null]))
        ->assertSessionHasErrors('file');

    $this->actingAs($user)
        ->post(route('admin.certifications.store'), certificationPayload([
            'file' => UploadedFile::fake()->create('malware.exe', 10),
        ]))
        ->assertSessionHasErrors('file');
});

test('a certification can be updated without replacing the file', function () {
    $certification = Certification::factory()->create();

    $this->actingAs(User::factory()->create())->put(route('admin.certifications.update', $certification), [
        'title' => 'Judul Baru',
        'issuer' => $certification->issuer,
        'issued_at' => '2025-01-15',
    ])->assertRedirect(route('admin.certifications.index'));

    expect($certification->refresh())->title->toBe('Judul Baru')->file->toStartWith('certifications/');
});

test('replacing the file removes the previous upload', function () {
    Storage::disk('public')->put('certifications/old.pdf', 'old');
    $certification = Certification::factory()->create(['file' => 'certifications/old.pdf']);

    $this->actingAs(User::factory()->create())->put(route('admin.certifications.update', $certification), [
        'title' => $certification->title,
        'issuer' => $certification->issuer,
        'issued_at' => '2025-01-15',
        'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
    ]);

    Storage::disk('public')->assertMissing('certifications/old.pdf');
    Storage::disk('public')->assertExists($certification->refresh()->file);
});

test('a certification and its file can be deleted', function () {
    Storage::disk('public')->put('certifications/a.pdf', 'x');
    $certification = Certification::factory()->create(['file' => 'certifications/a.pdf']);

    $this->actingAs(User::factory()->create())
        ->delete(route('admin.certifications.destroy', $certification))
        ->assertRedirect(route('admin.certifications.index'));

    $this->assertModelMissing($certification);
    Storage::disk('public')->assertMissing('certifications/a.pdf');
});

test('the about page lists certifications', function () {
    Certification::factory()->create(['title' => 'AWS Cloud Practitioner']);

    $this->get('/tentang/saya')
        ->assertInertia(fn ($page) => $page->has('certifications', 1)->where('certifications.0.title', 'AWS Cloud Practitioner'));
});
