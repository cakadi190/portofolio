<?php

namespace App\Services\Visitors;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Cached traffic summary. Definitions (humans only unless stated):
 * page views = sum of daily stats; unique visitors = distinct cookie
 * identifiers; visits = sessions split by an inactivity gap; repeat visitors
 * = visitors with more than one visit.
 */
class VisitorStats
{
    public const string CACHE_KEY = 'visitor-stats.summary';

    private const int TTL_SECONDS = 60;

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL_SECONDS, fn (): array => $this->compute());
    }

    /**
     * @return array<string, mixed>
     */
    private function compute(): array
    {
        $today = now()->toDateString();
        $month = now()->startOfMonth()->toDateString();
        $views = fn (bool $bot, ?string $from = null) => (int) DB::table('visitor_daily_stats')
            ->where('is_bot', $bot)->when($from, fn ($q) => $q->where('day', '>=', $from))->sum('page_views');

        return [
            'page_views' => $views(false),
            'page_views_today' => $views(false, $today),
            'page_views_month' => $views(false, $month),
            'bot_page_views' => $views(true),
            'unique_visitors' => DB::table('visitors')->where('is_bot', false)->count(),
            'unique_visitors_today' => DB::table('visitor_days')->join('visitors', 'visitors.id', '=', 'visitor_days.visitor_id')->where('visitors.is_bot', false)->where('day', $today)->count(),
            'unique_visitors_month' => DB::table('visitor_days')->join('visitors', 'visitors.id', '=', 'visitor_days.visitor_id')->where('visitors.is_bot', false)->where('day', '>=', $month)->distinct()->count('visitor_id'),
            'visits' => (int) DB::table('visitors')->where('is_bot', false)->sum('visits_count'),
            'repeat_visitors' => DB::table('visitors')->where('is_bot', false)->where('visits_count', '>', 1)->count(),
            'daily' => DB::table('visitor_daily_stats')->where('is_bot', false)->where('day', '>=', now()->subDays(29)->toDateString())
                ->selectRaw('day, sum(page_views) as page_views')->groupBy('day')->orderBy('day')->get()->all(),
            'top_pages' => DB::table('visitor_daily_stats')->where('is_bot', false)->where('day', '>=', now()->subDays(29)->toDateString())
                ->selectRaw('path, sum(page_views) as page_views')->groupBy('path')->orderByDesc('page_views')->limit(10)->get()->all(),
            'top_countries' => DB::table('visitors')->join('ip_lookups', 'ip_lookups.ip_address', '=', 'visitors.ip_address')
                ->where('visitors.is_bot', false)->where('ip_lookups.status', 'ok')->whereNotNull('ip_lookups.country')
                ->selectRaw('ip_lookups.country as country, count(*) as visitors')->groupBy('ip_lookups.country')->orderByDesc('visitors')->limit(10)->get()->all(),
        ];
    }
}
