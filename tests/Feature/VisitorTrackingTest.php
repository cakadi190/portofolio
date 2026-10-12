<?php

use App\Jobs\EnrichIpAddress;
use App\Models\IpLookup;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use App\Services\Visitors\IpInfoClient;
use App\Services\Visitors\VisitorStats;
use App\Services\Visitors\VisitorTracker;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

function setSetting(string $key, string $value): void
{
    SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'is_active' => true]);
    app(SystemSettingService::class)->forget();
}

function track(string $token, string $path = '/blog', string $ip = '203.0.113.9', string $ua = 'Mozilla/5.0 Firefox', ?string $referrer = null): bool
{
    return app(VisitorTracker::class)->record($token, $path, $ip, $ua, $referrer, null);
}

const TOKEN_A = '11111111-1111-4111-8111-111111111111';
const TOKEN_B = '22222222-2222-4222-8222-222222222222';

test('a public page view issues a cookie and is counted after the response', function () {
    $this->get('/')->assertOk()->assertCookie('visitor_id');

    expect(DB::table('visitors')->count())->toBe(1)
        ->and((int) DB::table('visitor_daily_stats')->sum('page_views'))->toBe(1);
});

test('admin, assets, non-GET and error responses are not counted', function () {
    $this->get('/admin');
    $this->get('/up');
    $this->get('/favicon.ico');
    $this->get('/halaman-yang-tidak-ada');
    $this->post('/', []);

    expect(DB::table('visitors')->count())->toBe(0);
});

test('repeat views in a session share one visit, a long gap starts another', function () {
    // Pinned mid-day so the 31 minute gap never crosses midnight and adds a second visitor day.
    $this->travelTo(now()->startOfDay()->addHours(12));

    track(TOKEN_A, '/a');
    Cache::flush();
    track(TOKEN_A, '/b');

    expect(DB::table('visits')->count())->toBe(1)
        ->and((int) DB::table('visits')->value('page_views'))->toBe(2)
        ->and((int) DB::table('visitors')->value('visits_count'))->toBe(1);

    $this->travel(31)->minutes();
    track(TOKEN_A, '/c');

    expect(DB::table('visits')->count())->toBe(2)
        ->and((int) DB::table('visitors')->value('visits_count'))->toBe(2)
        ->and((int) DB::table('visitors')->value('page_views_count'))->toBe(3)
        ->and(DB::table('visitor_days')->count())->toBe(2 - 1);
});

test('duplicate submissions in the dedupe window count once', function () {
    expect(track(TOKEN_A))->toBeTrue()->and(track(TOKEN_A))->toBeFalse();
    expect((int) DB::table('visitor_daily_stats')->sum('page_views'))->toBe(1);
});

test('stats separate page views, unique visitors, visits, repeat visitors and bots', function () {
    track(TOKEN_A, '/a');
    Cache::flush();
    track(TOKEN_A, '/b');
    track(TOKEN_B, '/a');
    track('33333333-3333-4333-8333-333333333333', '/a', '198.51.100.7', 'Googlebot/2.1');

    $this->travel(31)->minutes();
    track(TOKEN_A, '/c');

    $stats = app(VisitorStats::class)->summary();

    expect($stats['page_views'])->toBe(4)
        ->and($stats['unique_visitors'])->toBe(2)
        ->and($stats['visits'])->toBe(3)
        ->and($stats['repeat_visitors'])->toBe(1)
        ->and($stats['bot_page_views'])->toBe(1);
});

test('the bot policy can exclude crawlers and tracking can be disabled', function () {
    setSetting('visitor_bot_policy', 'exclude');
    expect(track(TOKEN_A, '/a', '198.51.100.7', 'Googlebot/2.1'))->toBeFalse();

    setSetting('visitor_tracking_enabled', '0');
    expect(track(TOKEN_B))->toBeFalse()->and(DB::table('visitors')->count())->toBe(0);
});

test('cookie-less floods are capped per client', function () {
    $recorded = 0;

    foreach (range(1, 30) as $i) {
        $recorded += (int) app(VisitorTracker::class)->record(null, "/p{$i}", '203.0.113.50', 'Mozilla/5.0', null, null);
    }

    expect($recorded)->toBe(20)->and(DB::table('visitors')->count())->toBe(20);
});

test('ip storage can be disabled', function () {
    setSetting('visitor_store_ip', '0');
    track(TOKEN_A);

    expect(DB::table('visitors')->value('ip_address'))->toBeNull();
});

