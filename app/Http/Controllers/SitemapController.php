<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Career;
use App\Models\Clarification;
use App\Models\Education;
use App\Models\Note;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Service;
use App\Models\SpeakingEngagement;
use App\Services\ImageService;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;

/**
 * Serve the XML sitemaps for every publicly reachable page, plus the XSL
 * stylesheet that renders them readably in a browser.
 */
class SitemapController extends Controller
{
    /**
     * Display the sitemap index listing every child sitemap.
     */
    public function index(): Response
    {
        return $this->xml('misc.sitemaps.index', [
            'sitemaps' => [
                ['title' => 'Halaman Statis', 'loc' => route('sitemaps.pages'), 'lastmod' => null],
                ['title' => 'Artikel', 'loc' => route('sitemaps.posts'), 'lastmod' => $this->latest(Post::query()->published())],
                ['title' => 'Portofolio', 'loc' => route('sitemaps.portfolios'), 'lastmod' => $this->latest(Portfolio::query())],
                ['title' => 'Klarifikasi', 'loc' => route('sitemaps.clarifications'), 'lastmod' => $this->latest(Clarification::query()->published())],
                ['title' => 'Catatan Singkat', 'loc' => route('sitemaps.notes'), 'lastmod' => $this->latest(Note::query()->published())],
                ['title' => 'Layanan', 'loc' => route('sitemaps.services'), 'lastmod' => $this->latest(Service::query())],
            ],
        ]);
    }

    /**
     * Display the sitemap of static pages declared in config/seo.php.
     */
    public function pages(): Response
    {
        $lastmods = [
            'blog.index' => $this->latest(Post::query()->published()),
            'notes.index' => $this->latest(Note::query()->published()),
            'portfolios.index' => $this->latest(Portfolio::query()),
            'clarifications.index' => $this->latest(Clarification::query()->published()),
            'services.index' => $this->latest(Service::query()),
            'speaking.index' => $this->latest(SpeakingEngagement::query()->published()),
            'career.index' => $this->latest(Career::query()),
            'education.index' => $this->latest(Education::query()),
            'awards.index' => $this->latest(Award::query()),
        ];

        $urls = collect(config('seo.pages'))
            ->map(fn (array $page, string $name): array => [
                'title' => $page['title'],
                'loc' => route($name),
                'lastmod' => $lastmods[$name] ?? null,
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
                'images' => [],
            ])
            ->values();

        return $this->xml('misc.sitemaps.urlset', ['urls' => $urls]);
    }

    /**
     * Display the sitemap of published articles, including their cover images.
     */
    public function posts(): Response
    {
        $urls = Post::query()
            ->published()
            ->latest('published_at')
            ->get(['id', 'title', 'title_en', 'slug', 'cover_image', 'published_at', 'updated_at'])
            ->map(fn (Post $post): array => [
                'title' => $post->title,
                'loc' => route('blog.show', $post),
                'lastmod' => $post->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.7',
                'images' => $this->images([[$post->cover_image, $post->title]]),
                'locales' => $post->translatedLocales(),
            ]);

        return $this->xml('misc.sitemaps.urlset', ['urls' => $urls]);
    }

    /**
     * Display the sitemap of portfolios, including their cover and gallery images.
     */
    public function portfolios(): Response
    {
        $urls = Portfolio::query()
            ->with('galleries:id,portfolio_id,image_url,description')
            ->latest()
            ->get(['id', 'name', 'slug', 'image', 'updated_at'])
            ->map(fn (Portfolio $portfolio): array => [
                'title' => $portfolio->name,
                'loc' => route('portfolios.show', $portfolio),
                'lastmod' => $portfolio->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.8',
                'images' => $this->images([
                    [$portfolio->image, $portfolio->name],
                    ...$portfolio->galleries->map(fn ($gallery): array => [
                        $gallery->image_url,
                        $gallery->description ?: $portfolio->name,
                    ])->all(),
                ]),
            ]);

        return $this->xml('misc.sitemaps.urlset', ['urls' => $urls]);
    }

    /**
     * Display the sitemap of published clarifications, including their cover images.
     */
    public function clarifications(): Response
    {
        $urls = Clarification::query()
            ->published()
            ->latest('published_at')
            ->get(['id', 'title', 'slug', 'cover_image', 'published_at', 'updated_at'])
            ->map(fn (Clarification $clarification): array => [
                'title' => $clarification->title,
                'loc' => route('clarifications.show', $clarification),
                'lastmod' => $clarification->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.6',
                'images' => $this->images([[$clarification->cover_image, $clarification->title]]),
            ]);

        return $this->xml('misc.sitemaps.urlset', ['urls' => $urls]);
    }

    /**
     * Display the sitemap of published short notes.
     */
    public function notes(): Response
    {
        $urls = Note::query()
            ->published()
            ->latest('published_at')
            ->get(['id', 'title', 'title_en', 'slug', 'published_at', 'updated_at'])
            ->map(fn (Note $note): array => [
                'title' => $note->title,
                'loc' => route('notes.show', $note),
                'lastmod' => $note->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.6',
                'images' => [],
                'locales' => $note->translatedLocales(),
            ]);

        return $this->xml('misc.sitemaps.urlset', ['urls' => $urls]);
    }

    /**
     * Display the sitemap of service detail pages, including their images.
     */
    public function services(): Response
    {
        $urls = Service::query()
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'image', 'updated_at'])
            ->map(fn (Service $service): array => [
                'title' => $service->name,
                'loc' => route('services.show', $service),
                'lastmod' => $service->updated_at,
                'changefreq' => 'monthly',
                'priority' => '0.7',
                'images' => $this->images([[$service->image, $service->name]]),
            ]);

        return $this->xml('misc.sitemaps.urlset', ['urls' => $urls]);
    }

    /**
     * Display the XSL stylesheet used to render sitemaps for humans.
     */
    public function style(): Response
    {
        return response()
            ->view('misc.sitemaps.style')
            ->header('Content-Type', 'application/xslt+xml');
    }

    /**
     * Display robots.txt pointing crawlers at the sitemap index.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /settings',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            'Disallow: /two-factor-challenge',
            'Disallow: /resume',
            'Disallow: /certifications',
            '',
            'Sitemap: '.route('sitemaps.index'),
        ];

        return response(implode("\n", $lines)."\n")->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * Keep only images that exist and resolve them to absolute URLs.
     *
     * @param  list<array{0: ?string, 1: ?string}>  $candidates  Pairs of image path and title.
     * @return list<array{loc: string, title: ?string}>
     */
    private function images(array $candidates): array
    {
        return collect($candidates)
            ->filter(fn (array $candidate): bool => filled($candidate[0]))
            ->map(fn (array $candidate): array => [
                'loc' => url(ImageService::url($candidate[0])),
                'title' => $candidate[1],
            ])
            ->unique('loc')
            ->values()
            ->all();
    }

    /**
     * Most recent modification time of a query's records.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     */
    private function latest($query): ?CarbonInterface
    {
        $value = $query->max('updated_at');

        return $value ? Date::parse($value) : null;
    }

    /**
     * Render a view as an XML response.
     *
     * @param  array<string, mixed>  $data
     */
    private function xml(string $view, array $data = []): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'text/xml; charset=UTF-8');
    }
}
