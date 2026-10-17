<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;

/**
 * Fluent wrapper around Intervention Image for loading, transforming, and
 * persisting images sourced from an HTTP request or a filesystem disk.
 *
 * A single instance holds at most one loaded image at a time; call
 * fromRequest() or fromPath() before any transform or persistence method.
 */
class ImageService
{
    /**
     * Quality is lowered by this many points per attempt while hunting for compressTarget.
     */
    protected const COMPRESS_QUALITY_STEP = 5;

    /**
     * Quality will not be lowered below this floor, even if compressTarget is not met.
     */
    protected const COMPRESS_MIN_QUALITY = 10;

    /**
     * The currently loaded image, or null until fromRequest()/fromPath() is called.
     */
    protected ?Image $imageInstance = null;

    /**
     * @param  ImageManager  $image  Intervention image manager used to read/decode source images.
     * @param  Request  $request  Current HTTP request, used by fromRequest() to pull uploaded files.
     */
    public function __construct(
        protected ImageManager $image,
        protected Request $request,
    ) {}

    /**
     * Load an image from an uploaded file on the current HTTP request.
     *
     * @param  string  $name  The request input name holding the uploaded file.
     * @return static Fluent instance with the image loaded, ready for further chaining.
     *
     * @throws Exception If no uploaded file is present under the given request key.
     */
    public function fromRequest(string $name): static
    {
        if (! $this->request->hasFile($name)) {
            throw new Exception(
                "Request key named [{$name}] is not found! Check your HTTP Request query!"
            );
        }

        $this->imageInstance = $this->image->decode($this->request->file($name));

        return $this;
    }

    /**
     * Load an image from a path on a filesystem disk.
     *
     * @param  string  $path  Path to the image file, relative to the disk root.
     * @param  string|null  $disk  Disk name to read from; null uses the default disk.
     * @return static Fluent instance with the image loaded, ready for further chaining.
     *
     * @throws Exception If the file does not exist on the given disk.
     */
    public function fromPath(string $path, ?string $disk = null): static
    {
        $contents = $disk
            ? Storage::disk($disk)->get($path)
            : Storage::get($path);

        if ($contents === null) {
            throw new Exception("File [{$path}] was not found on disk [{$disk}]!");
        }

        $this->imageInstance = $this->image->decode($contents);

        return $this;
    }

    /**
     * Load an image from raw binary contents already held in memory.
     *
     * Used for sources that are neither an upload nor a disk file, such as a
     * poster image downloaded from a video provider.
     *
     * @param  string  $contents  Raw encoded image bytes.
     * @return static Fluent instance with the image loaded, ready for further chaining.
     */
    public function fromContents(string $contents): static
    {
        $this->imageInstance = $this->image->decode($contents);

        return $this;
    }

    /**
     * Fill exact dimensions with the loaded image, cropping from the center.
     *
     * The aspect ratio is preserved: the image is scaled to cover the target
     * box and the overflow is trimmed evenly from both sides, so the result
     * is always exactly $width x $height and never stretched.
     *
     * @param  int  $width  Target width in pixels.
     * @param  int  $height  Target height in pixels.
     * @return static Fluent instance with the image cropped, ready for further chaining.
     *
     * @throws Exception If no image has been loaded yet.
     */
    public function cover(int $width, int $height): static
    {
        $this->ensureLoaded();

        $this->imageInstance->cover($width, $height);

        return $this;
    }

    /**
     * Re-encode the loaded image into another format, in-memory only.
     *
     * Does not persist anything to storage; call save() afterwards to write
     * the converted image to disk.
     *
     * @param  string  $ext  Target file extension/format (e.g. 'webp', 'jpg', 'png').
     * @param  int  $quality  Encoding quality, 0-100. Used as the starting quality when compressTarget is set.
     * @param  int  $compressTarget  Target file size in KB; when > 0, quality is progressively lowered
     *                               (down to a floor) until the encoded size fits, or 0 to skip compression.
     * @return static Fluent instance with the image re-encoded, ready for further chaining.
     *
     * @throws Exception If no image has been loaded yet.
     */
    public function convert(string $ext = 'webp', int $quality = 80, int $compressTarget = 0): static
    {
        $this->ensureLoaded();

        $encoded = $this->encodeToTarget($ext, $quality, $compressTarget);

        $this->imageInstance = $this->image->decode($encoded);

        return $this;
    }