test('enrichment is queued once per ip, never inline, and skipped for private addresses', function () {
    Bus::fake();
    setSetting('ipinfo_enabled', '1');
    setSetting('ipinfo_token', 'tok_secret');

    track(TOKEN_A, '/a', '203.0.113.9');
    track(TOKEN_B, '/a', '203.0.113.9');
    track('44444444-4444-4444-8444-444444444444', '/a', '10.0.0.5');

    Bus::assertDispatchedTimes(EnrichIpAddress::class, 1);
});

test('ipinfo lookups map the response, send the token only in the header and hit the cache', function () {
    setSetting('ipinfo_enabled', '1');
    setSetting('ipinfo_token', 'tok_secret');
    setSetting('ipinfo_plan', 'core');
    Http::fake(['api.ipinfo.io/*' => Http::response(['ip' => '203.0.113.9', 'city' => 'Ngawi', 'region' => 'Jawa Timur', 'country' => 'ID', 'loc' => '-7.40,111.44', 'org' => 'AS7713 Telkom', 'timezone' => 'Asia/Jakarta'])]);

    $client = app(IpInfoClient::class);
    $first = $client->lookup('203.0.113.9');
    $second = $client->lookup('203.0.113.9');

    expect($first->status)->toBe('ok')->and($first->city)->toBe('Ngawi')->and($second->is($first))->toBeTrue();
    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer tok_secret')
        && ! str_contains($request->url(), 'tok_secret')
        && str_starts_with($request->url(), 'https://api.ipinfo.io/lookup/'));
});

test('ipinfo failures and rate limits never fabricate metadata', function () {
    setSetting('ipinfo_enabled', '1');
    setSetting('ipinfo_token', 'tok_secret');
    Http::fake(['api.ipinfo.io/*' => Http::sequence()->push([], 429)->push([], 500)]);

    $limited = app(IpInfoClient::class)->lookup('203.0.113.9');
    expect($limited->status)->toBe('rate_limited')->and($limited->country)->toBeNull();

    // While paused, other addresses are not requested at all.
    expect(app(IpInfoClient::class)->lookup('203.0.113.10'))->toBeNull();
    Http::assertSentCount(1);

    Cache::forget('ipinfo:paused');
    expect(app(IpInfoClient::class)->lookup('203.0.113.11')->status)->toBe('failed');
});

test('private addresses are classified without calling the provider and a disabled integration does nothing', function () {
    Http::fake();
    expect(app(IpInfoClient::class)->lookup('10.1.2.3'))->toBeNull();

    setSetting('ipinfo_enabled', '1');
    setSetting('ipinfo_token', 'tok_secret');
    expect(app(IpInfoClient::class)->lookup('10.1.2.3')->status)->toBe('private');
    Http::assertNothingSent();
});

test('a connection error is recorded as failed', function () {
    setSetting('ipinfo_enabled', '1');
    setSetting('ipinfo_token', 'tok_secret');
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(app(IpInfoClient::class)->lookup('203.0.113.9')->status)->toBe('failed');
});

test('pruning removes only expired visitor data in bounded batches', function () {
    track(TOKEN_A);
    track(TOKEN_B, '/x', '203.0.113.10');
    DB::table('visitors')->where('token', TOKEN_A)->update(['last_seen_at' => now()->subDays(400)]);
    DB::table('visits')->where('visitor_id', DB::table('visitors')->where('token', TOKEN_A)->value('id'))->update(['last_activity_at' => now()->subDays(400)]);
    IpLookup::query()->create(['ip_address' => '203.0.113.1', 'status' => 'ok', 'expires_at' => now()->subDays(400)]);

    $this->artisan('visitors:prune', ['--batch' => 10, '--max-batches' => 1])->assertSuccessful();

    expect(DB::table('visitors')->count())->toBe(1)
        ->and(DB::table('visits')->count())->toBe(1)
        ->and(IpLookup::query()->count())->toBe(0)
        ->and((int) DB::table('visitor_daily_stats')->sum('page_views'))->toBe(2);
});

test('repeated tracking of many visitors stays consistent', function () {
    $start = microtime(true);
    foreach (range(1, 200) as $i) {
        track(sprintf('00000000-0000-4000-8000-%012d', $i), '/blog', '203.0.113.'.($i % 250 + 1));
    }
    $seconds = microtime(true) - $start;

    expect(DB::table('visitors')->count())->toBe(200)
        ->and((int) DB::table('visitor_daily_stats')->sum('page_views'))->toBe(200)
        ->and($seconds)->toBeLessThan(30);
    fwrite(STDERR, sprintf("[bench] 200 sequential tracked views in %.2fs (%.0f/s), sqlite in-memory test db\n", $seconds, 200 / max($seconds, 0.001)));
});
