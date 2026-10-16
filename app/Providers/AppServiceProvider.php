<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationActivity;
use App\Models\User;
use App\Services\Monitoring\SentryConfigurator;
use App\Services\Monitoring\SentrySettings;
use App\Services\SeoService;
use App\Services\SystemSettingService;
use App\Support\AppLocale;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
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
use Spatie\Translatable\Translatable;

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
            Model::setConnectionResolver($this->app->make('db'));
            Model::setEventDispatcher($this->app->make('events'));

            (new SentryConfigurator(new SentrySettings(new SystemSettingService), $this->app->make('config')))->apply();
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
        $this->configureTranslatableContent();
        $this->configureAuthorization();
        $this->configureAuditListeners();
        $this->configureSecurityToasts();
        $this->configureAuthMails();
    }

    /**
     * Render the framework's reset-password and verify-email mails with the site's mail layout.
     */
    protected function configureAuthMails(): void
    {
        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject('Reset Password')
                ->view(['mail.auth.reset-password', 'mail.text.reset-password'], [
                    'name' => $notifiable->name,
                    'url' => $url,
                    'expires' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]);
        });

        VerifyEmail::toMailUsing(fn (object $notifiable, string $url): MailMessage => (new MailMessage)
            ->subject('Verifikasi Alamat Email')
            ->view(['mail.auth.verify-email', 'mail.text.verify-email'], [
                'name' => $notifiable->name,
                'url' => $url,
                'expires' => config('auth.verification.expire', 60),
            ]));
    }

    /**
     * Fortify handles two-factor and password routes itself, so surface their outcome as a toast here.
     */
    protected function configureSecurityToasts(): void
    {
        $messages = [
            TwoFactorAuthenticationEnabled::class => 'Autentikasi dua faktor diaktifkan. Selesaikan dengan memasukkan kode konfirmasi.',
            TwoFactorAuthenticationConfirmed::class => 'Autentikasi dua faktor berhasil dikonfirmasi.',
            TwoFactorAuthenticationDisabled::class => 'Autentikasi dua faktor dinonaktifkan.',
            RecoveryCodesGenerated::class => 'Kode pemulihan baru berhasil dibuat.',
            PasswordUpdatedViaController::class => 'Kata sandi berhasil diperbarui.',
        ];

        foreach ($messages as $event => $message) {
            Event::listen($event, fn () => Inertia::flash('toast', ['type' => 'success', 'message' => $message]));
        }
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
    /**
     * Untranslated content shows in Indonesian, or whatever language has a value.
     */
    protected function configureTranslatableContent(): void
    {
        $this->app->make(Translatable::class)->fallback(
            fallbackLocale: AppLocale::DEFAULT,
            fallbackAny: true,
        );
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        if (app()->isProduction()) {
            URL::forceHttps();
        }

        // Generated links keep the visitor's language: `/blog` becomes `/en/blog` on English pages.
        URL::formatPathUsing(fn (string $path): string => AppLocale::localize($path, AppLocale::urlLocale()));

        Paginator::currentPathResolver(fn (): string => AppLocale::localize(URL::current(), AppLocale::urlLocale()));

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
