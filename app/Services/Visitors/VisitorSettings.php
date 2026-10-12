<?php

namespace App\Services\Visitors;

use App\Services\SystemSettingService;

/**
 * Typed, defaulted view of the visitor analytics and IPInfo settings.
 */
class VisitorSettings
{
    public const array DEFAULT_EXCLUDED_PATHS = ['admin', 'settings', 'up', 'storage', 'build', 'livewire', 'api', 'sitemap', 'robots.txt', 'favicon.ico'];

    public function __construct(private readonly SystemSettingService $settings) {}

    public function trackingEnabled(): bool
    {
        return $this->settings->bool('visitor_tracking_enabled', true);
    }

    public function excludesBots(): bool
    {
        return $this->settings->get('visitor_bot_policy', 'separate') === 'exclude';
    }

    public function storesIp(): bool
    {
        return $this->settings->bool('visitor_store_ip', true);
    }

    public function sessionMinutes(): int
    {
        return max(5, $this->settings->int('visitor_session_minutes', 30));
    }

    public function retentionDays(): int
    {
        return max(7, $this->settings->int('visitor_retention_days', 180));
    }

    /**
     * @return list<string>
     */
    public function excludedPaths(): array
    {
        $extra = array_filter(array_map(
            static fn (string $path): string => trim($path, " \t/"),
            explode(',', (string) $this->settings->get('visitor_excluded_paths', '')),
        ));

        return array_values(array_unique([...self::DEFAULT_EXCLUDED_PATHS, ...$extra]));
    }

    public function ipInfoSwitchedOn(): bool
    {
        return $this->settings->bool('ipinfo_enabled', false);
    }

    public function ipInfoEnabled(): bool
    {
        return $this->ipInfoSwitchedOn()
            && $this->ipInfoToken() !== null
            && $this->storesIp();
    }

    public function ipInfoToken(): ?string
    {
        $token = $this->settings->get('ipinfo_token');

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function ipInfoPlan(): string
    {
        return $this->settings->get('ipinfo_plan', 'lite') === 'core' ? 'core' : 'lite';
    }

    public function ipInfoCacheDays(): int
    {
        return max(1, $this->settings->int('ipinfo_cache_ttl_days', 30));
    }
}
