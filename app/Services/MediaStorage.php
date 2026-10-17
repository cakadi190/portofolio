<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Decides where media files live. Settings choose `local` (the `public` disk)
 * or any S3-compatible bucket (AWS S3, Cloudflare R2, MinIO, Wasabi, DO Spaces…);
 * the bucket disk is built at runtime from System Settings, so no `.env` change
 * or deploy is needed to switch.
 */
#[Scoped]
class MediaStorage
{
    public const string LOCAL_DISK = 'public';

    public const string S3_DISK = 'media_s3';

    private const string UNSYNCED_CACHE_KEY = 'media.unsynced';

    /** @var array<string, string>|null */
    private ?array $unsynced = null;

    public function __construct(protected SystemSettingService $settings) {}

    /**
     * Whether the bucket is selected and has the minimum credentials.
     */
    public function usesS3(): bool
    {
        return $this->settings->get('storage_driver') === 's3' && $this->isS3Configured();
    }

    public function isS3Configured(): bool
    {
        return filled($this->settings->get('storage_s3_bucket'))
            && filled($this->settings->get('storage_s3_access_key'))
            && filled($this->settings->get('storage_s3_secret_key'));
    }

    /**
     * Disk name that new uploads are written to.
     */
    public function activeDisk(): string
    {
        return $this->usesS3() ? self::S3_DISK : self::LOCAL_DISK;
    }

    /**
     * Filesystem for a disk name (default: the active one).
     */
    public function filesystem(?string $disk = null): Filesystem
    {
        $disk ??= $this->activeDisk();

        if ($disk === self::S3_DISK) {
            $this->registerS3Disk();
        }

        return Storage::disk($disk);
    }

    /**
     * Browser-loadable URL of a stored path. A file whose record has not been
     * copied to the active storage yet (a sync in progress) keeps its current
     * URL, so switching storage never produces a 404 mid-way.
     */
    public function url(string $path): string
    {
        return $this->urlOn($this->diskOf($path), $path);
    }

    /**
     * URL of a path on a specific disk.
     */
    public function urlOn(string $disk, string $path): string
    {
        return $disk === self::S3_DISK ? $this->s3BaseUrl().'/'.ltrim($path, '/') : '/storage/'.ltrim($path, '/');
    }

    /**
     * Disk currently holding a path: the record's own disk while it still lags
     * behind the active storage, otherwise the active disk. Variant files
     * (`name-640w.webp`) follow their original.
     */
    public function diskOf(string $path): string
    {
        $unsynced = $this->unsynced();

        if ($unsynced === []) {
            return $this->activeDisk();
        }

        return $unsynced[$path] ?? $unsynced[preg_replace('/-\d+w\.webp$/i', '.webp', $path) ?? $path] ?? $this->activeDisk();
    }

    /**
     * Forget the cached list of records still on another disk (after a sync or a settings change).
     */
    public function forgetUnsynced(): void
    {
        $this->unsynced = null;
        Cache::forget(self::UNSYNCED_CACHE_KEY.'.'.self::LOCAL_DISK);
        Cache::forget(self::UNSYNCED_CACHE_KEY.'.'.self::S3_DISK);
    }

    /**
     * Paths of records not yet on the active disk, mapped to the disk they are on.
     *
     * @return array<string, string>
     */
    protected function unsynced(): array
    {
        if ($this->unsynced !== null) {
            return $this->unsynced;
        }

        try {
            return $this->unsynced = Cache::remember(
                self::UNSYNCED_CACHE_KEY.'.'.$this->activeDisk(),
                now()->addHour(),
                fn (): array => Media::query()->where('disk', '!=', $this->activeDisk())->pluck('disk', 'path')->all(),
            );
        } catch (QueryException) {
            return $this->unsynced = [];
        }
    }

    /**
     * Media path behind a `/storage/...` or bucket URL, or null for any other source.
     */
    public function pathFromUrl(string $src): ?string
    {
        if (str_starts_with($src, '/storage/')) {
            return ltrim(substr($src, strlen('/storage/')), '/');
        }

        $base = $this->s3BaseUrl();

        return $base !== '' && str_starts_with($src, $base.'/') ? ltrim(substr($src, strlen($base) + 1), '/') : null;
    }

    /**
     * Write, read and delete a probe object to prove the bucket settings work.
     *
     * @return array{ok: bool, message: string}
     */
    public function probe(): array
    {
        if (! $this->isS3Configured()) {
            return ['ok' => false, 'message' => __('server.storage_probe_incomplete')];
        }

        $key = 'media/.connection-test-'.Str::lower(Str::random(12));

        try {
            $disk = $this->filesystem(self::S3_DISK);
            $disk->put($key, 'ok');
            $readable = $disk->get($key) === 'ok';
            $disk->delete($key);

            return $readable
                ? ['ok' => true, 'message' => __('server.storage_probe_ok')]
                : ['ok' => false, 'message' => __('server.storage_probe_unreadable')];
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'message' => __('server.storage_probe_failed', ['error' => Str::limit($exception->getMessage(), 160)])];
        }
    }

    /**
     * Public base URL without a trailing slash: the CDN/public URL setting, or
     * the endpoint plus bucket.
     */
    public function bucketBaseUrl(): string
    {
        return $this->s3BaseUrl();
    }

    protected function s3BaseUrl(): string
    {
        if (filled($url = $this->settings->get('storage_s3_url'))) {
            return rtrim((string) $url, '/');
        }

        $bucket = (string) $this->settings->get('storage_s3_bucket');
        $endpoint = rtrim((string) $this->settings->get('storage_s3_endpoint'), '/');

        if ($bucket === '') {
            return '';
        }

        if ($endpoint === '') {
            return 'https://'.$bucket.'.s3.'.$this->settings->get('storage_s3_region', 'us-east-1').'.amazonaws.com';
        }

        return $this->settings->bool('storage_s3_path_style') ? "{$endpoint}/{$bucket}" : preg_replace('#^(https?://)#', '$1'.$bucket.'.', $endpoint);
    }

    /**
     * Build the bucket disk from settings. A disk already resolved (a test fake)
     * is kept; one built from older settings in a long-lived worker is rebuilt.
     */
    protected function registerS3Disk(): void
    {
        $config = [
            'driver' => 's3',
            'key' => $this->settings->get('storage_s3_access_key'),
            'secret' => $this->settings->get('storage_s3_secret_key'),
            'region' => $this->settings->get('storage_s3_region', 'us-east-1'),
            'bucket' => $this->settings->get('storage_s3_bucket'),
            'endpoint' => $this->settings->get('storage_s3_endpoint'),
            'use_path_style_endpoint' => $this->settings->bool('storage_s3_path_style'),
            'throw' => true,
            'report' => false,
        ];
        $current = config('filesystems.disks.'.self::S3_DISK);

        if ($current === $config) {
            return;
        }

        config(['filesystems.disks.'.self::S3_DISK => $config]);

        if ($current !== null) {
            Storage::forgetDisk(self::S3_DISK);
        }
    }
}
