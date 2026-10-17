<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Central entry point for the media library: every uploaded file is stored
 * once on the public disk and registered as a Media record, so any form
 * can reference it by path.
 */
class MediaService
{
    /**
     * Columns that hold a media path (or, for `like`, embed media URLs in
     * rich text). Used to refuse deleting media that is still referenced.
     *
     * @var array<string, array{exact: list<string>, like: list<string>}>
     */
    public const USAGES = [
        'users' => ['exact' => ['avatar'], 'like' => []],
        'educations' => ['exact' => ['logo'], 'like' => []],
        'certifications' => ['exact' => ['file'], 'like' => []],
        'coffee_places' => ['exact' => ['image'], 'like' => []],
        'coffee_place_galleries' => ['exact' => ['image_url'], 'like' => []],
        'portfolios' => ['exact' => ['image'], 'like' => ['description']],
        'portfolio_galleries' => ['exact' => ['image_url'], 'like' => []],
        'services' => ['exact' => ['image'], 'like' => ['description']],
        'speaking_engagements' => ['exact' => ['poster'], 'like' => []],
        'posts' => ['exact' => ['cover_image'], 'like' => ['content']],
        'clarifications' => ['exact' => ['cover_image'], 'like' => ['content']],
    ];

    public function __construct(protected ImageService $images, protected MediaStorage $storage) {}

    /**
     * Store an uploaded file: images are scaled down and compressed to WebP,
     * other files (PDF) are kept as-is.
     */
    public function store(UploadedFile $file, ?int $userId = null, string $directory = 'media'): Media
    {
        $originalName = $file->getClientOriginalName();
        $directory = $directory.'/'.now()->format('Y/m');
        $filename = $this->kebabFilename($originalName);
        $disk = $this->storage->activeDisk();

        if (str_starts_with((string) $file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/svg+xml') {
            $this->assertDecodable($file);

            $path = null;
            $variants = [];

            try {
                $image = $this->images->fromContents($file->getContent())->scaleDown(1920, 1920);
                $width = $image->get()?->width();
                $height = $image->get()?->height();
                $path = $image->save($directory, 'webp', 80, 400, $filename, $disk);
                $variants = $this->images->createVariants($path, $width, $disk);
            } catch (Throwable $exception) {
                report($exception);

                if ($path !== null) {
                    $this->deleteFiles($path, $disk);
                }

                throw ValidationException::withMessages([
                    'file' => __('server.image_unprocessable'),
                ]);
            }
            $mime = 'image/webp';
            $size = $this->storage->filesystem($disk)->size($path);
        } else {
            $path = $file->storeAs($directory, $filename.'.'.($file->extension() ?: 'bin'), $disk);
            $width = $height = $variants = null;
            $mime = (string) $file->getMimeType();
            $size = $file->getSize();
        }

        return Media::query()->create([
            'user_id' => $userId,
            'disk' => $disk,
            'path' => $path,
            'name' => $originalName,
            'mime_type' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'variants' => $variants,
        ]);
    }

    /**
     * (Re)build the responsive variants of a stored image and record them.
     * Deterministic file names make this safe to run repeatedly.
     */
    public function regenerateVariants(Media $media): void
    {
        $variants = $this->images->createVariants($media->path, $media->width, $media->disk);

        $media->update(['variants' => $variants]);
    }

    /**
     * GD holds the whole bitmap in memory (4 bytes per pixel) and the container
     * has little headroom, so a decode beyond this many pixels ends in a PHP
     * fatal error (HTTP 500) instead of a catchable exception.
     */
    public const MAX_DECODE_PIXELS = 24_000_000;

    /**
     * Reject images too large to decode safely, with a message the uploader can act on.
     *
     * @throws ValidationException
     */
    protected function assertDecodable(UploadedFile $file): void
    {
        $dimensions = @getimagesize($file->getRealPath());

        if ($dimensions === false || $dimensions[0] * $dimensions[1] <= self::MAX_DECODE_PIXELS) {
            return;
        }

        throw ValidationException::withMessages([
            'file' => "Resolusi gambar {$dimensions[0]}×{$dimensions[1]} px terlalu besar untuk diproses server (maksimal ".(self::MAX_DECODE_PIXELS / 1_000_000).' megapiksel, misalnya 6000×4000). Perkecil resolusinya, lalu coba lagi.',
        ]);
    }

    /**
     * Register an existing file from the `public/` directory (e.g. a seeded
     * `/images/...` asset) in the media library and return its stored path.
     */
    public function storeFromPublicPath(string $publicPath, ?int $userId = null): Media
    {
        $absolutePath = public_path(ltrim($publicPath, '/'));

        $file = new UploadedFile($absolutePath, basename($absolutePath), mime_content_type($absolutePath) ?: null, null, true);

        return $this->store($file, $userId);
    }

    /**
     * Build a kebab-case, collision-safe filename (without extension) from the
     * original upload name, e.g. "Foto Profil 2024.JPG" => "foto-profil-2024-a1b2c3d4".
     */
    protected function kebabFilename(string $originalName): string
    {
        $base = Str::slug(Str::limit(pathinfo($originalName, PATHINFO_FILENAME), 80, ''));

        return ($base !== '' ? $base : 'file').'-'.Str::lower(Str::random(8));
    }

    /**
     * Number of records currently referencing this media.
     */
    public function usageCount(Media $media): int
    {
        $count = 0;

        foreach (self::USAGES as $table => $columns) {
            $query = DB::table($table);

            $query->where(function ($group) use ($columns, $media): void {
                foreach ($columns['exact'] as $column) {
                    $group->orWhere($column, $media->path);
                }

                foreach ($columns['like'] as $column) {
                    $group->orWhere($column, 'like', '%'.addcslashes($media->path, '\\%_').'%');
                }
            });

            $count += $query->count();
        }

        return $count;
    }

    public function delete(Media $media): void
    {
        $this->deleteFiles($media->path, $media->disk);
        $media->delete();
    }

    /**
     * Remove a stored file together with every possible responsive variant.
     */
    protected function deleteFiles(string $path, string $disk = MediaStorage::LOCAL_DISK): void
    {
        $variants = array_map(fn (int $width): string => ImageService::variantPath($path, $width), ImageService::variantWidths());

        $this->storage->filesystem($disk)->delete([$path, ImageService::thumbnailPath($path), ...$variants]);
        ImageService::forgetThumbnailCache($path);
    }

    /**
     * Copy a record's files (original, thumbnail, every variant) to the active
     * storage and repoint the record. Files already on the target are skipped.
     *
     * @throws RuntimeException When the original file is missing from its source disk.
     */
    public function syncToActiveStorage(Media $media, bool $deleteSource = false): void
    {
        $target = $this->storage->activeDisk();

        if ($media->disk === $target) {
            return;
        }

        $source = $this->storage->filesystem($media->disk);
        $destination = $this->storage->filesystem($target);
        $variants = array_map(fn (int $width): string => ImageService::variantPath($media->path, $width), ImageService::variantWidths());
        $copied = [];

        foreach (array_unique([$media->path, ImageService::thumbnailPath($media->path), ...$variants]) as $path) {
            if (! $source->exists($path)) {
                continue;
            }

            if (! $destination->exists($path)) {
                $stream = $source->readStream($path);
                $destination->writeStream($path, $stream);

                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            $copied[] = $path;
        }

        if (! in_array($media->path, $copied, true)) {
            throw new RuntimeException('source file is missing');
        }

        $media->update(['disk' => $target]);
        ImageService::forgetThumbnailCache($media->path);
        $this->storage->forgetUnsynced();

        if ($deleteSource) {
            $source->delete($copied);
        }
    }
}
