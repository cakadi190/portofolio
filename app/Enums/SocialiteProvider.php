<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabel;
use App\Models\SystemSetting;
use App\Services\Socialite\ConfigResolver;

/**
 * A Socialite OAuth login provider offered on the login screen.
 *
 * Each case corresponds 1:1 to a Laravel Socialite driver name (the enum's
 * backing value, e.g. `'google'`), which also namespaces both
 * `config('services.<driver>')` and its administrator-managed SystemSetting
 * rows (see {@see SystemSetting::socialiteKey()}).
 */
enum SocialiteProvider: string
{
    use HasLabel;

    case Google = 'google';
    case Github = 'github';
    case Facebook = 'facebook';
    case Twitter = 'twitter';
    case Instagram = 'instagram';

    /**
     * Whether an administrator has enabled this provider.
     */
    public function active(): bool
    {
        return app(ConfigResolver::class)->isActive($this);
    }

    /**
     * Every provider currently enabled by an administrator, in declaration
     * order. Used to render only the active login buttons on the login page.
     *
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $provider) => $provider->active()));
    }
}
