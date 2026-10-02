<?php

namespace App\Services;

use Illuminate\Container\Attributes\Scoped;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Verify Cloudflare Turnstile tokens using keys managed in system settings.
 *
 * Turnstile is active only when both the site key and the secret key are set.
 */
#[Scoped]
class TurnstileService
{
    public const string VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const string RESPONSE_FIELD = 'cf-turnstile-response';

    public function __construct(private SystemSettingService $systemSettings) {}

    public function isEnabled(): bool
    {
        return filled($this->siteKey()) && filled($this->systemSettings->get('turnstile_secret_key'));
    }

    /**
     * The public site key for the widget, or null while Turnstile is disabled.
     */
    public function siteKey(): ?string
    {
        return $this->systemSettings->get('turnstile_site_key');
    }

    /**
     * Check a widget token with Cloudflare. Fails closed when the siteverify
     * endpoint cannot be reached.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (blank($token)) {
            return false;
        }

        try {
            return Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_URL, array_filter([
                    'secret' => $this->systemSettings->get('turnstile_secret_key'),
                    'response' => $token,
                    'remoteip' => $ip,
                ]))
                ->json('success') === true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }
}
