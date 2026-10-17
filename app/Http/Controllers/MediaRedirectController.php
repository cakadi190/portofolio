<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Services\MediaStorage;
use Illuminate\Http\RedirectResponse;

/**
 * Keeps legacy `/storage/<path>` URLs (saved inside article HTML, external
 * links, cached pages) working after media moved to a bucket: web servers
 * serve files still on disk directly, everything else lands here and is
 * redirected to the bucket. Only known media paths are redirected.
 */
class MediaRedirectController extends Controller
{
    public function __invoke(string $path, MediaStorage $storage): RedirectResponse
    {
        $original = preg_replace('/-\d+w\.webp$/i', '.webp', $path) ?? $path;

        $media = Media::query()->whereIn('path', array_unique([$path, $original]))->first();

        abort_unless($media !== null && $media->disk === MediaStorage::S3_DISK, 404);

        return redirect()->away($storage->urlOn($media->disk, $path), 302)->header('Cache-Control', 'public, max-age=300');
    }
}