    /**
     * Resize the loaded image, preserving the original aspect ratio.
     *
     * When only one of width/height is given, the other is calculated
     * automatically to preserve the original aspect ratio.
     *
     * @param  int  $width  Target width in pixels.
     * @param  int|null  $height  Target height in pixels, or null to derive from width.
     * @return static Fluent instance with the image resized, ready for further chaining.
     *
     * @throws Exception If no image has been loaded yet.
     */
    public function resize(int $width, ?int $height = null): static
    {
        $this->ensureLoaded();

        $this->imageInstance->scale(width: $width, height: $height);

        return $this;
    }

    /**
     * Scale the loaded image down proportionally so it fits within the given
     * bounds, without upscaling or distorting it.
     *
     * @param  int|null  $width  Maximum width in pixels.
     * @param  int|null  $height  Maximum height in pixels.
     * @return static Fluent instance with the image scaled down, ready for further chaining.
     *
     * @throws Exception If no image has been loaded yet.
     */
    public function scaleDown(?int $width = null, ?int $height = null): static
    {
        $this->ensureLoaded();

        $this->imageInstance->scaleDown(width: $width, height: $height);

        return $this;
    }

    /**
     * Encode and persist the loaded image to a storage disk.
     *
     * @param  string  $path  Destination directory, relative to the disk root.
     * @param  string  $ext  File extension/format to encode as (e.g. 'webp', 'jpg', 'png').
     * @param  int  $quality  Encoding quality, 0-100. Used as the starting quality when compressTarget is set.
     * @param  int  $compressTarget  Target file size in KB; when > 0, quality is progressively lowered
     *                               (down to a floor) until the encoded size fits, or 0 to skip compression.
     * @param  string|null  $filename  Filename without extension; null generates a random one.
     * @param  string|null  $disk  Disk name to write to; null uses the default disk.
     * @return string The stored file's path, relative to the disk root.
     *
     * @throws Exception If no image has been loaded yet.
     */
    public function save(
        string $path,
        string $ext = 'webp',
        int $quality = 80,
        int $compressTarget = 0,
        ?string $filename = null,
        ?string $disk = 'public',
    ): string {
        $this->ensureLoaded();

        $encoded = $this->encodeToTarget($ext, $quality, $compressTarget);

        $filename = ($filename ? Str::slug($filename) : Str::random(32)).".{$ext}";

        $fullPath = trim($path, '/').'/'.$filename;

        $disk ? app(MediaStorage::class)->filesystem($disk)->put($fullPath, $encoded) : Storage::put($fullPath, $encoded);

        return $fullPath;
    }

    /**
     * Resolve a stored image reference into a browser-loadable URL.
     *
     * Values already absolute (http(s) URLs) or rooted at the web root
     * (e.g. "/images/foo.webp" from seeders) are returned untouched; anything
     * else is treated as a path on the public disk, so a leading slash or
     * "/storage/" prefix can never double up.
     *
     * @param  string|null  $path  Stored image value.
     * @return string|null Public URL, or null when nothing is stored.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return app(MediaStorage::class)->url($path);
    }

    /**
     * Width of the card-sized variant stored beside each uploaded image.
     */
    public const THUMBNAIL_WIDTH = 640;

