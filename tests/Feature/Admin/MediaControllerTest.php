<?php

use App\Models\Certification;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.media.index'))->assertRedirect(route('login'));
    $this->postJson(route('admin.media.store'))->assertUnauthorized();
});

test('an image is compressed to webp and registered in the library', function () {
    $response = $this->actingAs(User::factory()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('foto.jpg', 800, 600)]);

    $response->assertCreated()->assertJsonPath('mime_type', 'image/webp')->assertJsonPath('is_image', true);

    $media = Media::query()->firstOrFail();
    expect($media->name)->toBe('foto.jpg')->and($media->width)->toBe(800);
    Storage::disk('public')->assertExists($media->path);
});

test('a pdf is stored as-is', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
        ->assertCreated()
        ->assertJsonPath('is_image', false);

    expect(Media::query()->firstOrFail()->path)->toEndWith('.pdf');
});

test('unsupported files are rejected', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('malware.exe', 10)])
        ->assertJsonValidationErrors('file');
});

test('the browse endpoint searches and filters by type', function () {
    Media::factory()->create(['name' => 'banner.webp']);
    Media::factory()->pdf()->create(['name' => 'resume.pdf']);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('admin.media.browse', ['type' => 'image']))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'banner.webp');

    $this->actingAs($user)->getJson(route('admin.media.browse', ['search' => 'resume']))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'resume.pdf');
});

test('media details can be updated', function () {
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->create())
        ->put(route('admin.media.update', $media), ['name' => 'baru.webp', 'alt' => 'Teks alternatif'])
        ->assertRedirect(route('admin.media.index'));

    expect($media->refresh())->name->toBe('baru.webp')->alt->toBe('Teks alternatif');
});

test('unused media is deleted together with its file', function () {
    $media = Media::factory()->create();
    Storage::disk('public')->put($media->path, 'x');

    $this->actingAs(User::factory()->create())->delete(route('admin.media.destroy', $media));

    $this->assertModelMissing($media);
    Storage::disk('public')->assertMissing($media->path);
});

test('media in use by a record or embedded in post content is kept', function () {
    $user = User::factory()->create();
    $cover = Media::factory()->create();
    $inline = Media::factory()->create();
    Certification::factory()->create(['file' => $cover->path]);
    Post::factory()->create(['content' => '<img src="/storage/'.$inline->path.'">']);

    $this->actingAs($user)->delete(route('admin.media.destroy', $cover));
    $this->actingAs($user)->delete(route('admin.media.destroy', $inline));

    $this->assertModelExists($cover);
    $this->assertModelExists($inline);
});

test('stored files get a kebab-case name derived from the original name', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('Foto Profil_2024.JPG', 100, 100)])
        ->assertCreated();

    $this->actingAs(User::factory()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('Surat Lamaran Kerja.PDF', 10, 'application/pdf')])
        ->assertCreated();

    $paths = Media::query()->orderBy('id')->pluck('path');

    expect($paths[0])->toMatch('#^media/\d{4}/\d{2}/foto-profil-2024-[a-z0-9]{8}\.webp$#')
        ->and($paths[1])->toMatch('#^media/\d{4}/\d{2}/surat-lamaran-kerja-[a-z0-9]{8}\.pdf$#');
});
