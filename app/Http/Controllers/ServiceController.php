<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Service;
use App\Services\ImageService;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Show the list of services.
     */
    public function index(): Response
    {
        return Inertia::render('service/index', [
            'services' => Service::query()
                ->withCount('portfolios')
                ->orderBy('id')
                ->get()
                ->map(fn (Service $service): array => [
                    'name' => $service->name,
                    'slug' => $service->slug,
                    'color' => $service->color,
                    'image' => ImageService::url($service->image),
                    'excerpt' => str(strip_tags((string) $service->description))->squish()->limit(160)->toString(),
                    'portfoliosCount' => $service->portfolios_count,
                ]),
        ]);
    }

    /**
     * Show a single service along with the portfolios that belong to it.
     */
    public function show(Service $service, SeoService $seo): Response
    {
        $excerpt = str(strip_tags((string) $service->description))->squish()->limit(160)->toString();

        $seo->set([
            'title' => $service->name,
            'description' => $excerpt ?: $service->name,
            'image' => ImageService::url($service->image),
            'image_alt' => $service->name,
        ]);

        return Inertia::render('service/show', [
            'service' => [
                'name' => $service->name,
                'slug' => $service->slug,
                'color' => $service->color,
                'image' => ImageService::url($service->image),
                'description' => $service->description,
            ],
            'portfolios' => $service->portfolios()
                ->with('technologies:id,name')
                ->latest()
                ->get()
                ->map(fn (Portfolio $portfolio): array => [
                    'name' => $portfolio->name,
                    'slug' => $portfolio->slug,
                    'image' => ImageService::url($portfolio->image),
                    'shortDesc' => $portfolio->short_desc,
                    'services' => [['name' => $service->name, 'color' => $service->color]],
                    'technologies' => $portfolio->technologies->pluck('name'),
                ]),
        ]);
    }
}
