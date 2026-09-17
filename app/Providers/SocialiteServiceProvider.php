<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Facebook\Provider as FacebookProvider;
use SocialiteProviders\GitHub\Provider as GitHubProvider;
use SocialiteProviders\Instagram\Provider as InstagramProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Twitter\Provider as TwitterProvider;
use SocialiteProviders\Twitter\Server as TwitterServer;

/**
 * Register the extra Socialite providers used by this application. Google is
 * supported natively by laravel/socialite and needs no registration here.
 */
class SocialiteServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event): void {
            collect([
                'facebook' => [FacebookProvider::class],
                'github' => [GitHubProvider::class],
                'instagram' => [InstagramProvider::class],
                'twitter' => [TwitterProvider::class, TwitterServer::class],
            ])->each(function (array $providerClasses, string $driver) use ($event): void {
                $event->extendSocialite($driver, ...$providerClasses);
            });
        });
    }
}
