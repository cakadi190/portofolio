<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SeoService;
use Carbon\CarbonImmutable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\DriverInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
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
