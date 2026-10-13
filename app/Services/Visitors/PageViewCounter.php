<?php

namespace App\Services\Visitors;

use Illuminate\Support\Facades\DB;

/**
 * Human page views per public path, summed from the daily analytics counters.
 *
 * Counts come from the same source as the admin analytics, so bots are
 * excluded and numbers stay consistent across the site.
 */
class PageViewCounter
{
    /**
     * @param  list<string>  $paths  Paths with a leading slash, e.g. `/notes/auth-context`.
     * @return array<string, int> Views keyed by path; paths without views are 0.
     */
    public function forPaths(array $paths): array
    {
        if ($paths === []) {
            return [];
        }

        $counts = DB::table('visitor_daily_stats')
            ->where('is_bot', false)
            ->whereIn('path', $paths)
            ->groupBy('path')
            ->selectRaw('path, SUM(page_views) as views')
            ->pluck('views', 'path');

        return collect($paths)
            ->mapWithKeys(fn (string $path): array => [$path => (int) ($counts[$path] ?? 0)])
            ->all();
    }

    public function forPath(string $path): int
    {
        return $this->forPaths([$path])[$path] ?? 0;
    }

    /**
     * Total human views across the whole site.
     */
    public function total(): int
    {
        return (int) DB::table('visitor_daily_stats')->where('is_bot', false)->sum('page_views');
    }
}
