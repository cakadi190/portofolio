<?php

use App\Models\Media;
use App\Services\ContentImageOptimizer;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
});

it('adds intrinsic size, lazy loading and async decoding to stored images', function (): void {
    Media::factory()->create(['path' => 'media/2026/10/foto-a1b2c3d4.webp', 'width' => 1600, 'height' => 900]);

    $html = ContentImageOptimizer::optimize('<p>Halo</p><img src="/storage/media/2026/10/foto-a1b2c3d4.webp" alt="Foto">');

    expect($html)
        ->toContain('width="1600"')
        ->toContain('height="900"')
        ->toContain('loading="lazy"')
        ->toContain('decoding="async"')
        ->toContain('<p>Halo</p>');
});

it('offers the 640 px thumbnail as a srcset candidate when it exists', function (): void {
    Media::factory()->create(['path' => 'media/2026/10/lebar-a1b2c3d4.webp', 'width' => 1600, 'height' => 900]);
    Storage::disk('public')->put('media/2026/10/lebar-a1b2c3d4-640w.webp', 'x');

    $html = ContentImageOptimizer::optimize('<img src="/storage/media/2026/10/lebar-a1b2c3d4.webp" alt="">');

    expect($html)->toContain('srcset="/storage/media/2026/10/lebar-a1b2c3d4-640w.webp 640w, /storage/media/2026/10/lebar-a1b2c3d4.webp 1600w"');
});

it('keeps explicit attributes and leaves external or unknown images alone', function (): void {
    $html = ContentImageOptimizer::optimize(
        '<img src="https://cdn.example.com/a.png" alt="" loading="eager"><img src="/storage/media/tidak-ada.webp" width="10" height="10" alt="">'
    );

    expect($html)
        ->toContain('loading="eager"')
        ->toContain('width="10"')
        ->not->toContain('srcset');
});

it('returns html without images untouched', function (): void {
    expect(ContentImageOptimizer::optimize('<p>Tanpa gambar</p>'))->toBe('<p>Tanpa gambar</p>')
        ->and(ContentImageOptimizer::optimize(''))->toBe('');
});

it('describes a cover image with its original, thumbnail and size', function (): void {
    Media::factory()->create(['path' => 'media/2026/10/cover-a1b2c3d4.webp', 'width' => 1920, 'height' => 1080]);
    Storage::disk('public')->put('media/2026/10/cover-a1b2c3d4-640w.webp', 'x');

    $cover = ContentImageOptimizer::cover('media/2026/10/cover-a1b2c3d4.webp');

    expect($cover)->toMatchArray([
        'src' => '/storage/media/2026/10/cover-a1b2c3d4.webp',
        'srcset' => '/storage/media/2026/10/cover-a1b2c3d4-640w.webp 640w, /storage/media/2026/10/cover-a1b2c3d4.webp 1920w',
        'width' => 1920,
        'height' => 1080,
    ])
        ->and(ContentImageOptimizer::cover(null))->toMatchArray(['src' => null, 'srcset' => null, 'width' => null]);
});

it('builds the srcset and sizes from stored responsive variants', function (): void {
    Media::factory()->create([
        'path' => 'media/2026/10/varian-a1b2c3d4.webp',
        'width' => 1200,
        'height' => 800,
        'variants' => [
            ['width' => 320, 'height' => 213, 'path' => 'media/2026/10/varian-a1b2c3d4-320w.webp', 'size' => 1, 'format' => 'webp'],
            ['width' => 750, 'height' => 500, 'path' => 'media/2026/10/varian-a1b2c3d4-750w.webp', 'size' => 1, 'format' => 'webp'],
        ],
    ]);

    $html = ContentImageOptimizer::optimize('<img src="/storage/media/2026/10/varian-a1b2c3d4.webp" alt="">');
    $cover = ContentImageOptimizer::cover('media/2026/10/varian-a1b2c3d4.webp');

    expect($html)
        ->toContain('srcset="/storage/media/2026/10/varian-a1b2c3d4-320w.webp 320w, /storage/media/2026/10/varian-a1b2c3d4-750w.webp 750w, /storage/media/2026/10/varian-a1b2c3d4.webp 1200w"')
        ->toContain('sizes="'.config('media.sizes.content').'"')
        ->and($cover['srcset'])->toContain('-750w.webp 750w')
        ->and($cover['sizes'])->toBe(config('media.sizes.cover'));
});

it('renders old media without variants or thumbnail as a plain image', function (): void {
    Media::factory()->create(['path' => 'media/2026/10/tua-a1b2c3d4.webp', 'width' => 1600, 'height' => 900]);

    expect(ContentImageOptimizer::optimize('<img src="/storage/media/2026/10/tua-a1b2c3d4.webp" alt="">'))->not->toContain('srcset');
});
