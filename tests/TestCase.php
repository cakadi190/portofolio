<?php

namespace Tests;

use App\Support\AppLocale;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The `testing` Docker stage never runs `bun run build`, so no
        // public/build/manifest.json exists when tests hit an Inertia page.
        $this->withoutVite();
    }

    /**
     * Plain public GETs redirect to the default language, so tests that are not about
     * routing request the prefixed page directly. Call `withoutLocalePrefixing()` to hit the
     * plain URL itself.
     */
    protected bool $prefixesPublicUrls = true;

    protected function withoutLocalePrefixing(): static
    {
        $this->prefixesPublicUrls = false;

        return $this;
    }

    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        if ($this->prefixesPublicUrls && in_array(strtoupper($method), ['GET', 'HEAD'], true)) {
            $path = '/'.ltrim((string) parse_url($uri, PHP_URL_PATH), '/');

            if (! in_array(explode('/', trim($path, '/'))[0], AppLocale::available(), true)) {
                $uri = AppLocale::localize($uri, AppLocale::DEFAULT);
            }
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }

    /**
     * A public URL as the sitemaps and canonicals list it: under the default language prefix.
     */
    protected function defaultLanguageUrl(string $url): string
    {
        return AppLocale::localize($url, AppLocale::DEFAULT);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
