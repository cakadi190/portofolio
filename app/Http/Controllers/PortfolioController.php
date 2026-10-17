<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioGallery;
use App\Models\PortfolioRating;
use App\Services\ContentImageOptimizer;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use App\Services\SeoService;
use App\Support\AppLocale;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    /**
     * Show the paginated list of portfolios.
     */
    public function index(): Response
    {
        $portfolios = Portfolio::query()
            ->with(['services:id,name,color', 'technologies:id,name,icon'])
            ->latest()
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Portfolio $portfolio): array => [
                'name' => $portfolio->name,
                'slug' => $portfolio->slug,
                'image' => ImageService::thumbnailUrl($portfolio->image),
                'shortDesc' => $portfolio->short_desc,
                'services' => $portfolio->services->map(fn ($service): array => [
                    'name' => $service->name,
                    'color' => $service->color,
                ]),
                'technologies' => $portfolio->technologies->map(fn ($technology): array => ['name' => $technology->name, 'icon' => $technology->icon]),
            ]);

        return Inertia::render('portfolio/index', [
            'portfolios' => Inertia::scroll($portfolios),
        ]);
    }

    /**
     * Show a single portfolio.
     */
    public function show(Portfolio $portfolio, SeoService $seo): Response
    {
        $portfolio->load(['technologies:id,name,icon', 'galleries:id,portfolio_id,image_url,description']);

        $image = ImageService::url($portfolio->image);

        $seo->set([
            'locales' => $portfolio->translatedLocales(),
            'title' => $portfolio->name,
            'description' => $portfolio->short_desc ?: $portfolio->name,
            'image' => $image ?? ImageService::url($portfolio->galleries->first()?->image_url),
            'image_alt' => $portfolio->name,
            'breadcrumbs' => [
                ['name' => 'Portofolio', 'url' => route('portfolios.index')],
                ['name' => $portfolio->name, 'url' => route('portfolios.show', $portfolio)],
            ],
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'CreativeWork',
                'name' => $portfolio->name,
                'description' => $portfolio->short_desc ?: $portfolio->name,
                'image' => $image ? url($image) : url(config('seo.image')),
                'url' => route('portfolios.show', $portfolio),
                'dateCreated' => $portfolio->created_at?->toAtomString(),
                'dateModified' => $portfolio->updated_at?->toAtomString(),
                'keywords' => $portfolio->technologies->pluck('name')->implode(', '),
                'inLanguage' => AppLocale::languageTag(),
                'author' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
            ]],
        ]);

        $approvedRatings = $portfolio->ratings()->where('is_approved', true);

        $cover = ContentImageOptimizer::cover($portfolio->image);
        $gallerySizes = ContentImageOptimizer::dimensionsFor($portfolio->galleries->pluck('image_url')->all());

        return Inertia::render('portfolio/show', [
            'portfolio' => [
                'name' => $portfolio->name,
                'shortDesc' => $portfolio->short_desc,
                'description' => ContentImageOptimizer::optimize(HtmlSanitizer::clean($portfolio->description)),
                'image' => $cover['src'],
                'imageSrcset' => $cover['srcset'],
                'imageSize' => $cover['width'] ? ['width' => $cover['width'], 'height' => $cover['height']] : null,
                'demoLink' => $portfolio->demo_link,
                'sourceCode' => $portfolio->source_code,
                'isPrivate' => $portfolio->is_private,
                'technologies' => $portfolio->technologies->map(fn ($technology): array => ['name' => $technology->name, 'icon' => $technology->icon]),
                'slug' => $portfolio->slug,
                'galleries' => $portfolio->galleries->map(fn (PortfolioGallery $gallery): array => [
                    'url' => ImageService::url($gallery->image_url),
                    'thumb' => ImageService::thumbnailUrl($gallery->image_url),
                    'size' => $gallerySizes[$gallery->image_url] ?? null,
                    'title' => $gallery->description,
                ]),
            ],
            'reviews' => (clone $approvedRatings)->latest()->get()->map(fn (PortfolioRating $review): array => [
                'id' => $review->id,
                'name' => $review->reviewer_name,
                'company' => $review->reviewer_company,
                'title' => $review->title,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'createdAt' => $review->created_at?->translatedFormat('d F Y'),
            ]),
            'reviewSummary' => [
                'average' => round((float) $approvedRatings->avg('rating'), 1),
                'count' => $approvedRatings->count(),
            ],
        ]);
    }
}
