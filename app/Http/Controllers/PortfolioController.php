<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioGallery;
use App\Models\PortfolioRating;
use App\Services\ImageService;
use App\Services\SeoService;
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
            ->with(['categories:id,name,color', 'technologies:id,name'])
            ->latest()
            ->paginate(9)
            ->withQueryString()
            ->through(fn (Portfolio $portfolio): array => [
                'name' => $portfolio->name,
                'slug' => $portfolio->slug,
                'image' => ImageService::url($portfolio->image),
                'shortDesc' => $portfolio->short_desc,
                'categories' => $portfolio->categories->map(fn ($category): array => [
                    'name' => $category->name,
                    'color' => $category->color,
                ]),
                'technologies' => $portfolio->technologies->pluck('name'),
            ]);

        return Inertia::render('portfolio/index', [
            'portfolios' => $portfolios,
        ]);
    }

    /**
     * Show a single portfolio.
     */
    public function show(Portfolio $portfolio, SeoService $seo): Response
    {
        $portfolio->load(['technologies:id,name', 'galleries:id,portfolio_id,image_url,description']);

        $image = ImageService::url($portfolio->image);

        $seo->set([
            'title' => $portfolio->name,
            'description' => $portfolio->short_desc ?: $portfolio->name,
            'image' => $image ?? ImageService::url($portfolio->galleries->first()?->image_url),
            'image_alt' => $portfolio->name,
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
                'inLanguage' => 'id-ID',
                'author' => ['@type' => 'Person', 'name' => config('seo.author'), 'url' => url('/')],
            ]],
        ]);

        $approvedRatings = $portfolio->ratings()->where('is_approved', true);

        return Inertia::render('portfolio/show', [
            'portfolio' => [
                'name' => $portfolio->name,
                'shortDesc' => $portfolio->short_desc,
                'description' => $portfolio->description,
                'image' => ImageService::url($portfolio->image),
                'demoLink' => $portfolio->demo_link,
                'sourceCode' => $portfolio->source_code,
                'isPrivate' => $portfolio->is_private,
                'technologies' => $portfolio->technologies->pluck('name'),
                'slug' => $portfolio->slug,
                'galleries' => $portfolio->galleries->map(fn (PortfolioGallery $gallery): array => [
                    'url' => ImageService::url($gallery->image_url),
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
