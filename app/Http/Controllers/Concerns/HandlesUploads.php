<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ImageService;
use Illuminate\Support\Facades\Storage;

trait HandlesUploads
{
    /**
     * Compress an uploaded image to WebP and store it on the public disk.
     *
     * The image is scaled down (never up) to fit the bounds, then re-encoded
     * with quality lowered until it fits the target size, so pages load fast.
     *
     * @param  string  $field  Request input name holding the uploaded image.
     * @param  string  $directory  Destination directory on the public disk.
     * @param  int  $maxWidth  Maximum width in pixels.
     * @param  int  $maxHeight  Maximum height in pixels.
     * @param  int  $targetKb  Target file size in KB.
     * @return string Stored path, relative to the public disk root.
     */
    protected function storeImage(
        string $field,
        string $directory,
        int $maxWidth = 1600,
        int $maxHeight = 1600,
        int $targetKb = 300,
    ): string {
        return app(ImageService::class)
            ->fromRequest($field)
            ->scaleDown($maxWidth, $maxHeight)
            ->save($directory, 'webp', 80, $targetKb);
    }

    protected function deleteUpload(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
