<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Icon search (the Iconify API behind icones.js.org) with a local copy of every icon
 * that gets used, so pages never depend on the API at view time.
 */
class IconService
{
    private const API = 'https://api.iconify.design';

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const THROTTLE_KEY = 'icons.api.throttled';

    private const ID = '/^[a-z0-9]+(?:-[a-z0-9]+)*:[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function isValidId(string $id): bool
    {
        return preg_match(self::ID, $id) === 1;
    }

    /**
     * @return array<int, array{id: string, name: string, url: string}>
     */
    public function search(string $query, int $limit = 48): array
    {
        $query = Str::limit(trim($query), 60, '');

        if ($query === '') {
            return [];
        }

        $ids = Cache::remember('icons.search.'.md5($query.'|'.$limit), now()->addDay(), function () use ($query, $limit): ?array {
            try {
                $response = Http::timeout(8)->acceptJson()->get(self::API.'/search', ['query' => $query, 'limit' => $limit]);
            } catch (Throwable) {
                return null;
            }

            return $response->successful() ? (array) $response->json('icons', []) : null;
        });

        $valid = collect($ids ?? [])
            ->filter(fn ($id): bool => is_string($id) && self::isValidId($id))
            ->values();

        $this->warm($valid->all());

        return $valid
            ->map(fn (string $id): array => ['id' => $id, 'name' => self::label($id), 'url' => self::url($id)])
            ->all();
    }

    /**
     * Download the icons that have no local copy yet, one request per icon set instead of
     * one per icon, so a page of search results cannot trip the API's rate limit.
     *
     * @param  list<string>  $ids  `prefix:name` icon ids
     */
    public function warm(array $ids): void
    {
        if ($this->isThrottled()) {
            return;
        }

        $disk = Storage::disk('local');

        $missing = collect($ids)
            ->map(fn (string $id): array => explode(':', $id, 2))
            ->reject(fn (array $pair): bool => $disk->exists("icons/{$pair[0]}/{$pair[1]}.svg"))
            ->groupBy(0);

        foreach ($missing as $prefix => $pairs) {
            try {
                $response = Http::timeout(8)->get(self::API."/{$prefix}.json", ['icons' => $pairs->pluck(1)->implode(',')]);
            } catch (Throwable) {
                continue;
            }

            if ($this->noteThrottle($response)) {
                return;
            }

            if (! $response->successful()) {
                continue;
            }

            foreach ((array) $response->json('icons', []) as $name => $icon) {
                if (! is_array($icon) || ! is_string($icon['body'] ?? null) || preg_match(self::SLUG, (string) $name) !== 1) {
                    continue;
                }

                $width = $icon['width'] ?? $response->json('width', 16);
                $height = $icon['height'] ?? $response->json('height', 16);

                $this->store($prefix, (string) $name, sprintf(
                    '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="1em" height="1em" viewBox="0 0 %d %d">%s</svg>',
                    $width,
                    $height,
                    $icon['body'],
                ));
            }
        }
    }

    /**
     * The SVG markup for an icon, fetched once and kept on the local disk afterwards.
     */
    public function svg(string $prefix, string $name): ?string
    {
        if (preg_match(self::SLUG, $prefix) !== 1 || preg_match(self::SLUG, $name) !== 1) {
            return null;
        }

        $disk = Storage::disk('local');
        $path = "icons/{$prefix}/{$name}.svg";

        if ($disk->exists($path)) {
            return $disk->get($path);
        }

        if ($this->isThrottled()) {
            return null;
        }

        try {
            $response = Http::timeout(8)->get(self::API."/{$prefix}/{$name}.svg");
        } catch (Throwable) {
            return null;
        }

        if ($this->noteThrottle($response) || ! $response->successful()) {
            return null;
        }

        return $this->store($prefix, $name, $response->body());
    }

    /**
     * Keep an icon on the local disk, unless it is not a plain SVG (no scripts, no handlers).
     */
    private function store(string $prefix, string $name, string $svg): ?string
    {
        if (! str_starts_with(ltrim($svg), '<svg') || preg_match('/<script|\son\w+\s*=/i', $svg) === 1) {
            return null;
        }

        Storage::disk('local')->put("icons/{$prefix}/{$name}.svg", $svg);

        return $svg;
    }

    private function isThrottled(): bool
    {
        return Cache::has(self::THROTTLE_KEY);
    }

    /**
     * On a 429, stop calling the API until its `Retry-After` has passed.
     */
    private function noteThrottle(Response $response): bool
    {
        if ($response->status() !== 429) {
            return false;
        }

        Cache::put(self::THROTTLE_KEY, true, now()->addSeconds(min(max((int) $response->header('Retry-After'), 30), 3600)));

        return true;
    }

    public static function url(string $id): string
    {
        [$prefix, $name] = explode(':', $id, 2);

        return route('icons.show', ['prefix' => $prefix, 'name' => $name], false);
    }

    public static function label(string $id): string
    {
        return Str::headline(explode(':', $id, 2)[1] ?? $id);
    }
}
