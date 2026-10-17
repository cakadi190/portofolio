<?php

use App\Models\Certification;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

beforeEach(function () {
    Storage::fake('public');
});

test('guests are redirected to the login page', function () {
    $this->get(route('admin.media.index'))->assertRedirect(route('login'));
    $this->postJson(route('admin.media.store'))->assertUnauthorized();
});

test('an image is compressed to webp and registered in the library', function () {
    $response = $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('foto.jpg', 800, 600)]);

    $response->assertCreated()->assertJsonPath('mime_type', 'image/webp')->assertJsonPath('is_image', true);

    $media = Media::query()->firstOrFail();
    expect($media->name)->toBe('foto.jpg')->and($media->width)->toBe(800);
    Storage::disk('public')->assertExists($media->path);
});

test('a pdf is stored as-is', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
        ->assertCreated()
        ->assertJsonPath('is_image', false);

    expect(Media::query()->firstOrFail()->path)->toEndWith('.pdf');
});

test('unsupported files are rejected', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('malware.exe', 10)])
        ->assertJsonValidationErrors('file');
});

test('the browse endpoint searches and filters by type', function () {
    Media::factory()->create(['name' => 'banner.webp']);
    Media::factory()->pdf()->create(['name' => 'resume.pdf']);
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->getJson(route('admin.media.browse', ['type' => 'image']))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'banner.webp');

    $this->actingAs($user)->getJson(route('admin.media.browse', ['search' => 'resume']))
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'resume.pdf');
});

test('media details can be updated', function () {
    $media = Media::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.media.update', $media), ['name' => 'baru.webp', 'alt' => 'Teks alternatif'])
        ->assertRedirect(route('admin.media.index'));

    expect($media->refresh())->name->toBe('baru.webp')->alt->toBe('Teks alternatif');
});

test('unused media is deleted together with its file', function () {
    $media = Media::factory()->create();
    Storage::disk('public')->put($media->path, 'x');

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.media.destroy', $media));

    $this->assertModelMissing($media);
    Storage::disk('public')->assertMissing($media->path);
});

test('media in use by a record or embedded in post content is kept', function () {
    $user = User::factory()->admin()->create();
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
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('Foto Profil_2024.JPG', 100, 100)])
        ->assertCreated();

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->create('Surat Lamaran Kerja.PDF', 10, 'application/pdf')])
        ->assertCreated();

    $paths = Media::query()->orderBy('id')->pluck('path');

    expect($paths[0])->toMatch('#^media/\d{4}/\d{2}/foto-profil-2024-[a-z0-9]{8}\.webp$#')
        ->and($paths[1])->toMatch('#^media/\d{4}/\d{2}/surat-lamaran-kerja-[a-z0-9]{8}\.pdf$#');
});

test('an image the server cannot decode returns a readable validation error instead of a 500', function () {
    $broken = UploadedFile::fake()->createWithContent('rusak.png', 'bukan gambar sungguhan');

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => $broken])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');

    expect(Media::query()->count())->toBe(0);
});

test('an image with too many pixels is refused with a clear message before it is decoded', function () {
    $header = "\x89PNG\r\n\x1a\n".pack('N', 13).'IHDR'.pack('NN', 8000, 4500)."\x08\x02\x00\x00\x00".pack('N', 0);
    $huge = UploadedFile::fake()->createWithContent('raksasa.png', $header);

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => $huge])
        ->assertUnprocessable()
        ->assertJsonPath('errors.file.0', fn (string $message): bool => str_contains($message, '8000×4500') && str_contains($message, 'megapiksel'));

    expect(Media::query()->count())->toBe(0);
});

test('a wide image also gets a card-sized thumbnail that is used for card urls', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('lebar.jpg', 1200, 800)])
        ->assertCreated();

    $media = Media::query()->firstOrFail();
    $thumbnail = ImageService::thumbnailPath($media->path);

    Storage::disk('public')->assertExists($thumbnail);
    expect(ImageService::thumbnailUrl($media->path))->toBe('/storage/'.$thumbnail);
});

