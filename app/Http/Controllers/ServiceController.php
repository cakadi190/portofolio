<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Service;
use App\Services\ContentImageOptimizer;
use App\Services\HtmlSanitizer;
use App\Services\ImageService;
use App\Services\SeoService;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Show the list of services.
     */
    public function index(SeoService $seo): Response
    {
        $services = Service::query()->withCount('portfolios')->orderBy('id')->get();

        $seo->set(['json_ld' => [[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Layanan',
            'itemListElement' => $services->values()->map(fn (Service $service, int $index): array => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'url' => route('services.show', $service),
                'name' => $service->name,
            ])->all(),
        ]]]);

        return Inertia::render('service/index', [
            'services' => $services
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
            'breadcrumbs' => [
                ['name' => 'Layanan', 'url' => route('services.index')],
                ['name' => $service->name, 'url' => route('services.show', $service)],
            ],
            'json_ld' => [[
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => $service->name,
                'description' => $excerpt ?: $service->name,
                'url' => route('services.show', $service),
                'serviceType' => $service->name,
                'areaServed' => ['@type' => 'Country', 'name' => 'Indonesia'],
                'provider' => ['@type' => 'Person', 'name' => $seo->author(), 'url' => url('/')],
            ]],
        ]);

        return Inertia::render('service/show', [
            'service' => [
                'name' => $service->name,
                'slug' => $service->slug,
                'color' => $service->color,
                'image' => ImageService::url($service->image),
                'description' => ContentImageOptimizer::optimize(HtmlSanitizer::clean($service->description)),
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
