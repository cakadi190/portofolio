<?php

namespace App\Services\Monitoring;

use Illuminate\Contracts\Config\Repository;

/**
 * Copy the Sentry system settings into runtime config. Must run before the
 * Sentry service provider boots (during `register()`), because the SDK only
 * activates its integrations when a DSN is present at boot.
 */
class SentryConfigurator
{
    public function __construct(private readonly SentrySettings $settings, private readonly Repository $config) {}

    public function apply(): void
    {
        $dsn = $this->settings->backendDsn();

        $this->config->set('sentry.dsn', $dsn);
        $this->config->set('sentry.environment', $this->settings->environment());
        $this->config->set('sentry.release', $this->settings->release());
        $this->config->set('sentry.traces_sample_rate', $dsn === null ? null : $this->settings->tracesSampleRate());
        $this->config->set('sentry.profiles_sample_rate', $dsn === null ? null : $this->settings->profilesSampleRate());
        $this->config->set('sentry.enable_logs', $dsn !== null && $this->settings->logsEnabled());
        $this->config->set('sentry.send_default_pii', false);

        if ($dsn !== null && $this->settings->logsEnabled()) {
            $channels = (array) $this->config->get('logging.channels.stack.channels', []);

            if (! in_array('sentry_logs', $channels, true)) {
                $this->config->set('logging.channels.stack.channels', [...$channels, 'sentry_logs']);
            }
        }
    }
}
