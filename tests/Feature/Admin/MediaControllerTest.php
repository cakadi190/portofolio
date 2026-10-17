<?php

use App\Jobs\SyncMediaStorageJob;
use App\Models\Certification;
use App\Models\Media;
use App\Models\Post;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ContentImageOptimizer;
use App\Services\ImageService;
use App\Services\MediaContentUrlRewriter;
use App\Services\MediaService;
use App\Services\MediaStorage;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
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
        public function createVariants(string $path, ?int $originalWidth = null, ?string $diskName = null): array
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

test('uploads go to the bucket when it is selected in system settings and are served from its URL', function () {
    Storage::fake('media_s3');
    foreach ([
        'storage_driver' => 's3', 'storage_s3_bucket' => 'catatan', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK',
        'storage_s3_url' => 'https://cdn.example.com/',
    ] as $key => $value) {
        SystemSetting::query()->create(['key' => $key, 'value' => $value, 'is_active' => true]);
    }

    $this->actingAs(User::factory()->admin()->create())
        ->postJson(route('admin.media.store'), ['file' => UploadedFile::fake()->image('awan.jpg', 1500, 1000)])
        ->assertCreated()
        ->assertJsonPath('disk', 'media_s3');

    $media = Media::query()->firstOrFail();
    Storage::disk('media_s3')->assertExists($media->path);
    Storage::disk('media_s3')->assertExists($media->variants[0]['path']);
    Storage::disk('public')->assertMissing($media->path);
    expect($media->url)->toBe('https://cdn.example.com/'.$media->path)
        ->and($media->srcset)->toContain('https://cdn.example.com/');

    $this->actingAs(User::factory()->admin()->create())->delete(route('admin.media.destroy', $media));
    expect(Storage::disk('media_s3')->allFiles())->toBe([]);
});

