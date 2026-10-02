<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        'portfolios' => ['exact' => ['image'], 'like' => ['description']],
        'portfolio_galleries' => ['exact' => ['image_url'], 'like' => []],
        'posts' => ['exact' => ['cover_image'], 'like' => ['content']],
    ];

    public function __construct(protected ImageService $images) {}

    /**
     * Store an uploaded file: images are scaled down and compressed to WebP,
     * other files (PDF) are kept as-is.
     */
    public function store(UploadedFile $file, ?int $userId = null, string $directory = 'media'): Media
    {
        $originalName = $file->getClientOriginalName();
        $directory = $directory.'/'.now()->format('Y/m');

        if (str_starts_with((string) $file->getMimeType(), 'image/') && $file->getMimeType() !== 'image/svg+xml') {
            $image = $this->images->fromContents($file->getContent())->scaleDown(1920, 1920);
            $path = $image->save($directory, 'webp', 80, 400);
            $width = $image->get()?->width();
            $height = $image->get()?->height();
            $mime = 'image/webp';
            $size = Storage::disk('public')->size($path);
        } else {
            $path = $file->store($directory, 'public');
            $width = $height = null;
            $mime = (string) $file->getMimeType();
            $size = $file->getSize();
        }

        return Media::query()->create([
            'user_id' => $userId,
            'path' => $path,
            'name' => $originalName,
            'mime_type' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
        ]);
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
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
