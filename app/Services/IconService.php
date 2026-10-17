<?php

namespace App\Services;

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

        return collect($ids ?? [])
            ->filter(fn ($id): bool => is_string($id) && self::isValidId($id))
            ->map(fn (string $id): array => ['id' => $id, 'name' => self::label($id), 'url' => self::url($id)])
            ->values()
            ->all();
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

        try {
            $response = Http::timeout(8)->get(self::API."/{$prefix}/{$name}.svg");
        } catch (Throwable) {
            return null;
        }

        $svg = $response->body();

        if (! $response->successful() || ! str_starts_with(ltrim($svg), '<svg') || preg_match('/<script|\son\w+\s*=/i', $svg) === 1) {
            return null;
        }

        $disk->put($path, $svg);

        return $svg;
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
