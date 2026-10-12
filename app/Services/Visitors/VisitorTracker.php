<?php

namespace App\Services\Visitors;

use App\Jobs\EnrichIpAddress;
use App\Models\IpLookup;
use App\Models\Visitor;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Record one eligible page view.
 *
 * Writes are aggregated (a visitor row, a session row, two counter rows) rather
 * than one row per request, kept in one short transaction, never touch the
 * network, and retry a bounded number of times on SQLite lock contention.
 */
class VisitorTracker
{
    private const int MAX_WRITE_ATTEMPTS = 3;

    private const int DUPLICATE_WINDOW_SECONDS = 2;

    private const int NEW_VISITORS_PER_IP_PER_HOUR = 20;

    public const string BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|curl|wget|python-requests|httpclient|headless|lighthouse|^$/i';

    public function __construct(private readonly VisitorSettings $settings) {}

    public function isBot(?string $userAgent): bool
    {
        return preg_match(self::BOT_PATTERN, (string) $userAgent) === 1;
    }

    public function isExcludedPath(string $path): bool
    {
        $path = trim($path, '/');

        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            return true;
        }

        foreach ($this->settings->excludedPaths() as $excluded) {
            if ($path === $excluded || str_starts_with($path, $excluded.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool Whether a page view was recorded.
     */
    public function record(?string $token, string $path, ?string $ip, ?string $userAgent, ?string $referrer, ?int $userId): bool
    {
        if (! $this->settings->trackingEnabled()) {
            return false;
        }

        $isBot = $this->isBot($userAgent);

        if ($isBot && $this->settings->excludesBots()) {
            return false;
        }

        $token = $token !== null && Str::isUuid($token) ? $token : null;
        $ip = $this->settings->storesIp() && $ip !== null ? IpInfoClient::normalize($ip) : null;

        if ($token === null) {
            $limiterKey = 'visitor-new:'.sha1((string) $ip.'|'.$userAgent);

            if (RateLimiter::tooManyAttempts($limiterKey, self::NEW_VISITORS_PER_IP_PER_HOUR)) {
                return false;
            }

            RateLimiter::hit($limiterKey, 3600);
            $token = (string) Str::uuid();
        }

        $path = '/'.Str::limit(trim($path, '/'), 189, '');

        if (! Cache::add('visitor-dup:'.sha1($token.'|'.$path), true, self::DUPLICATE_WINDOW_SECONDS)) {
            return false;
        }

        $this->withRetry(fn () => $this->write($token, $path, $ip, $userAgent, $referrer, $userId, $isBot));
        $this->queueEnrichment($ip);

        return true;
    }

    private function write(string $token, string $path, ?string $ip, ?string $userAgent, ?string $referrer, ?int $userId, bool $isBot): void
    {
        $now = now();
        $day = $now->toDateString();

        DB::transaction(function () use ($token, $path, $ip, $userAgent, $referrer, $userId, $isBot, $now, $day): void {
            DB::table('visitors')->insertOrIgnore([
                'token' => $token, 'user_id' => $userId, 'ip_address' => $ip,
                'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 255, ''),
                'is_bot' => $isBot, 'first_seen_at' => $now, 'last_seen_at' => $now,
                'created_at' => $now, 'updated_at' => $now,
            ]);

            $visitorId = (int) DB::table('visitors')->where('token', $token)->value('id');

            $visit = DB::table('visits')->where('visitor_id', $visitorId)->orderByDesc('last_activity_at')->first(['id', 'last_activity_at']);
            $isNewVisit = $visit === null
                || $now->diffInMinutes(Carbon::parse($visit->last_activity_at), true) > $this->settings->sessionMinutes();

            if ($isNewVisit) {
                DB::table('visits')->insert([
                    'visitor_id' => $visitorId, 'entry_path' => $path,
                    'referrer_domain' => $this->referrerDomain($referrer),
                    'page_views' => 1, 'started_at' => $now, 'last_activity_at' => $now,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            } else {
                DB::table('visits')->where('id', $visit->id)->update([
                    'page_views' => DB::raw('page_views + 1'), 'last_activity_at' => $now, 'updated_at' => $now,
                ]);
            }

            DB::table('visitors')->where('id', $visitorId)->update([
                'visits_count' => DB::raw('visits_count + '.($isNewVisit ? 1 : 0)),
                'page_views_count' => DB::raw('page_views_count + 1'),
                'last_seen_at' => $now, 'updated_at' => $now,
                'ip_address' => $ip ?? DB::raw('ip_address'),
                'user_id' => $userId ?? DB::raw('user_id'),
            ]);

            DB::table('visitor_days')->insertOrIgnore(['visitor_id' => $visitorId, 'day' => $day]);

            DB::table('visitor_daily_stats')->insertOrIgnore(['day' => $day, 'path' => $path, 'is_bot' => $isBot, 'page_views' => 0]);
            DB::table('visitor_daily_stats')->where(['day' => $day, 'path' => $path, 'is_bot' => $isBot])->increment('page_views');
        });

        Cache::forget(VisitorStats::CACHE_KEY);
    }

    private function referrerDomain(?string $referrer): ?string
    {
        $host = $referrer === null ? null : parse_url($referrer, PHP_URL_HOST);

        if (! is_string($host) || $host === '' || $host === parse_url((string) config('app.url'), PHP_URL_HOST)) {
            return null;
        }

        return Str::limit(Str::lower($host), 191, '');
    }

    private function queueEnrichment(?string $ip): void
    {
        if ($ip === null || ! $this->settings->ipInfoEnabled() || ! IpInfoClient::isPublicIp($ip)) {
            return;
        }

        if (IpLookup::query()->where('ip_address', $ip)->where('expires_at', '>', now())->exists()) {
            return;
        }

        if (Cache::add('ipinfo-queued:'.$ip, true, now()->addHour())) {
            EnrichIpAddress::dispatch($ip)->afterResponse();
        }
    }

    private function withRetry(\Closure $callback): void
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                $callback();

                return;
            } catch (QueryException $exception) {
                $locked = str_contains(strtolower($exception->getMessage()), 'locked') || str_contains(strtolower($exception->getMessage()), 'busy');

                if (! $locked || $attempt >= self::MAX_WRITE_ATTEMPTS) {
                    throw $exception;
                }

                usleep(random_int(20, 80) * 1000 * $attempt);
            }
        }
    }
}
