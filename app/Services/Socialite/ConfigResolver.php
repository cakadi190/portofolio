<?php

namespace App\Services\Socialite;

use App\Enums\SocialiteProvider;
use App\Models\SystemSetting;

/**
 * Overlay administrator-managed Socialite credentials from SystemSetting onto
 * `config('services.<provider>')`.
 *
 * `config/services.php` only ever holds static, `.env`-sourced defaults. This
 * resolver overlays whatever an administrator has saved through the System
 * Settings page on top of those defaults, without ever writing secrets back
 * into config files. It is intentionally request-scoped: nothing here is
 * cached, so a saved change takes effect on the very next request.
 */
class ConfigResolver
{
    /**
     * Whether a provider is enabled for use, e.g. to gate the login route
     * and to decide whether to render its login button.
     *
     * Reads the `socialite_{provider}_is_active` SystemSetting row and falls
     * back to `config('services.<provider>.is_active')` (the `.env`-sourced
     * default) when no administrator override has been saved yet.
     */
    public function isActive(SocialiteProvider $provider): bool
    {
        return (bool) SystemSetting::get(
            SystemSetting::socialiteKey($provider, 'is_active'),
            config("services.{$provider->value}.is_active", false),
        );
    }

    /**
     * Resolve and write the effective, DB-overlaid credentials for a
     * provider back into the config repository, so that a subsequent call to
     * `Socialite::driver($provider->value)` — which reads
     * `config('services.<provider>')` internally — picks up the
     * administrator-managed `client_id` and `client_secret` instead of only
     * the static `.env` values. The `redirect` is always the app's own
     * `auth.callback` route, never administrator-managed.
     *
     * Call this immediately before invoking the Socialite driver for a given
     * provider; it must run per-request since config is not persisted.
     */
    public function apply(SocialiteProvider $provider): void
    {
        $defaults = config("services.{$provider->value}", []);

        config(["services.{$provider->value}" => [
            'client_id' => SystemSetting::get(SystemSetting::socialiteKey($provider, 'client_id'), $defaults['client_id'] ?? null),
            'client_secret' => SystemSetting::get(SystemSetting::socialiteKey($provider, 'client_secret'), $defaults['client_secret'] ?? null),
            'redirect' => route('auth.callback', $provider->value),
            'is_active' => $this->isActive($provider),
        ]]);
    }
}
