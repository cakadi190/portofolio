<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Create the responsive variants for library images that predate them (or all
 * of them with --force). File names are deterministic, so re-running overwrites
 * instead of duplicating.
 */
#[Signature('media:variants {--force : Regenerate variants for images that already have them}')]
#[Description('Generate missing responsive image variants for images in the media library')]
class GenerateMediaVariants extends Command
{
    public function handle(MediaService $media): int
    {
        $generated = 0;
        $failed = 0;

        Media::query()
            ->where('mime_type', 'image/webp')
            ->where('disk', 'public')
            ->when(! $this->option('force'), fn ($query) => $query->whereNull('variants'))
            ->each(function (Media $item) use ($media, &$generated, &$failed): void {
                try {
                    $media->regenerateVariants($item);
                    $generated++;
                } catch (Throwable $exception) {
                    $failed++;
                    $this->components->warn("{$item->path}: {$exception->getMessage()}");
                }
            });

        $this->components->info("Varian dibuat untuk {$generated} gambar, gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
