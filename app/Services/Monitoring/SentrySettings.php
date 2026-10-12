<?php

namespace App\Services\Monitoring;

use App\Services\SystemSettingService;

/**
 * Typed, defaulted view of the Sentry settings stored in system settings,
 * falling back to the SENTRY_* environment values in `config/sentry.php`.
 */
class SentrySettings
{
    public function __construct(private readonly SystemSettingService $settings) {}

    public function enabled(): bool
    {
        return $this->settings->bool('sentry_enabled', false);
    }

    public function backendDsn(): ?string
    {
        return $this->enabled() ? $this->dsn('sentry_backend_dsn', 'sentry.dsn') : null;
    }

    public function frontendDsn(): ?string
    {
        return $this->enabled() ? $this->dsn('sentry_frontend_dsn', 'sentry.frontend_dsn') : null;
    }

    public function environment(): string
    {
        return (string) $this->settings->get('sentry_environment', config('sentry.environment') ?: app()->environment());
    }

    public function release(): ?string
    {
        $release = $this->settings->get('sentry_release', config('sentry.release'));

        return is_string($release) && $release !== '' ? $release : null;
    }

    public function tracesSampleRate(): float
    {
        return $this->rate('sentry_traces_sample_percent', 10);
    }

    public function profilesSampleRate(): float
    {
        return $this->rate('sentry_profiles_sample_percent', 0);
    }

    public function logsEnabled(): bool
    {
        return $this->settings->bool('sentry_enable_logs', false);
    }

    /**
     * Public browser configuration, or null while frontend monitoring is off.
     *
     * @return array{dsn: string, environment: string, release: string|null, tracesSampleRate: float, profilesSampleRate: float}|null
     */
    public function frontendConfig(): ?array
    {
        $dsn = $this->frontendDsn();

        if ($dsn === null) {
            return null;
        }

        return [
            'dsn' => $dsn,
            'environment' => $this->environment(),
            'release' => $this->release(),
            'tracesSampleRate' => $this->tracesSampleRate(),
            'profilesSampleRate' => $this->profilesSampleRate(),
        ];
    }

    private function dsn(string $key, string $configKey): ?string
    {
        $dsn = $this->settings->get($key, config($configKey));

        return is_string($dsn) && $dsn !== '' ? $dsn : null;
    }

    private function rate(string $key, int $default): float
    {
        return min(100, max(0, $this->settings->int($key, $default))) / 100;
    }
}
