<?php

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Storage::fake('public');
});

function fakeUploadedImage(string $name = 'photo.jpg', int $width = 200, int $height = 100): UploadedFile
{
    return UploadedFile::fake()->image($name, $width, $height);
}

/**
 * Build an uploaded image filled with random noise, since Intervention's placeholder
 * fakes are near-solid color and barely shrink under lossy compression regardless
 * of quality — noise gives quality-dependent, deterministically decreasing sizes.
 */
function noisyUploadedImage(string $name = 'noisy.jpg', int $width = 300, int $height = 300, int $seed = 42): UploadedFile
{
    $canvas = imagecreatetruecolor($width, $height);

    mt_srand($seed);
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            imagesetpixel($canvas, $x, $y, imagecolorallocate($canvas, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
    }

    $path = tempnam(sys_get_temp_dir(), 'noisy').'.jpg';
    imagejpeg($canvas, $path, 100);
    imagedestroy($canvas);

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

it('loads an image from the request', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage());

    $service = app(ImageService::class)->fromRequest('image');

    expect($service->get())->not->toBeNull();
});

it('throws when the request key is missing', function (): void {
    app(ImageService::class)->fromRequest('missing');
})->throws(Exception::class, 'Request key named [missing] is not found! Check your HTTP Request query!');

it('loads an image from a filesystem disk', function (): void {
    $contents = fakeUploadedImage()->get();
    Storage::disk('local')->put('source.jpg', $contents);

    $service = app(ImageService::class)->fromPath('source.jpg');

    expect($service->get())->not->toBeNull();
});

it('throws when the path does not exist on disk', function (): void {
    app(ImageService::class)->fromPath('missing.jpg');
})->throws(Exception::class);

it('resizes the loaded image', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage(width: 400, height: 200));

    $service = app(ImageService::class)->fromRequest('image')->resize(100);

    expect($service->get()->width())->toBe(100)
        ->and($service->get()->height())->toBe(50);
});

it('scales the image down without exceeding the given bounds', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage(width: 400, height: 200));

    $service = app(ImageService::class)->fromRequest('image')->scaleDown(width: 100, height: 100);

    expect($service->get()->width())->toBeLessThanOrEqual(100)
        ->and($service->get()->height())->toBeLessThanOrEqual(100);
});

it('throws when transforming without a loaded image', function (): void {
    app(ImageService::class)->resize(100);
})->throws(Exception::class, 'Image instance is empty. Call fromRequest() or fromPath() first!');

it('converts the image to another format in-memory', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage(name: 'photo.jpg'));

    $path = app(ImageService::class)
        ->fromRequest('image')
        ->convert('png')
        ->save('images', ext: 'png', disk: 'local');

    expect($path)->toEndWith('.png');
    Storage::disk('local')->assertExists($path);
});

it('saves the image to the default disk with a random filename', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage());

    $path = app(ImageService::class)->fromRequest('image')->save('banners');

    expect($path)->toStartWith('banners/')->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);
});

it('saves the image to a given disk with a custom filename', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage());

    $path = app(ImageService::class)
        ->fromRequest('image')
        ->save('banners', filename: 'My Banner Name', disk: 'public');

    expect($path)->toBe('banners/my-banner-name.webp');
    Storage::disk('public')->assertExists($path);
});

it('throws when saving without a loaded image', function (): void {
    app(ImageService::class)->save('banners');
})->throws(Exception::class, 'Image instance is empty. Call fromRequest() or fromPath() first!');

it('progressively lowers quality until the saved image fits the target size', function (): void {
    $this->app['request']->files->set('image', noisyUploadedImage());

    $uncompressedPath = app(ImageService::class)->fromRequest('image')->save('images', quality: 90, disk: 'local');
    $uncompressedSize = Storage::disk('local')->size($uncompressedPath);
    $targetKb = (int) floor($uncompressedSize / 1024 / 2);

    $this->app['request']->files->set('image', noisyUploadedImage());

    $compressedPath = app(ImageService::class)
        ->fromRequest('image')
        ->save('images', quality: 90, compressTarget: $targetKb, disk: 'local');

    expect(Storage::disk('local')->size($compressedPath))
        ->toBeLessThanOrEqual($targetKb * 1024)
        ->toBeLessThan($uncompressedSize);
});

it('stops lowering quality at the floor when the target is unreachable', function (): void {
    $this->app['request']->files->set('image', noisyUploadedImage());

    $path = app(ImageService::class)
        ->fromRequest('image')
        ->save('images', quality: 90, compressTarget: 1, disk: 'local');

    Storage::disk('local')->assertExists($path);
});

it('skips compression entirely when compressTarget is zero', function (): void {
    $this->app['request']->files->set('image', fakeUploadedImage());

    $path = app(ImageService::class)->fromRequest('image')->save('images', disk: 'local');

    Storage::disk('local')->assertExists($path);
});

it('converts the image in-memory to fit the target size', function (): void {
    $this->app['request']->files->set('image', noisyUploadedImage());

    $service = app(ImageService::class)->fromRequest('image');
    $uncompressedSize = strlen((string) $service->get()->encodeUsingFileExtension('webp', quality: 90));
    $targetKb = (int) floor($uncompressedSize / 1024 / 2);

    $encodeToTarget = new ReflectionMethod(ImageService::class, 'encodeToTarget');
    $encoded = $encodeToTarget->invoke($service, 'webp', 90, $targetKb);

    expect(strlen($encoded))
        ->toBeLessThanOrEqual($targetKb * 1024)
        ->toBeLessThan($uncompressedSize);

    $service->convert('webp', quality: 90, compressTarget: $targetKb);

    expect($service->get())->not->toBeNull();
});

it('deletes a saved image from storage', function (): void {
    Storage::disk('local')->put('banners/to-delete.webp', 'fake-content');

    $deleted = app(ImageService::class)->delete('banners/to-delete.webp', 'local');

    expect($deleted)->toBeTrue();
    Storage::disk('local')->assertMissing('banners/to-delete.webp');
});
