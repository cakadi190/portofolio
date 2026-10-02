<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Provide cached, scoped access to active, encrypted runtime settings.
 *
 * All active settings are preloaded in one query, kept in a persistent cache
 * until {@see self::forget()} is called, and memoized for the current Laravel
 * scope. Inject this service instead of querying {@see SystemSetting}.
 */
#[Scoped]
class SystemSettingService
{
    private const string CACHE_KEY = 'system-settings.all';

    /** @var array<string, string|null>|null */
    private ?array $settings = null;

    /**
     * Get a setting value, or the default when it is missing or blank.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->load()[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    /**
     * Discard the in-memory snapshot and the persistent cache after the
     * settings change.
     */
    public function forget(): void
    {
        $this->settings = null;
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string|null>
     */
    private function load(): array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        try {
            return $this->settings = Cache::remember(
                self::CACHE_KEY,
                now()->addDay(),
                fn (): array => SystemSetting::query()
                    ->where('is_active', true)
                    ->get(['key', 'value'])
                    ->pluck('value', 'key')
                    ->all(),
            );
        } catch (QueryException) {
            return $this->settings = [];
        }
    }
}