    /**
     * Path of the responsive variant of a stored WebP image.
     *
     * @param  string  $path  Stored image path, e.g. "media/2026/10/foo.webp".
     * @param  int  $width  Variant width in pixels.
     * @return string Variant path, e.g. "media/2026/10/foo-640w.webp".
     */
    public static function variantPath(string $path, int $width): string
    {
        return preg_replace('/\.webp$/i', "-{$width}w.webp", $path) ?? $path;
    }

    /**
     * Path of the thumbnail variant of a stored WebP image.
     */
    public static function thumbnailPath(string $path): string
    {
        return self::variantPath($path, self::THUMBNAIL_WIDTH);
    }

    /**
     * Public URL for card-sized rendering: the thumbnail when one exists on the
     * public disk, otherwise the original image.
     *
     * @param  string|null  $path  Stored image value.
     * @return string|null Public URL, or null when nothing is stored.
     */
    public static function thumbnailUrl(?string $path): ?string
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return self::url($path);
        }

        $thumbnail = self::thumbnailPath($path);

        return $thumbnail !== $path && self::thumbnailExists($thumbnail)
            ? self::url($thumbnail)
            : self::url($path);
    }

    /**
     * Whether the thumbnail file exists. A bucket lookup is a network call, so
     * its answer is cached (cleared when variants are regenerated or deleted).
     */
    protected static function thumbnailExists(string $thumbnail): bool
    {
        $storage = app(MediaStorage::class);

        if (! $storage->usesS3()) {
            return $storage->filesystem()->exists($thumbnail);
        }

        return Cache::remember('media.thumbnail.'.md5($thumbnail), now()->addDay(), fn (): bool => $storage->filesystem()->exists($thumbnail));
    }

    /**
     * Forget cached thumbnail lookups for a stored image.
     */
    public static function forgetThumbnailCache(string $path): void
    {
        Cache::forget('media.thumbnail.'.md5(self::thumbnailPath($path)));
    }

    /**
     * Create the thumbnail variant of a stored WebP image.
     *
     * @param  string  $path  Stored image path on the public disk.
     * @return string|null Thumbnail path, or null when the image is missing, not WebP or already small enough.
     */
    public function createThumbnail(string $path): ?string
    {
        return $this->createVariant($path, self::THUMBNAIL_WIDTH)['path'] ?? null;
    }

    /**
     * Create one responsive variant beside a stored WebP image. Deterministic
     * naming makes re-running overwrite instead of piling up files.
     *
     * @param  string  $path  Stored image path on the public disk.
     * @param  int  $width  Target width; nothing is made unless the image is wider.
     * @param  string|null  $diskName  Disk the image lives on; null uses the active media disk.
     * @return array{width: int, height: int, path: string, size: int, format: string}|null
     */
    public function createVariant(string $path, int $width, ?string $diskName = null): ?array
    {
        $diskName ??= app(MediaStorage::class)->activeDisk();
        $disk = app(MediaStorage::class)->filesystem($diskName);
        $variant = self::variantPath($path, $width);

        if ($variant === $path || ! $disk->exists($path)) {
            return null;
        }

        $image = $this->fromContents((string) $disk->get($path));

        if (($image->get()?->width() ?? 0) <= $width) {
            return null;
        }

        $saved = $image->scaleDown($width)->save(
            dirname($variant),
            'webp',
            (int) config('media.variant_quality', 75),
            0,
            pathinfo($variant, PATHINFO_FILENAME),
            $diskName,
        );

        self::forgetThumbnailCache($path);

        return [
            'width' => (int) $image->get()?->width(),
            'height' => (int) $image->get()?->height(),
            'path' => $saved,
            'size' => $disk->size($saved),
            'format' => 'webp',
        ];
    }

    /**
     * Create every configured variant narrower than the stored image.
     *
     * @param  string  $path  Stored image path on the public disk.
     * @param  int|null  $originalWidth  Width of the stored image, used to skip widths that would upscale.
     * @param  string|null  $diskName  Disk the image lives on; null uses the active media disk.
     * @return list<array{width: int, height: int, path: string, size: int, format: string}>
     */
    public function createVariants(string $path, ?int $originalWidth = null, ?string $diskName = null): array
    {
        $variants = [];

        foreach (self::variantWidths() as $width) {
            if ($originalWidth !== null && $width >= $originalWidth) {
                break;
            }

            $variant = $this->createVariant($path, $width, $diskName);

            if ($variant !== null) {
                $variants[] = $variant;
            }
        }

        return $variants;
    }

    /**
     * Configured candidate widths, ascending and unique.
     *
     * @return list<int>
     */
    public static function variantWidths(): array
    {
        $widths = array_map('intval', (array) config('media.variant_widths', [self::THUMBNAIL_WIDTH]));
        $widths = array_values(array_unique(array_filter($widths, fn (int $width): bool => $width > 0)));
        sort($widths);

        return $widths;
    }

    /**
     * Build a `srcset` value from stored variants plus the original image.
     *
     * @param  string|null  $path  Stored image path.
     * @param  int|null  $width  Intrinsic width of the original.
     * @param  array<int, array{width: int, path: string}>|null  $variants  Stored variant metadata.
     * @return string|null The `srcset`, or null when there is nothing to choose between.
     */
    public static function srcset(?string $path, ?int $width, ?array $variants): ?string
    {
        if (blank($path) || ! $width || blank($variants) || Str::startsWith($path, ['http://', 'https://', '/'])) {
            return null;
        }

        $candidates = [];

        foreach ($variants as $variant) {
            if ($variant['width'] < $width) {
                $candidates[$variant['width']] = (string) self::url($variant['path']);
            }
        }

        if ($candidates === []) {
            return null;
        }

        $candidates[$width] = (string) self::url($path);
        ksort($candidates);

        return collect($candidates)->map(fn (string $url, int $candidate): string => "{$url} {$candidate}w")->implode(', ');
    }

    /**
     * Delete a previously saved image from storage.
     *
     * @param  string  $path  Path to the file to delete, relative to the disk root.
     * @param  string|null  $disk  Disk name to delete from; null uses the default disk.
     * @return bool Whether the file was successfully deleted.
     */
    public function delete(string $path, ?string $disk = null): bool
    {
        return $disk ? Storage::disk($disk)->delete($path) : Storage::delete($path);
    }

    /**
     * Get the currently loaded Intervention image instance, if any.
     *
     * @return Image|null The loaded image, or null if nothing has been loaded yet.
     */
    public function get(): ?Image
    {
        return $this->imageInstance;
    }

    /**
     * Encode the loaded image, optionally compressing quality down until the
     * result fits within a target file size.
     *
     * @param  string  $ext  Target file extension/format.
     * @param  int  $quality  Starting encoding quality, 0-100.
     * @param  int  $compressTarget  Target file size in KB; 0 disables compression and encodes at $quality as-is.
     * @return string The encoded image contents.
     */
    protected function encodeToTarget(string $ext, int $quality, int $compressTarget): string
    {
        $encoded = (string) $this->imageInstance->encodeUsingFileExtension($ext, quality: $quality);

        if ($compressTarget <= 0) {
            return $encoded;
        }

        $targetBytes = $compressTarget * 1024;

        while (strlen($encoded) > $targetBytes && $quality > self::COMPRESS_MIN_QUALITY) {
            $quality = max($quality - self::COMPRESS_QUALITY_STEP, self::COMPRESS_MIN_QUALITY);
            $encoded = (string) $this->imageInstance->encodeUsingFileExtension($ext, quality: $quality);
        }

        return $encoded;
    }

    /**
     * Guard against operating on the service before an image has been loaded.
     *
     * @throws Exception If no image has been loaded via fromRequest() or fromPath().
     */
    protected function ensureLoaded(): void
    {
        if (! $this->imageInstance) {
            throw new Exception(
                'Image instance is empty. Call fromRequest() or fromPath() first!'
            );
        }
    }
}
