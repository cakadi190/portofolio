<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Keeps image URLs embedded in editor HTML in step with the active media
 * storage: after every file has moved, `/storage/media/…` and the bucket URL
 * are swapped in the rich-text columns (both languages). Rendering already
 * rewrites on the fly; this makes the stored HTML — and the editor that loads
 * it — point at the storage in use.
 */
class MediaContentUrlRewriter
{
    /**
     * Rich-text columns that can embed media URLs.
     *
     * @var array<string, list<string>>
     */
    public const COLUMNS = [
        'posts' => ['content', 'content_en'],
        'notes' => ['content', 'content_en'],
        'clarifications' => ['content', 'content_en'],
        'portfolios' => ['description', 'description_en'],
        'services' => ['description', 'description_en'],
        'users' => ['bio', 'bio_en'],
    ];

    /**
     * Point embedded media URLs at the given storage.
     *
     * @param  string  $bucketBaseUrl  Public base URL of the bucket, without a trailing slash.
     * @param  bool  $toBucket  True to move `/storage/media/` URLs to the bucket, false for the reverse.
     * @return int Number of columns that were scanned.
     */
    public function rewrite(string $bucketBaseUrl, bool $toBucket): int
    {
        if ($bucketBaseUrl === '') {
            return 0;
        }

        $local = '/storage/media/';
        $remote = $bucketBaseUrl.'/media/';
        [$from, $to] = $toBucket ? [$local, $remote] : [$remote, $local];
        $scanned = 0;

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $wrapped = DB::getQueryGrammar()->wrap($column);

                DB::table($table)
                    ->where($column, 'like', '%'.addcslashes($from, '\\%_').'%')
                    ->update([$column => DB::raw("REPLACE({$wrapped}, ".DB::getPdo()->quote($from).', '.DB::getPdo()->quote($to).')')]);

                $scanned++;
            }
        }

        return $scanned;
    }
}
