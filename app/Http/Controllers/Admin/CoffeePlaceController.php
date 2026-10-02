<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CafeFacility;
use App\Enums\CafePriceTier;
use App\Enums\WifiSpeed;
use App\Http\Controllers\Concerns\PaginatesTables;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CoffeePlaceRequest;
use App\Models\CoffeePlace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CoffeePlaceController extends Controller
{
    use PaginatesTables;

    public function index(Request $request): Response
    {
        return Inertia::render('admin/coffee-places/index', [
            'coffeePlaces' => $this->paginateTable(
                CoffeePlace::query()->with('galleries:id,coffee_place_id,image_url,description')->orderBy('name'),
                $request,
                ['name', 'address'],
                ['name', 'address', 'price_tier', 'is_recommended'],
            ),
            'filters' => $this->tableFilters($request, ['name', 'address', 'price_tier', 'is_recommended']),
            'wifiSpeeds' => WifiSpeed::options(),
            'priceTiers' => CafePriceTier::options(),
            'facilities' => CafeFacility::options(),
            'regions' => CoffeePlace::query()->whereNotNull('region')->distinct()->orderBy('region')->pluck('region'),
        ]);
    }

    public function store(CoffeePlaceRequest $request): RedirectResponse
    {
        $coffeePlace = CoffeePlace::query()->create($request->safe()->except('galleries'));
        $this->syncGalleries($coffeePlace, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil ditambahkan.']);

        return to_route('admin.coffee-places.index');
    }

    public function update(CoffeePlaceRequest $request, CoffeePlace $coffeePlace): RedirectResponse
    {
        $coffeePlace->update($request->safe()->except('galleries'));
        $this->syncGalleries($coffeePlace, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil diperbarui.']);

        return to_route('admin.coffee-places.index');
    }

    public function destroy(CoffeePlace $coffeePlace): RedirectResponse
    {
        $coffeePlace->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kedai kopi berhasil dihapus.']);

        return to_route('admin.coffee-places.index');
    }

    private function syncGalleries(CoffeePlace $coffeePlace, CoffeePlaceRequest $request): void
    {
        $coffeePlace->galleries()->delete();
        $coffeePlace->galleries()->createMany(array_values($request->validated('galleries', [])));
    }
}