test('media:sync-storage copies local files to the bucket and repoints the records', function () {
    Storage::fake('media_s3');
    foreach (['storage_driver' => 's3', 'storage_s3_bucket' => 'b', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK'] as $key => $value) {
        SystemSetting::query()->create(['key' => $key, 'value' => $value, 'is_active' => true]);
    }
    $media = Media::factory()->create(['path' => 'media/2026/10/lama.webp']);
    Storage::disk('public')->put($media->path, 'asli');
    Storage::disk('public')->put(ImageService::thumbnailPath($media->path), 'thumb');

    $this->artisan('media:sync-storage')->assertSuccessful();

    expect($media->refresh()->disk)->toBe('media_s3');
    Storage::disk('media_s3')->assertExists($media->path);
    Storage::disk('media_s3')->assertExists(ImageService::thumbnailPath($media->path));
    Storage::disk('public')->assertExists($media->path);

    $this->artisan('media:sync-storage')->assertSuccessful();
});

function enableBucket(): void
{
    Storage::fake('media_s3');

    foreach ([
        'storage_driver' => 's3', 'storage_s3_bucket' => 'b', 'storage_s3_access_key' => 'AK', 'storage_s3_secret_key' => 'SK',
        'storage_s3_url' => 'https://cdn.example.com',
    ] as $key => $value) {
        SystemSetting::query()->create(['key' => $key, 'value' => $value, 'is_active' => true]);
    }
}

test('urls keep pointing at the local copy until a record is synced to the bucket', function () {
    enableBucket();
    $pending = Media::factory()->create(['path' => 'media/2026/10/belum.webp', 'width' => 800]);
    Storage::disk('public')->put($pending->path, 'x');

    expect(ImageService::url($pending->path))->toBe('/storage/media/2026/10/belum.webp')
        ->and(ImageService::url('media/2026/10/belum-320w.webp'))->toBe('/storage/media/2026/10/belum-320w.webp')
        ->and(ImageService::url('media/2026/10/baru.webp'))->toBe('https://cdn.example.com/media/2026/10/baru.webp');

    $this->artisan('media:sync-storage')->assertSuccessful();

    expect(ImageService::url($pending->path))->toBe('https://cdn.example.com/media/2026/10/belum.webp');
});

test('rendered article images switch to the bucket URL once their media is synced', function () {
    enableBucket();
    $media = Media::factory()->create(['path' => 'media/2026/10/isi.webp', 'width' => 800, 'height' => 600]);
    Storage::disk('public')->put($media->path, 'x');
    $html = '<img src="/storage/media/2026/10/isi.webp" alt="">';

    expect(ContentImageOptimizer::optimize($html))->toContain('src="/storage/media/2026/10/isi.webp"');

    $this->artisan('media:sync-storage')->assertSuccessful();

    expect(ContentImageOptimizer::optimize($html))->toContain('src="https://cdn.example.com/media/2026/10/isi.webp"');
});

test('legacy storage urls of synced media redirect to the bucket and unknown ones stay 404', function () {
    enableBucket();
    Media::factory()->create(['path' => 'media/2026/10/pindah.webp', 'disk' => 'media_s3']);
    Media::factory()->create(['path' => 'media/2026/10/lokal.webp', 'disk' => 'public']);

    $this->get('/storage/media/2026/10/pindah.webp')->assertRedirect('https://cdn.example.com/media/2026/10/pindah.webp');
    $this->get('/storage/media/2026/10/pindah-640w.webp')->assertRedirect('https://cdn.example.com/media/2026/10/pindah-640w.webp');
    $this->get('/storage/media/2026/10/lokal.webp')->assertNotFound();
    $this->get('/storage/media/2026/10/tidak-ada.webp')->assertNotFound();
});

test('syncing to the bucket rewrites embedded image urls in editor content, and syncing back restores them', function () {
    enableBucket();
    $media = Media::factory()->create(['path' => 'media/2026/10/rte.webp', 'width' => 800, 'height' => 600]);
    Storage::disk('public')->put($media->path, 'x');
    $post = Post::factory()->create(['content' => '<p><img src="/storage/media/2026/10/rte.webp" alt=""></p>', 'content_en' => '<img src="/storage/media/2026/10/rte.webp">']);

    (new SyncMediaStorageJob)->handle(app(MediaService::class), app(MediaStorage::class), app(MediaContentUrlRewriter::class));

    expect($post->refresh()->getRawOriginal('content'))->toContain('https://cdn.example.com/media/2026/10/rte.webp')
        ->and($post->getRawOriginal('content_en'))->toContain('https://cdn.example.com/media/2026/10/rte.webp')
        ->and($media->refresh()->disk)->toBe('media_s3');

    SystemSetting::query()->where('key', 'storage_driver')->firstOrFail()->update(['value' => 'local']);
    app(SystemSettingService::class)->forget();
    app(MediaStorage::class)->forgetUnsynced();
    Storage::disk('public')->delete($media->path);

    (new SyncMediaStorageJob)->handle(app(MediaService::class), app(MediaStorage::class), app(MediaContentUrlRewriter::class));

    expect($media->refresh()->disk)->toBe('public')
        ->and($post->refresh()->getRawOriginal('content'))->toContain('src="/storage/media/2026/10/rte.webp"');
    Storage::disk('public')->assertExists($media->path);
});

test('content is left untouched while some media failed to move', function () {
    enableBucket();
    Media::factory()->create(['path' => 'media/2026/10/hilang.webp']);
    $post = Post::factory()->create(['content' => '<img src="/storage/media/2026/10/hilang.webp">']);

    (new SyncMediaStorageJob)->handle(app(MediaService::class), app(MediaStorage::class), app(MediaContentUrlRewriter::class));

    expect($post->refresh()->getRawOriginal('content'))->toContain('src="/storage/media/2026/10/hilang.webp"');
});

test('turning the bucket off from settings queues the copy back to local', function () {
    Queue::fake();
    Storage::fake('media_s3');
    $this->actingAs(User::factory()->admin()->create());
    enableBucket();

    $this->put(route('admin.system-settings.update'), ['storage_driver' => 'local'])->assertRedirect();

    Queue::assertPushed(SyncMediaStorageJob::class);
});
