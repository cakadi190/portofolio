<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\MediaContentUrlRewriter;
use App\Services\MediaService;
use App\Services\MediaStorage;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * Copy library files (original, thumbnail and every variant) from the disk
 * each record sits on to the storage chosen in System Settings, then repoint
 * the record. Files already on the target are skipped, so re-running is safe.
 * Switching storage in System Settings queues the same work automatically.
 */
#[Signature('media:sync-storage {--delete-source : Remove the source copies after a record was moved}')]
#[Description('Copy media files to the storage selected in System Settings (local or S3-compatible bucket)')]
class SyncMediaStorage extends Command
{
    public function handle(MediaService $media, MediaStorage $storage, MediaContentUrlRewriter $rewriter): int
    {
        $target = $storage->activeDisk();
        $moved = 0;
        $failed = 0;

        $this->components->info("Target: {$target}");

        Media::query()->where('disk', '!=', $target)->each(function (Media $item) use ($media, &$moved, &$failed): void {
            try {
                $media->syncToActiveStorage($item, (bool) $this->option('delete-source'));
                $moved++;
            } catch (Throwable $exception) {
                $failed++;
                $this->components->warn("{$item->path}: {$exception->getMessage()}");
            }
        });

        if ($failed === 0) {
            $rewriter->rewrite($storage->bucketBaseUrl(), $storage->usesS3());
        }

        $this->components->info("Dipindahkan: {$moved}, gagal: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