test('a small image gets no thumbnail and card urls fall back to the original', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('kecil.jpg', 300, 200)])
        ->assertCreated();

    $media = Media::query()->firstOrFail();

    Storage::disk('public')->assertMissing(ImageService::thumbnailPath($media->path));
    expect(ImageService::thumbnailUrl($media->path))->toBe('/storage/'.$media->path)
        ->and(ImageService::thumbnailUrl('/images/foto.webp'))->toBe('/images/foto.webp')
        ->and(ImageService::thumbnailUrl(null))->toBeNull();
});

test('deleting media removes its thumbnail too', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('hapus.jpg', 1200, 800)]);
    $media = Media::query()->firstOrFail();
    $thumbnail = ImageService::thumbnailPath($media->path);

    $this->actingAs($user)->delete(route('admin.media.destroy', $media));

    Storage::disk('public')->assertMissing($thumbnail);
});

test('media:variants backfills images uploaded before variants existed and is re-runnable', function () {
    $media = Media::factory()->create(['mime_type' => 'image/webp', 'path' => 'media/2026/10/lama.webp', 'width' => 1200, 'height' => 800]);
    Storage::disk('public')->put($media->path, UploadedFile::fake()->image('lama.jpg', 1200, 800)->getContent());

    $this->artisan('media:variants')->assertSuccessful();

    $widths = collect($media->refresh()->variants)->pluck('width')->all();
    expect($widths)->toBe([320, 480, 640, 750, 1024]);
    Storage::disk('public')->assertExists(ImageService::thumbnailPath($media->path));
    $files = count(Storage::disk('public')->allFiles());

    $this->artisan('media:variants --force')->assertSuccessful();

    expect(count(Storage::disk('public')->allFiles()))->toBe($files);
});

test('an upload produces non-upscaled responsive variants that keep the aspect ratio', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('lebar.jpg', 1500, 1000)])
        ->assertCreated();

    $media = Media::query()->firstOrFail();

    expect(collect($media->variants)->pluck('width')->all())->toBe([320, 480, 640, 750, 1024, 1280]);

    foreach ($media->variants as $variant) {
        Storage::disk('public')->assertExists($variant['path']);
        expect($variant['format'])->toBe('webp')
            ->and($variant['height'])->toBe((int) round($variant['width'] * 1000 / 1500))
            ->and($variant['size'])->toBeGreaterThan(0);
    }

    expect($media->srcset)->toContain('-320w.webp 320w')->toContain("{$media->path} 1500w");
});

test('small images get no variants and no srcset', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('kecil.jpg', 300, 200)])
        ->assertCreated()
        ->assertJsonPath('srcset', null);

    expect(Media::query()->firstOrFail()->variants)->toBe([])
        ->and(Storage::disk('public')->allFiles())->toHaveCount(1);
});

test('a failure while generating variants leaves no files or records behind', function () {
    $this->app->bind(ImageService::class, fn ($app) => new class($app->make(ImageManager::class), $app->make(Request::class)) extends ImageService
    {
        public function createVariants(string $path, ?int $originalWidth = null): array
        {
            throw new RuntimeException('boom');
        }
    });

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('rusak.jpg', 1500, 1000)])
        ->assertJsonValidationErrors('file');

    expect(Media::query()->count())->toBe(0)->and(Storage::disk('public')->allFiles())->toBe([]);
});

test('deleting media removes every variant file', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('hapus.jpg', 1500, 1000)]);
    $media = Media::query()->firstOrFail();

    $this->actingAs($user)->delete(route('admin.media.destroy', $media));

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

test('the media index and browse endpoints paginate traditionally', function () {
    Media::factory()->count(30)->create();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->getJson(route('admin.media.browse', ['page' => 2]))
        ->assertJsonPath('current_page', 2)->assertJsonPath('last_page', 2)->assertJsonPath('total', 30)->assertJsonCount(2, 'data');

    $this->actingAs($user)->get(route('admin.media.index', ['page' => 2]))
        ->assertInertia(fn ($page) => $page->where('media.current_page', 2)->where('media.total', 30)->has('media.data', 30 - 24));
});
