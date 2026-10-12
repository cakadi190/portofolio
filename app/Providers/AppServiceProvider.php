<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationActivity;
use App\Models\User;
use App\Services\Monitoring\SentryConfigurator;
use App\Services\Monitoring\SentrySettings;
use App\Services\SeoService;
use App\Services\SystemSettingService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\DriverInterface;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Sentry boots before this provider's boot(); `booting` runs ahead of every provider boot
        // and, unlike register(), can already reach the cache. A private settings instance keeps
        // its snapshot out of the scoped one.
        $this->app->booting(function (): void {
            // Eloquent is normally wired by DatabaseServiceProvider::boot(), which has not run yet.
            Model::setConnectionResolver($this->app['db']);
            Model::setEventDispatcher($this->app['events']);

            (new SentryConfigurator(new SentrySettings(new SystemSettingService), $this->app['config']))->apply();
        });

        $this->app->scoped(SeoService::class);

        $this->app->singleton(ImageManager::class, fn () => new ImageManager(
            driver: $this->resolveImageDriver(),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureAuditListeners();
    }

    /**
     * Route authentication and security events to the audit trail.
     */
    protected function configureAuditListeners(): void
    {
        Event::listen([
            Login::class,
            Logout::class,
            Failed::class,
            Lockout::class,
            Registered::class,
            PasswordReset::class,
            PasswordResetLinkSent::class,
            Verified::class,
            PasswordUpdatedViaController::class,
            TwoFactorAuthenticationEnabled::class,
            TwoFactorAuthenticationConfirmed::class,
            TwoFactorAuthenticationDisabled::class,
            TwoFactorAuthenticationFailed::class,
            RecoveryCodesGenerated::class,
        ], LogAuthenticationActivity::class);
    }

    /**
     * `manage-site` is the full admin area (admins only); `manage-blog` is the
     * blogging area shared with editorial staff (redaktur). Both are enforced
     * at the route level with the `can:` middleware.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('manage-site', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-blog', fn (User $user): bool => $user->canManageBlog());
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (app()->isProduction()) {
            URL::forceHttps();

            Paginator::currentPathResolver(fn (): string => URL::current());
        }

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Pick the Intervention Image driver to use: Imagick when the ext-imagick
     * extension is loaded, falling back to GD otherwise.
     *
     * @return class-string<DriverInterface>
     */
    protected function resolveImageDriver(): string
    {
        return extension_loaded('imagick') ? ImagickDriver::class : GdDriver::class;
    }
}
