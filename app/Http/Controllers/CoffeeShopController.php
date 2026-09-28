<?php

namespace App\Http\Controllers;

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
