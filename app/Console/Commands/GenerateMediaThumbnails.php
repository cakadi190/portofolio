<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\ImageService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Create the card-sized thumbnail beside every library image that predates
 * thumbnail generation. Safe to re-run: existing thumbnails are skipped.
 */
#[Signature('media:thumbnails {--force : Regenerate thumbnails that already exist}')]
#[Description('Generate missing thumbnail variants for images in the media library')]
class GenerateMediaThumbnails extends Command
{
    public function handle(ImageService $images): int
    {
        $created = 0;
        $failed = 0;

        Media::query()
            ->where('mime_type', 'image/webp')
            ->where('disk', 'public')
            ->each(function (Media $media) use ($images, &$created, &$failed): void {
                if (! $this->option('force') && Storage::disk('public')->exists(ImageService::thumbnailPath($media->path))) {
                    return;
                }

                try {
                    $images->createThumbnail($media->path) !== null && $created++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->components->warn("{$media->path}: {$exception->getMessage()}");
                }
            });

        $this->components->info("Thumbnail dibuat: {$created}, gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
