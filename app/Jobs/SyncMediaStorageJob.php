<?php

namespace App\Jobs;

use App\Models\Media;
use App\Services\MediaContentUrlRewriter;
use App\Services\MediaService;
use App\Services\MediaStorage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Copies existing library files to the storage just selected in System
 * Settings (or back to local when the bucket is turned off), then points the
 * URLs embedded in editor content at it. Idempotent: records already on the
 * active disk are skipped, a failed record is reported without stopping the
 * rest, and content is only rewritten when every record moved.
 */
class SyncMediaStorageJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function handle(MediaService $media, MediaStorage $storage, MediaContentUrlRewriter $rewriter): void
    {
        $failed = false;

        Media::query()->where('disk', '!=', $storage->activeDisk())->each(function (Media $item) use ($media, &$failed): void {
            try {
                $media->syncToActiveStorage($item);
            } catch (Throwable $exception) {
                $failed = true;
                report($exception);
            }
        });

        if (! $failed) {
            $rewriter->rewrite($storage->bucketBaseUrl(), $storage->usesS3());
        }
    }
}
