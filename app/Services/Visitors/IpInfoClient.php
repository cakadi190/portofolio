<?php

namespace App\Services\Visitors;

use App\Models\IpLookup;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolve IP metadata through the official IPInfo API
 * (Lite: api.ipinfo.io/lite/{ip}, Core/Plus: api.ipinfo.io/lookup/{ip}).
 *
 * The token travels in the Authorization header, never in the URL, so it
 * cannot leak through logs or exception messages. Results are cached by
 * normalized IP in the database and the cache store; failures are recorded
 * with a short expiry and never invent metadata. Callers must not run this
 * inside a database transaction or on the request path.
 */
class IpInfoClient
{
    private const string BASE_URL = 'https://api.ipinfo.io';

    private const int FAILURE_TTL_MINUTES = 15;

    private const int RATE_LIMIT_PAUSE_MINUTES = 10;

    public function __construct(private readonly VisitorSettings $settings) {}

    public static function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    public static function normalize(string $ip): ?string
    {
        $packed = @inet_pton(trim($ip));

        return $packed === false ? null : inet_ntop($packed);
    }

    /**
     * Return a fresh cached lookup or fetch one. Null means enrichment is
     * disabled, paused by a rate limit, or the IP is invalid.
     */
    public function lookup(string $ip): ?IpLookup
    {
        $ip = self::normalize($ip);

        if ($ip === null || ! $this->settings->ipInfoEnabled()) {
            return null;
        }

        $existing = IpLookup::query()->where('ip_address', $ip)->first();

        if ($existing !== null && $existing->expires_at?->isFuture()) {
            return $existing;
        }

        if (! self::isPublicIp($ip)) {
            return $this->store($ip, IpLookup::STATUS_PRIVATE, [], $existing, now()->addDays($this->settings->ipInfoCacheDays()));
        }

        if (Cache::has('ipinfo:paused')) {
            return null;
        }

        return $this->fetch($ip, $existing);
    }

    /**
     * Safe connectivity check against a documentation address.
     *
     * @return array{ok: bool, message: string}
     */
    public function test(): array
    {
        if ($this->settings->ipInfoToken() === null) {
            return ['ok' => false, 'message' => 'Token IPInfo belum diisi.'];
        }

        try {
            $response = $this->request()->get($this->url('8.8.8.8'));
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Tidak dapat terhubung ke IPInfo.'];
        }

        return match (true) {
            $response->successful() => ['ok' => true, 'message' => 'Koneksi IPInfo berhasil.'],
            in_array($response->status(), [401, 403], true) => ['ok' => false, 'message' => 'Token IPInfo ditolak.'],
            $response->status() === 429 => ['ok' => false, 'message' => 'Kuota IPInfo habis (429).'],
            default => ['ok' => false, 'message' => 'IPInfo mengembalikan status '.$response->status().'.'],
        };
    }

    /**
     * @return array{enabled: bool, configured: bool, reason: string|null}
     */
    public function status(): array
    {
        $configured = $this->settings->ipInfoToken() !== null;

        $reason = match (true) {
            ! $configured => 'Token API belum diisi.',
            ! $this->settings->ipInfoSwitchedOn() => 'Sakelar "Aktifkan IPInfo" masih nonaktif. Aktifkan lalu simpan.',
            ! $this->settings->storesIp() => 'Penyimpanan alamat IP dimatikan di tab Analitik Pengunjung, sehingga IPInfo tidak berjalan.',
            default => null,
        };

        return ['enabled' => $this->settings->ipInfoEnabled(), 'configured' => $configured, 'reason' => $reason];
    }

    private function fetch(string $ip, ?IpLookup $existing): IpLookup
    {
        $attempts = ($existing->attempts ?? 0) + 1;

        try {
            $response = $this->request()->get($this->url($ip));
        } catch (ConnectionException) {
            return $this->store($ip, IpLookup::STATUS_FAILED, ['attempts' => $attempts], $existing, now()->addMinutes(self::FAILURE_TTL_MINUTES));
        }

        if ($response->status() === 429) {
            Cache::put('ipinfo:paused', true, now()->addMinutes(self::RATE_LIMIT_PAUSE_MINUTES));

            return $this->store($ip, IpLookup::STATUS_RATE_LIMITED, ['attempts' => $attempts], $existing, now()->addMinutes(self::RATE_LIMIT_PAUSE_MINUTES));
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data) || isset($data['error'])) {
            return $this->store($ip, IpLookup::STATUS_FAILED, ['attempts' => $attempts], $existing, now()->addMinutes(self::FAILURE_TTL_MINUTES));
        }

        if (($data['bogon'] ?? false) === true) {
            return $this->store($ip, IpLookup::STATUS_PRIVATE, [], $existing, now()->addDays($this->settings->ipInfoCacheDays()));
        }

        return $this->store($ip, IpLookup::STATUS_OK, $this->map($data) + ['attempts' => $attempts], $existing, now()->addDays($this->settings->ipInfoCacheDays()));
    }

    /**
     * Map the documented response (flat, or with nested geo/as objects) to our
     * columns. Fields the plan does not return stay null.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function map(array $data): array
    {
        $geo = is_array($data['geo'] ?? null) ? $data['geo'] : $data;
        $as = is_array($data['as'] ?? null) ? $data['as'] : [];
        $loc = isset($data['loc']) ? explode(',', (string) $data['loc']) : [];

        $str = static fn (mixed $v, int $max): ?string => is_scalar($v) && $v !== '' ? mb_substr((string) $v, 0, $max) : null;
        $num = static fn (mixed $v): ?float => is_numeric($v) ? (float) $v : null;

        return [
            'country' => $str($geo['country_code'] ?? $geo['country'] ?? null, 8),
            'region' => $str($geo['region'] ?? null, 128),
            'city' => $str($geo['city'] ?? null, 128),
            'timezone' => $str($geo['timezone'] ?? null, 64),
            'latitude' => $num($geo['latitude'] ?? $loc[0] ?? null),
            'longitude' => $num($geo['longitude'] ?? $loc[1] ?? null),
            'asn' => $str($data['asn'] ?? $as['asn'] ?? null, 32),
            'organization' => $str($data['as_name'] ?? $as['name'] ?? $data['org'] ?? null, 255),
            'hostname' => $str($data['hostname'] ?? null, 255),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function store(string $ip, string $status, array $attributes, ?IpLookup $existing, \DateTimeInterface $expiresAt): IpLookup
    {
        $lookup = $existing ?? new IpLookup(['ip_address' => $ip]);
        $lookup->fill($attributes + ['provider' => 'ipinfo', 'status' => $status, 'fetched_at' => now(), 'expires_at' => $expiresAt]);

        if ($status !== IpLookup::STATUS_OK) {
            $lookup->fill(['country' => null, 'region' => null, 'city' => null, 'timezone' => null, 'asn' => null, 'organization' => null, 'hostname' => null, 'latitude' => null, 'longitude' => null]);
        }

        $lookup->save();

        return $lookup;
    }

    private function url(string $ip): string
    {
        return self::BASE_URL.($this->settings->ipInfoPlan() === 'core' ? '/lookup/' : '/lite/').rawurlencode($ip);
    }

    private function request(): PendingRequest
    {
        return Http::withToken((string) $this->settings->ipInfoToken())
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(5)
            ->retry(2, 200, fn (\Throwable $e): bool => $e instanceof ConnectionException, throw: false);
    }
}
