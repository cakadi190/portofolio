<?php

namespace App\Http\Controllers;

use App\Enums\CafeFacility;
use App\Models\CoffeePlace;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoffeeShopController extends Controller
{
    /**
     * Show the paginated, filterable list of coffee places.
     */
    public function index(Request $request): Response
    {
        $region = $request->string('region')->trim()->value() ?: null;
        $search = $request->string('search')->trim()->value() ?: null;

        $places = CoffeePlace::query()
            ->with('galleries:id,coffee_place_id,image_url,description')
            ->when($region, fn ($query) => $query->where('region', $region))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('region', 'like', "%{$search}%")))
            ->orderByDesc('is_recommended')
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString()
            ->through(fn (CoffeePlace $place): array => [
                'id' => $place->id,
                'name' => $place->name,
                'region' => $place->region,
                'description' => $place->description,
                'image' => ImageService::url($place->image),
                'address' => $place->address,
                'mapUrl' => $place->map_url,
                'opensAt' => $place->opens_at,
                'closesAt' => $place->closes_at,
                'parkFee' => $place->park_fee,
                'isRecommended' => $place->is_recommended,
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'wifiProvider' => $place->wifi_provider,
                'wifiSpeed' => $place->wifi_speed?->label(),
                'wifiSpeedColor' => $place->wifi_speed?->badgeColor(),
                'priceTier' => $place->price_tier?->label(),
                'priceTierColor' => $place->price_tier?->badgeColor(),
                'facilities' => collect($place->facilities ?? [])
                    ->map(fn (string $facility): ?string => CafeFacility::tryFrom($facility)?->label())
                    ->filter()
                    ->values(),
                'galleries' => $place->galleries->map(fn ($gallery): array => [
                    'url' => ImageService::url($gallery->image_url),
                    'title' => $gallery->description,
                ]),
            ]);

        return Inertia::render('sumber-daya/tempat-ngopi', [
            'places' => $places,
            'regions' => CoffeePlace::query()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
            'filters' => [
                'region' => $region,
                'search' => $search,
            ],
        ]);
    }
}
