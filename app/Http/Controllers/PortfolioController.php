<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Services\ImageService;
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
    public function show(Portfolio $portfolio): Response
    {
        $portfolio->load('technologies:id,name');

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
            ],
        ]);
    }
}
